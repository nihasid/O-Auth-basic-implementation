<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employees;

class EmployeesCertificates extends Model
{
    use HasFactory;
    use Uuids;

    protected $fillable = [
        'employees_id',
        'duties_id',
        'certificate',
        'status',
        'certificate_created_at',
        'certificate_expires_at'
    ];

    protected $dates = [];
    public static function boot()
    {
        parent::boot();
        parent::bootUuid();
    }

    public function employee()
    {
        return $this->belongsTo(Employees::class);
    }

    public function getCertificateAttribute($value)
    {
        $certificate = env('APP_URL') . 'storage/' . $value;
        return $certificate;
    }

    // public function setCertificateCreatedAtAttribute($value)
    // {
    //     $this->attributes['certificate_created_at'] = (new Carbon($value))->format('Y-m-d');
    // }

    // public function setCertificateExpiresAtAttribute($value)
    // {
    //     $this->attributes['certificate_expires_at'] = (new Carbon($value))->format('Y-m-d');
    // }
}
