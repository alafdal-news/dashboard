<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Category extends Model
{
    // 1. LEGACY MAPPING
    protected $table = 'categories';
    // Primary key is standard 'id', so no need to define it
    public $timestamps = false;

    protected $fillable = [
        'name',
        'active',
        'parent_id',
        'is_parent',
        'addBy',
        'addDate'
    ];

    protected $casts = [
        // NOTE: active is enum('0','1') in the legacy DB.
        // Do NOT use boolean cast — it causes MySQL enum index comparison bugs.
        // Use explicit Attribute accessor below instead.
        'is_parent' => 'boolean',
    ];

    // Handle legacy enum('0','1') column safely
    protected function active(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === '1',
            set: fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value,
        );
    }

    // 2. RELATIONSHIPS

    // A category is created by a user
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // A category belongs to a parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // A category has many child categories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // A category has many articles (mapped via 'id_cat')
    public function articles()
    {
        return $this->hasMany(Article::class, 'id_cat', 'id');
    }
}
