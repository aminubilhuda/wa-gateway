<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(AutoReply::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AutoReply::class, 'parent_id')->orderBy('keyword');
    }

    public function isParent()
    {
        return is_null($this->parent_id);
    }

    public function isChild()
    {
        return ! is_null($this->parent_id);
    }
}
