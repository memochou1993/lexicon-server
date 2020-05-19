<?php

namespace App\Models;

use App\Traits\HasPermissions;
use App\Traits\HasUsers;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasUsers;
    use HasPermissions;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];
}
