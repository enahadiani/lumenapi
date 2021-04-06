<?php

namespace App\Http\Controllers\Esaku\Anggaran;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PengajuanRRAController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }
    
    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select a.no_pdrk,convert(varchar,a.tanggal,103) as tgl,b.no_dokumen,a.keterangan,a.progress,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status 
            from rra_pdrk_m a 
            inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi
            where a.modul = 'MULTI' and a.kode_lokasi='".$kode_lokasi."' and a.progress in ('0','R') order by a.tanggal 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'nik_app' => 'required',
            'donor' => 'required',
            'kode_pp_terima' => 'required',
            'kode_akun_terima' => 'required',
            'bulan_terima' => 'required',
            'nilai_terima' => 'required',
            'kode_pp_aktif' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'bulan' => 'required|array',
            'saldo' => 'required|array',
            'nilai' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            if (floatval($request->nilai_terima) != floatval($request->donor)) {
                $msg = "Transaksi tidak valid. Total Terima dan Pemberi tidak sama.";
                $sts = false;						
            }else{
                if (floatval($request->nilai_terima) <= 0 || floatval($request->donor) <= 0) {
                    $msg = "Transaksi tidak valid. Total Terima atau Pemberi tidak boleh nol atau kurang.";
                    $sts = false;						
                }else{

                    if (intval(substr($periode_aktif,0,4)) > intval(substr($periode,0,4))){
                        $msg = "Periode transaksi tidak valid. Periode transaksi harus dalam tahun anggaran (periode sistem) yang sama.["+substr($periode_aktif,0,4)+"]";
                        $sts = false;
                    }else{

                        $no_bukti = $this->generateKode("anggaran_m", "no_agg", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");
            
                        $ins = DB::connection($this->db)->insert("insert into anggaran_m (no_agg,kode_lokasi,no_dokumen,tanggal,keterangan,tahun,kode_curr,nilai,tgl_input,nik_user,posted,no_del,nik_buat,nik_setuju,jenis) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->deskripsi','".substr($request->periode,0,4)."','IDR',".intval($request->donor).",getdate(),'".$nik."','T','-','".$nik."','".$request->nik_app."','RR')");	
                            
                        $ins2 = DB::connection($this->db)->insert("insert into rra_pdrk_m(no_pdrk,kode_lokasi,keterangan,kode_pp,kode_bidang,jenis_agg,tanggal,periode,nik_buat,nik_app1,nik_app2,nik_app3,sts_pdrk,justifikasi, nik_user, tgl_input,progress,modul) values ('".$no_bukti."','".$kode_lokasi."','".$request->deskripsi."','".$request->kode_pp_aktif."','-','-','".$request->tanggal."','".$periode."','".$nik."','".$nik."','".$request->nik_app."','".$request->nik_app."','RRR','-','".$nik."',getdate(),'0','MULTI')");
                        
                        $per = "";
                        if (count($request->kode_akun) > 0){
                            for ($i=0;$i < count($request->kode_akun);$i++){
                                $per = substr($periode,0,4).''.$request->bulan[$i];
                                $ins3[$i] = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_akun[$i]."','".$request->kode_pp[$i]."','-','".$periode."',".$request->saldo[$i].",".$request->nilai[$i].",'C','-')");
            
                                $ins4[$i] = DB::connection($this->db)->insert("insert into anggaran_d(no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai_sat,nilai,dc,satuan,nik_user,tgl_input,modul) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_pp[$i]."','".$request->kode_akun[$i]."','-',1,'".$periode."',".$request->nilai[$i].",".$request->nilai[$i].",'C','-','".$nik."',getdate(),'RRA')");
                            }
                        }
            
                        $per2 = substr($periode,0,4).''.$request->bulan_terima;
                        $ins5 = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',999,'".$request->kode_akun_terima."','".$request->kode_pp_terima."','-','".$per2."',0,".floatval($request->nilai_terima).",'D','-')");
            
                        DB::connection($this->db)->commit();
                        $sts = true;
                        $msg = "Data Pengajuan RRA berhasil disimpan ";
                    }
                }

            }
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan RRA gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'nik_app' => 'required',
            'donor' => 'required',
            'kode_pp_terima' => 'required',
            'kode_akun_terima' => 'required',
            'bulan_terima' => 'required',
            'nilai_terima' => 'required',
            'kode_pp_aktif' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'bulan' => 'required|array',
            'saldo' => 'required|array',
            'nilai' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $request->no_bukti;
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            if (floatval($request->nilai_terima) != floatval($request->donor)) {
                $msg = "Transaksi tidak valid. Total Terima dan Pemberi tidak sama.";
                $sts = false;						
            }else{
                if (floatval($request->nilai_terima) <= 0 || floatval($request->donor) <= 0) {
                    $msg = "Transaksi tidak valid. Total Terima atau Pemberi tidak boleh nol atau kurang.";
                    $sts = false;						
                }else{

                    if (intval(substr($periode_aktif,0,4)) > intval(substr($periode,0,4))){
                        $msg = "Periode transaksi tidak valid. Periode transaksi harus dalam tahun anggaran (periode sistem) yang sama.["+substr($periode_aktif,0,4)+"]";
                        $sts = false;
                    }else{

                        $del1 = DB::connection($this->db)->table('anggaran_m')->where('kode_lokasi', $kode_lokasi)->where('no_agg', $no_bukti)->delete();

                        $del2 = DB::connection($this->db)->table('anggaran_d')->where('kode_lokasi', $kode_lokasi)->where('no_agg', $no_bukti)->delete();

                        $del3 = DB::connection($this->db)->table('rra_pdrk_m')->where('kode_lokasi', $kode_lokasi)->where('no_pdrk', $no_bukti)->delete();

                        $del4 = DB::connection($this->db)->table('rra_pdrk_d')->where('kode_lokasi', $kode_lokasi)->where('no_pdrk', $no_bukti)->delete();

            
                        $ins = DB::connection($this->db)->insert("insert into anggaran_m (no_agg,kode_lokasi,no_dokumen,tanggal,keterangan,tahun,kode_curr,nilai,tgl_input,nik_user,posted,no_del,nik_buat,nik_setuju,jenis) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->deskripsi','".substr($request->periode,0,4)."','IDR',".intval($request->donor).",getdate(),'".$nik."','T','-','".$nik."','".$request->nik_app."','RR')");	
                            
                        $ins2 = DB::connection($this->db)->insert("insert into rra_pdrk_m(no_pdrk,kode_lokasi,keterangan,kode_pp,kode_bidang,jenis_agg,tanggal,periode,nik_buat,nik_app1,nik_app2,nik_app3,sts_pdrk,justifikasi, nik_user, tgl_input,progress,modul) values ('".$no_bukti."','".$kode_lokasi."','".$request->deskripsi."','".$request->kode_pp_aktif."','-','-','".$request->tanggal."','".$periode."','".$nik."','".$nik."','".$request->nik_app."','".$request->nik_app."','RRR','-','".$nik."',getdate(),'0','MULTI')");
                        
                        $per = "";
                        if (count($request->kode_akun) > 0){
                            for ($i=0;$i < count($request->kode_akun);$i++){
                                $per = substr($periode,0,4).''.$request->bulan[$i];
                                $ins3[$i] = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_akun[$i]."','".$request->kode_pp[$i]."','-','".$periode."',".$request->saldo[$i].",".$request->nilai[$i].",'C','-')");
            
                                $ins4[$i] = DB::connection($this->db)->insert("insert into anggaran_d(no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai_sat,nilai,dc,satuan,nik_user,tgl_input,modul) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_pp[$i]."','".$request->kode_akun[$i]."','-',1,'".$periode."',".$request->nilai[$i].",".$request->nilai[$i].",'C','-','".$nik."',getdate(),'RRA')");
                            }
                        }
            
                        $per2 = substr($periode,0,4).''.$request->bulan_terima;
                        $ins5 = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',999,'".$request->kode_akun_terima."','".$request->kode_pp_terima."','-','".$per2."',0,".floatval($request->nilai_terima).",'D','-')");
            
                        DB::connection($this->db)->commit();
                        $sts = true;
                        $msg = "Data Pengajuan RRA berhasil diubah ";
                    }
                }

            }
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan RRA gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $del1 = DB::connection($this->db)->table('anggaran_m')->where('kode_lokasi', $kode_lokasi)->where('no_agg', $no_bukti)->delete();

            $del2 = DB::connection($this->db)->table('anggaran_d')->where('kode_lokasi', $kode_lokasi)->where('no_agg', $no_bukti)->delete();

            $del3 = DB::connection($this->db)->table('rra_pdrk_m')->where('kode_lokasi', $kode_lokasi)->where('no_pdrk', $no_bukti)->delete();

            $del4 = DB::connection($this->db)->table('rra_pdrk_d')->where('kode_lokasi', $kode_lokasi)->where('no_pdrk', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pengajuan RRA berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan RRA gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select b.no_dokumen,a.nik_app3,a.tanggal,a.keterangan 
            from rra_pdrk_m a inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi 
            where a.no_pdrk='$request->no_bukti' and a.kode_lokasi='$kode_lokasi' ");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_akun,c.nama as nama_akun,a.nilai 
            from rra_pdrk_d a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            				    inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi  
            where a.no_pdrk='$request->no_bukti' and a.dc ='C'");
            $res2= json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->db)->select("select substring(a.periode,5,2) as bulan,a.kode_pp,a.kode_akun,a.nilai 
            from rra_pdrk_d a 
            where a.no_pdrk='$request->no_bukti' and a.dc ='D' ");
            $res3= json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_penerima'] = $res3;
                $success['detail_pemberi'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail_penerima'] = [];
                $success['detail_pemberi'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNIKApp(Request $request)
    {
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='$kode_lokasi' ");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.flag,b.nama from spro a inner join karyawan b on a.flag=b.nik and a.kode_lokasi=b.kode_lokasi where kode_spro='GARAPP' and a.kode_lokasi='$kode_lokasi' ");
            if(count($res2) > 0){
                $nik_app = $res2[0]->flag;
            }else{
                $nik_app = "";
            }
            $success['nik_app'] = $nik_app;

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

    public function getPPTerima(Request $request)
    {
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where b.nik='$nik' and a.flag_aktif= '1' and a.kode_lokasi='$kode_lokasi' ");						
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

    public function getAkunTerima(Request $request)
    {
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a where a.kode_lokasi='$kode_lokasi' and a.status_gar='0' ");						
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

    public function getSaldo(Request $request)
    {
        $this->validate($request,[
            'bulan_terima' => 'required',
            'kode_pp_terima' => 'required',
            'kode_akun_terima' => 'required',
            'periode' => 'required',
            'bulan_terima' => 'required',
            'no_bukti' => 'required'
        ]);
        
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per_terima = substr($request->periode,0,4).''.$request->bulan_terima;

            $res = DB::connection($this->db)->select("select dbo.fn_garakunpp('$request->kode_akun_terima','$request->kode_pp_terima','$per_terima','$kode_lokasi','$no_bukti') as gar ");						
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $line = $res[0];
                $data = explode(";",$line['gar']);
                $sls = floatval($data[0]) - floatval($data[1]);
                $success['saldo'] = floatval($sls);
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['saldo'] = 0;
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkun(Request $request)
    {
        $this->validate($request,[
            'tahun' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select distinct a.kode_akun,a.nama from masakun a inner join anggaran_d b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.periode like '".$request->tahun."%' where a.status_gar='0' and a.block='0' and a.kode_lokasi='$kode_lokasi' ");						
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

    public function getPP(Request $request)
    {
        $this->validate($request,[
            'tahun' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where b.nik='$nik' and a.flag_aktif= '1' and a.kode_lokasi = '$kode_lokasi'");						
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

