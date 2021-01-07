<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class KontController extends Controller
{
    
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function isUnik($isi,$kode_lokasi){        
        $auth = DB::connection($this->sql)->select("select no_dokumen from sai_kontrak where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection($this->sql)->select("select a.no_kontrak,a.no_dokumen,a.tgl_awal,a.tgl_akhir,a.keterangan,a.nilai,a.nilai_ppn,a.nama_up,a.alamat_up,a.progress 
                                                       from sai_kontrak a where a.kode_lokasi='".$kode_lokasi."' and a.status_kontrak='PROYEK' ");
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'kode_cust' => 'required',
            'keterangan' => 'required',
            'nilai'=>'required',
            'nilai_ppn'=>'required',
            'nama_up'=>'required',
            'alamat_up'=>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->no_dokumen,$kode_lokasi)){

                $periode = date('Ym');
                $per = substr($periode,2,4);
                $no_bukti = $this->generateKode("sai_kontrak", "no_kontrak", $kode_lokasi."-PR".$per.".", "0001");

                $tgl_awal = $request->tgl_awal.date(" H:i:s");
                $tgl_akhir = $request->tgl_akhir.date(" H:i:s");

                $ins = DB::connection($this->sql)->insert("insert into sai_kontrak (no_kontrak,no_dokumen,tgl_awal,tgl_akhir,keterangan,nilai,kode_lokasi,kode_cust,nilai_ppn,nama_up,alamat_up,status_kontrak,progress) values ('".$no_bukti."','".$request->no_dokumen."','".$tgl_awal."','".$tgl_akhir."','".$request->keterangan."',".$request->nilai.",'".$kode_lokasi."','".$request->kode_cust."',".$request->nilai_ppn.",'".$request->nama_up."','".$request->alamat_up."','PROYEK','1') ");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Kontrak berhasil disimpan. No Kontrak:".$no_bukti;
                $success['no_kontrak'] = $no_bukti;
              
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                $success['no_kontrak'] = '-';
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_kontrak' => 'required'
        ]);
        try {
                        
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_kontrak,a.kode_cust,a.no_dokumen,a.tgl_awal,a.tgl_akhir,a.keterangan,a.nilai,a.nilai_ppn,a.nama_up,a.alamat_up,a.progress from sai_kontrak a where a.kode_lokasi='".$kode_lokasi."' and a.no_kontrak='$request->no_kontrak' ";            
            $res = DB::connection($this->sql)->select($sql);
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_kontrak' => 'required',
            'no_dokumen' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'kode_cust' => 'required',
            'keterangan' => 'required',
            'nilai'=>'required',
            'nilai_ppn'=>'required',
            'nama_up'=>'required',
            'alamat_up'=>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_kontrak;            
            $del = DB::connection($this->sql)->table('sai_kontrak')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $request->no_kontrak)->delete();

            $tgl_awal = $request->tgl_awal.date(" H:i:s");
            $tgl_akhir = $request->tgl_akhir.date(" H:i:s");
            
            $ins = DB::connection($this->sql)->insert("insert into sai_kontrak (no_kontrak,no_dokumen,tgl_awal,tgl_akhir,keterangan,nilai,kode_lokasi,kode_cust,nilai_ppn,nama_up,alamat_up,status_kontrak,progress) values ('".$no_bukti."','".$request->no_dokumen."','".$tgl_awal."','".$tgl_akhir."','".$request->keterangan."',".$request->nilai.",'".$kode_lokasi."','".$request->kode_cust."',".$request->nilai_ppn.",'".$request->nama_up."','".$request->alamat_up."','PROYEK','1') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kontrak berhasil diubah. No Kontrak:".$no_bukti;
            $success['no_kontrak'] = $no_bukti;
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request,[
            'no_kontrak' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti= $request->no_kontrak;
            $del = DB::connection($this->sql)->table('sai_kontrak')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kontrak berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


}
