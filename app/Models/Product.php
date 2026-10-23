<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'sku',
        'name',
        'description',
        'price',
        'stock',
        'category',
        'unit'
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
