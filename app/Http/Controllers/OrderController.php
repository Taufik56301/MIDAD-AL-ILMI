<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $orderStatus = $request->query('order_status', '');
        $paymentStatus = $request->query('payment_status', '');

        $orders = Order::query()
            ->with(['customer', 'user', 'details.material', 'payments'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_whatsapp', 'like', "%{$search}%"));
                });
            })
            ->when($orderStatus !== '', fn ($q) => $q->where('order_status', $orderStatus))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact('orders', 'search', 'orderStatus', 'paymentStatus'));
    }

    public function create(): View
    {
        $customers = Customer::query()->orderBy('name')->get();
        $materials = Material::query()->orderBy('name')->get();

        return view('orders.create', compact('customers', 'materials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $customerMode = $request->input('customer_mode', 'existing');

        $rules = [
            'customer_mode' => ['required', Rule::in(['existing', 'new'])],
            'file_design_link' => ['nullable', 'string', 'max:1000'],
            'design_file' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.material_id' => ['required', 'exists:materials,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.finishing_notes' => ['nullable', 'string', 'max:500'],
            'items.*.sub_total' => ['required', 'numeric', 'min:0'],
        ];

        if ($customerMode === 'new') {
            $rules['new_customer_name'] = ['required', 'string', 'max:255'];
            $rules['new_customer_whatsapp'] = ['required', 'string', 'max:30'];
            $rules['new_customer_address'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        }

        $validated = $request->validate($rules);

        $requiredByMaterial = $this->inventoryService->aggregateQuantities($validated['items']);
        $stockErrors = $this->inventoryService->findInsufficientStockErrors($requiredByMaterial);

        if (! empty($stockErrors)) {
            return back()
                ->withInput()
                ->with('insufficient_stock', $stockErrors)
                ->withErrors(['stock' => $stockErrors[0]]);
        }

        $designFilePath = null;
        if ($request->hasFile('design_file')) {
            $designFilePath = $request->file('design_file')->store('designs', 'public');
        }

        $order = DB::transaction(function () use ($request, $validated, $customerMode, $designFilePath): Order {
            if ($customerMode === 'new') {
                $customer = Customer::create([
                    'name' => $validated['new_customer_name'],
                    'phone_whatsapp' => $validated['new_customer_whatsapp'],
                    'address' => $validated['new_customer_address'] ?? null,
                ]);
                $customerId = $customer->id;
            } else {
                $customerId = (int) $validated['customer_id'];
            }

            $totalAmount = collect($validated['items'])->sum(fn (array $item) => (float) $item['sub_total']);

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customerId,
                'user_id' => $request->user()->id,
                'total_amount' => round($totalAmount, 2),
                'payment_status' => Order::PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ANTREAN,
                'file_design_link' => $validated['file_design_link'] ?? null,
                'design_file_path' => $designFilePath,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                    'size' => $item['size'] ?? null,
                    'finishing_notes' => $item['finishing_notes'] ?? null,
                    'sub_total' => $item['sub_total'],
                ]);
            }

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Pesanan {$order->order_number} berhasil dibuat. Silakan catat pembayaran DP/Lunas agar masuk ke Antrean Produksi.");
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'user', 'details.material', 'payments.user']);

        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'order_status' => ['required', Rule::in(Order::ORDER_STATUSES)],
        ]);

        $newStatus = $validated['order_status'];
        $user = $request->user();

        if ($newStatus === Order::STATUS_CLOSED && ! $user->hasRole('admin', 'owner')) {
            abort(403, 'Hanya Admin atau Owner yang dapat menutup pesanan (Sudah Diambil).');
        }

        if (in_array($newStatus, [Order::STATUS_DIPRODUKSI, Order::STATUS_SIAP_DIAMBIL], true) && ! $order->isProductionEligible()) {
            return back()->with('error', 'Pesanan belum memiliki pembayaran DP atau Lunas sehingga belum bisa diproses produksi.');
        }

        if ($newStatus === Order::STATUS_CLOSED && ! $order->canBeClosed()) {
            return back()->with('error', 'Integritas Keuangan: Pesanan belum lunas 100%, tidak dapat diubah ke status Sudah Diambil (Closed).');
        }

        try {
            DB::transaction(function () use ($order, $newStatus): void {
                $order->update(['order_status' => $newStatus]);
            });
        } catch (DomainException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = match ($newStatus) {
            Order::STATUS_DIPRODUKSI => "Status pesanan {$order->order_number} diubah menjadi Sedang Diproduksi. Stok bahan baku otomatis terpotong oleh OrderObserver.",
            Order::STATUS_SIAP_DIAMBIL => "Status pesanan {$order->order_number} diubah menjadi Selesai / Siap Diambil.",
            Order::STATUS_CLOSED => "Pesanan {$order->order_number} telah diambil pelanggan dan ditutup (Closed).",
            default => "Status pesanan {$order->order_number} diperbarui.",
        };

        return back()->with('success', $message);
    }

    public function downloadDesign(Order $order): BinaryFileResponse|RedirectResponse
    {
        if ($order->design_file_path && Storage::disk('public')->exists($order->design_file_path)) {
            return response()->download(Storage::disk('public')->path($order->design_file_path));
        }

        if ($order->file_design_link) {
            return redirect()->away($order->file_design_link);
        }

        return back()->with('error', 'Tidak ada berkas atau tautan desain yang terlampir pada pesanan ini.');
    }
}

