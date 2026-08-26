<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * Fired when syncing an offline sale pushes a product's stock below zero.
 * Two devices/staff can both sell the last unit of the same product while
 * offline — this is expected and allowed (we never block a sale for stock
 * reasons offline), but the admin needs to see it so they can restock or
 * correct the count. Stored in the same `notifications` table your existing
 * LowStockAlert uses, so it shows up in the same notification bell/list in
 * the app with no extra plumbing needed on the Flutter side.
 */
class StockReconciliationAlert extends Notification
{
    use Queueable;

    protected Product $product;
    protected float $oversoldBy;
    protected string $clientSaleId;

    public function __construct(Product $product, float $oversoldBy, string $clientSaleId)
    {
        $this->product = $product;
        $this->oversoldBy = $oversoldBy;
        $this->clientSaleId = $clientSaleId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type'            => 'stock_reconciliation',
            'title'           => 'Stock reconciliation needed',
            'message'         => "\"{$this->product->name}\" went negative by {$this->oversoldBy} after an offline sale synced. Current stock: {$this->product->stock_quantity}.",
            'product_id'      => $this->product->id,
            'product_name'    => $this->product->name,
            'shop_id'         => $this->product->shop_id,
            'stock_quantity'  => $this->product->stock_quantity,
            'oversold_by'     => $this->oversoldBy,
            'client_sale_id'  => $this->clientSaleId,
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}