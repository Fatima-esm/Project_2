<?php

namespace App\Http\Controllers\ClubPage;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\GymHall;

class GymHallController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = GymHall::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type); // group | individual
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'available');
        }

        $halls = $query->orderBy('type')->orderBy('name')->get();

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب قائمة الصالات بنجاح',
            'data'    => $halls,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,group',
            'capacity' => 'required|integer|min:1',
            'status' => 'sometimes|in:available,reserved',
            'description' => 'nullable|string',
        ]);

        $hall = GymHall::create($request->all());

        return response()->json([
            'status' => 201,
            'message' => 'تم إنشاء الصالة بنجاح',
            'data' => $hall
        ], 201);
    }

    public function show($id) // تم تصحيح الكتابة العامة
    {
        $hall = GymHall::findOrFail($id);

        return response()->json([
            'status' => 200,
            'data' => $hall
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $hall = GymHall::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:individual,group',
            'capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:available,reserved',
            'description' => 'nullable|string',
        ]);

        $hall->update($request->all());

        return response()->json([
            'status' => 200,
            'message' => 'تم تحديث بيانات الصالة بنجاح',
            'data' => $hall
        ], 200);
    }

    public function destroy($id)
    {
        $hall = GymHall::findOrFail($id);
        $hall->delete();

        return response()->json([
            'status' => 200,
            'message' => 'تم حذف الصالة بنجاح'
        ], 200);
    }
}
