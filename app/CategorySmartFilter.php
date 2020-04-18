<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategorySmartFilter extends Model
{
    protected $guarded = [];

    public static function storeOrUpdate($categoryId, $smartFilterIds)
    {
        $categorySmartFilterIds = collect();
        foreach ($smartFilterIds as $smartFilterId) {
            $categorySmartFilter = self::firstOrCreate([
                'category_id' => $categoryId,
                'smart_filter_id' => $smartFilterId
            ]);
            $categorySmartFilterIds->push($categorySmartFilter->id);
        }
        self::where('category_id', $categoryId)->whereNotIn('id', $categorySmartFilterIds)->delete();
    }
}
