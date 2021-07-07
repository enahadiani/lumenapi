<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use App\Imports\WargaImport;
use Maatwebsite\Excel\Facades\Excel;

class WargaDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public $successStatus = 200;
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }
    
    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter .= "";
                }else{
                    $filter .= " and no_rumah='$request->no_rumah' ";
                }
            }else{
                $filter .= "";
            }

            $sql= "select distinct kode_blok,no_rumah,kode_pp,kode_lokasi from rt_warga_d where kode_lokasi='$kode_lokasi' $filter ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_rt' => 'required',
            'blok' => 'required',
            'no_rumah' => 'required',
            'tgl_masuk' => 'required',
            'sts_masuk' => 'required',
            'nama' => 'required|array',
            'alias' => 'required|array',
            'jk' => 'required|array',
            'tempat_lahir' => 'required|array',
            'tgl_lahir' => 'required|array',
            'agama' => 'required|array',
            'goldar' => 'required|array',
            'pendidikan' => 'required|array',
            'pekerjaan' => 'required|array',
            'sts_nikah' => 'required|array',
            'sts_hub' => 'required|array',
            'sts_wni' => 'required|array',
            'no_hp' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $cek = DB::connection($this->db)->select("select no_bukti from rt_warga_d where kode_lokasi='$kode_lokasi' and no_rumah='$request->no_rumah' and no_bukti is not null ");
            if(count($cek) > 0){
                $no_bukti = $cek[0]->no_bukti;
            }else{

                $no_bukti = $this->generateKode("rt_warga_d", "no_bukti", $kode_lokasi.$request->no_rumah, "01");
            }
            
            $del3 = DB::connection($this->db)->table('rt_warga_d')->where('kode_lokasi', $kode_lokasi)->where('no_rumah', $request->no_rumah)->delete();

            $no_urut = 1;
            for($i=0; $i<count($request->nama);$i++){
                if(isset($request->alias[$i])){
                    $alias = $request->alias[$i];
                }else{
                    $alias = "-";
                }

                if($request->no_hp != "" && $request->no_hp != "-"){
                    $pass = substr($request->no_hp[$i],6);
                    $password = app('hash')->make($pass);
                }else{
                    $pass = "-";
                    $password = "-";
                }

                $ins = DB::connection($this->db)->insert('insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,no_hp,kode_lokasi,no_bukti,kode_jk,kode_agama,tempat_lahir,tgl_lahir,kode_goldar,kode_didik,kode_kerja,kode_sts_nikah,kode_sts_hub,kode_sts_wni,kode_pp,tgl_masuk,sts_masuk,alias,pass,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->blok,$request->no_rumah,$no_urut,$request->nama[$i],$request->no_hp[$i],$kode_lokasi,$no_bukti,$request->jk[$i],$request->agama[$i],$request->tempat_lahir[$i],$request->tgl_lahir[$i],$request->goldar[$i],$request->pendidikan[$i],$request->pekerjaan[$i],$request->sts_nikah[$i],$request->sts_hub[$i],$request->sts_wni[$i],$request->kode_rt,$this->reverseDate($request->tgl_masuk,"/","-"),$request->sts_masuk,$alias,$pass,$password));
                $no_urut++;
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function show(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter .= "";
                }else{
                    $filter .= " and a.kode_rumah='$request->no_rumah' ";
                }
            }else{
                $filter .= "";
            }

            $filter2 = "";
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter2 .= "";
                }else{
                    $filter2 .= " and a.no_rumah='$request->no_rumah' ";
                }
            }else{
                $filter2 .= "";
            }

            $sql= "select distinct a.blok as kode_blok,a.kode_rumah as no_rumah,a.rt as kode_pp,a.kode_lokasi,convert(varchar,b.tgl_masuk,103) as tgl_masuk,b.sts_masuk 
            from rt_rumah a
            left join rt_warga_d b on a.kode_rumah=b.no_rumah and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' $filter ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql= "select no_urut,nama,no_hp,kode_jk as jk,kode_agama as agama,tempat_lahir,tgl_lahir,kode_goldar as goldar,kode_didik as pendidikan,kode_kerja as pekerjaan,kode_sts_nikah as sts_nikah,kode_sts_hub as sts_hub,kode_sts_wni as sts_wni,alias from rt_warga_d a where a.kode_lokasi='".$kode_lokasi."' $filter2 ";
            
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

  
}
