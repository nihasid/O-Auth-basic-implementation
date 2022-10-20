<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class Invitations extends Model
{
    use HasFactory;
    use Uuids;


    protected $table = 'invitations';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];


    public static function boot()
    {
        parent::boot();
        parent::bootUuid();
    }
}
