<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ModelTraits\WithData;
use Ixudra\Curl\Facades\Curl;
use Spatie\SchemaOrg\Schema;
use OpenGraph;
use Storage;

class Product extends Model
{
    use WithData;

    protected $guarded = [];
    protected $perPage = 50;
    protected $dates = ['price_updated_at'];

    public function getMainImageAttribute()
    {
        if (!isset($this->attributes['main_image'])) {
            $this->attributes['main_image'] = url('image/' . $this->sku);
        }
        return $this->attributes['main_image'];
    }

    public static function getTabProducts($tabs)
    {
        $tabFirst = $tabs->splice(0, 1)->first();
        $tabSecond = $tabs->splice(0, 1)->first();

        $products = self::withData()->withCategory()->withCategories()
            ->whereIn('category_id', $tabFirst->category_id_keys)->withAlias()
            ->wherePublished(true)->orderByDesc('sku')->take(20)->union(
                self::withData()->withCategory()->withCategories()
                    ->whereIn('category_id', $tabSecond->category_id_keys)->withAlias()
                    ->wherePublished(true)->orderByDesc('sku')->take(20)
            );

        foreach ($tabs as $tab) {
            $products->union(
                self::withData()->withCategory()->withCategories()
                    ->whereIn('category_id', $tab->category_id_keys)->withAlias()
                    ->wherePublished(true)->inRandomOrder()->take(8)
            );
        }

        $tabs->prepend($tabFirst);
        $tabs->prepend($tabSecond);

        return $products->get();
    }

    public static function storeOrUpdate($data, $id)
    {
        if (isset($id)) {
            $product = self::find($id);
            ProductData::where('product_id', $id)->delete();
            ProductCharacteristicValue::where('product_id', $id)->delete();
        } else {
            $product = new self();
        }

        $category = Category::with('alias')->find($data['category_id']);
        $str = !empty($data['alias']) ? $data['alias'] : ($category->alias->url . '/' . $data['data'][MAIN_LOCALE]['name']);
        $product->alias_id = Alias::storeOrUpdate($product->alias_id, $str, 'product');
        $product->brand_id = $data['brand_id'];
        $product->category_id = $data['category_id'];
        $product->published = $data['published'];
        $product->save();

        if (!in_array($data['category_id'], $data['category_ids'])) {
            array_push($data['category_ids'], $data['category_id']);
        }
        ProductCategory::storeOrUpdate($product->id, $data['category_ids']);

        foreach (AVAILABLE_LOCALES as $locale) {

            if (array_filter($data['data'][$locale])) {
                $productData = [
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'meta_title' => $data['data'][$locale]['meta_title'],
                    'meta_description' => $data['data'][$locale]['meta_description'],
                    'meta_keywords' => $data['data'][$locale]['meta_keywords'],
                    'name' => $data['data'][$locale]['name'],
                    'description' => $data['data'][$locale]['description'],
                    'text' => $data['data'][$locale]['text'],
                ];
                ProductData::create($productData);
            }

            if (array_filter($data['characteristics'][$locale])) {
                foreach ($data['characteristics'][$locale] as $characteristicId => $characteristicValue) {
                    if (isset($characteristicValue)) {

                        $cV = CharacteristicValue::whereCharacteristicId($characteristicId)
                            ->whereValue($characteristicValue)->whereLocale($locale)->first();

                        if (isset($cV)) {

                            $cV->value = $characteristicValue;
                            $cV->save();
                        } else {

                            $cV = new CharacteristicValue();
                            $cV->characteristic_id = $characteristicId;
                            $cV->value = $characteristicValue;
                            $cV->locale = $locale;
                            $cV->save();
                        }


//                        $characteristicValueId = CharacteristicValue::updateOrCreate([
//                            'characteristic_id' => $characteristicId,
//                            'value' => $characteristicValue,
//                            'locale' => $locale,
//                        ])->id;

                        ProductCharacteristicValue::create([
                            'product_id' => $product->id,
                            'characteristic_value_id' => $cV->id
                        ]);

                    }
                }
            }

        }

        Storage::disk('local')->delete('products/' . $product->sku . '.jpg');

        return true;
    }

    public static function updatePrices()
    {
        try {

            $productSku = self::all()->keyBy('sku')->keys()->toArray();

            $response = Curl::to('https://b2b-sandi.com.ua/api/price-center')
                ->withData([
                    'action' => 'get_ir_prices',
                    'sku_list' => $productSku
                ])->post();

            $prices = json_decode($response, true);

            foreach ($prices as $sku => $item) {
                if ($item['price'] != 'Недоступно') {
                    self::whereSku($sku)->update(['price' => $item['price']]);
                }
            }

            return true;

        } catch (\Exception $e) {

            return dd($e->getMessage());

        }
    }

    public function scopeWhereAlias($query, $url)
    {
        $query->whereHas('alias', function ($query) use ($url) {
            $query->where('url', $url);
        });
    }

    public function scopeWithAlias($query)
    {
        return $query->with('alias');
    }

    public function scopeWithBrand($query, $withData = true, $all = false)
    {
        return $query->with(['brand' => function ($query) use ($withData, $all) {
            if ($withData) {
                $query->with(($all ? 'all_' : '') . 'data');
            }
        }]);
    }

    public function scopeWithCategory($query, $withData = true, $all = false, $withCharacteristicGroup = false)
    {
        return $query->with(['category' => function ($query) use ($withData, $all, $withCharacteristicGroup) {
            if ($withData) $query->withData($all);
            if ($withCharacteristicGroup) $query->withCharacteristicGroup(true);
        }]);
    }

    public function scopeWithCategories($query, $withData = true, $all = false)
    {
        return $query->with(['categories' => function ($query) use ($withData, $all) {
            if ($withData) $query->withData($all);
        }]);
    }

//    public static function getFiltersByValue($value)
//    {
//        $productIds = self::select('id')->whereSearchString($value)->wherePublished(true)
//            ->get()->keyBy('id')->keys();
//
//        return self::getFiltersByProductIds($productIds);
//    }

    public function scopeWhereExistsCategoryId($query, $categoryId)
    {
        return $query->whereExists(function ($query) use ($categoryId) {

            $categoryId = is_array($categoryId) || is_object($categoryId) ? $categoryId : [$categoryId];
            $query->select('id')->from('product_categories')
                ->whereRaw('product_categories.product_id = products.id')
                ->whereIn('product_categories.category_id', $categoryId);
        });
    }

    public function scopeWhereNotExistsCategoryId($query, $categoryId)
    {
        return $query->whereExists(function ($query) use ($categoryId) {

            $categoryId = is_array($categoryId) || is_object($categoryId) ? $categoryId : [$categoryId];
            $query->select('id')->from('product_categories')
                ->whereRaw('product_categories.product_id != products.id')
                ->whereIn('product_categories.category_id', $categoryId);
        });
    }

    public function scopeJoinData($query)
    {
        return $query->leftJoin('product_data', function ($query) {

            $query->on('product_data.product_id', 'products.id')
                ->whereLocale('ru');
        });
    }

    public function scopeProductIdsOnly($query, $ids)
    {
        if (isset($ids)) {
            $query->whereIn('id', $ids);
        }
    }

    public function scopeWhereParameters($query, $parameters)
    {
        if (isset($parameters['filters'])) {

            $characteristicValues = CharacteristicValue::whereIn('id', $parameters['filters'])->get();

            $characteristicValueIdsGroupedByCharacteristicId = [];
            foreach ($characteristicValues as $value) {

                $characteristicValueIdsGroupedByCharacteristicId[$value->characteristic_id][] = $value->id;
            }

            $characteristicValueIdsCartesianProduct = cartesian($characteristicValueIdsGroupedByCharacteristicId);

            $query->where(function ($query) use ($characteristicValueIdsCartesianProduct) {

                foreach ($characteristicValueIdsCartesianProduct as $ids) {

                    $query->orWhereIn('products.id', function ($query) use ($ids) {

                        $query->select('product_id')->from('product_characteristic_values')
                            ->whereIn('characteristic_value_id', $ids)->groupBy('product_id')
                            ->havingRaw('COUNT(characteristic_value_id) = ' . count($ids));
                    });
                }
            });
        }

        $sort = isset($parameters['sort']) && in_array($parameters['sort'][0], ['price', '-price', 'name', '-name']) ? $parameters['sort'][0] : 'name';
        $ascOrDesc = 'asc';

        if ($sort[0] == '-') {

            $sort = substr($sort, 1);
            $ascOrDesc = 'desc';
        }

        $query->orderBy($sort, $ascOrDesc)->orderBy('products.id');

        if (isset($parameters['price']) && count($parameters['price']) === 2) {

            $query->whereBetween('price', $parameters['price']);
        }
    }

    public function scopeWhereSearchString($query, $value)
    {
        $values = explode(' ', trim($value));

        $query->where(function ($query) use ($values) {

            foreach ($values as $value) {

                $query->where('search_string', 'LIKE', '%' . $value . '%');
            }
        });
    }

    public static function formingSearchStrings()
    {
        self::withData(true)->orderBy('id')->chunk(200, function ($products) {

            foreach ($products as $product) {

                $space = ' ';
                $searchString = $product->sku . $space;

                foreach ($product->all_data as $data) {
                    $searchString .= $data->name . $space;
                }

                $characteristics = Characteristic::getWithData($product->id, true, true);

                foreach ($characteristics as $characteristic) {

                    if ($characteristic->values->isNotEmpty()) {

                        foreach ($characteristic->values as $value) {

                            $searchString .= $value->value . $space;
                        }
                    }
                }

                $product->search_string = rtrim($searchString);
                $product->save();
            }
        });
    }

    public static function getRecommended($product)
    {
        return self::with(['alias', 'data', 'category'])->where('category_id', $product->category->id)
            ->where('published', true)->where('id', '!=', $product->id)
            ->take(4)->inRandomOrder()->get();
    }

    public function brand()
    {
        return $this->hasOne('App\Brand', 'id', 'brand_id');
    }

    public function category()
    {
        return $this->hasOne('App\Category', 'id', 'category_id');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Category', 'product_categories');
    }

    public function alias()
    {
        return $this->hasOne('App\Alias', 'id', 'alias_id');
    }

    public function getJsonLd($characteristics)
    {
        return Schema::product()
            ->name($this->name)
            ->sku($this->sku)
            ->mpn($this->sku)
            ->url(url($this->alias->url))
            ->category($this->category->name)
            ->image(url('image' . $this->sku))
            ->brand(

                Schema::brand()
                    ->name('Q-tap')
                    ->logo(asset('site/libs/icon/logo.svg'))
            )
            ->manufacturer('Q-tap')
            ->model(isset($characteristics['Модель']) ? $characteristics['Модель']->value->value : '')
            ->color(isset($characteristics['Цвет']) ? $characteristics['Цвет']->value->value : '')
            ->itemCondition('NewCondition')
            ->description($this->description)
            ->offers(

                Schema::offer()
                    ->availability('https://schema.org/InStock')
                    ->url(url($this->alias->url))
                    ->priceValidUntil((date('Y') + 1) . '-05-31')
                    ->price($this->price)
                    ->priceCurrency('UAH')
            )
            ->toScript();
    }

    public function getOpenGraph()
    {
        return OpenGraph::locale('ru_UA')
            ->siteName('Интернет-магазин сантехники, купить смесители для кухонной мойки, умывальника, ванной, душа, в продаже также душевые боксы, керамические изделия и прочие аксессуары для ванной.')
            ->title($this->meta_title)
            ->url()
            ->description($this->meta_description)
//            ->image(url('image' . $this->sku))
            ->image(img(['type' => 'product', 'name' => $this->sku, 'data_value' => 0, 'size' => 458], false))
            ->type('product')
            ->renderTags();
    }
}
