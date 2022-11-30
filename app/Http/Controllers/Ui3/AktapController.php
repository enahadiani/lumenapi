<?php

namespace App\Http\Controllers\Ui3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helper\SaiHelpers;

class AktapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbui3';
    public $guard = 'ui3';

    public function store(Request $r)
    {
        $this->validate($r, [
            'data_aktap' => 'required|array',
            'data_aktap.*.tgl_perolehan' => 'required|date_format:Y-m-d',
            'data_aktap.*.tgl_susut' => 'required|date_format:Y-m-d',
            'data_aktap.*.jumlah' => 'required',
            'data_aktap.*.deskripsi' => 'required',
            'data_aktap.*.no_seri' => 'required',
            'data_aktap.*.merk' => 'required',
            'data_aktap.*.tipe' => 'required',
            'data_aktap.*.nilai' => 'required',
            'data_aktap.*.residu' => 'required',
            'data_aktap.*.pp_aktap' => 'required',
            'data_aktap.*.pp_susut' => 'required',
            'data_aktap.*.kode_klpfa' => 'required',
            'data_aktap.*.no_bukti' => 'required'
        ]);

        
        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }
            
            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_ui3_log (kode_lokasi,tgl_input,nik_user,datalog,nama_api,dbname)
            values (?, getdate(), ?, ?, ?, ?) ",array($kode_lokasi,$nik,$log,'AKTAP','devui3'));
            // END SAVE

            $sql = ""; $sql2 = ""; $sql3 = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            
            $i=1;$no=1;
            $jum_det = 0;
            set_time_limit(300);
            ini_set('max_execution_time', 300); 
            foreach($r->input('data_aktap') as $row){
                if($row['tgl_perolehan'] != ""){

                    $periode = substr($row['tgl_perolehan'],0,4).substr($row['tgl_perolehan'],5,2);
                    $cek = SaiHelpers::doCekPeriodeLebih($this->db,$kode_lokasi,$periode);
                    $sts = $cek['status'];
                    $msg = $cek['message'];
                    if(!$sts){
                        break;
                    }

                    $gt = DB::connection($this->db)->select("select a.kode_klpakun,b.nama,b.kode_akun,c.nama as nama_akun,b.umur,b.persen 
                    from fa_klp a 
                        inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on b.kode_akun=c.kode_akun and c.kode_lokasi = '$kode_lokasi'
                    where a.kode_lokasi='$kode_lokasi' and a.kode_klpfa='".$row['kode_klpfa']."' ");	
                    if(count($gt) > 0){
                        $kode_akun = $gt[0]->kode_akun;
                        $kode_klpakun = $gt[0]->kode_klpakun;
                        $umur = $gt[0]->umur;
                        $persen = $gt[0]->persen;
                    }else{
                        $msg = "Kode Kelompok Aktap (".$row['kode_klpfa'].") pada baris $no tidak ditemukan ";
                        $sts = false;
                        break;
                    }

                    $periodeSusut = substr($row['tgl_susut'],0,4).substr($row['tgl_susut'],5,2);
                    $nbfa = $nbfa2 = SaiHelpers::generateKode($this->db,"fa_asset", "no_fa", $kode_lokasi."-FA".substr($periode,2, 4).".", "001");
                    $nbfa = substr($nbfa,0, 10);
                    $idx = floatval(substr($nbfa2,10, 3));
                    $nu = $idx2 = "";
                    $jml = floatval($row['jumlah']);
                    $jum_det+= $jml;
                    $nsusut = round(floatval($row['nilai']) / floatval($umur));
                    for ($x = 0; $x < $jml; $x++) {
                        $idx2 = $idx;
                        if (strlen($idx2) == 1) $nu = "00".$idx2;
                        if (strlen($idx2) == 2) $nu = "0".$idx2;
                        if (strlen($idx2) == 3) $nu = $idx2;
                        
                        $nbfa2 = $nbfa.$nu;
                        $sql.= "insert into fa_asset(no_fa,kode_lokasi,kode_klpfa,kode_klpakun,kode_akun,umur,persen,nama,merk,tipe,no_seri,nilai,nilai_residu,kode_pp,kode_pp_susut,tgl_perolehan,tgl_susut,periode,periode_susut,progress,nik_user,tgl_input,catatan,kode_lokfa,nik_pnj,nilai_susut,jenis,akum_nilai) values ('".$nbfa2."','".$kode_lokasi."','".$row['kode_klpfa']."','".$kode_klpakun."','".$kode_akun."',".floatval($umur).",".floatval($persen).",'".$row['deskripsi']."','".$row['merk']."','".$row['tipe']."','".$row['no_seri']."',".floatval($row['nilai']).",".floatval($row['residu']).",'".$row['pp_aktap']."','".$row['pp_susut']."','".$row['tgl_perolehan']."','".$row['tgl_susut']."','".$periode."','".$periodeSusut."','2','".$nik."',getdate(),'".$row['no_bukti']."','-','-',".$nsusut.",'A',0)";
        
                        $sql.= "insert into fa_nilai(no_fa,kode_lokasi,no_bukti,dc,nilai,periode) values ('".$nbfa2."','".$kode_lokasi."','".$row['no_bukti']."','D',".floatval($row['nilai']).",'".$periode."')";

                        $idx = $idx + 1;
                    }

                    if($i % 100 == 0){
                        $sql = $begin.$sql.$commit;
                        DB::connection($this->db)->update($sql);
                        $sql = "";
                    }
                    if($i == count($r->input('data_aktap')) && ($i % 100 != 0) ){
                        $sql = $begin.$sql.$commit;
                        DB::connection($this->db)->update($sql);
                        $sql = "";
                    }
                    $i++;
                    $no++;	
                    
                }
            }
            
            if($sts){

                DB::connection($this->db)->commit();
                $sts = true;
                $msg = "Data Aktiva Tetap berhasil disimpan. ";  
            }else{
                DB::connection($this->db)->rollback();
            }

            $success['status'] = $sts;
            $success['message'] = $msg;
            if($sts){
                $success['jumlah_data'] = count($r->input('data_aktap'));
                $success['jumlah_detail'] = $jum_det;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function getKlpAkun(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($r->kode_klpfa) && $r->kode_klpfa != ""){
                $filter .= " and a.kode_klpfa = '$r->kode_klpfa'  ";
            }else{
                $filter .= "";
            }

            if(isset($r->kode_klpakun) && $r->kode_klpakun != ""){
                $filter .= " and a.kode_klpakun = '$r->kode_klpakun'  ";
            }else{
                $filter .= "";
            }
            $res = DB::connection($this->db)->select("select a.kode_klpakun,b.nama,b.kode_akun,c.nama as nama_akun,b.umur,b.persen 
            from fa_klp a 
            	 inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi 
            	 inner join masakun c on b.kode_akun=c.kode_akun and c.kode_lokasi = '$kode_lokasi'
            where a.kode_lokasi='$kode_lokasi' $filter ");	
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter .= " and a.kode_pp = '$r->kode_pp'  ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_pp, a.nama 
            from pp a
            where a.flag_aktif ='1'
            and a.kode_lokasi='$kode_lokasi' $filter ");	
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}

