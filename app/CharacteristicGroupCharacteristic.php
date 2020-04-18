<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CharacteristicGroupCharacteristic extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public static function storeOrUpdate($data, $characteristicGroupId)
    {
        self::whereGroupId($characteristicGroupId)->delete();

        foreach ($data['characteristics'] as $characteristicId => $value) {

            if (isset($value['use'])) {

                self::create([

                    'group_id' => $characteristicGroupId,
                    'characteristic_id' => $characteristicId,
                    'is_filter' => isset($value['is_filter']) ? 1 : 0,
                    'sort' => $value['sort']
                ]);
            }
        }
        return true;
    }
}
