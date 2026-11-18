<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_entry_blocks_when_quantity_exceeds_current_stock(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::create(['name' => 'Pelanggan Uji', 'phone_whatsapp' => '08123456789']);
        $material = Material::create([
            'name' => 'Stiker Vinyl A3+',
            'type' => 'Stiker',
            'current_stock' => 15,
            'unit' => 'lembar',
            'minimal_stock_alert' => 10,
        ]);

        // Single line exceeds stock
        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_name' => 'Stiker Produk',
                    'material_id' => $material->id,
                    'qty' => 25,
                    'size' => 'A3+',
                    'finishing_notes' => 'Kiss cut',
                    'sub_total' => 250000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('stock');
        $response->assertSessionHas('insufficient_stock');
        $this->assertDatabaseCount('orders', 0);

        // Two lines using same material whose combined qty exceeds stock (10 + 10 = 20 > 15)
        $responseCombined = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_name' => 'Stiker A',
                    'material_id' => $material->id,
                    'qty' => 10,
                    'sub_total' => 100000,
                ],
                [
                    'product_name' => 'Stiker B',
                    'material_id' => $material->id,
                    'qty' => 10,
                    'sub_total' => 100000,
                ],
            ],
        ]);

        $responseCombined->assertSessionHas('insufficient_stock');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_observer_automatically_deducts_stock_once_when_entering_production(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $customer = Customer::create(['name' => 'Sekolah Harapan', 'phone_whatsapp' => '0811111111']);
        $material = Material::create([
            'name' => 'Flexi 280gr',
            'type' => 'Banner',
            'current_stock' => 100,
            'unit' => 'meter',
            'minimal_stock_alert' => 20,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-0001',
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_amount' => 300000,
            'payment_status' => Order::PAYMENT_DP,
            'order_status' => Order::STATUS_ANTREAN,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_name' => 'Spanduk Utama',
            'material_id' => $material->id,
            'qty' => 25,
            'size' => '5 x 1 m',
            'sub_total' => 300000,
        ]);

        $response = $this->actingAs($operator)->patch(route('orders.status', $order), [
            'order_status' => Order::STATUS_DIPRODUKSI,
        ]);

        $response->assertSessionHas('success');
        $material->refresh();
        $order->refresh();

        $this->assertEquals(75.0, (float) $material->current_stock);
        $this->assertNotNull($order->stock_deducted_at);

        // Transitioning to siap_diambil must NOT deduct stock again
        $this->actingAs($operator)->patch(route('orders.status', $order), [
            'order_status' => Order::STATUS_SIAP_DIAMBIL,
        ])->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(75.0, (float) $material->current_stock);
    }

    public function test_order_cannot_be_closed_before_full_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::create(['name' => 'Toko Berkah', 'phone_whatsapp' => '0822222222']);
        $material = Material::create([
            'name' => 'Art Carton 260gr',
            'type' => 'Kertas',
            'current_stock' => 200,
            'unit' => 'lembar',
            'minimal_stock_alert' => 50,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-0002',
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_amount' => 200000,
            'payment_status' => Order::PAYMENT_UNPAID,
            'order_status' => Order::STATUS_ANTREAN,
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'product_name' => 'Kartu Nama',
            'material_id' => $material->id,
            'qty' => 20,
            'sub_total' => 200000,
        ]);

        // Payment 1: DP 100.000
        $this->actingAs($admin)->post(route('payments.store', $order), [
            'amount_paid' => 100000,
            'payment_method' => 'tunai',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::PAYMENT_DP, $order->payment_status);

        // Payment 2: still short (50.000 -> total 150.000 < 200.000)
        $this->actingAs($admin)->post(route('payments.store', $order), [
            'amount_paid' => 50000,
            'payment_method' => 'transfer',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::PAYMENT_DP, $order->payment_status);

        // Attempt to close order must fail
        $this->actingAs($admin)->patch(route('orders.status', $order), [
            'order_status' => Order::STATUS_CLOSED,
        ])->assertSessionHas('error');

        $order->refresh();
        $this->assertNotSame(Order::STATUS_CLOSED, $order->order_status);

        // Final payment: 50.000 -> paid 100%
        $this->actingAs($admin)->post(route('payments.store', $order), [
            'amount_paid' => 50000,
            'payment_method' => 'tunai',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);

        // Now closing succeeds
        $this->actingAs($admin)->patch(route('orders.status', $order), [
            'order_status' => Order::STATUS_CLOSED,
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::STATUS_CLOSED, $order->order_status);
    }

    public function test_role_based_access_blocks_operator_and_admin_from_owner_reports(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($operator)->get(route('reports.index'))
            ->assertStatus(403)
            ->assertSee('Akses Dilarang / Unauthorized');

        $this->actingAs($operator)->get(route('reports.excel', 'sales'))->assertStatus(403);
        $this->actingAs($operator)->get(route('reports.pdf', 'sales'))->assertStatus(403);
        $this->actingAs($operator)->get(route('users.index'))->assertStatus(403);

        $this->actingAs($admin)->get(route('reports.index'))->assertStatus(403);

        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
    }

    public function test_attendance_clock_in_and_clock_out_calculates_total_hours(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)->post(route('attendances.clock-in'))
            ->assertSessionHas('success');

        // Duplicate clock-in same day blocked
        $this->actingAs($operator)->post(route('attendances.clock-in'))
            ->assertSessionHas('error');

        $this->travel(8)->hours();

        $this->actingAs($operator)->post(route('attendances.clock-out'))
            ->assertSessionHas('success');

        $attendance = Attendance::where('user_id', $operator->id)->first();
        $this->assertNotNull($attendance->clock_in);
        $this->assertNotNull($attendance->clock_out);
        $this->assertEqualsWithDelta(8.0, (float) $attendance->total_hours, 0.05);
    }

    public function test_invoice_pdf_report_exports_and_api_summary_work(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $customer = Customer::create(['name' => 'Yayasan Midad', 'phone_whatsapp' => '0833333333']);
        $material = Material::create([
            'name' => 'HVS A4',
            'type' => 'Kertas',
            'current_stock' => 500,
            'unit' => 'lembar',
            'minimal_stock_alert' => 50,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-EXP-001',
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'total_amount' => 150000,
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_SIAP_DIAMBIL,
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'product_name' => 'Cetak Modul',
            'material_id' => $material->id,
            'qty' => 100,
            'sub_total' => 150000,
        ]);

        $invoiceRes = $this->actingAs($owner)->get(route('orders.invoice', $order));
        $invoiceRes->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $invoiceRes->headers->get('content-type'));

        $pdfRes = $this->actingAs($owner)->get(route('reports.pdf', 'sales'));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfRes->headers->get('content-type'));

        $excelRes = $this->actingAs($owner)->get(route('reports.excel', 'sales'));
        $excelRes->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $excelRes->headers->get('content-disposition'));

        $apiRes = $this->actingAs($owner)->getJson(route('api.v1.summary'));
        $apiRes->assertOk()->assertJsonStructure([
            'app',
            'timestamp',
            'kpi' => ['today_revenue', 'month_revenue', 'active_production_queue', 'ready_for_pickup'],
            'low_stock_materials',
        ]);
    }

    public function test_all_pages_and_report_types_render_cleanly(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $customer = Customer::create(['name' => 'Kantor Desa', 'phone_whatsapp' => '0844444444']);
        $material = Material::create([
            'name' => 'Art Paper 150gr',
            'type' => 'Kertas',
            'current_stock' => 10,
            'unit' => 'lembar',
            'minimal_stock_alert' => 20,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-UI-001',
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'total_amount' => 100000,
            'payment_status' => Order::PAYMENT_DP,
            'order_status' => Order::STATUS_ANTREAN,
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'product_name' => 'Brosur Desa',
            'material_id' => $material->id,
            'qty' => 5,
            'sub_total' => 100000,
        ]);

        $routes = [
            route('dashboard'),
            route('attendances.index'),
            route('production.index'),
            route('customers.index'),
            route('customers.create'),
            route('customers.show', $customer),
            route('orders.index'),
            route('orders.create'),
            route('orders.show', $order),
            route('payments.index'),
            route('materials.index'),
            route('materials.create'),
            route('users.index'),
            route('users.create'),
            route('reports.index', ['tab' => 'sales']),
            route('reports.index', ['tab' => 'cashflow']),
            route('reports.index', ['tab' => 'inventory']),
            route('reports.index', ['tab' => 'attendance']),
        ];

        foreach ($routes as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }

        foreach (['sales', 'cashflow', 'inventory', 'attendance'] as $type) {
            $this->actingAs($owner)->get(route('reports.excel', $type))->assertOk();
            $this->actingAs($owner)->get(route('reports.pdf', $type))->assertOk();
        }
    }
}

