<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\AnggaranImport;
use App\Exports\AnggaranExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Log; 

class AnggaranController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "yakes";
    public $db = "dbsapkug";

    public function isUnik($kode_lokasi,$kode_pp,$kode_ta,$kode_kelas,$kode_matpel,$kode_jenis,$kode_sem,$kode_kd){
        
        $auth = DB::connection($this->db)->select("select no_bukti from sis_nilai_m where kode_ta='$kode_ta' and kode_kelas='$kode_kelas' and kode_matpel='$kode_matpel' and kode_jenis='$kode_jenis' and kode_sem='$kode_sem' and kode_kd='$kode_kd' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $data['status']=false;
            $data['res'] = $auth;
        }else{
            $data['status']=true;
            $data['res'] = [];
        }

        return $data;
    }


    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }    

    public function getTahun(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select substring(periode,1,4) as tahun from periode where kode_lokasi='".$kode_lokasi."' ";

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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
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
            'tahun' => 'required', 
            'nik_user' => 'required', 
            'keterangan' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $ins = DB::connection($this->db)->insert("insert into anggaran_m(
                no_agg,kode_lokasi,no_dokumen,tanggal,keterangan,tahun,kode_curr,nilai,tgl_input,nik_user,posted,no_del,nik_buat,nik_setuju,jenis)  
                select a.no_agg,a.kode_lokasi,'-',getdate(),'$request->keterangan' as keterangan,'$request->tahun' as tahun,'IDR' as kode_curr,a.n1+a.n2+a.n3+a.n4+a.n5+a.n6+a.n7+a.n8+a.n9+a.n10+a.n11+a.n12 as nilai,getdate(),'$nik','T' as posted,'-','$nik','-','-' 
                from anggaran_load a 
                where a.kode_lokasi='$kode_lokasi' and a.nik_user='$request->nik_user'
                ");
                
            $del = DB::connection($this->db)->update("delete from anggaran_d where substring(periode,1,4)='".$request->tahun."' and kode_lokasi='$kode_lokasi' ");

            for($i=1;$i <= 12;$i++){
                $periode = ( $i < 10 ? $request->tahun."0".$i : $request->tahun.$i );
                $det[$i] = DB::connection($this->db)->insert("insert into anggaran_d (
                no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai,nilai_sat,dc,satuan,tgl_input,nik_user,modul,nilai_kas,no_sukka) 
                select no_agg,kode_lokasi,$i,kode_pp,kode_akun,'-',1 as volume,'".$periode."' as periode,n".$i." as nilai,n".$i." as nilai,'D','-',getdate(),'$request->nik_user','RRA',0 as nilai_kas,'-'
                from anggaran_load 
                where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user'
                ");
            }

            $del2 = DB::connection($this->db)->update("delete from anggaran_load where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Anggaran berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Anggran gagal disimpan. Internal Server Error.";
            Log::error($e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

   
    public function validateData($kode_akun,$kode_pp,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi' 
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Akun $kode_akun tidak valid. ";
        }

        $auth = DB::connection($this->db)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' 
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode PP $kode_pp tidak valid. ";
        }

        return $keterangan;

    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('anggaran_load')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            $per = date('ym');

            // $no_bukti = $this->generateKode("anggaran_m", "no_agg", $kode_lokasi."-RRU".$per.".", "0001");

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new AnggaranImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            $no_bukti = 0;
            $cekNoBukti = "select max(no_agg) as no_agg from anggaran_m where kode_lokasi='".$kode_lokasi."' and no_agg like '%RRU%' ";
            $cek = DB::connection($this->db)->select($cekNoBukti);
            if(count($cek) > 0){
                $nobukti = ($cek[0]->no_agg != NULL ? substr($cek[0]->no_agg,-4) : "0000") ;
            }else{
                $nobukti = "0000";
            }
            $prefix = $kode_lokasi."-RRU".$per.".";
            
            $no_bukti = (int) $nobukti;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateData($row[0],$row[1],$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    
                    $no_bukti++;
                    if(strlen($no_bukti) == 1) {
                        $noFix = "000".$no_bukti."";
                    } elseif (strlen($no_bukti) == 2) {
                        $noFix = "00".$no_bukti."";
                    } elseif (strlen($no_bukti) == 3) {
                        $noFix = "0".$no_bukti."";
                    } elseif (strlen($no_bukti) == 4) {
                        $noFix = $no_bukti;
                    }
                    $no_buktiFix = $kode_lokasi."-RRU".$per.".".$noFix;

                    $x[] = DB::connection($this->db)->insert("insert into anggaran_load(no_agg,kode_pp,kode_akun,n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12,nik_user,status,keterangan,nu,kode_lokasi)  values ('$no_buktiFix','".$row[1]."','".$row[0]."','".$row[2]."','".$row[3]."','".$row[4]."','".$row[5]."','".$row[6]."','".$row[7]."','".$row[8]."','".$row[9]."','".$row[10]."','".$row[11]."','".$row[12]."','".$row[13]."','".$request->nik_user."','".$sts."','".$ket."',".$no.",'$kode_lokasi') ");
                    $no++;
                }
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['no_bukti'] = $no_bukti;
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'nik' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $request->nik_user;
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new AnggaranExport($nik_user,$kode_lokasi,$request->type), 'Anggaran_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new AnggaranExport($nik_user,$kode_lokasi,$request->type,$request->tahun), 'Anggaran_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function loadAnggaran(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required'
        ]);

        $nik_user = $request->nik_user;
        $kode_pp = $request->kode_pp;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select a.no_agg,a.kode_pp,a.kode_akun,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9,a.n10,a.n11,a.n12 
            from anggaran_load a
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
            $res = DB::connection($this->db)->select($sql);
            $res= json_decode(json_encode($res),true);
            $success['sql'] = $sql;

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['detail'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }

}
