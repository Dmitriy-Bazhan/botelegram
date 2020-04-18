<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmartFilter extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function scopeWithCategory($query, $withData = true, $withAlias = true)
    {
        $query->with(['category' => function ($query) use ($withData, $withAlias) {

            if ($withData) $query->withData();
            if ($withAlias) $query->withAlias();
        }]);
    }

    public function scopeWithFilterCategory($query, $withData = true, $withAlias = true)
    {
        $query->with(['filter_category' => function ($query) use ($withData, $withAlias) {

            if ($withData) $query->withData();
            if ($withAlias) $query->withAlias();
        }]);
    }

    public function category()
    {
        return $this->hasOne('App\Category', 'id', 'category_id');
    }

    public function filter_category()
    {
        return $this->hasOne('App\Category', 'id', 'filter_category_id');
    }
}
