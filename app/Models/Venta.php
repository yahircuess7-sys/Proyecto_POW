<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_factura',
        'user_id',
        'subtotal',
        'impuesto',
        'total'
    ];

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
