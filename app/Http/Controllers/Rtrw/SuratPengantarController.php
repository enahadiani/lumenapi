<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SuratPengantarController extends Controller
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

            $res = DB::connection($this->db)->select("select a.no_surat,a.nomor,a.tanggal,a.id_warga,a.keperluan,a.kode_lokasi,a.kode_pp,a.tgl_input,a.nik_user,a.nama_rt,a.nama_rw,a.no_app_rt,a.no_app_rw,b.nik 
            from rt_surat_antar a 
            inner join rt_warga_d b on a.id_warga=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
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

    public function generateNBukti(Request $request)
    {
        $this->validate($request,[
            'tanggal' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per = substr($request->tanggal,8,2).substr($request->tanggal,3,2);
            $nb = $this->generateKode("rt_surat_antar", "no_surat", $kode_lokasi.'-SP'.$per.".", "0001");
            
            $res = DB::connection($this->db)->select("select a.nama_rt,a.nama_rw
            from rt_jabat a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
			inner join karyawan c on b.kode_pp=c.kode_pp and c.nik='$nik'
            where a.kode_lokasi='$kode_lokasi' 
            ");

            if(count($res) > 0){
                $success['nama_rt'] = $res[0]->nama_rt;
                $success['nama_rw'] = $res[0]->nama_rw;
            }else{
                $success['nama_rt'] = "-";
                $success['nama_rw'] = "-";
            }
            
            $success['status'] = true;
            $success['no_bukti'] = $nb;
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
            'tanggal' => 'required',
            'kode_pp' => 'required',
            'no_urut' => 'required',
            'keperluan' => 'required',
            'nama_rt' => 'required',
            'nama_rw' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per = substr($request->tanggal,8,2).substr($request->tanggal,3,2);
            $nb = $this->generateKode("rt_surat_antar", "no_surat", $kode_lokasi.'-SP'.$per.".", "0001");

            $insert = DB::connection($this->db)->insert("insert into rt_surat_antar (no_surat,nomor,tanggal,id_warga,keperluan,kode_lokasi,kode_pp,tgl_input,nik_user,nama_rt,nama_rw,no_app_rt,no_app_rw ) values ('$nb','$request->no_urut','".$this->reverseDate($request->tanggal,'/','-')."','$request->id_warga','$request->keperluan','$kode_lokasi','$request->kode_pp',getdate(),'$nik','$request->nama_rt','$request->nama_rw','-','-') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $nb;
            $success['message'] = "Data Surat Pengantar berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Surat Pengantar gagal disimpan ".$e;
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
            'no_surat' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/rtrw/storage');

            $sql = "
            select a.no_surat,a.nomor,convert(varchar,a.tanggal,103) as tanggal,a.id_warga,a.keperluan,a.kode_lokasi,a.kode_pp,a.tgl_input,a.nik_user,a.nama_rt,a.nama_rw,a.no_app_rt,a.no_app_rw,c.alamat,b.kode_jk as jk,b.tempat_lahir,convert(varchar,b.tgl_lahir,103) as tgl_lahir,b.kode_agama as agama,b.kode_kerja as pekerjaan,b.kode_sts_nikah as sts_nikah,b.nama,b.nik  
            from rt_surat_antar a 
            inner join rt_warga_d b on a.id_warga=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join rt_rumah c on b.no_rumah=c.kode_rumah and b.kode_lokasi=c.kode_lokasi
            where no_surat='".$request->no_surat."' 
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
            'tanggal' => 'required',
            'kode_pp' => 'required',
            'no_urut' => 'required',
            'keperluan' => 'required',
            'nama_rt' => 'required',
            'nama_rw' => 'required',
            'no_surat' => 'required'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('rt_surat_antar')->where('kode_lokasi', $kode_lokasi)->where('no_surat', $request->no_surat)->delete();
           
            $insert = DB::connection($this->db)->insert("insert into rt_surat_antar (no_surat,nomor,tanggal,id_warga,keperluan,kode_lokasi,kode_pp,tgl_input,nik_user,nama_rt,nama_rw,no_app_rt,no_app_rw ) values ('$request->no_surat','$request->no_urut','".$this->reverseDate($request->tanggal,'/','-')."','$request->id_warga','$request->keperluan','$kode_lokasi','$request->kode_pp',getdate(),'$nik','$request->nama_rt','$request->nama_rw','-','-') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Surat Pengantar berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Surat Pengantar gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request,[
            'no_surat' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }   
            
            $del = DB::connection($this->db)->table('rt_surat_antar')->where('kode_lokasi', $kode_lokasi)->where('no_surat', $request->no_surat)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Surat Pengantar berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Surat Pengantar gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
