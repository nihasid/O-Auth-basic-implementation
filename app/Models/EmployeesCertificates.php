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
        'certificate',
        'status',
        'certificate_created_at',
        'certificate_expires_at'
    ];

    public static function boot()
    {
        parent::boot();
        parent::bootUuid();
    }

    public function employee() {
        return $this->belongsTo(Employees::class);
    }
}
