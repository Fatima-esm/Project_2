<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        sleep(3);

        $paymentSuccess = $this->fakePaymentGateway();
//data consistency(اتساق البيانات)
        if ($paymentSuccess) {
            $this->order->update(['status' => 'completed']);
            Log::info("Order #{$this->order->id} completed successfully.");
        } else {
            // فشل الدفع: نعيد المخزون ونلغي الطلب
            DB::transaction(function () {
                foreach ($this->order->items as $item) {
                    Product::where('id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity);
                }
                $this->order->update(['status' => 'cancelled']);
            });
            Log::warning("Payment failed for order #{$this->order->id}. Stock restored.");
        }
    }

    private function fakePaymentGateway()
    {
        return rand(1, 100) <= 90;
    }
}
