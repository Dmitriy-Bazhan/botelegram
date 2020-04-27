<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductData extends Model
{
    protected $guarded = [];
    protected $table = 'product_data';

    public function product()
    {
        return $this->hasOne('App\Product', 'id', 'product_id');
    }
}
