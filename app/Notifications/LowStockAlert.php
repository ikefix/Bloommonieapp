<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'product_name' => $this->product->name,
            'stock_quantity' => $this->product->stock_quantity,
            'message' => 'Stock is low for ' . $this->product->name,
        ];
    }

    public function toFcm($notifiable)
    {
        return \Kreait\Firebase\Messaging\CloudMessage::withTarget(
            'token',
            $notifiable->fcm_token
        )->withNotification([
            'title' => '⚠️ Low Stock Alert',
            'body' => "{$this->product->name} is running low — only {$this->product->stock_quantity} left.",
        ]);
    }
}