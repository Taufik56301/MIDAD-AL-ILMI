<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Aggregate requested material quantities from order items or details.
     *
     * @param  iterable<int, array{material_id: int|string, qty: float|int|string}|object>  $items
     * @return array<int, float>
     */
    public function aggregateQuantities(iterable $items): array
    {
        $totals = [];

        foreach ($items as $item) {
            $materialId = (int) (is_array($item) ? $item['material_id'] : $item->material_id);
            $qty = (float) (is_array($item) ? $item['qty'] : $item->qty);

            if ($materialId > 0 && $qty > 0) {
                $totals[$materialId] = round(($totals[$materialId] ?? 0.0) + $qty, 2);
            }
        }

        return $totals;
    }

    /**
     * Validate stock availability before saving or producing an order.
     *
     * @param  array<int, float>  $requiredByMaterial
     * @return list<string> Human-readable insufficient stock error messages
     */
    public function findInsufficientStockErrors(array $requiredByMaterial, bool $lockRows = false): array
    {
        if (empty($requiredByMaterial)) {
            return [];
        }

        $query = Material::query()->whereIn('id', array_keys($requiredByMaterial));
        if ($lockRows) {
            $query->lockForUpdate();
        }
        $materials = $query->get()->keyBy('id');

        $errors = [];
        foreach ($requiredByMaterial as $materialId => $neededQty) {
            $material = $materials->get($materialId);
            if (! $material) {
                $errors[] = "Bahan baku dengan ID #{$materialId} tidak ditemukan.";
                continue;
            }

            if ((float) $material->current_stock < $neededQty) {
                $errors[] = sprintf(
                    'Insufficient Stock Alert: Stok %s tidak mencukupi (dibutuhkan %s %s, tersedia %s %s).',
                    $material->name,
                    $this->formatNumber($neededQty),
                    $material->unit,
                    $material->formattedStock(),
                    $material->unit
                );
            }
        }

        return $errors;
    }

    /**
     * Atomically deduct stock for an order when entering production.
     */
    public function deductStockForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $lockedOrder || $lockedOrder->stock_deducted_at !== null) {
                return;
            }

            $details = $lockedOrder->details()->get();
            $requiredByMaterial = $this->aggregateQuantities($details);

            $errors = $this->findInsufficientStockErrors($requiredByMaterial, lockRows: true);
            if (! empty($errors)) {
                throw new RuntimeException(implode(' ', $errors));
            }

            $materials = Material::query()
                ->whereIn('id', array_keys($requiredByMaterial))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requiredByMaterial as $materialId => $neededQty) {
                $material = $materials->get($materialId);
                if ($material) {
                    $material->current_stock = round((float) $material->current_stock - $neededQty, 2);
                    $material->save();
                }
            }

            $lockedOrder->stock_deducted_at = now();
            $lockedOrder->saveQuietly();
            $order->stock_deducted_at = $lockedOrder->stock_deducted_at;
        });
    }

    private function formatNumber(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : number_format($value, 2, ',', '.');
    }
}

