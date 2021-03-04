<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Imports\JurnalImport;
use App\Exports\JurnalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\JurnalTmp;

class JurnalPenutupController extends Controller
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

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bukti,tanggal,periode,keterangan,nilai,nik_pembuat,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input  from jp where kode_lokasi='$kode_lokasi'	 
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
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function isUnik($isi,$no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select no_bukti from trans_m where no_dokumen = '".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '".$no_bukti."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['no_bukti'] = $auth[0]['no_bukti'];
        }else{
            $res['status'] = true;
        }
        return $res;
    }

    function isPostedAll($periode,$nik_user){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        
        $sts = true;
        $msg  = "";
        $sql2 = "exec sp_glma2_pp_dw_tmp '".$kode_lokasi."','".$periode."','".$nik_user."' ";
        $exec = DB::connection($this->db)->update($sql2);
        $totD = $totC = 0;	

        $sql = "select round(a.so_akhir,0) as so_akhir
                from glma_pp_tmp a where a.kode_lokasi ='".$kode_lokasi."' and a.nik_user = '".$nik_user."' and round(a.so_akhir,0) <> 0";
        $data = DB::connection($this->db)->select($sql);
        $data = json_decode(json_encode($data),true);
        if (count($data) > 0){
            $dataJU = $data;
            for ($i=0; $i < count($dataJU);$i++){
                $line = $dataJU[$i];
                $totD += ((floatval($line['so_akhir']) > 0) ?  floatval($line['so_akhir']) : 0 );
                $totC += ((floatval($line['so_akhir']) < 0) ? floatval($line['so_akhir']) : 0 );
            }					
        } 
        
        if (abs($totD + $totC) > 1) {
            $msg .= "Closing tidak valid. Neraca Lajur tidak balance.";
            $sts = false;
        }

		//--------------------------- control modul		
        $sqlSUSUT = "select count(a.no_fa) jum from fa_asset a 
                    left join (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut 
                            from fasusut_d where kode_lokasi = '".$kode_lokasi."' group by no_fa,kode_lokasi
                    ) d on a.no_fa=d.no_fa and a.kode_lokasi=d.kode_lokasi
                    where a.progress = '2' and a.kode_lokasi='".$kode_lokasi."' and (a.nilai-a.nilai_residu) > isnull(d.tot_susut,0) and a.periode_susut = '".$periode."'";
        $ck = DB::connection($this->db)->select($sqlSUSUT);
        $ck = json_decode(json_encode($ck),true);
		if (count($ck) > 0) {  
			if ($ck[0]['jum'] > 0){
				$sts = false;
				$msg.= "Data Modul FA masih ada yang belum disusutkan.(".$ck[0]['jum']." data)";
			}
		}

		//--------------------------- posting modul
		$sqlJU = "select count(no_bukti) as jum from trans_m where posted = 'F' and substring(periode,1,4) = '".substr($periode,0,4)."' and kode_lokasi='".$kode_lokasi."'";	
		$ck = DB::connection($this->db)->select($sqlJU);
        $ck = json_decode(json_encode($ck),true);
        if (count($ck) > 0) {  
			if ($ck[0]['jum'] > 0){
				$sts = false;
				$msg.= "Data Transaksi masih ada yang belum diposting.(".$ck[0]['jum']." data)";
			}
        }
        
        $result = array(
            'sts' => $sts,
            'msg' => $msg
        );
		return $result;
    }

    public function getDataAwal(){
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }
            $data = DB::connection($this->db)->select("select cast(value1 as varchar) as value1 from spro where kode_spro='MAXPRD' and kode_lokasi='".$kode_lokasi."'");
            if(count($data) > 0){
                $line = $data[0];							
                $maxPeriode = intval($line->value1);
            }else{
                $maxPeriode = 0;
            }
            $akunJP = "";
            $strSQL = "select top 1 m.kode_akun from masakun m 
                    inner join flag_relasi b on b.kode_akun = m.kode_akun and m.kode_lokasi = b.kode_lokasi 
                    where b.kode_flag = '999' and m.kode_lokasi = '".$kode_lokasi."' ";
            $data2 = DB::connection($this->db)->select($strSQL);	
            if (count($data2) > 0){
                $line = $data2[0];					
                $akunJP = $line->kode_akun;
            }

            $strSQL3 = "select a.flag,b.nama from spro a inner join karyawan b on a.flag=b.nik and a.kode_lokasi=b.kode_lokasi where kode_spro='JUAPP' and a.kode_lokasi='".$kode_lokasi."'";
            $data3 = DB::connection($this->db)->select($strSQL3);
			if (count($data3)){
				$line = $data3[0];							
                $nik_app = $line['flag'];
                $nama_app = $line['nama'];
			} else {
                $nik_app = '-';
                $nama_app = '-';
            }		
            
            $success['nik_app'] = $nik_app;
            $success['nama_app'] = $nama_app;
            $success['akun_jp'] = $akunJP;
            $success['max_periode'] = $maxPeriode;
            $success['status'] = true;
            $success['message'] = 'Sukses!';
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'deskripsi' => 'required',
            'nik_closing' => 'required',
            'nik_app' => 'required',
            'periode_aktif' => 'required',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);					
            $no_bukti = $this->generateKode("jp_d", "no_bukti", $kode_lokasi."-JP".substr($periode,2,4).".", "001");
            
            $nik_user=$request->nik_user;
            $akunJP = $request->akun_jp;

            $result = $this->isPostedAll($request->periode_aktif, $nik_user);
            if ($result['sts']) {	
                //--------------------------------------------------------- JURNAL PENUTUP -----------------------------------------------------------		
                $totD = $totC = 0;
                $sql2 = "select a.kode_akun,b.nama,a.kode_pp,a.so_awal,a.debet,a.kredit,a.so_akhir
                from glma_pp_tmp a inner join masakun b on b.kode_akun = a.kode_akun and b.kode_lokasi = a.kode_lokasi and b.modul = 'L' 
                where a.kode_lokasi ='".$kode_lokasi."' and a.nik_user = '".$nik."' and round(a.so_akhir,0) <> 0 ";									   
                $res = DB::connection($this->db)->select($sql2);
                $res = json_decode(json_encode($res),true);
                
                if (count($res) > 0){
                    $nilai = 0;							
                    for ($i=0; $i < count($res); $i++){
                        $line = $res[$i];							
                        $totD += (($line['so_akhir'] > 0 ) ? floatval($line['so_akhir']) :  0);
                        $totC += (($line['so_akhir'] < 0 ) ? floatval($line['so_akhir']) :  0);
                        
                        $dc = ((floatval($line['so_akhir']) > 0 ) ? "C" : "D" );
                        $nilai = abs(floatval($line['so_akhir']));
                        
                        $ins[$i] =  DB::connection($this->db)->insert("insert into jp_d (no_bukti,kode_lokasi,kode_akun,dc,nilai,kode_pp) values ('".$no_bukti."','".$kode_lokasi."','".$line['kode_akun']."','".$dc."',".$nilai.",'".$line['kode_pp']."')");			
                        
                        $ins2[$i] =  DB::connection($this->db)->insert("insert into gldt (no_bukti, kode_lokasi, tgl_input, nik_user, periode, no_dokumen, tanggal, nu, kode_akun, dc, nilai, nilai_curr, keterangan, modul, jenis, kode_curr, kurs, kode_pp, kode_drk, kode_cust, kode_vendor, no_fa, no_selesai, no_ref1, no_ref2, no_ref3) values ('".$no_bukti."', '".$kode_lokasi."', getdate(), '".$nik."', '".$periode."', '-', '".$request->tanggal."', ".$i.", '".$line['kode_akun']."', '".$dc."', ".$nilai.", ".$nilai.", '".$request->deskripsi."', 'JP', 'AKUN', 'IDR', 1, '".$line['kode_pp']."', '-', '-', '-', '-', '-', '-', '-', '-')");
                    }
                }						
                $totJP = $totD + $totC;						
                $ins3 =  DB::connection($this->db)->insert("insert into jp(no_bukti, tanggal, periode, keterangan, nik_pembuat, kode_lokasi, nilai, tgl_input, nik_user )values
                ('".$no_bukti."','".$request->tanggal."','".$periode."','Jurnal JP periode ".$periode."', '".$request->nik_closing."','".$kode_lokasi."',".floatval($totJP).",getdate(), '".$nik."')");
                
                $dc = ( ($totJP > 0) ?  "D" : "C" );
                $totJP = abs($totJP);
                $ins4 =  DB::connection($this->db)->insert("insert into gldt (no_bukti, kode_lokasi, tgl_input, nik_user, periode, no_dokumen, tanggal, nu, kode_akun, dc, nilai, nilai_curr, keterangan, modul, jenis, kode_curr, kurs, kode_pp, kode_drk, kode_cust, kode_vendor, no_fa, no_selesai, no_ref1, no_ref2, no_ref3) values ('".$no_bukti."', '".$kode_lokasi."', getdate(), '".$nik."', '".$periode."', '-', '".$request->tanggal."', 99999, '".$akunJP."', '".$dc."', ".$totJP.", ".$totJP.", '".$request->deskripsi."', 'JP', 'JP', 'IDR', 1, '".$request->kode_pp."', '-', '-', '-', '-', '-', '-', '-', '-')");	
                
                //--------------------------------------------------------- JURNAL PENUTUP -----------------------------------------------------------
                
                $msg = "Data Jurnal Penutup berhasil disimpan ";
                $sts = true;
                
                $success['msg_det'] = '';
                DB::connection($this->db)->commit();
            }
            else {
                $msg = "Closing tidak dapat dilanjutkan. Semua modul harus sudah valid untuk diclosing.";
                $sts = false;
                $success['msg_det'] = $result['msg'];
            }
            
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal Penutup gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    
    }

}

