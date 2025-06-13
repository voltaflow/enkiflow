<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /**
     * Get spaces owned by the user.
     */
    public function ownedSpaces(): HasMany
    {
        return $this->hasMany(Space::class, 'owner_id');
    }

    /**
     * Get all spaces that the user belongs to.
     */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_users', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Check if the user owns the given space.
     */
    public function ownsSpace(Space $space): bool
    {
        return $this->id === $space->owner_id;
    }
}
