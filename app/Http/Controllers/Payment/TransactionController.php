<?php

namespace App\Http\Controllers\Payment;
use App\Http\Controllers\Controller;

use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\VerifyTransactionRequest;
use App\Services\TransactionService;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\ClubDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{

    public function __construct(
        protected TransactionService $service
    ){}


    // 3
    public function create(Request $request)
    {
        // 1. التحقق من المدخلات (المستخدم يدخل رقمه واسمه فقط)
        $validator = Validator::make($request->all(), [
            'sender_phone' => 'required|string|min:10|max:15',
            //'sender_name'  => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $subscription = $user->subscriptions()->where('status', 'pending')->latest()->first();

        if (!$subscription) {
            return response()->json(['status' => 400, 'message' => 'No pending subscription found.'], 400);
        }

        $clubDetail = ClubDetail::first();
        $companyPhone = $clubDetail ? $clubDetail->phone : null;

        // 3. إنشاء المعاملة (مع قيم ثابتة لا يمكن للمستخدم تغييرها)
        $transaction = Transaction::create([
            'user_id'            => $user->id,
            'subscription_id'    => $subscription->id,
            'transaction_number' => 'TRX-' . strtoupper(uniqid()),
            'amount'             => $subscription->price, // ثابت من قاعدة البيانات
            'company_phone'      => $companyPhone,       // ثابت من الكود
            'sender_phone'       => $request->sender_phone,
            'sender_name'        => $request->sender_name,
            'payment_method'     => 'bank',
            'notes'              => $request->notes ?? 'دفع اشتراك',
            'status'             => 'pending',
        ]);

        // 4. الرد على المستخدم مع التفاصيل المحدثة
        return response()->json([
            'status' => 200,
            'message' => 'تم تحويل المبلغ المطلوب بنجاح.. قم بحفظ رقم المعاملة للتحقق من الدفع لاحقا',
            'data' => [
                'transaction_number' => $transaction->transaction_number,
                'subscription_name'  => $subscription->plan->name ?? 'Plan',
                'amount'             => $transaction->amount, // المبلغ المدفوع
                'payment_method'     => $transaction->payment_method ?? 'تحويل بنكي', // طريقة الدفع
                'sender_name'        => $transaction->sender_name, // اسم المرسل
                'sender_phone'       => $transaction->sender_phone, // رقم المرسل
                'date'               => $transaction->created_at->toDateTimeString(), // تاريخ المعاملة
            ]
        ]);
    }

    // 4 verify transaction num
   public function verify(Request $request)
    {
        $request->validate([
            'transaction_number' => 'required|string|exists:transactions,transaction_number',
        ]);

        $transaction = Transaction::where('transaction_number', $request->transaction_number)
                                ->with('subscription.plan')
                                ->firstOrFail();

        // 1. التحقق من ملكية المستخدم
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'غير مصرح لك بالتحقق من هذه المعاملة'
            ], 403);
        }

        // 2. التحقق من أنها لم تُفعل مسبقاً
        if ($transaction->status === 'verified') {
            return response()->json([
                'status' => 400,
                'message' => 'هذه المعاملة تم التحقق منها مسبقاً'
            ], 400);
        }

        // 3. استخدام Database Transaction للسلامة
        return \DB::transaction(function () use ($transaction) {
            
            $transaction->update(['status' => 'verified']);

            // تفعيل الاشتراك
            if ($transaction->subscription) {
                $subscription = $transaction->subscription;
                
                $currentExpiry = $subscription->expires_at && $subscription->expires_at->isFuture() 
                     ? $subscription->expires_at 
                     : now();

                $subscription->update([
                    'status' => 'paid',
                    'starts_at' => now(),
                    'expires_at' => $currentExpiry->addDays($subscription->plan->duration_days)
                ]);

                // تفعيل حساب المستخدم
                $subscription->user->update([
                    'is_active' => true,
                    'subscription_ends_at' => $subscription->expires_at
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'تم التحقق من الدفع بنجاح وتفعيل الاشتراك!',
                'data' => [
                    'transaction_number' => $transaction->transaction_number,
                    'membership_number'  =>  $subscription->user->membership_number,

                    'subscription' => $transaction->subscription?->fresh()->load('plan')
                ]
            ]);
        });
    }

    public function lastTransaction(Request $request)
    {
        $transaction = $request->user()
            ->transactions()
            ->with(['subscription.plan']) // لجلب بيانات الاشتراك والباقة
            ->latest() // ترتيب تنازلي حسب التاريخ لجلب الأحدث
            ->first(); // جلب أول نتيجة فقط

        if (!$transaction) {
            return response()->json([
                'status' => 404,
                'message' => 'لا توجد معاملات سابقة لهذا المستخدم'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل المعاملة بنجاح',
            'data' => [
                'id'                 => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'amount'             => $transaction->amount,
                'payment_method'     => $transaction->payment_method,
                'notes'              => $transaction->notes,
                'created_at'         => $transaction->created_at->format('Y-m-d H:i'),
                
                'subscription' => $transaction->subscription ? [
                    'plan_name'  => $transaction->subscription->plan->name_ar ?? $transaction->subscription->plan->name,
                    'starts_at'  => $transaction->subscription->starts_at?->format('Y-m-d'),
                    'expires_at' => $transaction->subscription->expires_at?->format('Y-m-d'),
                ] : null
            ]
        ]);
    }

    // عرض جميع الاشتراكات والمدفوعات في النادي للمستخدم
    public function myTransactions(Request $request)
    {
        $transactions = $request->user()
            ->transactions()
            ->with(['subscription.plan'])   // جلب الاشتراك + الخطة
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $transactions->map(function ($transaction) {
            return [
                'id'                  => $transaction->id,
                'transaction_number'  => $transaction->transaction_number,
                'amount'              => $transaction->amount,
                'payment_method'      => $transaction->payment_method,
                'status'              => $transaction->status,
                'notes'               => $transaction->notes,
                'created_at'          => $transaction->created_at->format('Y-m-d H:i'),
                
                'subscription' => $transaction->subscription ? [
                    'plan_name'   => $transaction->subscription->plan->name_ar ?? $transaction->subscription->plan->name,
                    'starts_at'   => $transaction->subscription->starts_at?->format('Y-m-d'),
                    'expires_at'  => $transaction->subscription->expires_at?->format('Y-m-d'),
                ] : null
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب جميع المدفوعات بنجاح',
            'data' => $formatted,
            'total_payments' => $transactions->count(),
            'total_amount'   => $transactions->sum('amount')
        ]);


    }

    public function downloadInvoice($id, Request $request)
    {
        // جلب المعاملة مع بيانات المستخدم والاشتراك لضمان أن المعاملة تخص المستخدم نفسه
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['user', 'subscription.plan'])
            ->firstOrFail();

        // تجهيز البيانات للقالب
        $data = [
            'transaction' => $transaction,
            'user' => $request->user(),
        ];

        // تحميل قالب blade وتوليد الـ PDF
        $pdf = Pdf::loadView('invoices.receipt', $data);

        // إرجاع ملف الـ PDF للتحميل
        return $pdf->download('Receipt-' . $transaction->transaction_number . '.pdf');
    }

}