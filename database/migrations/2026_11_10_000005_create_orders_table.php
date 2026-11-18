<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid'); // unpaid, dp, paid
            $table->string('order_status', 30)->default('antrean'); // antrean, diproduksi, siap_diambil, closed
            $table->text('file_design_link')->nullable();
            $table->string('design_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('stock_deducted_at')->nullable();
            $table->timestamps();

            $table->index(['payment_status', 'order_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

