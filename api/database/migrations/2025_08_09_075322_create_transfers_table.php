<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();

            $table->string('client_identifier')->unique();
            $table->string('webhook_token')->nullable();

            $table->decimal('amount', 12, 2);
            $table->boolean('discount_fee_of_receiver')->default(false);

            $table->string('pix_type');
            $table->string('pix_key');

            $table->string('owner_ip', 64);
            $table->string('owner_name');
            $table->string('owner_document_type', 8);
            $table->string('owner_document_number', 32);

            $table->string('callback_url');

            $table->string('withdraw_id')->nullable()->unique();
            $table->decimal('withdraw_amount', 12, 2)->nullable();
            $table->decimal('withdraw_fee_amount', 12, 2)->nullable();
            $table->string('withdraw_currency', 8)->nullable();
            $table->string('withdraw_status', 32)->nullable();
            $table->timestamp('withdraw_created_at')->nullable();
            $table->timestamp('withdraw_updated_at')->nullable();

            $table->string('payout_account_id')->nullable()->index();
            $table->string('payout_account_status', 32)->nullable();
            $table->string('payout_pix')->nullable();
            $table->string('payout_pix_type', 16)->nullable();
            $table->timestamp('payout_created_at')->nullable();
            $table->timestamp('payout_updated_at')->nullable();
            $table->timestamp('payout_deleted_at')->nullable();

            $table->string('status', 32)->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfers');
    }
};
