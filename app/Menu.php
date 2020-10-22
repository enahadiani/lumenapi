<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $connection = 'sqlsrvyptkug';
    protected $table = 'menu';

    public $incrementing = false;

    protected $fillable = ['kode_menu','nama','kode_klp','kode_form','level_menu','rowindex','icon','jenis_menu','kode_induk'];


    public function children()
    {
        return $this->hasMany('App\Menu', 'kode_induk','kode_menu')->with('children');
    }

}
