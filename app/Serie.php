<?php

namespace App;

use App\ModelTraits\WithData;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use WithData;

    protected $table = 'characteristic_values';

    public static function storeOrUpdate($data, $id)
    {
        SerieData::where('serie_id', $id)->delete();

        foreach (AVAILABLE_LOCALES as $locale) {

            if (array_filter($data['data'][$locale])) {

                $serieData = [
                    'locale' => $locale,
                    'serie_id' => $id,
                    'popular' => $data['popular'],
                    'safely' => $data['safely'],
                    'kit' => $data['kit'],
                    'new' => $data['new'],
                    'product_sku' => $data['product_sku'],
                    'name' => $data['data'][$locale]['name'],
                    'description' => $data['data'][$locale]['description'],
                    'text' => $data['data'][$locale]['text'],
                ];

                SerieData::create($serieData);
            }
        }
    }

    public function product()
    {
        return $this->hasOneThrough('App\Product', 'App\ProductCharacteristicValue', 'product_id', 'id', 'id', 'product_id');
//        return $this->belongsToMany('App\Product', 'product_characteristic_values', 'characteristic_value_id', 'product_id');
//        return $this->belongsTo('App\Product', 'product_id', 'characteristic_value_id', 'App\ProductCharacteristicValue');
    }
}
