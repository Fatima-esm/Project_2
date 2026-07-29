<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionService
{

    public function create($request)
    {

        $plan=Plan::findOrFail($request->plan_id);

        $activeSubscription = Subscription::where('user_id', auth()->id())
            ->where('status', 'paid')
            ->where('expires_at', '>', now())
            ->first();

        if ($activeSubscription) {
            return [
                'success' => false,
                'message' => 'You already have an active subscription.'
            ];

        }
        $subscription=Subscription::create([

            'user_id'=>auth()->id(),
            'plan_id'=>$plan->id,
            'price'=>$plan->price,
            'status'=>'pending'

        ]);

        return response()->json(['subscribtion' => $subscription]);

    }

    public function index()
    {
        $subscription= Subscription::with('plan','user')->get();
        return response()->json(['subscribtion' => $subscription]);

    }

    public function checkExpiration()
    {
        $subscription= Subscription::where('status','paid')
            ->where('expires_at','<',now())
            ->update([
                'status'=>'expired'
            ]);

        return response()->json(['subscribtion' => $subscription]);
   

    }

}