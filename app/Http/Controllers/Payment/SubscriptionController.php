<?php

namespace App\Http\Controllers\Payment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\CreateSubscriptionRequest;
use App\Services\SubscriptionService;
use App\Models\Plan;
use App\Models\User;     // <--- تأكد من إضافة هذا السطر
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{

    protected $service;

    public function __construct(SubscriptionService $service)
    {
        $this->service=$service;
    }

    public function currentSubscription(Request $request)
    {
        $user = $request->user();

        // جلب أحدث اشتراك نشط
        $subscription = $user->subscriptions()
                            ->with('plan')
                            ->where('status', 'paid')
                            ->where('expires_at', '>', now())
                            ->latest('expires_at')
                            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => 404,
                'message' => 'لا يوجد اشتراك نشط حالياً',
                'data' => null
            ], 404);
        }

        // حساب الأيام المتبقية
        $now = now();
        $daysRemaining = $now->diffInDays($subscription->expires_at, false); // false = يسمح بالسالب

        $isExpired = $daysRemaining < 0;

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب الاشتراك الحالي بنجاح',
            'data' => [
                'subscription_id'    => $subscription->id,
                'plan_name'          => $subscription->plan->name_ar ?? $subscription->plan->name,
                'price'              => $subscription->price,
                'starts_at'          => $subscription->starts_at->format('Y-m-d'),
                'expires_at'         => $subscription->expires_at->format('Y-m-d'),
                'days_remaining'     => max(0, $daysRemaining),   // لا يظهر سالب
                'is_active'          => !$isExpired,
                'status'             => $subscription->status,
                'plan'               => $subscription->plan
            ]
        ]);
    }


    // 2
    //create subscription
    public function create(CreateSubscriptionRequest $request)
    {
        $user = $request->user();
        $existingPendingSub = $user->subscriptions()
                               ->where('status', 'pending')
                               ->first();

        if ($existingPendingSub) {
            return response()->json([
                'status' => 400,
                'message' => 'لديك بالفعل طلب اشتراك قيد الانتظار، يرجى إتمام عملية الدفع له أولاً.',
                'data' => [
                    'subscription_id' => $existingPendingSub->id,
                    'plan_name' => $existingPendingSub->plan->name ?? 'Plan'
                ]
            ], 400);
        }
        
        $plan = Plan::findOrFail($request->plan_id);

        $activeSub = $user->subscriptions()
                      ->where('status', 'paid')
                      ->where('expires_at', '>', now())
                      ->latest('expires_at')
                      ->first();

       
        if ($activeSub) {
            $daysRemaining = now()->diffInDays($activeSub->expires_at, false);

                if ($daysRemaining > 15) {
                    return response()->json([
                       'status' => 400,
                       'message' => "You already have an active subscription ({$activeSub->plan->name}) with {$daysRemaining} days remaining. You cannot renew it now.",  'data' => [
                            'current_plan' => $activeSub->plan->name,
                            'days_remaining' => $daysRemaining,
                            'expires_at' => $activeSub->expires_at->format('Y-m-d')
                        ]
                    ], 400);
                }
        }


        // إنشاء اشتراك جديد
        $subscription = Subscription::create([
            'user_id'            => $user->id,
            'plan_id'            => $plan->id,
            'price'              => $plan->price,
            'duration_days'      => $plan->duration_days,
            'starts_at'          => now(),
            'expires_at'         => now()->addDays($plan->duration_days),
            'status'             => 'pending',
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'تم ارسال طلب الاشتراك. يرجى إتمام الدفع.',
            'subscription' => $subscription->load('plan'),
            'plan' => $plan
        ]);

    }

    // تجديد اشتراك المتدرب من قبل الإدارة
    public function renewSubscriptionByAdmin(Request $request, $id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'plan_id'        => 'required|exists:plans,id',
            'payment_method' => 'required|in:cash,bank,online,card', 
            'notes'           => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $plan = Plan::findOrFail($request->plan_id);

            // 1. فحص الاشتراك الحالي لترحيل الأيام
            $activeSub = $user->subscriptions()
                              ->where('status', 'paid')
                              ->where('expires_at', '>', now())
                              ->latest('expires_at')
                              ->first();

            $startsAt = now();
            $expiresAt = now()->addDays($plan->duration_days);

            if ($activeSub) {
                $daysRemainingCurrent = now()->diffInDays($activeSub->expires_at, false);

                // منع التجديد إذا كان المتبقي أكثر من شهر
                if ($daysRemainingCurrent > 30) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 400,
                        'message' => "عذراً، لا يمكن التجديد الآن. المتبقي في اشتراكه الحالي {$daysRemainingCurrent} يوماً (أكثر من شهر)."
                    ], 400);
                }

                $startsAt = $activeSub->expires_at;
                $expiresAt = (clone $activeSub->expires_at)->addDays($plan->duration_days);

                $activeSub->update(['status' => 'expired']);
            }

            // 2. إنشاء سجل الاشتراك الجديد
            $subscription = Subscription::create([
                'user_id'        => $user->id,
                'plan_id'        => $plan->id,
                'price'          => $plan->price,
                'duration_days'  => $plan->duration_days,
                'starts_at'      => $startsAt,
                'expires_at'     => $expiresAt,
                'status'         => 'paid', 
                'note'           => $request->note,
            ]);

            // 3. إنشاء سجل المعاملة المالية المرتبطة وتوليد رقم معاملة تلقائياً في الخلفية
            $transaction = Transaction::create([
                'transaction_number' => 'TRX-' . strtoupper(uniqid()),
                'amount'             => $plan->price,
                'user_id'            => $user->id,
                'sender_name'        => $user->full_name,
                'sender_phone'        => $user->phone,
                'subscription_id'    => $subscription->id,
                'payment_method'     => $request->payment_method,
                'status'             => 'verified',
                'notes'              => $request->note,
            ]);

            DB::commit();

            $user->load(['activeSubscription.plan']);
            $daysRemaining = now()->diffInDays($expiresAt, false);

            return response()->json([
                'status' => 200,
                'message' => 'تم تجديد الاشتراك بنجاح',
                'data' => [
                    'trainee_info' => [
                        'id'                => $user->id,
                        'full_name'         => $user->full_name,
                        'membership_number' => $user->membership_number,
                        'email'             => $user->email,
                        'phone'             => $user->phone,
                    ],
                    'subscription_details' => [
                        'subscription_id'   => $subscription->id,
                        'plan_name'         => $plan->name_ar ?? $plan->name,
                        'price'             => $subscription->price,
                        'payment_method'    => $transaction->payment_method,
                        'status'            => $subscription->status,
                        'starts_at'         => $subscription->starts_at->format('Y-m-d'),
                        'expires_at'        => $subscription->expires_at->format('Y-m-d'),
                        'days_remaining'    => max(0, $daysRemaining),
                        'notes'              => $transaction->notes,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء تجديد الاشتراك',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}