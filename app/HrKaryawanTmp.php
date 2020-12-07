<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HrKaryawanTmp extends Model
{
    protected $connection = 'dbsapkug';
    protected $table = 'hr_karyawan_tmp';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nik','nama','tgl_lahir','gender','sts_organik','sts_medis','sts_edu','sts_aktif','kode_pp','tgl_input','nik_user','periode','sts_upload','ket_upload','nu'
    ];

}
