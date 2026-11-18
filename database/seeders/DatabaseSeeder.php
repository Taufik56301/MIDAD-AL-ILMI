<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@midad.test'],
            [
                'name' => 'H. Ahmad (Owner)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OWNER,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@midad.test'],
            [
                'name' => 'Siti Aminah (Admin)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@midad.test'],
            [
                'name' => 'Budi Santoso (Operator)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
            ]
        );

        $materials = [
            ['name' => 'Flexi China 280gr (Roll)', 'type' => 'Banner / Spanduk', 'current_stock' => 120, 'unit' => 'meter', 'minimal_stock_alert' => 25],
            ['name' => 'Art Carton 260gr A3+', 'type' => 'Kertas Cetak', 'current_stock' => 450, 'unit' => 'lembar', 'minimal_stock_alert' => 100],
            ['name' => 'HVS 80gr A4', 'type' => 'Kertas Dokumen', 'current_stock' => 1500, 'unit' => 'lembar', 'minimal_stock_alert' => 300],
            ['name' => 'Stiker Vinyl Glossy A3+', 'type' => 'Stiker', 'current_stock' => 18, 'unit' => 'lembar', 'minimal_stock_alert' => 30],
            ['name' => 'Art Paper 150gr A3+', 'type' => 'Kertas Brosur', 'current_stock' => 300, 'unit' => 'lembar', 'minimal_stock_alert' => 80],
            ['name' => 'Tinta Cyan / Magenta / Yellow / Black', 'type' => 'Tinta Printer', 'current_stock' => 8, 'unit' => 'botol', 'minimal_stock_alert' => 10],
        ];

        $materialModels = collect($materials)->map(fn (array $m) => Material::firstOrCreate(['name' => $m['name']], $m));

        $cust1 = Customer::firstOrCreate(
            ['phone_whatsapp' => '081234567890'],
            ['name' => 'Pondok Pesantren Al-Hidayah', 'address' => 'Jl. Pendidikan No. 12']
        );
        $cust2 = Customer::firstOrCreate(
            ['phone_whatsapp' => '085678901234'],
            ['name' => 'Klinik Sehat Mandiri', 'address' => 'Jl. Merdeka Timur No. 45']
        );
        $cust3 = Customer::firstOrCreate(
            ['phone_whatsapp' => '082198765432'],
            ['name' => 'Panitia Seminar Kampus', 'address' => 'Gedung Rektorat Lt. 2']
        );

        if (Order::count() === 0) {
            // Order 1: Lunas & Siap Diambil (sudah dipotong stoknya)
            $order1 = Order::create([
                'order_number' => 'ORD-'.now()->subDays(2)->format('Ymd').'-0001',
                'customer_id' => $cust1->id,
                'user_id' => $admin->id,
                'total_amount' => 250000,
                'payment_status' => Order::PAYMENT_PAID,
                'order_status' => Order::STATUS_SIAP_DIAMBIL,
                'file_design_link' => 'https://drive.google.com/example-spanduk-pesantren',
                'notes' => 'Pasang mata ayam tiap sudut',
                'stock_deducted_at' => now()->subDays(1),
                'created_at' => now()->subDays(2),
            ]);
            OrderDetail::create([
                'order_id' => $order1->id,
                'product_name' => 'Spanduk Penerimaan Santri Baru',
                'material_id' => $materialModels[0]->id,
                'qty' => 10,
                'size' => '3 x 1 meter',
                'finishing_notes' => 'Lipat pinggir + 6 mata ayam',
                'sub_total' => 250000,
            ]);
            Payment::create([
                'order_id' => $order1->id,
                'user_id' => $admin->id,
                'amount_paid' => 250000,
                'payment_type' => Payment::TYPE_LUNAS,
                'payment_method' => Payment::METHOD_TRANSFER,
                'date_paid' => now()->subDays(2),
                'notes' => 'Lunas via transfer BCA',
            ]);

            // Order 2: DP & Masuk Antrean Produksi (belum mulai diproduksi -> stok belum terpotong)
            $order2 = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-0001',
                'customer_id' => $cust2->id,
                'user_id' => $admin->id,
                'total_amount' => 360000,
                'payment_status' => Order::PAYMENT_DP,
                'order_status' => Order::STATUS_ANTREAN,
                'file_design_link' => 'https://drive.google.com/example-brosur-klinik',
                'notes' => 'Cetak 2 sisi warna cerah',
                'created_at' => now()->subHours(2),
            ]);
            OrderDetail::create([
                'order_id' => $order2->id,
                'product_name' => 'Brosur Layanan Klinik A4',
                'material_id' => $materialModels[4]->id,
                'qty' => 100,
                'size' => 'A3+ (Potong A4)',
                'finishing_notes' => 'Potong rapi + lipat 3',
                'sub_total' => 360000,
            ]);
            Payment::create([
                'order_id' => $order2->id,
                'user_id' => $admin->id,
                'amount_paid' => 150000,
                'payment_type' => Payment::TYPE_DP,
                'payment_method' => Payment::METHOD_TUNAI,
                'date_paid' => now()->subHours(2),
                'notes' => 'DP Tunai di kasir',
            ]);

            // Order 3: Belum bayar (Belum masuk antrean produksi)
            $order3 = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-0002',
                'customer_id' => $cust3->id,
                'user_id' => $admin->id,
                'total_amount' => 180000,
                'payment_status' => Order::PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ANTREAN,
                'file_design_link' => 'https://drive.google.com/example-sertifikat-seminar',
                'notes' => 'Menunggu konfirmasi DP via WA',
                'created_at' => now()->subHour(),
            ]);
            OrderDetail::create([
                'order_id' => $order3->id,
                'product_name' => 'Sertifikat Peserta Seminar',
                'material_id' => $materialModels[1]->id,
                'qty' => 50,
                'size' => 'A4',
                'finishing_notes' => 'Laminasi Doff',
                'sub_total' => 180000,
            ]);
        }

        // Sample Attendance kemarin untuk laporan kehadiran
        Attendance::firstOrCreate(
            ['user_id' => $admin->id, 'date' => now()->subDay()->toDateString()],
            [
                'clock_in' => now()->subDay()->setTime(8, 0),
                'clock_out' => now()->subDay()->setTime(16, 30),
                'total_hours' => 8.5,
            ]
        );
        Attendance::firstOrCreate(
            ['user_id' => $operator->id, 'date' => now()->subDay()->toDateString()],
            [
                'clock_in' => now()->subDay()->setTime(8, 15),
                'clock_out' => now()->subDay()->setTime(17, 0),
                'total_hours' => 8.75,
            ]
        );
        Attendance::firstOrCreate(
            ['user_id' => $owner->id, 'date' => now()->subDay()->toDateString()],
            [
                'clock_in' => now()->subDay()->setTime(9, 0),
                'clock_out' => now()->subDay()->setTime(16, 0),
                'total_hours' => 7.0,
            ]
        );
    }
}
