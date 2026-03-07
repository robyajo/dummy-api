<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Contact extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $table = 'contacts';
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'avatar',
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
    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        $path = $this->avatar;
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, 'assets/')) {
            return asset($path);
        }
        return Storage::url($path);
    }
}
