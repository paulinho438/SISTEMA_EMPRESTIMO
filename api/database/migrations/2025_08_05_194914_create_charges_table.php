<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('gateway_transaction_id')->unique();
            $table->string('external_transaction_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->nullable();
            $table->text('pix_code')->nullable();
            $table->longText('pix_base64')->nullable();
            $table->string('webhook_token')->nullable();
            $table->decimal('valor_bruto', 12, 2); // ex: 120.00
            $table->decimal('valor_liquido', 12, 2); // ex: 100.00
            $table->decimal('taxa_gateway', 12, 2)->default(0.10);
            $table->decimal('taxa_cliente', 12, 2)->default(0.00);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
}
