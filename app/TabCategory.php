<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TabCategory extends Model
{
    protected $guarded = [];

    public static function storeOrUpdate($tabId, $categoryIds)
    {
        $tabCategoryIds = collect();
        foreach ($categoryIds as $categoryId) {
            $tabCategoryId = self::firstOrCreate([
                'tab_id' => $tabId,
                'category_id' => $categoryId
            ]);
            $tabCategoryIds->push($tabCategoryId->id);
        }
        self::where('tab_id', $tabId)->whereNotIn('id', $tabCategoryIds)->delete();
    }
}
