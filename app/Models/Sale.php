<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'concepto',
        'concepto_detalle',
        'precio',
        'firma_qr',
        'firma_validada'
    ];

    protected $casts = [
        'firma_validada' => 'boolean',
        'fecha_venta' => 'datetime',
    ];

    protected $dates = [
        'fecha_venta',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function validarFirma(string $qrCode): bool
    {
        $clienteId = $this->client?->identificacion;

        if (!$clienteId || $qrCode !== $clienteId) {
            return false;
        }

        $this->update([
            'firma_validada' => true,
            'firma_qr' => $qrCode
        ]);

        return true;
    }

    public function showQrValidationModal()
    {
        return redirect()->back()->with('showQrModal', $this->id);
    }

    // Scopes útiles para consultas comunes
    public function scopeValidados($query)
    {
        return $query->where('firma_validada', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('firma_validada', false);
    }

    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeDelMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
