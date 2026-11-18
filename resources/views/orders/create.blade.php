@extends('layouts.app')

@section('title', 'Buat Pesanan Baru - Midad Al-Ilmi')
@section('header', 'Input Pesanan Baru (Order Entry)')
@section('subheader', 'Tahap 2: Validasi ketersediaan stok bahan baku dilakukan secara otomatis sebelum penyimpanan')

@section('content')
@php
    $defaultItems = old('items', [
        ['product_name' => '', 'material_id' => '', 'qty' => 1, 'size' => '', 'finishing_notes' => '', 'sub_total' => 0]
    ]);
    $customerMode = old('customer_mode', 'existing');
    $selectedCustomerId = old('customer_id', request('customer_id'));
@endphp

<form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    {{-- Bagian 1: Data Pelanggan (CRM) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-bold text-slate-900">1. Identitas Pelanggan (CRM)</h2>
            <div class="flex items-center gap-4 text-sm">
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="customer_mode" value="existing" @checked($customerMode === 'existing') onchange="toggleCustomerMode('existing')">
                    <span>Pelanggan Terdaftar</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="customer_mode" value="new" @checked($customerMode === 'new') onchange="toggleCustomerMode('new')">
                    <span>Pelanggan Baru</span>
                </label>
            </div>
        </div>

        <div id="customer-existing-box" class="{{ $customerMode === 'new' ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Pelanggan</label>
            <select name="customer_id" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
                <option value="">-- Pilih Pelanggan --</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected($selectedCustomerId == $c->id)>{{ $c->name }} ({{ $c->phone_whatsapp }})</option>
                @endforeach
            </select>
        </div>

        <div id="customer-new-box" class="grid grid-cols-1 md:grid-cols-3 gap-4 {{ $customerMode === 'new' ? '' : 'hidden' }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pelanggan Baru</label>
                <input type="text" name="new_customer_name" value="{{ old('new_customer_name') }}" placeholder="Nama / Instansi" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">No. WhatsApp</label>
                <input type="text" name="new_customer_whatsapp" value="{{ old('new_customer_whatsapp') }}" placeholder="08xxxxxxxxxx" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Alamat (Opsional)</label>
                <input type="text" name="new_customer_address" value="{{ old('new_customer_address') }}" placeholder="Alamat singkat" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
        </div>
    </div>

    {{-- Bagian 2: Spesifikasi Produk & Bahan Baku --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">2. Detail Pesanan & Bahan Baku</h2>
                <p class="text-xs text-slate-500">Pilih bahan baku dan kuantitas. Sistem akan memperingatkan jika stok gudang tidak mencukupi.</p>
            </div>
            <button type="button" onclick="addItemRow()" class="px-3.5 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                + Tambah Baris Item
            </button>
        </div>

        <div id="items-container" class="space-y-4">
            @foreach($defaultItems as $idx => $item)
                <div class="item-row rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3" data-index="{{ $idx }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-slate-500 item-label">Item #{{ $idx + 1 }}</span>
                        <button type="button" onclick="removeItemRow(this)" class="text-xs font-semibold text-rose-600 hover:underline">Hapus</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Produk Cetak</label>
                            <input type="text" name="items[{{ $idx }}][product_name]" value="{{ $item['product_name'] ?? '' }}" required placeholder="Mis. Spanduk Acara / Brosur A4" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Bahan Baku</label>
                            <select name="items[{{ $idx }}][material_id]" required onchange="recalculateTotals()" class="material-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                <option value="">-- Pilih Bahan --</option>
                                @foreach($materials as $mat)
                                    <option value="{{ $mat->id }}" data-stock="{{ $mat->current_stock }}" data-unit="{{ $mat->unit }}" @selected(($item['material_id'] ?? '') == $mat->id)>
                                        {{ $mat->name }} (Stok: {{ $mat->formattedStock() }} {{ $mat->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Qty Kebutuhan Bahan</label>
                            <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][qty]" value="{{ $item['qty'] ?? 1 }}" required oninput="recalculateTotals()" class="qty-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Ukuran Cetak</label>
                            <input type="text" name="items[{{ $idx }}][size]" value="{{ $item['size'] ?? '' }}" placeholder="Mis. 3 x 1 m atau A3+" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Catatan Finishing</label>
                            <input type="text" name="items[{{ $idx }}][finishing_notes]" value="{{ $item['finishing_notes'] ?? '' }}" placeholder="Mis. Mata ayam, laminasi doff" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Subtotal Harga (Rp)</label>
                            <input type="number" step="100" min="0" name="items[{{ $idx }}][sub_total]" value="{{ $item['sub_total'] ?? 0 }}" required oninput="recalculateTotals()" class="subtotal-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">
                        </div>
                    </div>
                    <div class="stock-warning hidden rounded-lg bg-rose-100 border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-800"></div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-end border-t border-slate-200 pt-4">
            <div class="text-right">
                <div class="text-xs text-slate-500">Total Tagihan Pesanan</div>
                <div id="grand-total-display" class="text-2xl font-bold text-indigo-700">Rp 0</div>
            </div>
        </div>
    </div>

    {{-- Bagian 3: File / Tautan Desain & Catatan --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h2 class="text-base font-bold text-slate-900">3. Berkas Desain & Catatan Tambahan</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tautan Desain (Google Drive / Link WA)</label>
                <input type="text" name="file_design_link" value="{{ old('file_design_link') }}" placeholder="https://drive.google.com/..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Atau Unggah File Desain Langsung (Maks 20 MB)</label>
                <input type="file" name="design_file" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Umum Pesanan</label>
            <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm" placeholder="Instruksi khusus untuk kasir atau operator produksi...">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('orders.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm">
            Simpan Pesanan & Validasi Stok
        </button>
    </div>
</form>

<template id="item-row-template">
    <div class="item-row rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase text-slate-500 item-label">Item</span>
            <button type="button" onclick="removeItemRow(this)" class="text-xs font-semibold text-rose-600 hover:underline">Hapus</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Produk Cetak</label>
                <input type="text" data-field="product_name" required placeholder="Mis. Kartu Nama / Stiker" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Bahan Baku</label>
                <select data-field="material_id" required onchange="recalculateTotals()" class="material-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">-- Pilih Bahan --</option>
                    @foreach($materials as $mat)
                        <option value="{{ $mat->id }}" data-stock="{{ $mat->current_stock }}" data-unit="{{ $mat->unit }}">
                            {{ $mat->name }} (Stok: {{ $mat->formattedStock() }} {{ $mat->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Qty Kebutuhan Bahan</label>
                <input type="number" step="0.01" min="0.01" data-field="qty" value="1" required oninput="recalculateTotals()" class="qty-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Ukuran Cetak</label>
                <input type="text" data-field="size" placeholder="Ukuran" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Catatan Finishing</label>
                <input type="text" data-field="finishing_notes" placeholder="Finishing" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Subtotal Harga (Rp)</label>
                <input type="number" step="100" min="0" data-field="sub_total" value="0" required oninput="recalculateTotals()" class="subtotal-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">
            </div>
        </div>
        <div class="stock-warning hidden rounded-lg bg-rose-100 border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-800"></div>
    </div>
</template>
@endsection

@push('scripts')
<script>
let itemCounter = {{ count($defaultItems) }};

function toggleCustomerMode(mode) {
    document.getElementById('customer-existing-box').classList.toggle('hidden', mode !== 'existing');
    document.getElementById('customer-new-box').classList.toggle('hidden', mode !== 'new');
}

function addItemRow() {
    const template = document.getElementById('item-row-template');
    const clone = template.content.cloneNode(true);
    const row = clone.querySelector('.item-row');
    const idx = itemCounter++;
    row.querySelectorAll('[data-field]').forEach(el => {
        el.name = `items[${idx}][${el.dataset.field}]`;
    });
    document.getElementById('items-container').appendChild(clone);
    reindexLabels();
    recalculateTotals();
}

function removeItemRow(btn) {
    const rows = document.querySelectorAll('#items-container .item-row');
    if (rows.length <= 1) return;
    btn.closest('.item-row').remove();
    reindexLabels();
    recalculateTotals();
}

function reindexLabels() {
    document.querySelectorAll('#items-container .item-row').forEach((row, i) => {
        row.querySelector('.item-label').textContent = `Item #${i + 1}`;
    });
}

function recalculateTotals() {
    let total = 0;
    const requestedByMaterial = {};
    const rows = document.querySelectorAll('#items-container .item-row');

    rows.forEach(row => {
        const sub = parseFloat(row.querySelector('.subtotal-input')?.value || 0);
        total += isNaN(sub) ? 0 : sub;

        const select = row.querySelector('.material-select');
        const qty = parseFloat(row.querySelector('.qty-input')?.value || 0);
        if (select && select.value && qty > 0) {
            requestedByMaterial[select.value] = (requestedByMaterial[select.value] || 0) + qty;
        }
    });

    rows.forEach(row => {
        const select = row.querySelector('.material-select');
        const warnBox = row.querySelector('.stock-warning');
        if (!select || !select.value) {
            warnBox.classList.add('hidden');
            return;
        }
        const opt = select.options[select.selectedIndex];
        const stock = parseFloat(opt.dataset.stock || 0);
        const unit = opt.dataset.unit || '';
        const needed = requestedByMaterial[select.value] || 0;

        if (needed > stock) {
            warnBox.textContent = `Peringatan: Total kebutuhan (${needed} ${unit}) melebihi stok tersedia (${stock} ${unit})!`;
            warnBox.classList.remove('hidden');
        } else {
            warnBox.classList.add('hidden');
        }
    });

    document.getElementById('grand-total-display').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', recalculateTotals);
</script>
@endpush

