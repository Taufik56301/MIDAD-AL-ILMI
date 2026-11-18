<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');

        $materials = Material::query()
            ->when($filter === 'low', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $lowStockCount = Material::query()->lowStock()->count();

        return view('materials.index', compact('materials', 'filter', 'lowStockCount'));
    }

    public function create(): View
    {
        return view('materials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'minimal_stock_alert' => ['required', 'numeric', 'min:0'],
        ]);

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', compact('material'));
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'minimal_stock_alert' => ['required', 'numeric', 'min:0'],
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Data bahan baku berhasil diperbarui.');
    }

    public function restock(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'add_stock' => ['required', 'numeric', 'gt:0'],
        ]);

        $material->current_stock = round((float) $material->current_stock + (float) $validated['add_stock'], 2);
        $material->save();

        return back()->with('success', "Stok {$material->name} berhasil ditambah menjadi {$material->formattedStock()} {$material->unit}.");
    }

    public function destroy(Material $material): RedirectResponse
    {
        if ($material->orderDetails()->exists()) {
            return back()->with('error', 'Bahan baku telah digunakan dalam transaksi pesanan dan tidak dapat dihapus.');
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Bahan baku berhasil dihapus.');
    }
}

