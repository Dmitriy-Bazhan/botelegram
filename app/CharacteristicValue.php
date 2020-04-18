<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CharacteristicValue extends Model
{
    protected $guarded = [];

    public function scopeWhereCharacteristicIsFilter($query, $characteristicIds = null)
    {
        if (isset($characteristicIds)) {

            $query->whereIn('characteristic_id', $characteristicIds);
        } else {

            $query->whereExists(function ($query) {

                $query->select('id')->from('characteristics')
                    ->whereRaw('characteristics.id = characteristic_values.characteristic_id')
                    ->where('characteristics.is_filter', true);
            });
        }
    }
}
