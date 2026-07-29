<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\CreatePlanRequest;
use App\Services\PlanService;
use App\Models\Plan;

class PlanController extends Controller
{

    protected $service;
    

    public function __construct(PlanService $service)
    {
        $this->service=$service;
    }

    // all plans
    public function index()
    {
        return response()->json([
            'status' => 200,
            'message' => 'تم استرجاع الباقات بنجاح',
            'data' => $this->service->index()
        ]);
    }

    public function show($id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json([
                'status' => 404,
                'message' => 'الباقة غير موجودة'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'duration_days' => $plan->duration_days,
                // يمكنك إضافة أي حقول أخرى لديك في جدول الخطط
            ]
        ]);
    }

    //choose plan


    //payment details by rememebership num

    //confirm payment by transaction num

    //show subscription after succesful





    

}