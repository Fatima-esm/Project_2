<?php

namespace App\Http\Controllers\Payment;
use App\Http\Controllers\Controller;
use App\Models\Payment;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //سجل المدفوعات
    public function index()
    {
        $payments = Payment::where('user_id', auth()->id())->latest()->get();
        return response()->json([
            'status' => 200,
            'message' => 'تم استرجاع جميع عمليات الدفع بنجاح',
            'payments' => $payments
        
        ]);
       }


}
