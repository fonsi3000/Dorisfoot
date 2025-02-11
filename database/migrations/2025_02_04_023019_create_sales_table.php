<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Relación con el cliente
            $table->foreignId('client_id')
                ->constrained('clients')
                ->onDelete('restrict');

            // Detalles de la venta
            $table->enum('concepto', ['Desayuno', 'Almuerzo', 'Otro']);
            $table->string('concepto_detalle')->nullable(); // Para cuando se seleccione 'Otro'
            $table->decimal('precio', 10, 2);

            // Firma (QR escaneado del carnet)
            $table->string('firma_qr')->nullable();
            $table->boolean('firma_validada')->default(false);

            // Registro automático de fecha y hora
            $table->timestamp('fecha_venta')->useCurrent(); // Guarda automáticamente la fecha/hora de la venta
            $table->timestamps(); // created_at y updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
