<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Comment extends Model
{
    use NodeTrait, SoftDeletes;

    protected $guarded = [];

    public static function storeOrUpdate($data, $id = null)
    {

        return self::findOrNew($id)->fill($data)->save();
    }

    public function approve()
    {
        $this->approved = true;
        return $this->save();
    }

    public function product()
    {
        return $this->hasOne('App\Product', 'id', 'resource_id');
    }
}
