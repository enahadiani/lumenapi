<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NilaiTmpPH extends Model
{
    protected $connection = 'sqlsrvtarbak';
    protected $table = 'sis_nilai_tmp2';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nis','nilai','kode_lokasi','kode_pp','no_bukti','status','keterangan','nu','nik_user','kode_jenis'
    ];

}
