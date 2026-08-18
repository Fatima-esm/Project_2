<?php

namespace App\Http\Controllers\Rating;

use App\Http\Controllers\Controller;
use App\Models\CoachRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoachRatingController extends Controller
{
    // تقييم المتدرب الكوتش المسؤول عنه
    public function store(Request $request)
    {
        $trainee = auth()->user();

        if ($trainee->role !== 'trainee') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if (!$trainee->coach_id) {
            return response()->json(['message' => 'ليس لديك كوتش معيّن حالياً قم باختيار كوتش'], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'التقييم مطلوب',
            'rating.min'      => 'أقل تقييم 1',
            'rating.max'      => 'أعلى تقييم 5',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $coach = User::where('id', $trainee->coach_id)
            ->where('role', 'coach')
            ->where('status', 'active')
            ->first();

        if (!$coach) {
            return response()->json(['message' => 'الكوتش غير متاح'], 404);
        }

        $rating = CoachRating::updateOrCreate(
            [
                'trainee_id' => $trainee->id,
                'coach_id'   => $coach->id,
            ],
            [
                'rating'  => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'status'  => 201,
            'message' => 'تم حفظ تقييمك بنجاح',
            'data'    => [
                'id'         => $rating->id,
                'coach_id'   => $coach->id,
                'coach_name' => $coach->full_name,
                'rating'     => $rating->rating,
                'comment'    => $rating->comment,
                'updated_at' => $rating->updated_at->format('Y-m-d H:i'),
            ],
        ], 201);
    }

    // عرض تقييم المتدرب الحالي لكوتشه
    public function myRating()
    {
        $trainee = auth()->user();

        if ($trainee->role !== 'trainee') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if (!$trainee->coach_id) {
            return response()->json([
                'status' => 200,
                'data'   => null,
                'message'=> 'لا يوجد كوتش معيّن',
            ]);
        }

        $rating = CoachRating::where('trainee_id', $trainee->id)
            ->where('coach_id', $trainee->coach_id)
            ->first();

        return response()->json([
            'status' => 200,
            'data'   => $rating ? [
                'id'      => $rating->id,
                'rating'  => $rating->rating,
                'comment' => $rating->comment,
                'date'    => $rating->updated_at->format('Y-m-d H:i'),
            ] : null,
        ]);
    }

    // ملخص تقييمات كوتش
    public function coachSummary()
    {
        $coach = auth()->user();

        if ($coach->role !== 'coach') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $ratings = CoachRating::where('coach_id', $coach->id)
            ->with('trainee:id,full_name,profile_image')
            ->latest()
            ->get();

        $avg = round((float) $ratings->avg('rating'), 1);

        return response()->json([
            'status' => 200,
            'data'   => [
                'average_rating' => $avg,
                'total_ratings'  => $ratings->count(),
                'ratings'        => $ratings->map(fn ($r) => [
                    'id'           => $r->id,
                    'rating'       => $r->rating,
                    'comment'      => $r->comment,
                    'trainee_name' => $r->trainee?->full_name,
                    'trainee_image'=> $r->trainee?->profile_image_url
                        ?? ($r->trainee?->profile_image
                            ? asset('storage/' . $r->trainee->profile_image)
                            : null),
                    'date'         => $r->created_at->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    public function getCoachSummaryForAdmin($coachId)
    {
        $admin = auth()->user();
        if (!in_array($admin->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $coach = User::where('id', $coachId)
            ->where('role', 'coach')
            ->first();

        if (!$coach) {
            return response()->json(['message' => 'المدرب غير موجود'], 404);
        }

        $ratings = CoachRating::where('coach_id', $coach->id)
            ->with('trainee:id,full_name,profile_image')
            ->latest()
            ->get();

        $avg = round((float) $ratings->avg('rating'), 1);

        return response()->json([
            'status' => 200,
            'data'   => [
                'coach_id'       => $coach->id,
                'coach_name'     => $coach->full_name,
                'average_rating' => $avg,
                'total_ratings'  => $ratings->count(),
                'ratings'        => $ratings->map(fn ($r) => [
                    'id'           => $r->id,
                    'rating'       => $r->rating,
                    'comment'      => $r->comment,
                    'trainee_name' => $r->trainee?->full_name,
                    'trainee_image'=> $r->trainee?->profile_image_url
                        ?? ($r->trainee?->profile_image
                            ? asset('storage/' . $r->trainee->profile_image)
                            : null),
                    'date'         => $r->created_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    public function coachRatings($coachId)
    {
        $coach = User::where('role', 'coach')->find($coachId);

        if (!$coach) {
            return response()->json(['message' => 'الكوتش غير موجود'], 404);
        }

        $ratings = CoachRating::where('coach_id', $coachId)
            ->with('trainee:id,full_name,profile_image')
            ->latest()
            ->get();

        return response()->json([
            'status' => 200,
            'data'   => [
                'coach' => [
                    'id'   => $coach->id,
                    'name' => $coach->full_name,
                ],
                'average_rating' => round((float) $ratings->avg('rating'), 1),
                'total_ratings'  => $ratings->count(),
                'ratings'        => $ratings->map(fn ($r) => [
                    'rating'       => $r->rating,
                    'comment'      => $r->comment,
                    'trainee_name' => $r->trainee?->full_name,
                    'date'         => $r->created_at->format('Y-m-d'),
                ]),
            ],
        ]);
    }

    // حذف تقييم المتدرب
    public function destroy()
    {
        $trainee = auth()->user();

        if ($trainee->role !== 'trainee' || !$trainee->coach_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        CoachRating::where('trainee_id', $trainee->id)
            ->where('coach_id', $trainee->coach_id)
            ->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'تم حذف التقييم',
        ]);
    }





}