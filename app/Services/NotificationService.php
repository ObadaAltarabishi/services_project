<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Events\NotificationCreated;

class NotificationService
{
    public static function createServiceStatusNotification(User $user, Service $service, string $status)
    {
        $content = $status === 'accepted' 
            ? "Your service '{$service->name}' has been approved!" 
            : "Your service '{$service->name}' has been rejected.";

        $title = $status === 'accepted' 
            ? 'Service Approved' 
            : 'Service Rejected';

        return self::createNotification([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'title' => $title,
            'content' => $content,
            'type' => 'service_' . $status,
        ]);
    }

    public static function createOrderStatusNotification(Order $order, string $status)
{
    if (!$order->service) {
        \Log::error("Service not found for Order #{$order->id}");
        return null; // أو يمكنك إرجاع إشعار بديل
    }

    $notificationData = [
        'user_id' => $order->user_id,
        'order_id' => $order->id,
        'type' => 'order_' . $status,
    ];

    switch ($status) {
        case 'accepted':
            $notificationData['title'] = 'Order Accepted';
            $notificationData['content'] = "Your order for {$order->service->title} has been accepted";
            break;
        case 'rejected':
            $notificationData['title'] = 'Order Rejected';
            $notificationData['content'] = "Your order for {$order->service->title} has been rejected";
            break;
        case 'completed':
            $notificationData['title'] = 'Order Completed';
            $notificationData['content'] = "Your order for {$order->service->title} has been completed";
            break;
        case 'canceled':
            $notificationData['title'] = 'Order Canceled';
            $notificationData['content'] = "Your order for {$order->service->title} has been canceled";
            break;
    }

    return self::createNotification($notificationData);
}

    public static function createSellerOrderNotification(Order $order, string $status)
    {
        $notificationData = [
            'user_id' => $order->service->user_id,
            'order_id' => $order->id,
            'type' => 'seller_order_' . $status,
        ];

        switch ($status) {
            case 'created':
                $notificationData['title'] = 'New Order Received';
                $notificationData['content'] = "New order received for your service: {$order->service->title}";
                break;
            case 'completed':
                $notificationData['title'] = 'Order Completed';
                $notificationData['content'] = "Order completed for your service: {$order->service->title}";
                break;
        }

        return self::createNotification($notificationData);
    }

    private static function createNotification(array $data)
    {
        $notification = Notification::create(array_merge($data, [
            'is_seen' => false,
        ]));

        // event(new NotificationCreated($notification));

        return $notification;
    }

    public static function createServiceExchangeNotification(Order $order, Service $providedService)
{
    $content = "New service exchange request for your service: {$order->service->title}. " .
               "The buyer is offering: {$providedService->title}";

    return self::createNotification([
        'user_id' => $order->service->user_id,
        'order_id' => $order->id,
        'service_id' => $providedService->id,
        'title' => 'Service Exchange Request',
        'content' => $content,
        'type' => 'service_exchange_request'
    ]);
}

}