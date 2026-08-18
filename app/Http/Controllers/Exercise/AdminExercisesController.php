<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Support\Facades\Storage;

class AdminExercisesController extends Controller
{
    // 1. توابع إدارة التمارين (Exercises Admin)

    public function getAdminExercises(Request $request) {
        $query = Exercise::with('category');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json([
            'exercises' => $query->paginate(15)
        ]);
    }

    // إضافة تمرين جديد
    public function storeExercise(Request $request)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'video_url'     => 'nullable|url|max:500',
            'target_muscles'=> 'nullable|string|max:255',
             ]);

        $exercise = Exercise::create($data);

        return response()->json([
            'status'  => 201,
            'message' => 'تم إضافة التمرين',
            'data'    => $exercise->load('category:id,name,image'),
        ], 201);
    
    }

    public function showExercise($id) 
    {
        $exercise = Exercise::with('category')->find($id);

        if (!$exercise) {
            return response()->json([
                'status'  => 404,
                'message' => 'التمرين غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => $exercise
        ]);
    }

    // تعديل تمرين
    public function updateExercise(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);

        $data = $request->validate([
            'category_id'   => 'sometimes|exists:categories,id',
            'name'          => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'video_url'     => 'nullable|url|max:500',
            'target_muscles'=> 'nullable|string|max:255',
        ]);

        $exercise->update($data);

        return response()->json([
            'status'  => 200,
            'message' => 'تم تحديث التمرين',
            'data'    => $exercise->fresh()->load('category:id,name,image'),
        ]);
    
    }

    // حذف تمرين
    public function deleteExercise($id) 
    {
        $exercise = Exercise::find($id);
        if (!$exercise) {
            return response()->json(['message' => 'التمرين غير موجود'], 404);
        }

        $exercise->delete();

        return response()->json(['message' => 'تم حذف التمرين بنجاح']);
    }


    public function getCategories() 
    {
        $categories = Category::withCount('exercises')->get();

        return response()->json([
            'status' => 200,
            'data'   => $categories
        ]);
    }

    // إضافة تصنيف جديد
    public function storeCategory(Request $request) 
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($validated);

        return response()->json([
            'message'  => 'تم إضافة التصنيف بنجاح',
            'category' => $category
        ], 201);
    }

    public function showCategory($id) 
    {
        $category = Category::with('exercises')->find($id);

        if (!$category) {
            return response()->json([
                'status'  => 404,
                'message' => 'التصنيف غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => $category
        ]);
    }

    // تعديل تصنيف
    public function updateCategory(Request $request, $id) {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود'], 404);
        }

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);

        return response()->json([
            'message'  => 'تم تحديث التصنيف بنجاح',
            'category' => $category
        ]);
    }

    // حذف تصنيف
    public function deleteCategory($id) {

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود'], 404);
        }

        // منع الحذف إذا كان التصنيف يحتوي على تمارين
        if ($category->exercises()->count() > 0) {
            return response()->json(['message' => 'لا يمكن حذف التصنيف لأنه مرتبط بتمارين حالية'], 422);
        }

        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        $category->forceDelete();

        return response()->json(['message' => 'تم حذف التصنيف بنجاح']);
    }



}