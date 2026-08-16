<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    protected $casts = ['is_admin' => 'boolean'];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    public function hasPermission(string $perm): bool
    {
        return $this->is_admin || $this->permissions->contains('permission', $perm);
    }

    /** Daftar key izin yang dimiliki. */
    public function permissionKeys(): array
    {
        return $this->permissions->pluck('permission')->all();
    }
}
