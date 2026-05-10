<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'status',
        'category',
        'usage',
        'image',
        'available_date',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
