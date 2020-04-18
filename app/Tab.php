<?php

namespace App;

use App\ModelTraits\WithData;
use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    use WithData;

    protected $guarded = [];

    public function getCategoryIdKeysAttribute()
    {
        if (!isset($this->attributes['category_id_keys'])) {
            $this->attributes['category_id_keys'] = $this->category_ids->keyBy('category_id')->keys();
        }
        return $this->attributes['category_id_keys'];
    }

    public static function storeOrUpdate($data, $id)
    {
        if (isset($id)) {
            $tab = self::find($id);
            TabData::where('tab_id', $id)->delete();
        } else {
            $tab = new self();
        }

        $tab->sort = $data['sort'];
        $tab->dark_theme = $data['dark_theme'];
        $tab->save();

        TabCategory::storeOrUpdate($tab->id, $data['category_ids']);

        foreach (AVAILABLE_LOCALES as $locale) {

            if (array_filter($data['data'][$locale])) {

                $tabData = [
                    'locale' => $locale,
                    'tab_id' => $tab->id,
                    'name' => $data['data'][$locale]['name'],
                ];

                TabData::create($tabData);
            }
        }
    }

    public function scopeWithCategoryIds($query)
    {
        return $query->with('category_ids');
    }

    public function category_ids()
    {
        return $this->hasMany('App\TabCategory', 'tab_id', 'id');
    }
}
