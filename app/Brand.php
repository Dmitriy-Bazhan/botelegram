<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ModelTraits\WithData;

class Brand extends Model
{
    use WithData;

    protected $guarded = [];

    public function getNameAttribute()
    {
        return $this->getDataAttr('name');
    }

    public static function importedBrands()
    {
        $importedBrandRefs = ImportedBrand::all()->keyBy('ref')->keys();
        return self::with('data')->whereIn('ref', $importedBrandRefs)->get();
    }

    public static function getWithData($allData = false)
    {
        $data = $allData ? 'all_data' : 'data';
        $products = self::with($data);
        return $products->get();
    }
}
