<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Exercise;

class ExercisesController extends Controller
{
    

    //  all exercises
    public function getAllExercises() {
        return response()->json(['exercises' => Exercise::with('category')->get()]);
    }
    

    // 1. all categories
    public function getCategories() {
        return response()->json(['categories' => Category::all()]);
    }

    // 2. show exercises by category
    public function getExercisesByCategory($categoryId) {
        $exercises = Exercise::where('category_id', $categoryId)->get();

        if (!$exercises) {
           return response()->json(['message' => 'Exercise not found'], 404);
        }

        return response()->json(['exercises' => $exercises]);
    }

    // 3. details of a specific exercise
    public function getExerciseDetails($id) {
        $exercise = Exercise::find($id);
        if (!$exercise) {
           return response()->json(['message' => 'Exercise not found'], 404);
        }

        return response()->json(['exercise' => $exercise]);

    }

    

    

}
