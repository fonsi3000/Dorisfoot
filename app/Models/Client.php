<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre_completo',
        'identificacion',
        'telefono',
        'area'
    ];

    // Añadimos la relación con Sale
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Validaciones básicas que podemos usar en el recurso de Filament
    public static function rules()
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'identificacion' => ['required', 'string', 'unique:clients,identificacion'],
            'telefono' => ['required', 'string', 'max:20'],
            'area' => ['required', 'string', 'max:100'],
        ];
    }
}
