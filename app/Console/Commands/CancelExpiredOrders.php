<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Notification;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired';
    protected $description = 'Automatically cancel orders that have passed their end date';

    public function handle()
    {
        $expiredOrders = Order::where('end_date', '<', Carbon::now())
            ->whereNotIn('status', ['completed', 'canceled', 'rejected'])
            ->get();

        foreach ($expiredOrders as $order) {
            $order->update(['status' => 'canceled']);
            
            // Send notification to buyer
            Notification::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'title' => 'Order Expired',
                'content' => 'Your order for ' . $order->service->title . ' has been automatically canceled because the end date passed',
                'type' => 'order_expired',
                'date' => now()
            ]);
            
            // Send notification to seller
            Notification::create([
                'user_id' => $order->service->user_id,
                'order_id' => $order->id,
                'title' => 'Order Expired',
                'content' => 'Order #' . $order->id . ' has been automatically canceled because the end date passed',
                'type' => 'order_expired',
                'date' => now()
            ]);
        }

        $this->info('Canceled '.$expiredOrders->count().' expired orders');
    }
}