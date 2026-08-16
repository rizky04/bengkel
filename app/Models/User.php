<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'aktif', 'branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /** Model role (berdasarkan slug users.role), di-cache per request. */
    public function roleModel(): ?Role
    {
        return $this->relationLoaded('roleModel')
            ? $this->getRelation('roleModel')
            : $this->setRelation('roleModel', Role::with('permissions')->where('key', $this->role)->first())->getRelation('roleModel');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->roleModel()?->is_admin;
    }

    /** Boleh mengakses menu/izin tertentu? Admin selalu boleh. */
    public function canAccess(string $permission): bool
    {
        return (bool) $this->roleModel()?->hasPermission($permission);
    }

    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }

    public function isMekanik(): bool
    {
        return $this->role === 'mekanik';
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
}
