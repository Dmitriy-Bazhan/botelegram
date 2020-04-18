<?php

namespace App;

use App\ModelTraits\WithData;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class BlogCategory extends Model
{
    protected $guarded = [];

    use WithData, NodeTrait;

    public function hasAttribute($attr)
    {
        return array_key_exists($attr, $this->attributes);
    }

    public static function storeOrUpdate($data, $id = null)
    {
        self::fixTree();

        if (isset($id)) {
            $blogCategory = self::find($id);
            BlogCategoryData::whereBlogCategoryId($id)->delete();
        } else {
            $blogCategory = new self();
        }

        $str = !empty($data['alias']) ? $data['alias'] : str_replace('/', '-', $data['data'][MAIN_LOCALE]['name']);
        $blogCategory->alias_id = Alias::storeOrUpdate($blogCategory->alias_id, $str, 'blog_category');
        $blogCategory->published = $data['published'];
        $blogCategory->parent_id = $data['parent_id'];
        $blogCategory->sort = $data['sort'];
        $blogCategory->save();

        foreach (AVAILABLE_LOCALES as $locale) {

            if (array_filter($data['data'][$locale])) {
                $blogCategoryData = [
                    'blog_category_id' => $blogCategory->id,
                    'locale' => $locale,
                    'meta_title' => $data['data'][$locale]['meta_title'],
                    'meta_description' => $data['data'][$locale]['meta_description'],
                    'meta_keywords' => $data['data'][$locale]['meta_keywords'],
                    'h1' => $data['data'][$locale]['h1'],
                    'name' => $data['data'][$locale]['name'],
                    'description' => $data['data'][$locale]['description'],
                    'text' => $data['data'][$locale]['text'],
                ];
                BlogCategoryData::create($blogCategoryData);
            }

        }

        return true;
    }

    public function scopeWithAlias($query)
    {
        return $query->with('alias');
    }

    public function alias()
    {
        return $this->hasOne('App\Alias', 'id', 'alias_id');
    }

    public function scopeWithDescendants($query, $withData = true, $all = false, $withAlias = true)
    {
        return $query->with(['descendants' => function ($query) use ($withData, $all, $withAlias) {
            $query->wherePublished(true);
            if ($withAlias) $query->withAlias();
            if ($withData) return $all ? $query->withData() : $query->joinData();
        }]);
    }
}
