<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use App\ModelTraits\WithData;
use Spatie\SchemaOrg\Schema;
use OpenGraph;

class Category extends Model
{
    use NodeTrait, WithData;

    protected $guarded = [];

    public function hasAttribute($attr)
    {
        return array_key_exists($attr, $this->attributes);
    }

    public static function storeOrUpdate($data, $id = null)
    {
        if (isset($id)) {
            $category = self::find($id);
            CategoryData::whereCategoryId($id)->delete();
        } else {
            $category = new self();
        }

        $str = !empty($data['alias']) ? $data['alias'] : $data['data'][MAIN_LOCALE]['name'];
        $category->alias_id = Alias::storeOrUpdate($category->alias_id, $str, 'category');
        $category->published = $data['published'];
        $category->parent_id = $data['parent_id'];
        $category->sort = $data['sort'];
        $category->is_menu_item = $data['is_menu_item'];
        $category->dark_theme = $data['dark_theme'];
        $category->save();

        foreach (AVAILABLE_LOCALES as $locale) {

            if (array_filter($data['data'][$locale])) {
                $categoryData = [
                    'category_id' => $category->id,
                    'locale' => $locale,
                    'meta_title' => $data['data'][$locale]['meta_title'],
                    'meta_description' => $data['data'][$locale]['meta_description'],
                    'meta_keywords' => $data['data'][$locale]['meta_keywords'],
                    'h1' => $data['data'][$locale]['h1'],
                    'name' => $data['data'][$locale]['name'],
                    'description' => $data['data'][$locale]['description'],
                    'text' => $data['data'][$locale]['text'],
                    'banner_btn_text' => $data['data'][$locale]['banner_btn_text'],
                    'banner_url' => $data['data'][$locale]['banner_url'],
                ];
                CategoryData::create($categoryData);
            }

        }

        return true;
    }

    public static function getByParentId($id)
    {
        return self::with('data')->where('parent_id', $id)->get();
    }

    public static function firstWithDataById($id, $allData = false)
    {
        $data = $allData ? 'all_data' : 'data';
        $category = self::with([$data, 'alias', 'smart_filters'])->where('id', $id);
        return $category->firstOrFail();
    }

    public static function getWithData()
    {
        return self::with(['data', 'alias'])->where('published', true)
            ->where('is_menu_item', true)
            ->orderBy('sort')->get()->keyBy('id');
    }

    public function scopeWithAlias($query)
    {
        return $query->with('alias');
    }

    public function scopeWhereHasAlias($query, $url)
    {
        $query->whereHas('alias', function ($query) use ($url) {
            $query->where('url', $url);
        });
    }

    public function scopeWithCharacteristicGroups($query, $withCharacteristicGroupCharacteristics = true)
    {
        $query->with(['characteristic_group' => function ($query) use ($withCharacteristicGroupCharacteristics) {

            if ($withCharacteristicGroupCharacteristics) $query->with('characteristic_group_characteristics');
        }]);
    }

    public function scopeWithCharacteristicGroup($query, $withCharacteristicGroupCharacteristic = true)
    {
        return $query->with(['characteristic_group' => function ($query) use ($withCharacteristicGroupCharacteristic) {

            if ($withCharacteristicGroupCharacteristic) $query->withCharacteristicGroupCharacteristic();
        }]);
    }

    public function scopeWithSmartFilterData($query, $withFilterCategory = true)
    {
        return $query->with(['smart_filter_data' => function ($query) use ($withFilterCategory) {

            if ($withFilterCategory) $query->withFilterCategory();
        }]);
    }

    public function scopeWithSmartFilters($query, $withData = true, $withAlias = true)
    {
        return $query->with(['smart_filters' => function ($query) use ($withData, $withAlias) {
            if ($withData) $query->withData();
            if ($withAlias) $query->withAlias();
        }]);
    }

    public function scopeWithAncestors($query, $withData = true, $withAlias = true)
    {
        return $query->with(['ancestors' => function ($query) use ($withData, $withAlias) {
            if ($withData) $query->withData();
            if ($withAlias) $query->withAlias();
        }]);
    }

    public function scopeWithDescendants($query, $withData = true, $withAlias = true)
    {
        return $query->with(['descendants' => function ($query) use ($withData, $withAlias) {
            if ($withData) $query->withData();
            if ($withAlias) $query->withAlias();
        }]);
    }

    public static function updateCharacteristicGroup($data, $characteristicGroupId)
    {
        self::whereCharacteristicGroupId($characteristicGroupId)->update(['characteristic_group_id' => null]);
        self::whereIn('id', $data['category_ids'])->update(['characteristic_group_id' => $characteristicGroupId]);
        return true;
    }

    public function characteristic_group()
    {
        return $this->hasOne('App\CharacteristicGroup', 'id', 'characteristic_group_id');
    }

    public function alias()
    {
        return $this->hasOne('App\Alias', 'id', 'alias_id');
    }

    public function products()
    {
        return $this->belongsToMany('App\Product', 'product_categories');
    }

    public function smart_filters()
    {
        return $this->belongsToMany('App\Category', 'smart_filters', 'category_id', 'filter_category_id');
    }

    public function smart_filter_data()
    {
        return $this->hasMany('App\SmartFilter', 'category_id', 'id');
    }

    public function getJsonLd($data)
    {
        if ($data['count']) {

            return Schema::aggregateOffer()
                ->name($this->name)
                ->offerCount($data['count'])
                ->highPrice((int)$data['max_price'])
                ->lowPrice((int)$data['min_price'])
                ->priceCurrency('UAH')
                ->toScript();
        } else {

            return null;
        }
    }

    public function getOpenGraph()
    {
        return OpenGraph::locale('ru_UA')
            ->siteName('Интернет-магазин сантехники, купить смесители для кухонной мойки, умывальника, ванной, душа, в продаже также душевые боксы, керамические изделия и прочие аксессуары для ванной.')
            ->title($this->meta_title)
            ->url()
            ->description($this->meta_description)
//            ->image(asset('site/libs/icon/logo.svg'))
            ->image(img(['type' => 'category', 'original' => 'site/img/img_menu/' . $this->id . '.jpg', 'name' => $this->id, 'size' => 175], false))
            ->type('product.group')
            ->renderTags();
    }
}
