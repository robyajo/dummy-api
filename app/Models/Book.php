<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books';
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'description',
        'isbn',
        'publisher',
        'published_date',
        'pages',
        'language',
        'price',
        'stock_quantity',
        'cover_image',
        'cover_image_url',
        'categories',
        'authors',
        'rating',
        'rating_count',
        'user_id',
    ];

    protected $casts = [
        'published_date' => 'date',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'categories' => 'array',
        'authors' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) str()->uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
