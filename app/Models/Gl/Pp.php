<?php

namespace App\Models\Gl;

use Illuminate\Database\Eloquent\Model;

class Pp extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = 'pp';

    protected $primaryKey = 'kode_pp';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kode_lokasi','kode_pp','nama','flag_aktif'
    ];
}
