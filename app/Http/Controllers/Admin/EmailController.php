<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\CoachApplicationMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\StaffAttendance;

class EmailController extends Controller
{

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
                ->bcc('solaimanesmaeel334@gmail.com') // إيميلك
                ->send(new CoachApplicationMail(
                    $request->subject,
                    $request->message,
                    $coach->full_name
                ));
            \App\Models\SentEmail::create([
                'sent_by'    => auth()->id(),
                'coach_id'   => $coach->id,
                'coach_name' => $coach->full_name,
                'to_email'   => $coach->email,
                'subject'    => $request->subject,
                'body'       => $request->message,
                'type'       => 'coach_message',
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'تم إرسال الإيميل بنجاح',
                'data'    => [
                    'coach_id'    => $coach->id,
                    'coach_name'  => $coach->full_name,
                    'coach_email' => $coach->email,
                    'subject'     => $request->subject,
                    'message'     => $request->message,
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

    public function sentEmails(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $query = \App\Models\SentEmail::with([
                'sender:id,full_name',
                'coach:id,full_name,membership_number',
            ])
            ->orderByDesc('created_at');

        // فلترة اختيارية
        if ($request->filled('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('coach_name', 'like', "%{$search}%")
                ->orWhere('to_email', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate($request->get('per_page', 20));

        $data = collect($emails->items())->map(function ($email) {
            return [
                'id'           => $email->id,
                'coach_id'     => $email->coach_id,
                'coach_name'   => $email->coach_name,
                'to_email'     => $email->to_email,
                'subject'      => $email->subject,
                'body'         => $email->body,
                'type'         => $email->type,
                'sent_by'      => $email->sender?->full_name,
                'sent_at'      => $email->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب سجل الإيميلات بنجاح',
            'count'   => $emails->total(),
            'data'    => $data,
            'pagination' => [
                'current_page' => $emails->currentPage(),
                'last_page'    => $emails->lastPage(),
                'per_page'     => $emails->perPage(),
            ],
        ]);
    }

    public function showSentEmail($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $email = \App\Models\SentEmail::with(['sender:id,full_name', 'coach:id,full_name'])
            ->find($id);

        if (!$email) {
            return response()->json(['message' => 'الإيميل غير موجود'], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => [
                'id'         => $email->id,
                'coach_name' => $email->coach_name,
                'to_email'   => $email->to_email,
                'subject'    => $email->subject,
                'body'       => $email->body,
                'type'       => $email->type,
                'sent_by'    => $email->sender?->full_name,
                'sent_at'    => $email->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }



}
