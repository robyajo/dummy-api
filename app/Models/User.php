<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['uuid', 'name', 'email', 'avatar', 'password', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Ambil identifier unik user yang akan disimpan di dalam JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // biasanya ID user
    }

    /**
     * Tambahkan klaim (claims) tambahan jika diperlukan.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the default avatar URL based on user name.
     */
    public static function getDefaultAvatarUrl(?string $name): string
    {
        $seed = strtolower(trim($name ?? 'user'));

        return "https://api.dicebear.com/7.x/adventurer/svg?seed={$seed}";
    }

    /**
     * Get the avatar URL, falling back to default if not uploaded.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/assets/images/user/avatar/' . $this->avatar);
        }

        return static::getDefaultAvatarUrl($this->name);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) str()->uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
