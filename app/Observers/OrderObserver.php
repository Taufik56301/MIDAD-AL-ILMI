<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;
use DomainException;

class OrderObserver
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Enforce domain integrity rules before saving an order status update.
     */
    public function updating(Order $order): void
    {
        if (! $order->isDirty('order_status')) {
            return;
        }

        if ($order->order_status === Order::STATUS_CLOSED && ! $order->canBeClosed()) {
            throw new DomainException(
                'Integritas Keuangan: Pesanan belum lunas 100%, tidak dapat diubah ke status Sudah Diambil (Closed).'
            );
        }

        if ($order->order_status === Order::STATUS_DIPRODUKSI && $order->stock_deducted_at === null) {
            $requiredByMaterial = $this->inventoryService->aggregateQuantities($order->details()->get());
            $errors = $this->inventoryService->findInsufficientStockErrors($requiredByMaterial);

            if (! empty($errors)) {
                throw new DomainException(implode(' ', $errors));
            }
        }
    }

    /**
     * Event-Driven automatic stock deduction triggered when status changes to "Sedang Diproduksi".
     */
    public function updated(Order $order): void
    {
        if (
            $order->wasChanged('order_status')
            && $order->order_status === Order::STATUS_DIPRODUKSI
            && $order->stock_deducted_at === null
        ) {
            $this->inventoryService->deductStockForOrder($order);
        }
    }
}

