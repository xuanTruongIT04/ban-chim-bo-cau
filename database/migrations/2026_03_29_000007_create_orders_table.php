<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 255);
            $table->string('customer_phone', 20);
            $table->text('delivery_address');
            $table->string('order_status', 20)->default('cho_xac_nhan');
            $table->string('payment_method', 20);
            $table->string('payment_status', 20)->default('chua_thanh_toan');
            $table->string('delivery_method', 20)->nullable();
            $table->decimal('total_amount', 12, 0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('order_status');
            $table->index('customer_phone');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
