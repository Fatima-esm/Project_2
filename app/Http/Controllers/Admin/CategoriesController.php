<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator= Validator::make($request->all(),[
            'name'=>['required','string','max:255']
        ]);
        if($validator->fails()){
            return response()->json([
                'errors'=>$validator->errors(),
            ],401);
        }
        try{
            $create_category= Category::create($validator->validated());
            if($create_category){
                return response()->json([
                    'data'=>$create_category,
                ]);
            }else{
                return response()->json([
                    'errors'=>"errors creating record",
                ]);
            }
        }
        catch (\Exception $e){
            return response()->json([
                'errors'=>" an exception occurred",
            ]);
        }
        catch (\Error $e){
            return response()->json([
                'errors'=>" an error occurred",
            ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
