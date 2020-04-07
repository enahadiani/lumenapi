<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = "upload_file";
    protected $fillable = ['file_dok','nama'];
}
