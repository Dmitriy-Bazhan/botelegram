<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryData extends Model
{
    protected $guarded = [];
    protected $connection = 'mysql';
    protected $table = 'category_data';
}
