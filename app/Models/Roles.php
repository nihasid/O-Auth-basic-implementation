<?php

namespace App\Models;

use App\Traits\Uuids;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExists;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\RefreshesPermissionCache;
use Spatie\Permission\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Permissions;
use App\Models\Role_has_Permissions;

class Roles extends Model
{
    use CrudTrait;
    use HasPermissions;
    use RefreshesPermissionCache;
    use Uuids;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'roles';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public static function boot()
    {
        parent::boot();
        parent::bootUuid();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function hasPermissionTo($permission): bool
    {
        if (config('permission.enable_wildcard_permission', false)) {
            return $this->hasWildcardPermission($permission, $this->getDefaultGuardName());
        }

        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission, $this->getDefaultGuardName());
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission, $this->getDefaultGuardName());
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            throw GuardDoesNotMatch::create($permission->guard_name, $this->getGuardNames());
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    static function getAllRoles() {
        return Roles::whereNotIn('name', ['super-admin', 'pro-data', 'standard-data'])->select('id', 'name')->get()->toArray();
    }
}
