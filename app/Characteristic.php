<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ModelTraits\WithData;

class Characteristic extends Model
{
    use WithData;

    protected $guarded = [];

    public function getKeyedValuesAttribute()
    {
        if (!isset($this->attributes['keyed_values'])) {
            $this->attributes['keyed_values'] = $this->getRelationValue('values')->keyBy('locale');
        }
        return $this->attributes['keyed_values'];
    }

    public static function firstWithData($id, $allData = false)
    {
        $data = $allData ? 'all_data' : 'data';
        $characteristic = self::with($data)->where('id', $id);
        return $characteristic->first();
    }

    public static function storeOrUpdate($data, $id)
    {
        $storeOrUpdateData = [
            'is_filter' => $data['is_filter'],
            'published' => $data['published'],
            'sort' => $data['sort'],
        ];

        if (isset($id)) {
            self::where('id', $id)->update($storeOrUpdateData);
            CharacteristicData::where('characteristic_id', $id)->delete();
        } else {
            $characteristic = self::create($storeOrUpdateData);
            $id = $characteristic->id;
        }

        foreach (AVAILABLE_LOCALES as $locale) {

            if(array_filter($data['data'][$locale])) {
                $characteristicData = [
                    'characteristic_id' => $id,
                    'locale' => $locale,
                    'name' => $data['data'][$locale]['name'],
                ];
                CharacteristicData::create($characteristicData);
            }

        }

        return true;
    }

    public function scopeWithValues($query, $productId = null, $characteristicIds = null, $values = true, $byLocale = true)
    {
        $productId = is_object($productId) ? $productId : [$productId];
        $values = $values ? 'values' : 'value';

        $query->with([$values => function ($query) use ($productId, $characteristicIds, $byLocale) {

            if ($byLocale) {

                $query->where('locale', app()->getLocale());
            }

            $query->whereExists(function ($query) use ($productId) {

                $query->select('id')->from('product_characteristic_values')
                    ->whereRaw('product_characteristic_values.characteristic_value_id = characteristic_values.id')
                    ->whereIn('product_characteristic_values.product_id', $productId);
            });

            if (isset($characteristicIds)) {

                $query->whereIn('characteristic_id', $characteristicIds);
            }
        }]);
    }

    public function scopeWhereHasValues($query, $productId = null, $characteristicIds = null, $values = true, $byLocale = true, $isFilter = true)
    {
        $productId = is_object($productId) ? $productId : [$productId];
        $values = $values ? 'values' : 'value';

        $query->whereHas($values, function ($query) use ($productId, $characteristicIds, $byLocale) {

            if ($byLocale) {

                $query->where('locale', app()->getLocale());
            }

            $query->whereExists(function ($query) use ($productId) {

                $query->select('id')->from('product_characteristic_values')
                    ->whereRaw('product_characteristic_values.characteristic_value_id = characteristic_values.id')
                    ->whereIn('product_characteristic_values.product_id', $productId);
            });

            if (isset($characteristicIds)) {

                $query->whereIn('characteristic_id', $characteristicIds);
            }
        });

        if (!isset($characteristicIds) && $isFilter) {

            $query->whereIsFilter(true);
        }
    }

    public function scopeWithCharacteristicGroupCharacteristic($query, $groupId)
    {
        $query->with(['characteristic_group_characteristic' => function ($query) use ($groupId) {

            $query->whereGroupId($groupId);
        }]);
    }

    public static function getWithData($productId = null, $allData = false, $publishedOnly = false)
    {
        $data = $allData ? 'all_data' : 'data';
        $characteristics = self::with($data);

        if ($publishedOnly) {
            $characteristics->wherePublished(true);
        }

        if (isset($productId)) {

            $value = $allData ? 'values' : 'value';
            $byLocale = false;

            if (is_object($productId)) {
                $characteristicValue = ProductCharacteristicValue::whereIn('product_id', $productId)->get();
                $value = 'values';
                $byLocale = true;
                $characteristics->where('is_filter', true);
            } else {
                $characteristicValue = ProductCharacteristicValue::where('product_id', $productId)->get();
            }
            $characteristicValueIds = $characteristicValue->keyBy('characteristic_value_id')->keys();

            $characteristics->with([$value => function ($query) use ($characteristicValueIds, $byLocale) {
                $query->whereIn('id', $characteristicValueIds);
                if ($byLocale) {
                    $query->where('locale', app()->getLocale());
                }
            }]);

            if (!$allData) {
                $characteristics->whereHas($value, function ($query) use ($characteristicValueIds, $byLocale) {
                    $query->whereIn('id', $characteristicValueIds);
                    if ($byLocale) {
                        $query->where('locale', app()->getLocale());
                    }
                });
            }

        }

        return $characteristics->orderBy('sort')->get();
    }

    public function value()
    {
        return $this->hasOne('App\CharacteristicValue', 'characteristic_id', 'id')
            ->where('locale', app()->getLocale());
    }

    public function values()
    {
        return $this->hasMany('App\CharacteristicValue', 'characteristic_id', 'id');
    }

    public function characteristic_group_characteristic()
    {
        return $this->hasOne('App\CharacteristicGroupCharacteristic', 'characteristic_id', 'id');
    }
}
