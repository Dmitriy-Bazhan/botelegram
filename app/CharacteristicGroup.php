<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CharacteristicGroup extends Model
{
    protected $guarded = [];

    public static function storeOrUpdate($data, $id)
    {
        $updateOrCreateData = ['name' => $data['name']];

        if (isset($id)) {

            self::whereId($id)->update($updateOrCreateData);
        } else {

            $id = self::create($updateOrCreateData)->id;
        }

        CharacteristicGroupCharacteristic::storeOrUpdate($data, $id);
        Category::updateCharacteristicGroup($data, $id);
        return true;
    }

    public function scopeWithCategories($query)
    {
        return $query->with(['categories' => function ($query) {
            return $query->withData();
        }]);
    }

    public function scopeWithCharacteristicGroupCharacteristic($query)
    {
        return $query->with('characteristic_group_characteristics');
    }

    public function categories()
    {
        return $this->hasMany('App\Category', 'characteristic_group_id', 'id');
    }

    public function characteristic_group_characteristics()
    {
        return $this->hasMany('App\CharacteristicGroupCharacteristic', 'group_id', 'id');
    }
}
