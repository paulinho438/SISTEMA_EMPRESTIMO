<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('valor', 12, 2);
            $table->enum('tipo', ['credit', 'debit']);
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->enum('origem', ['gateway', 'manual', 'ajuste'])->default('gateway');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}
