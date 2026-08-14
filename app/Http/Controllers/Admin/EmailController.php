<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CoachApplicationMail;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    /**
     * إرسال بريد لكوتش محدد بالـ id
     */
    public function sendEmailToCoach(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $coach = User::where('role', 'coach')->find($id);

        if (!$coach) {
            return response()->json(['message' => 'الكوتش غير موجود'], 404);
        }

        if (!$coach->email) {
            return response()->json(['message' => 'لا يوجد بريد إلكتروني لهذا الكوتش'], 400);
        }

        try {
            Mail::to($coach->email)
                ->bcc('solaimanesmaeel334@gmail.com')
                ->send(new CoachApplicationMail(
                    $request->subject,
                    $request->message,
                    $coach->full_name
                ));

            SentEmail::create([
                'sent_by'   => auth()->id(),
                'user_id'   => $coach->id,
                'user_name' => $coach->full_name,
                'to_email'  => $coach->email,
                'subject'   => $request->subject,
                'body'      => $request->message,
                'type'      => 'coach_message',
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'تم إرسال الإيميل بنجاح',
                'data'    => [
                    'user_id'   => $coach->id,
                    'user_name' => $coach->full_name,
                    'to_email'  => $coach->email,
                    'subject'   => $request->subject,
                    'message'   => $request->message,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'فشل إرسال الإيميل',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرسال بريد لأي مستخدم بالإيميل
     */
    public function sendEmailToUser(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $data = $request->validate([
            'email'   => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'email.required'   => 'البريد الإلكتروني مطلوب',
            'email.email'      => 'البريد الإلكتروني غير صالح',
            'subject.required' => 'عنوان الرسالة مطلوب',
            'message.required' => 'نص الرسالة مطلوب',
        ]);

        $user = User::where('email', $data['email'])->first();
        $userName = $user?->full_name ?? 'عزيزي المشترك';

        try {
            Mail::to($data['email'])
                ->bcc('solaimanesmaeel334@gmail.com')
                ->send(new CoachApplicationMail(
                    $data['subject'],
                    $data['message'],
                    $userName
                ));

            $record = SentEmail::create([
                'sent_by'   => auth()->id(),
                'user_id'   => $user?->id,
                'user_name' => $userName,
                'to_email'  => $data['email'],
                'subject'   => $data['subject'],
                'body'      => $data['message'],
                'type'      => 'admin_message',
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'تم إرسال البريد وحفظه بنجاح',
                'data'    => [
                    'id'        => $record->id,
                    'user_id'   => $user?->id,
                    'user_name' => $userName,
                    'to_email'  => $data['email'],
                    'subject'   => $data['subject'],
                    'sent_at'   => $record->created_at->format('Y-m-d H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'فشل إرسال البريد',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرسال بريد لمستخدم بالـ id (متدرب / كوتش / استقبال)
     */
    public function sendEmailByUserId(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        if (!$user->email) {
            return response()->json(['message' => 'لا يوجد بريد إلكتروني لهذا المستخدم'], 400);
        }

        try {
            Mail::to($user->email)
                ->bcc('solaimanesmaeel334@gmail.com')
                ->send(new CoachApplicationMail(
                    $request->subject,
                    $request->message,
                    $user->full_name
                ));

            SentEmail::create([
                'sent_by'   => auth()->id(),
                'user_id'   => $user->id,
                'user_name' => $user->full_name,
                'to_email'  => $user->email,
                'subject'   => $request->subject,
                'body'      => $request->message,
                'type'      => $user->role . '_message',
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'تم إرسال الإيميل بنجاح',
                'data'    => [
                    'user_id'   => $user->id,
                    'user_name' => $user->full_name,
                    'role'      => $user->role,
                    'to_email'  => $user->email,
                    'subject'   => $request->subject,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'فشل إرسال الإيميل',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * سجل كل الإيميلات المرسلة
     */
    public function sentEmails(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $query = SentEmail::with([
                'sender:id,full_name',
                'user:id,full_name,membership_number,role',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('to_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate($request->get('per_page', 20));

        $data = collect($emails->items())->map(fn($email) => [
            'id'        => $email->id,
            'user_id'   => $email->user_id,
            'user_name' => $email->user_name,
            'role'      => $email->user?->role,
            'to_email'  => $email->to_email,
            'subject'   => $email->subject,
            'body'      => $email->body,
            'type'      => $email->type,
            'sent_by'   => $email->sender?->full_name,
            'sent_at'   => $email->created_at->format('Y-m-d H:i'),
        ]);

        return response()->json([
            'status'     => 200,
            'message'    => 'تم جلب سجل الإيميلات بنجاح',
            'count'      => $emails->total(),
            'data'       => $data,
            'pagination' => [
                'current_page' => $emails->currentPage(),
                'last_page'    => $emails->lastPage(),
                'per_page'     => $emails->perPage(),
            ],
        ]);
    }

    /**
     * تفاصيل إيميل مرسل
     */
    public function showSentEmail($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $email = SentEmail::with(['sender:id,full_name', 'user:id,full_name,role'])
            ->find($id);

        if (!$email) {
            return response()->json(['message' => 'الإيميل غير موجود'], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => [
                'id'        => $email->id,
                'user_id'   => $email->user_id,
                'user_name' => $email->user_name,
                'role'      => $email->user?->role,
                'to_email'  => $email->to_email,
                'subject'   => $email->subject,
                'body'      => $email->body,
                'type'      => $email->type,
                'sent_by'   => $email->sender?->full_name,
                'sent_at'   => $email->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }
}