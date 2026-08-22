<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\AdminResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'role',
        'admin_role',
        'admin_permissions',
        'is_active',
        'password',
        'profile_picture',
        'fcm_token',
        'google_id',
        'deactivated_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'admin_permissions' => 'array',
            'password_changed_at' => 'datetime',
        ];
    }

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function effectiveAdminRole(): ?string
    {
        if (! $this->isAdmin()) {
            return null;
        }

        return $this->admin_role ?: 'super_admin';
    }

    public function adminRoleLabel(): string
    {
        $role = $this->effectiveAdminRole();

        return AdminRole::definition($role)?->label
            ?? config("admin.roles.{$role}.label", 'Administrator');
    }

    public function isSuperAdmin(): bool
    {
        return $this->effectiveAdminRole() === 'super_admin';
    }

    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdmin() || ! $this->is_active) {
            return false;
        }

        $role = $this->effectiveAdminRole();
        $rolePermissions = AdminRole::definition($role)?->permissions
            ?? config("admin.roles.{$role}.permissions", []);
        $permissions = array_unique([...$rolePermissions, ...($this->admin_permissions ?? [])]);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminResetPasswordNotification($token));
    }
}
