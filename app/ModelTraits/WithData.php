<?php

namespace App\ModelTraits;

use Illuminate\Support\Str;

trait WithData
{
    public function getDataClassNamespace()
    {
        return __CLASS__ . 'Data';
    }

    public function getClassName()
    {
        return (new \ReflectionClass(__CLASS__))->getShortName();
    }

    public function getForeignKey()
    {
        return Str::snake($this->getClassName()) . '_id';
    }

    public function data()
    {
        return $this->hasOne($this->getDataClassNamespace(), $this->getForeignKey(), 'id')
            ->where('locale', 'ru');
    }

    public function all_data()
    {
        return $this->hasMany($this->getDataClassNamespace(), $this->getForeignKey(), 'id');
    }

    public function scopeWithData($query, $all = false)
    {
        return $query->with(($all ? 'all_' : '') . 'data');
    }

    public function getKeyedAllDataAttribute()
    {
        if (!isset($this->attributes['keyed_all_data'])) {
            $this->attributes['keyed_all_data'] = $this->getRelationValue('all_data')->keyBy('locale');
        }
        return $this->attributes['keyed_all_data'];
    }

    public function getDataAttr($key, $orKey = null)
    {
        if (!isset($this->attributes[$key])) {
            $this->attributes[$key] = isset($this->data->$key) ? $this->data->$key :
                (isset($this->data->$orKey) ? $this->data->$orKey : null);
        }
        return $this->attributes[$key];
    }

    public function getMetaTitleAttribute()
    {
        return $this->getDataAttr('meta_title');
    }

    public function getMetaDescriptionAttribute()
    {
        return $this->getDataAttr('meta_description');
    }

    public function getNameAttribute()
    {
        return $this->getDataAttr('name');
    }

    public function getDescriptionAttribute()
    {
        return $this->getDataAttr('description');
    }

    public function getTextAttribute()
    {
        return $this->getDataAttr('text');
    }

    public function getH1Attribute()
    {
        return $this->getDataAttr('h1', 'name');
    }
}