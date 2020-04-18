<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use App\SmartFilter;
use Illuminate\Http\Request;

class myView extends Controller
{
    private $products;

    public function main()
    {
//        $alias = 'vreznoj-smesitel-dlya-rakoviny';
        $alias = 'cvetnye-smesiteli-dlya-umyvalnika';

        $category_id = 9;
        $child_id = 10;

        $categories = Category::withData()->withAlias()->withSmartFilters()->wherePublished(true)
            ->whereIsMenuItem(true)->orderBy('sort')->get()->toTree();

//        dd($categories->where('id', 9)[$categories->where('id', 9)->keys()[0]]);

//        foreach ($categories as $category) {
//            dump($category->data->name);
//            dump($category->id);
//
//
//            foreach ($category->children as $childs) {
//                dump($childs->data->name);
//                dump($childs->id);
//                foreach ($childs->children as $child)
//                {
//                    dump($child->data->name);
//                    dump($child->alias->url);
//                }
//                echo '<hr>';
//            }
//            echo '<hr>';
//die();
//        }

        $category = Category::withDescendants()->withData()->withSmartFilterData()->withCharacteristicGroups()
            ->withAlias()->whereHasAlias($alias)->wherePublished(true)->firstOrFail();

//        dd($category);
//
        $descendants = $category->descendants;
        $categoryIds = isset($category->parent_id) ? $descendants->keyBy('id')->keys()->push($category->id) : null;
        $data['descendants'] = $descendants->toTree();

        $categoryId = $category->id;
        $smartFilterCategory = SmartFilter::withCategory()->whereFilterCategoryId($categoryId)->first();
        $data['smart_filter_category'] = $smartFilterCategory;

        $parameters = null;
        $parameterData = parse_string_of_parameters($parameters);

        if (isset($smartFilterCategory)) {
//            $characteristicValueId = isset($parameterData['filters']) ? implode(',', $parameterData['filters']) : null;
//            if (isset($characteristicValueId) && (string)$smartFilterCategory->characteristic_value_id != $characteristicValueId) {
//                return redirect(url_with_locale($smartFilterCategory->category->alias->url . '/' . $parameters));
//            }
            $parameterData['filters'] = [$smartFilterCategory->characteristic_value_id];
            $categoryIds = collect()->push($smartFilterCategory->category_id);
//            $categoryId = $smartFilterCategory->category_id;
        }

//        $data['links_to_sort'] = get_links_to_sort($parameterData, $pageNumber);

//        if ($category->smart_filter_data->isNotEmpty() && isset($parameterData['filters']) && count($parameterData['filters']) === 1) {
//            $smartFilterData = $category->smart_filter_data->where('characteristic_value_id', $parameterData['filters'][0])->first();
//            if (isset($smartFilterData)) {
//                $semicolon = isset($parameterData['price']) || isset($parameterData['sort']) ? ';' : '';
//                $parameters = str_replace('filters=' . $parameterData['filters'][0] . $semicolon, '', $parameters);
//                return redirect(url_with_locale($smartFilterData->filter_category->alias->url . '/' . $parameters));
//            }
//        }

        $products = Product::withAlias()->joinData()->whereExistsCategoryId($categoryIds)
            ->whereParameters($parameterData)->wherePublished(true)->get();

        dump( $categoryIds, $category, $products->toArray(), $products->keyBy('category_id')->keys());

        return view('my-view');
    }
}

//$categoryIds, $category,
