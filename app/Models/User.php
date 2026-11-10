<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'outlet_id', 'role_id', 'name', 'email', 'phone', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function activeShift(): ?Shift
    {
        return $this->shifts()
            ->where('status', 'OPEN')
            ->latest('opened_at')
            ->first();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role) {
            return false;
        }

        if ($this->role->name === 'Super Admin') {
            return true;
        }

        return $this->role->permissions()->where('name', $permission)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'Super Admin';
    }
}
