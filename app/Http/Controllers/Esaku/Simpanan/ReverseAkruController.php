<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReverseAkruController extends Controller
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
                    $msg = "Transaksi tidak dapat disimpan karena tanggal di periode tersebut di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif modul $modul belum disetting.";
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

    public function generateNo(Request $request) {
        $this->validate($request, [    
            'tanggal' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-RSM".substr($periode,2,4).".", "0001");

            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="
            select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai1,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
            from trans_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.posted='F' and a.form='BTLBILL' ";

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
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required|max:100',
            'akun_piutang' => 'required|array',
            'akun_simpanan' => 'required|array',
            'no_agg' => 'required',
            'no_akru' => 'required|array',
            'no_kartu' => 'required|array',
            'nilai' => 'required|array',
            'angsuran' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-RSM".substr($periode,2,4).".", "0001");

            $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($getPP) > 0){
                $kode_pp = $getPP[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }	

            $cek = $this->doCekPeriode2('KP',$status_admin,$periode);

            if($cek['status']){

                $idx = 0; $total =0;
                for ($i=0;$i < count($request->no_kartu);$i++){						
                    //selisih antara nilai_bill - nilai_bayar, bisa dilunasi sebagai reverse jurnal (modul diisi BTLBILL--> supaya tidak dihitung sbg total angsuran)
                    $nilai = floatval($request->nilai[$i]) - floatval($request->angsuran[$i]);
                    
                    $ins2[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_kartu[$i]."','".$request->tanggal."',".$idx.",'".$request->akun_simpanan[$i]."','D',".$nilai.",".$nilai.",'".$request->keterangan."','BTLBILL','APSIMP','IDR',1,'".$kode_lokasi."','-','-','-','-','-','-','-','-')");
                    $idx++;
                    
                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_kartu[$i]."','".$request->tanggal."',".$idx.",'".$request->akun_piutang[$i]."','C',".$nilai.",".$nilai.",'".$request->keterangan."','BTLBILL','ARSIMP','IDR',1,'".$kode_lokasi."','-','-','-','-','-','-','-','-')");
                    $idx++;
                    
                    $ins4[$i] = DB::connection($this->db)->insert("insert into kop_simpangs_d (no_angs,no_simp,no_bill,akun_piutang,nilai,kode_lokasi,dc,periode,modul,no_agg) values ('".$no_bukti."','".$request->no_kartu[$i]."','".$request->no_akru[$i]."','".$request->akun_piutang[$i]."',".$nilai.",'".$kode_lokasi."','D','".$periode."','BTLBILL','".$request->no_agg."')");	
                    
                    $total += $nilai;
                }		
                
                if($total > 0){

                    $ins1 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$kode_lokasi."','".$periode."','KP','BTLBILL','F','0','0','".$kode_pp."','".$request->tanggal."','-','".$request->keterangan."','IDR',1,".floatval($total).",0,0,'-','-','-','-','-','-','-','-','-')");
                    
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['kode'] = $no_bukti;
                    $success['message'] = "Data Reverse Akru Simpanan berhasil disimpan";
                
                }else{

                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['kode'] = "-";
                    $success['message'] = "Transaksi tidak valid. Nilai pembatalan akru tidak boleh kurang/sama dgn 0.";
                }
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
            $success['message'] = "Data Reverse Akru Simpanan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
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
            'no_bukti' => 'required|max:20',
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required|max:100',
            'akun_piutang' => 'required|array',
            'akun_simpanan' => 'required|array',
            'no_agg' => 'required',
            'no_akru' => 'required|array',
            'no_kartu' => 'required|array',
            'nilai' => 'required|array',
            'angsuran' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            
            $del2 = DB::connection($this->db)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $del4 = DB::connection($this->db)->table('kop_simpangs_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_angs', $no_bukti)
            ->delete();

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($getPP) > 0){
                $kode_pp = $getPP[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $cek = $this->doCekPeriode2('KP',$status_admin,$periode);

            if($cek['status']){

                $idx = 0; $total =0;
                for ($i=0;$i < count($request->no_kartu);$i++){						
                    //selisih antara nilai_bill - nilai_bayar, bisa dilunasi sebagai reverse jurnal (modul diisi BTLBILL--> supaya tidak dihitung sbg total angsuran)
                    $nilai = floatval($request->nilai[$i]) - floatval($request->angsuran[$i]);
                    
                    $ins2[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_kartu[$i]."','".$request->tanggal."',".$idx.",'".$request->akun_simpanan[$i]."','D',".$nilai.",".$nilai.",'".$request->keterangan."','BTLBILL','APSIMP','IDR',1,'".$kode_lokasi."','-','-','-','-','-','-','-','-')");
                    $idx++;
                    
                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_kartu[$i]."','".$request->tanggal."',".$idx.",'".$request->akun_piutang[$i]."','C',".$nilai.",".$nilai.",'".$request->keterangan."','BTLBILL','ARSIMP','IDR',1,'".$kode_lokasi."','-','-','-','-','-','-','-','-')");
                    $idx++;
                    
                    $ins4[$i] = DB::connection($this->db)->insert("insert into kop_simpangs_d (no_angs,no_simp,no_bill,akun_piutang,nilai,kode_lokasi,dc,periode,modul,no_agg) values ('".$no_bukti."','".$request->no_kartu[$i]."','".$request->no_akru[$i]."','".$request->akun_piutang[$i]."',".$nilai.",'".$kode_lokasi."','D','".$periode."','BTLBILL','".$request->no_agg."')");	
                    
                    $total += $nilai;
                }		
                
                if($total > 0){

                    $ins1 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$kode_lokasi."','".$periode."','KP','BTLBILL','F','0','0','".$kode_pp."','".$request->tanggal."','-','".$request->keterangan."','IDR',1,".floatval($total).",0,0,'-','-','-','-','-','-','-','-','-')");
                    
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['kode'] = $no_bukti;
                    $success['message'] = "Data Reverse Akru Simpanan berhasil diubah";
                
                }else{

                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['kode'] = "-";
                    $success['message'] = "Transaksi tidak valid. Nilai pembatalan akru tidak boleh kurang/sama dgn 0.";
                }
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
            $success['no_bukti'] = "-";
            $success['message'] = "Data Reverse Akru Simpanan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            $del2 = DB::connection($this->db)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            $del4 = DB::connection($this->db)->table('kop_simpangs_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_angs', $no_bukti)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Reverse Akru Simpanan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Reverse Akru Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getNoKartu(Request $request)
    {
        $this->validate($request,[
            'no_agg' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = "";
            if(isset($request->no_simp)){
                if($request->no_simp != ""){
                    $filter.= " and a.no_simp ='$request->no_simp'  ";
                }else{
                    $filter.= "";
                }
            }else{
                $filter.= "";
            }

            $sql="select a.no_simp, b.nama from kop_simp_m a 
            inner join kop_simp_param b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_agg='".$request->no_agg."' $filter ";
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

    public function getDaftarAkru(Request $request)
    {
        $this->validate($request,[
            'no_simp' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.no_simp,a.jenis,b.nilai,b.akun_piutang,b.akun_titip,b.periode,b.no_bill,c.keterangan as ket,isnull(d.bayar,0) as bayar 
            from  kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp  and a.kode_lokasi=b.kode_lokasi 
                inner join trans_m c on b.no_bill=c.no_bukti and b.kode_lokasi=c.kode_lokasi 	
                left outer join (select y.no_simp, y.no_bill, y.kode_lokasi, sum(case dc when 'D' then y.nilai else -y.nilai end) as bayar
                        from kop_simpangs_d y inner join trans_m x on y.no_angs=x.no_bukti and y.kode_lokasi=x.kode_lokasi 
                        where y.no_simp = '".$request->no_simp."' and y.kode_lokasi='".$kode_lokasi."' 
                        group by y.no_simp, y.no_bill, y.kode_lokasi) d on b.no_simp=d.no_simp and b.no_bill=d.no_bill and b.kode_lokasi=d.kode_lokasi 
                where  a.no_simp = '".$request->no_simp."' and b.nilai+isnull(d.bayar,0)>0 and a.kode_lokasi= '".$kode_lokasi."' order by a.no_simp,b.periode"; //and d.bayar is null <--- sudah bayar pun selisihnya bisa di lunasi sbg pembatalan
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

    public function getAnggota(Request $request)
    {
        $this->validate($request,[
            'status_simpan' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->status_simpan == 1){

                $strSQL = "select no_agg, nama from kop_agg where kode_lokasi= '".$kode_lokasi."' "; 
            }else{
                $strSQL = "select a.no_simp, b.nama from kop_simp_m a 
                inner join kop_simp_param b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' and a.no_simp='".$request->no_simp."' ";
            }
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

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required',
            'no_simp' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.tanggal,a.keterangan, b.no_simp,c.no_agg 
            from trans_m a inner join kop_simpangs_d b on a.no_bukti=b.no_angs and a.kode_lokasi=b.kode_lokasi 							 
                inner join kop_simp_m c on b.no_simp=c.no_simp and c.kode_lokasi=b.kode_lokasi 
                where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."'";	
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);

            $strSQL2 = "select a.no_simp,a.jenis,b.nilai,b.akun_piutang,b.akun_titip,b.periode,b.no_bill,c.keterangan as ket,isnull(d.bayar,0) as bayar 
            from  kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp  and a.kode_lokasi=b.kode_lokasi 
            inner join trans_m c on b.no_bill=c.no_bukti and b.kode_lokasi=c.kode_lokasi 
            inner join kop_simpangs_d dd on b.no_simp=dd.no_simp and b.no_bill=dd.no_bill and b.kode_lokasi=dd.kode_lokasi 
            left outer join   
                (select y.no_simp, y.no_bill, y.kode_lokasi, sum(case dc when 'D' then y.nilai else -y.nilai end) as bayar 
                from kop_simpangs_d y inner join trans_m x on y.no_angs=x.no_bukti and y.kode_lokasi=x.kode_lokasi 
                where y.no_angs<>'".$request->no_bukti."' and y.no_simp = '".$request->no_simp."' and y.kode_lokasi='".$kode_lokasi."'                 
                group by y.no_simp, y.no_bill, y.kode_lokasi) d on b.no_simp=d.no_simp and b.no_bill=d.no_bill and b.kode_lokasi=d.kode_lokasi 							 
            where dd.no_angs='".$request->no_bukti."' and a.no_simp = '".$request->no_simp."' and a.kode_lokasi= '".$kode_lokasi."' order by a.no_simp,b.periode"; 
            
            $rs2 = DB::connection($this->db)->select($strSQL2);
            $res2 = json_decode(json_encode($rs2),true);

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
