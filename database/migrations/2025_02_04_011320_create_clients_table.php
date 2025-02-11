<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('identificacion')->unique();
            $table->string('telefono');
            $table->string('area');
            $table->timestamps();
            $table->softDeletes(); // Para manejar eliminación lógica
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
