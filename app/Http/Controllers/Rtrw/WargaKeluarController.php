<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class WargaKeluarController extends Controller
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


    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_blok,no_rumah,nama,no_urut,alias,tgl_keluar,no_bukti,sts_masuk as sts_keluar from rt_warga_d where kode_lokasi='$kode_lokasi' and sts_keluar in ('PINDAH','MENINGGAL') and flag_aktif=1
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function generateIDWarga(Request $request)
    {
        $this->validate($request,[
            'tgl_keluar' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per = substr($request->tgl_keluar,8,2).substr($request->tgl_keluar,3,2);
            $id_warga = $this->generateKode("rt_warga_d", "no_bukti", $kode_lokasi.'-OUT'.$per.".", "0001");

            
            $success['status'] = true;
            $success['no_bukti'] = $id_warga;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'id_warga' => 'required',
            'tgl_keluar' => 'required',
            'sts_keluar' => 'required',
            'keterangan' => 'required',
            'dok_keluar' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per = substr($request->tgl_keluar,8,2).substr($request->tgl_keluar,3,2);
            $no_bukti_keluar = $this->generateKode("rt_warga_d", "no_bukti", $kode_lokasi.'-OUT'.$per.".", "0001");

            $update = DB::connection($this->db)->update("update rt_warga_d set no_bukti_keluar='$no_bukti_keluar',sts_keluar='$request->sts_keluar',tgl_keluar='".$this->reverseDate($request->tgl_keluar,"/","-")."',dok_keluar='$request->dok_keluar',ket_keluar='$request->keterangan' where no_bukti='$request->id_warga' and kode_lokasi='$kode_lokasi' ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti_keluar;
            $success['message'] = "Data Warga Keluar berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Keluar gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti_keluar' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/rtrw/storage');

            $sql = "
            select kode_blok,no_bukti as id_warga,no_rumah,nama,alias,nik,kode_jk as jk,tempat_lahir,convert(varchar,tgl_lahir,103) as tgl_lahir,kode_agama as agama,kode_goldar as goldar,kode_didik as pendidikan,kode_kerja as pekerjaan,kode_sts_nikah as sts_nikah,sts_domisili,kode_sts_hub as sts_hub,no_hp,no_telp_emergency as emerg_call,ket_emergency,convert(varchar,tgl_masuk,103) as tgl_masuk,sts_masuk,kode_pp as kode_rt,kode_lokasi as kode_rw,case when foto != '-' then '".$url."/'+foto else '-' end as foto
            from rt_warga_d a 
            where no_bukti_keluar='".$request->no_bukti_keluar."' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'id_warga' => 'required',
            'tgl_keluar' => 'required',
            'sts_keluar' => 'required',
            'keterangan' => 'required',
            'dok_keluar' => 'required',
            'no_bukti_keluar' => 'required'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id_warga = $request->id_warga;
           
            $update = DB::connection($this->db)->update("update rt_warga_d set no_bukti_keluar='$no_bukti_keluar',sts_keluar='$request->sts_keluar',tgl_keluar='".$this->reverseDate($request->tgl_keluar,"/","-")."',dok_keluar='$request->dok_keluar',ket_keluar='$request->keterangan' where no_bukti_keluar='$request->no_bukti_keluar' and kode_lokasi='$kode_lokasi' ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Warga Keluar berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Keluar gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

}
