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
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->string('tipo_origem', 20)->default('NOVO')->after('company_id');
            $table->unsignedBigInteger('emprestimo_origem_id')->nullable()->after('tipo_origem');
            $table->foreign('emprestimo_origem_id')->references('id')->on('emprestimos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dropForeign(['emprestimo_origem_id']);
            $table->dropColumn(['tipo_origem', 'emprestimo_origem_id']);
        });
    }
};
