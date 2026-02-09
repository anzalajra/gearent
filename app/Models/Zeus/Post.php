<?php

namespace App\Models\Zeus;

use LaraZeus\Sky\Models\Post as ZeusPost;

class Post extends ZeusPost
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'post_type',
        'content',
        'user_id',
        'parent_id',
        'featured_image',
        'published_at',
        'sticky_until',
        'password',
        'ordering',
        'status',
        'options',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'options' => 'array',
        ]);
    }
}
