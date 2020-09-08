<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JurnalTmp extends Model
{
    protected $connection = 'tokoaws';
    protected $table = 'jurnal_tmp';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kode_akun','dc','keterangan','nilai','kode_pp','kode_lokasi','nik_user','tgl_input','status','ket_status'
    ];

}
