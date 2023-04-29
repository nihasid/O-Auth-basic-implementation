<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Constant;
use DB;

class Users extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use Uuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'date_of_birth',
        'password',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        parent::bootUuid();
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }

   

    // static function userList($request)
    // {
    //     $conditions = [];
    //     $searchValue = $request->search;

    //     //companies.company
    //     $query = self::with('company', 'roles');


    //     $user = Auth::user();


    //     // $query->whereHas('roles', function ($q) {
    //     //     $q->whereIn('role_id', [
    //     //         Constant::USER_ROLES['super-admin'],
    //     //         Constant::USER_ROLES['pro-admin'],
    //     //         Constant::USER_ROLES['pro-data'],
    //     //         Constant::USER_ROLES['standard-admin'],
    //     //         Constant::USER_ROLES['standard-data'],
    //     //     ]);
    //     // });
    //     if (!auth()->user()->hasRole('super-admin')) {
    //         // info: all users w.r.t auth company_id
    //         $query->whereHas('company', function ($q) use ($user) {
    //             $q->where('company_id', $user->company->id)->orWhereNull('company_id');
    //         });
    //     }



    //     if (isset($searchValue)) {
    //         $query->whereHas('userDetails', function ($q) use ($searchValue, $project_memberIds) {
    //             $q->where('email', 'LIKE', '%' . $searchValue . '%')
    //                 ->orWhere('full_name', 'LIKE', '%' . $searchValue . "%");
    //         });
    //     }

    //     // $query->where('is_active', true);

    //     // // exclude super admin users
    //     // $excludedIds = [1];


    //     if ($request->limit) {
    //         $query->offset($request->offset)->limit($request->limit);
    //     }

    //     return $query->orderBy('created_at', 'DESC')->get()->toArray();
    // }

    // static function updateUser($data, $user)
    // {
    //     $user->fill([
    //         'email'         => $data['email'],
    //         'is_active'     => Constant::BOOL_STR[$data['status']],
    //         'company_id'    => $data['company_id'],
    //         'created_by'    => Auth::id(),
    //         'name'            => $data['name'],
    //         // 'email_notification'    => (isset($data['emailNotification']) ? 1 : 0),
    //     ]);
    //     $user->update();

    //     if ($data['role'] && Roles::where('name', $data['role'])->exists()) {

    //         $isDeleteRole = DB::table('model_has_roles')->where('model_id', $user->id)->delete();
    //         // dd($user->assignRole($data['role']));
    //         $assign_role = $user->assignRole($data['role']);
    //     }

    //     $user->load(['company', 'roles' => function ($q) {
    //         $q->select('id', 'name');
    //     }]);
        
    //     return $user;
    // }

    // static function deleteUsers($id)
    // {
    //     $response = self::where('id', $id)->delete();
    //     if ($response) {
    //         DB::table('model_has_roles')->where('model_id', $id)->delete();
    //     }
    //     return $response;
    // }
}
