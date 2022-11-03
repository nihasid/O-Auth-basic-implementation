<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon;

class DutiesEmployees extends Model
{
    use HasFactory;
    use Uuids;

    protected $table = 'duties_employees';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    protected $fillable = [
        'employees_id',
        'duties_id',
        'company_id',
        'status',
        'enrolled_date_started_at',
        'enrolled_date_ended_at'
    ];
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

    // public function setIdAttribute()
    // {
    //     $this->attributes['id'] = Str::uuid()->toString();
    // }

    public function getCertificateAttribute($value) {
        $certificate = env('APP_URL').'storage/'.$value;
		return $certificate;
    }


}
