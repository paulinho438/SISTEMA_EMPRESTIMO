<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Configurações para WhatsApp Cloud (Facebook Graph)
            $table->string('whatsapp_cloud_phone_number_id')->nullable()->after('whatsapp_cobranca');
            $table->text('whatsapp_cloud_token')->nullable()->after('whatsapp_cloud_phone_number_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_cloud_phone_number_id', 'whatsapp_cloud_token']);
        });
    }
};


