<?php

namespace App\Services;

use App\Models\Plan;

class PlanService
{

    public function index()
    {
        return Plan::all();
    }


    public function create($request)
    {
        return Plan::create([

            'name'=>$request->name,
            'price'=>$request->price,
            'duration_days'=>$request->duration_days

        ]);
    }

}