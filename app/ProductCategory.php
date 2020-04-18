<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $guarded = [];

    public static function storeOrUpdate($productId, $categoryIds)
    {
        $productCategoryIds = collect();
        foreach ($categoryIds as $categoryId) {
            $productCategoryId = self::firstOrCreate([
                'product_id' => $productId,
                'category_id' => $categoryId
            ]);
            $productCategoryIds->push($productCategoryId->id);
        }
        self::where('product_id', $productId)->whereNotIn('id', $productCategoryIds)->delete();
    }
}
