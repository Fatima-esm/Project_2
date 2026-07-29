<?php

namespace App\Services;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Payment;
class TransactionService
{

    public function create($request)
    {

        return Transaction::create([

            'transaction_number'=>$request->transaction_number,
            'value'=>$request->value

        ]);

    }

    public function verify($request)
    {

        $subscription=Subscription::findOrFail($request->subscription_id);
        $transaction=Transaction::where( 'transaction_number', $request->transaction_number)->first();

        if(!$transaction){
            return [
                'success'=>false,
                'message'=>'Transaction not found'
            ];

        }

        if($transaction->value!=$subscription->price){
            return [
                'success'=>false,
                'message'=>'Amount mismatch'
            ];

        }

        if ($subscription->expires_at && $subscription->expires_at > now()) {
            $startDate = $subscription->expires_at;
        } else {

            $startDate = now();

        }

        $subscription->status = 'paid';
        $subscription->transaction_number = $transaction->transaction_number;
        $subscription->starts_at = now();

        $currentExpiry = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : null;

        if ($currentExpiry && $currentExpiry->gt(now())) {
            $startDate = $currentExpiry;
        } else {
            $startDate = now();
        }

        $subscription->expires_at = $startDate->copy()->addDays(
        $subscription->plan->duration_days
        );

        $subscription->save();

        Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'transaction_number' => $transaction->transaction_number,
            'amount' => $transaction->value,
            'payment_method' => 'Bank Transfer'
        ]);

        $transaction->delete();

        return [

            'success'=>true,
            'subscription'=>$subscription

        ];

    }

}