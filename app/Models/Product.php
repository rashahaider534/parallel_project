<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name','quantity','price','discription'];
    public function cart()
    {
        return $this->belongsTo(Cart::class,'product_id');
    }


    public function orderdetail()
    {
        return $this->belongsTo(OrderDetail::class,'product_id');
    }

}
