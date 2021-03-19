<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Imports\PenerimaanImport;
use App\Exports\PenerimaanExport;

class PenerimaanUploadController extends Controller
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

    function namaPeriode($periode){
        $bulan = substr($periode,4,2);
        $tahun = substr($periode,0,4);
        switch ($bulan){
            case 1 : case '1' : case '01': $bulan = "Januari"; break;
            case 2 : case '2' : case '02': $bulan = "Februari"; break;
            case 3 : case '3' : case '03': $bulan = "Maret"; break;
            case 4 : case '4' : case '04': $bulan = "April"; break;
            case 5 : case '5' : case '05': $bulan = "Mei"; break;
            case 6 : case '6' : case '06': $bulan = "Juni"; break;
            case 7 : case '7' : case '07': $bulan = "Juli"; break;
            case 8 : case '8' : case '08': $bulan = "Agustus"; break;
            case 9 : case '9' : case '09': $bulan = "September"; break;
            case 10 : case '10' : case '10': $bulan = "Oktober"; break;
            case 11 : case '11' : case '11': $bulan = "November"; break;
            case 12 : case '12' : case '12': $bulan = "Desember"; break;
            default: $bulan = null;
        }
    
        return $bulan.' '.$tahun;
    }

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            if ($status == "A") {

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
            }else{

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
            }

            $auth = DB::connection($this->db)->select($strSQL);
            $auth = json_decode(json_encode($auth),true);
            if(count($auth) > 0){
                $perValid = true;
                $msg = "ok";
            }else{
                if ($status == "A") {

                    $strSQL2 = "select per_awal2 as per_awal,per_akhir2 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' ";
                }else{
    
                    $strSQL2 = "select per_awal1 as per_awal,per_akhir1 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."'";
                }
                $get = DB::connection($this->db)->select($strSQL2);
                if(count($get) > 0){
                    $per_awal = $this->namaPeriode($get[0]->per_awal);
                    $per_akhir = $this->namaPeriode($get[0]->per_akhir);
                    $msg = "Transaksi tidak dapat dieksekusi karena tanggal di periode tersebut di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat dieksekusi karena periode aktif modul $modul belum disetting.";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    function nextNPeriode($periode, $n) 
    {
        $bln = floatval(substr($periode,4,2));
        $thn = floatval(substr($periode,0,4));
        for ($i = 1; $i <= $n;$i++){
            if ($bln < 12) $bln++;
            else {
                $bln = 1;
                $thn++;
            }
        }
        if ($bln < 10) $bln = "0".$bln;
        return $thn."".$bln;
    }

    public function doHitungAR($dataJU) {
        $row = array();
        $dtJurnal = array();
		$nemu = false;
		$ix=0; 
        $dtJrnl = 0;
		for ($i=0;$i < count($dataJU);$i++){
			$line = $dataJU[$i];
			if (floatval($line['lunas']) != 0){
				$kdAkun = $line['akun_piutang'];				
				$nemu = false;
				$ix = 0;
				for ($j=0;$j < count($dtJurnal); $j++){		
				  if ($kdAkun == $dtJurnal[$j]['kode_akun']){
					$nemu = true;
					$row = $dtJurnal[$j];
					$ix = $j;
					break;
				  }
				}
				if (!$nemu){
					
					$row["kode_akun"] = $kdAkun;
					$row["nilai"] = floatval($line['lunas']);
					$dtJrnl++;
                    $dtJurnal[$dtJrnl] = $row;						
				}
				else $dtJurnal[$ix]['nilai'] = $row["nilai"] + floatval($line['lunas']);
			}
		}
		$gridAR = $dtJurnal;
        return $gridAR;
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
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required|max:100',
            'nilai_deposit' => 'required',
            'nilai_bayar' => 'required',
            'no_dokumen' => 'required',
            'akun_kas' => 'required',
            'no_agg' => 'required',
            'jenis' => 'required|in:MI,BM',
            'nilai_tagihan' => 'required|array',
            'akun_piutang' => 'required|array',
            'no_akru' => 'required|array',
            'no_kartu' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-STP".substr($periode,2,4).".", "0001");

            $cek = $this->doCekPeriode2('KP',$status_admin,$periode);

            if($cek['status']){

                $ins1 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','KP','LOAD','F','0','0','".$kode_pp."','".$request->tanggal."','-','".$request->keterangan."','IDR',1,".floatval($request->total_tagihan).",0,0,'-','-','-','".$request->akun_kastitip."','-','-','$nik','-','-')");
					
				$ins2 = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',0,'".$request->akun_kastitip."','D',".floatval($request->total_tagihan).",".floatval($request->total_tagihan).",'".$request->keterangan."','LOAD','TTP','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");
					
				$gridAR = $this->doHitungAR($request->detail_tagihan);
				
                for ($i=0; $i < count($gridAR); $i++){
                    $line = $gridAR[$i];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$i.",'".$line['kode_akun']."','C',".floatval($line['nilai']).",".floatval($line['nilai']).",'".$request->keterangan."','LOAD','PIUT','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");
                }											
                
                for ($i=0;$i < count($request->detail_tagihan); $i++){
                    $line = $request->detail_tagihan[$i];
                    if (floatval($line['lunas']) != 0){
                        $ins = DB::connection($this->db)->insert("insert into kop_simpangs_d (no_angs,no_simp,no_bill,akun_piutang,nilai,kode_lokasi,dc,periode,modul,no_agg,jenis) values ('".$no_bukti."','".$line['no_simp']."','".$line['no_bill']."','".$line['akun_piutang']."',".floatval($line['lunas']).",'".$kode_lokasi."','D','".$periode."','LOAD','".$line['no_agg']."','SIMP')");
                    }
                }		

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode'] = $no_bukti;
                $success['message'] = "Data Penerimaan Simpanan berhasil disimpan";

                
            }else{

                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['kode'] = "-";
                $success['message'] = $cek["message"];
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penerimaan Simpanan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function getAkunKasTitip(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != ""){
                    $filter.= " and a.kode_akun ='$request->kode_akun'  ";
                }else{
                    $filter.= "";
                }
            }else{
                $filter.= "";
            }
			
            
            $sql="select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag='036' where a.kode_lokasi='".$kode_lokasi."' $filter ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

			$sql2 = "select a.flag,b.nama from spro a inner join masakun b on a.flag=b.kode_akun and a.kode_lokasi=b.kode_lokasi where kode_spro='KBTTP' and a.kode_lokasi='".$kode_lokasi."'";
            $res2 = DB::connection($this->db)->select($sql2);
            if(count($res2) > 0){
                $success['kode_akundefault'] = $res2[0]->flag;
                $success['nama_akundefault'] = $res2[0]->nama;
            }else{
                $success['kode_akundefault'] = '-';
                $success['nama_akundefault'] = '-'; 
            }
            
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

    public function getTagihan(Request $request)
    {
        $this->validate($request,[
            'tanggal' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $strSQL = "select f.no_agg,f.nama as nama_agg,b.no_bill,a.no_simp,a.jenis,e.nama,b.akun_piutang,b.periode,b.nilai-isnull(d.bayar,0) as saldo,0 as lunas
            from  kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp  and a.kode_lokasi=b.kode_lokasi and b.modul <> 'BSIMP' 
                inner join trans_m c on b.no_bill=c.no_bukti and b.kode_lokasi=c.kode_lokasi 
                inner join kop_simp_param e on a.kode_param=e.kode_param and a.kode_lokasi=e.kode_lokasi 
                inner join kop_agg f on b.no_agg=f.no_agg and a.kode_lokasi=f.kode_lokasi 
                left outer join 
                    (select y.no_simp, y.no_bill, y.kode_lokasi, sum(case dc when 'D' then y.nilai else -y.nilai end) as bayar 
                    from kop_simpangs_d y inner join trans_m x on y.no_angs=x.no_bukti and y.kode_lokasi=x.kode_lokasi 
                    where y.periode<='".$periode."' and y.kode_lokasi='".$kode_lokasi."' and y.modul <> 'BSIMP' 
                    group by y.no_simp, y.no_bill, y.kode_lokasi) d on b.no_simp=d.no_simp and b.no_bill=d.no_bill and b.kode_lokasi=d.kode_lokasi
            where a.status_bayar = 'PGAJI' and b.periode<='".$periode."' and b.nilai-isnull(d.bayar,0)>0 and a.kode_lokasi= '".$kode_lokasi."' order by f.no_agg,e.nu"; //and d.bayar is null <--- sudah bayar pun selisihnya bisa di lunasi sbg pembatalan
            $res = DB::connection($this->db)->select($strSQL);
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

    public function validateAgg($no_agg,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select no_agg from kop_agg where no_agg='$no_agg' and kode_lokasi='$kode_lokasi'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "No Anggota $no_agg tidak valid. ";
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
            
            $del1 = DB::connection($this->db)->table('kop_bayar_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new PenerimaanImport($request->nik_user),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateAgg(strval($row[0]),$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $ins[$i] = DB::connection($this->db)->insert("insert into kop_bayar_tmp (no_agg,nilai_bayar,nik_user,tgl_input,kode_lokasi,sts_upload,ket_upload,nu) values ('".$row[0]."','".$row[1]."','$nik',getdate(),'$kode_lokasi','$sts','$ket',$no)
                    ");
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
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $this->validate($request, [
            'nik_user' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new PenerimaanExport($request->nik_user,$request->type), 'Penerimaan_'.$request->nik_user.'.xlsx');
        }else{
            return Excel::download(new PenerimaanExport($request->nik_user,$request->type), 'Penerimaan_'.$request->nik_user.'.xlsx');
        }
    }

    

    public function getTmp(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required'
        ]);

        $nik_user = $request->nik_user;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.no_agg.b.nama,a.nilai_bayar,a.nu 
            from kop_bayar_tmp a
            left join kop_agg b on a.no_agg=b.no_agg and a.kode_lokasi=b.kode_lokasi
            where a.nik_user = '".$nik_user."'
            order by a.nu";
            $res = DB::connection($this->db)->select($sql);
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

    public function getNoBukti(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = "";
            if(isset($request->tanggal)){
                if($request->tanggal != ""){
                    $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                    $filter.= " and periode ='$periode'  ";
                }else{
                    $filter.= "";
                }
            }else{
                $filter.= "";
            }

            if(isset($request->no_bukti)){
                if($request->no_bukti != ""){
                    $filter.= " and no_bukti ='$request->no_bukti'  ";
                }else{
                    $filter.= "";
                }
            }else{
                $filter.= "";
            }
			
            
            $sql="select no_bukti, keterangan from trans_m where form = 'LOAD' and posted='F' and kode_lokasi='".$kode_lokasi."' $filter ";
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

    public function loadDataHapus(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = "";

            if(isset($request->no_bukti)){
                if($request->no_bukti != ""){
                    $filter.= " and no_bukti ='$request->no_bukti'  ";
                }else{
                    $filter.= "";
                }
            }else{
                $filter.= "";
            }
			
            
            $sql="select a.tanggal,a.keterangan,a.periode,a.no_ref1,a.nik1 as nik_app,a.nilai1 
            from trans_m a 
            where kode_lokasi='".$kode_lokasi."' $filter ";
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

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2('KP',$status_admin,$periode);

            if($cek['status']){

                $del = DB::connection($this->db)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();
                
                $del2 = DB::connection($this->db)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('kop_simpangs_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_angs', $no_bukti)
                ->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Penerimaan Simpanan berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penerimaan Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }



}
