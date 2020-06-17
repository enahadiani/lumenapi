<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BarangFisik extends Model
{
    protected $connection = 'tokoaws';
    protected $table = 'brg_fisik_tmp';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kode_lokasi','nu','kode_barang','jumlah','nik_user'
    ];

}
