<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withCount('orders')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone_whatsapp', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_whatsapp' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Pelanggan baru berhasil disimpan.');
    }

    public function show(Customer $customer): View
    {
        $orders = $customer->orders()
            ->with(['details.material', 'payments'])
            ->latest()
            ->paginate(10);

        return view('customers.show', compact('customer', 'orders'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_whatsapp' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Pelanggan memiliki riwayat pesanan sehingga tidak dapat dihapus.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil dihapus.');
    }
}

