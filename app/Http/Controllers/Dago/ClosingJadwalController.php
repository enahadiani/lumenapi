<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class ClosingJadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvdago';
    public $guard = 'dago';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
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

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
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
                    $msg = "Transaksi tidak dapat disimpan karena tanggal di periode $periode ditutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif belum disetting ";
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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_closing as no_bukti,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai as total,isnull(c.status_closing,'-') as closing 
            from dgw_closing_d a 
            inner join trans_m b on a.no_closing=b.no_bukti and a.kode_lokasi=b.kode_lokasi 	
            left join (select a.no_jadwal,a.no_paket,a.kode_lokasi,c.status_closing 
                       from dgw_reg a 
                        left join dgw_pembayaran_d c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
                        where c.status_closing='closing' 
                        group by a.no_jadwal,a.no_paket,a.kode_lokasi,c.status_closing 
                        ) c on a.no_jadwal=c.no_jadwal and a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 				 					 
            where a.kode_lokasi='$kode_lokasi' and b.posted ='F' and b.form='CLOSING' 
            order by a.tanggal ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRegistrasi(Request $request)
    {
        $this->validate($request,[
            'no_paket' => 'required',
            'no_jadwal' => 'required',
            'kurs' => 'required',
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select 
            a.no_reg,b.no_peserta+' - '+b.nama as nama, f.kode_akun as akun_titip,f.akun_piutang,f.akun_pdpt, (a.harga+a.harga_room) as harga_paket, isnull(d.biaya_tambah,0)-a.diskon as biaya_tambah, isnull(h.biaya_dok,0) as biaya_dok, 						
            isnull(e.bayar_p,0) as bayar_p, isnull(e.bayar_t,0) as bayar_t, isnull(e.bayar_m,0) as bayar_m, 
            (a.harga+a.harga_room) - isnull(e.bayar_p,0) as saldo_p,  
            isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0) as saldo_t, 
            isnull(h.biaya_dok,0) - isnull(e.bayar_m,0) as saldo_m, 
            isnull(e.bayar_p_idr,0) as bayar_p_idr, 
            isnull(e.bayar_p_idr,0) + isnull(e.bayar_t,0) + isnull(e.bayar_m,0) as  tot_bayaridr, 
            (case when ((a.harga+a.harga_room) - isnull(e.bayar_p,0) <> 0) then (  ((a.harga+a.harga_room) - isnull(e.bayar_p,0)) * ".floatval($request->kurs)."  ) else 0 end) 
            + (isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0)) 
            + (isnull(h.biaya_dok,0) - isnull(e.bayar_m,0)) 
            as tot_saldo_idr, 
            (a.harga+a.harga_room) * ".floatval($request->kurs)." as pdpt_paket_idr 
            from dgw_reg a  
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi and c.no_closing='-' 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi =b.kode_lokasi 
            inner join dgw_paket g on a.no_paket=g.no_paket and a.kode_lokasi =g.kode_lokasi 
            inner join dgw_jenis_produk f on g.kode_produk=f.kode_produk and g.kode_lokasi =f.kode_lokasi 
            left join ( 
              select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_tambah 
              from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='TAMBAHAN' 
              where a.kode_lokasi ='".$kode_lokasi."' 
              group by a.kode_lokasi,a.no_reg 
            ) d on a.no_reg=d.no_reg and a.kode_lokasi=d.kode_lokasi 
            left join ( 
              select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_dok 
              from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='DOKUMEN' 
              where a.kode_lokasi ='".$kode_lokasi."' 
              group by a.kode_lokasi,a.no_reg 
            ) h on a.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi 
            left join (						 
              select  no_reg,kode_lokasi,sum(nilai_p) as bayar_p,sum(nilai_t) as bayar_t,sum(nilai_m) as bayar_m,sum(nilai_p * kurs) as bayar_p_idr 
              from dgw_pembayaran where kode_lokasi ='".$kode_lokasi."' 
              group by kode_lokasi,no_reg 						 
            ) e on a.no_reg=e.no_reg and a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi ='".$kode_lokasi."' and a.no_paket='".$request->no_paket."' and a.no_jadwal='".$request->no_jadwal."'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $success['sql'] = $sql;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
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
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required',
            'kode_pp' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'no_paket' => 'required',
            'no_jadwal' => 'required',
            'nilai_pdpt' => 'required',
            'no_reg' => 'required|array',
            'akun_titip' => 'required|array',
            'bayar_paket' => 'required|array',
            'akun_piutang' => 'required|array',
            'total_saldo' => 'required|array',
            'akun_pdpt' => 'required|array',
            'pdpt_paket' => 'required|array',
            'saldo_t' => 'required|array',
            'saldo_dok' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2("MI",$status_admin,$periode);

            $get = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('AKUNT','AKUND') and kode_lokasi = '".$kode_lokasi."'");
            if(count($get) > 0){
                foreach($get as $row){
                    if ($row->kode_spro == "AKUNT") $akunTambah = $row->flag;
                    if ($row->kode_spro == "AKUND") $akunDokumen = $row->flag;
                }
            }else{
                $akunTambah = "-";
                $akunDokumen = "-";
            }

            if($cek['status']){
                
                $no_bukti = $this->generateKode("trans_m", "no_bukti", 'CLJ/'.substr($periode,2,4)."/", "0001");

                $ins1 = DB::connection($this->db)->insert("insert into dgw_closing_d (no_closing,kode_lokasi,no_paket,no_jadwal,tanggal,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,tgl_input,nik_user) values  ('".$no_bukti."','".$kode_lokasi."','".$request->no_paket."','".$request->no_jadwal."','".$request->tanggal."','".$request->keterangan."','".$request->kode_pp."','CLOSING','".$periode."','".$request->kode_curr."',".floatval($request->kurs).",".floatval($request->nilai_pdpt).",getdate(),'".$nik."')");

                $ins2 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','CLOSING','F','-','-','".$request->kode_pp."','".$request->tanggal."','-','-','".$request->kode_curr."',".floatval($request->kurs).",".floatval($request->nilai_pdpt).",0,0,'-','-','-','-','-','-','-','-','-')");
		
                for ($i=0;$i < count($request->no_reg);$i++){

                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_titip[$i]."','D',".floatval($request->bayar_paket[$i]).",".floatval($request->bayar_paket[$i]).",'".$request->keterangan."','MI','TITIP','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                    
                    if (floatval($request->total_saldo[$i]) != 0) {		
                        $ins4[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_piutang[$i]."','D',".floatval($request->total_saldo[$i]).",".floatval($request->total_saldo[$i]).",'".$request->keterangan."','MI','PIUTANG','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");									
                    }
                    
                    $ins5[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_pdpt[$i]."','C',".floatval($request->pdpt_paket[$i]).",".floatval($request->pdpt_paket[$i]).",'".$request->keterangan."','MI','PDPT','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");			

                    $ins6[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$akunTambah."','C',".floatval($request->saldo_t[$i]).",".floatval($request->saldo_t[$i]).",'".$request->keterangan."','MI','PDPTTAMBAH','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	

                    $ins7[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$akunDokumen."','C',".floatval($request->saldo_dok[$i]).",".floatval($request->saldo_dok[$i]).",'".$request->keterangan."','MI','PDPTDOK','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");																																								
                }						
                
                $ins8[$i] = DB::connection($this->db)->update("update dgw_jadwal set no_closing='".$no_bukti."',kurs_closing=".floatval($request->kurs)." where no_paket='".$request->no_paket."' and no_jadwal='".$request->no_jadwal."' and kode_lokasi='".$kode_lokasi."'");
                        
                DB::connection($this->db)->commit();
                $success['status'] = "SUCCESS";
                $success['no_kwitansi'] = $no_bukti;
                $success['message'] = "Data Closing berhasil disimpan. No Bukti:".$no_bukti;
            }else{
                
                $success['status'] = "FAILED";
                $success['no_kwitansi'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Closing gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

     /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.*,convert(varchar,tgl_berangkat,103) as tgl_jadwal,c.nama as nama_paket 
            from dgw_closing_d a 
            inner join dgw_jadwal b on a.no_jadwal=b.no_jadwal and a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi
            where a.no_closing = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $kurs = $res[0]['kurs'];

            $sql = "select 
            a.no_reg,b.no_peserta+' - '+b.nama as nama, f.kode_akun as akun_titip,f.akun_piutang,f.akun_pdpt, 
            
            (a.harga+a.harga_room) as harga_paket,isnull(d.biaya_tambah,0)-a.diskon as biaya_tambah, isnull(h.biaya_dok,0) as biaya_dok, 						
            isnull(e.bayar_p,0) as bayar_p, isnull(e.bayar_t,0) as bayar_t, isnull(e.bayar_m,0) as bayar_m, 
            (a.harga+a.harga_room) - isnull(e.bayar_p,0) as saldo_p, 
            isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0) as saldo_t, 
            isnull(h.biaya_dok,0) - isnull(e.bayar_m,0) as saldo_m, 
            
            isnull(e.bayar_p_idr,0) as bayar_p_idr, 
            isnull(e.bayar_p_idr,0) + isnull(e.bayar_t,0) as  tot_bayaridr, 
            (case when ((a.harga+a.harga_room) - isnull(e.bayar_p,0) <> 0) then (  ((a.harga+a.harga_room) - isnull(e.bayar_p,0)) * ".floatval($kurs)."  ) else 0 end) 
            + (isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0)) 
            + (isnull(h.biaya_dok,0) - isnull(e.bayar_m,0)) 
            as tot_saldo_idr
            from dgw_reg a  
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi =b.kode_lokasi 
            inner join dgw_paket g on a.no_paket=g.no_paket and a.kode_lokasi =g.kode_lokasi 
            inner join dgw_jenis_produk f on g.kode_produk=f.kode_produk and g.kode_lokasi =f.kode_lokasi 
            left join ( 
            select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_tambah 
            from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='TAMBAHAN' 
            where a.kode_lokasi ='".$kode_lokasi."' 
            group by a.kode_lokasi,a.no_reg 
            ) d on a.no_reg=d.no_reg and a.kode_lokasi=d.kode_lokasi 
            left join ( 
            select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_dok 
            from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='DOKUMEN' 
            where a.kode_lokasi ='".$kode_lokasi."' 
            group by a.kode_lokasi,a.no_reg 
            ) h on a.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi     
            left join (						 
                select  no_reg,kode_lokasi,sum(nilai_p) as bayar_p,sum(nilai_t) as bayar_t,sum(nilai_m) as bayar_m,sum(nilai_p * kurs) as bayar_p_idr 
            from dgw_pembayaran where kode_lokasi ='".$kode_lokasi."' 
            group by kode_lokasi,no_reg 						 
            ) e on a.no_reg=e.no_reg and a.kode_lokasi=e.kode_lokasi 
            where c.kode_lokasi ='".$kode_lokasi."' and c.no_closing='".$request->no_bukti."'";
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
               
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['sql'] = $sql;
            $success['status'] = "FAILED";
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
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required',
            'kode_pp' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'no_paket' => 'required',
            'no_jadwal' => 'required',
            'nilai_pdpt' => 'required',
            'no_reg' => 'required|array',
            'akun_titip' => 'required|array',
            'bayar_paket' => 'required|array',
            'akun_piutang' => 'required|array',
            'total_saldo' => 'required|array',
            'akun_pdpt' => 'required|array',
            'pdpt_paket' => 'required|array',
            'saldo_t' => 'required|array',
            'saldo_dok' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2("MI",$status_admin,$periode);
            $no_bukti = $request->no_bukti;
            
            $del1 = DB::connection($this->db)->update("delete from trans_m where no_bukti='".$no_bukti."' and kode_lokasi ='".$kode_lokasi."'");
            $del2 = DB::connection($this->db)->update("delete from trans_j where no_bukti='".$no_bukti."' and kode_lokasi ='".$kode_lokasi."'");
            $del3 = DB::connection($this->db)->update("delete from dgw_closing_d where no_closing='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");						
            $del4 = DB::connection($this->db)->update("update dgw_jadwal set no_closing='-',kurs_closing=0 where no_closing='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");

            $get = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('AKUNT','AKUND') and kode_lokasi = '".$kode_lokasi."'");
            if(count($get) > 0){
                foreach($get as $row){
                    if ($row->kode_spro == "AKUNT") $akunTambah = $row->flag;
                    if ($row->kode_spro == "AKUND") $akunDokumen = $row->flag;
                }
            }else{
                $akunTambah = "-";
                $akunDokumen = "-";
            }

            if($cek['status']){
                

                $ins1 = DB::connection($this->db)->insert("insert into dgw_closing_d (no_closing,kode_lokasi,no_paket,no_jadwal,tanggal,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,tgl_input,nik_user) values  ('".$no_bukti."','".$kode_lokasi."','".$request->no_paket."','".$request->no_jadwal."','".$request->tanggal."','".$request->keterangan."','".$request->kode_pp."','CLOSING','".$periode."','".$request->kode_curr."',".floatval($request->kurs).",".floatval($request->nilai_pdpt).",getdate(),'".$nik."')");

                $ins2 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','CLOSING','F','-','-','".$request->kode_pp."','".$request->tanggal."','-','-','".$request->kode_curr."',".floatval($request->kurs).",".floatval($request->nilai_pdpt).",0,0,'-','-','-','-','-','-','-','-','-')");
		
                for ($i=0;$i < count($request->no_reg);$i++){

                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_titip[$i]."','D',".floatval($request->bayar_paket[$i]).",".floatval($request->bayar_paket[$i]).",'".$request->keterangan."','MI','TITIP','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                    
                    if (floatval($request->total_saldo[$i]) != 0) {		
                        $ins4[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_piutang[$i]."','D',".floatval($request->total_saldo[$i]).",".floatval($request->total_saldo[$i]).",'".$request->keterangan."','MI','PIUTANG','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");									
                    }
                    
                    $ins5[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$request->akun_pdpt[$i]."','C',".floatval($request->pdpt_paket[$i]).",".floatval($request->pdpt_paket[$i]).",'".$request->keterangan."','MI','PDPT','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");			

                    $ins6[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$akunTambah."','C',".floatval($request->saldo_t[$i]).",".floatval($request->saldo_t[$i]).",'".$request->keterangan."','MI','PDPTTAMBAH','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	

                    $ins7[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',".$i.",'".$akunDokumen."','C',".floatval($request->saldo_dok[$i]).",".floatval($request->saldo_dok[$i]).",'".$request->keterangan."','MI','PDPTDOK','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");																																								
                }						
                
                $ins8 = DB::connection($this->db)->insert("update dgw_jadwal set no_closing='".$no_bukti."',kurs_closing=".floatval($request->kurs)." where no_paket='".$request->no_paket."' and no_jadwal='".$request->no_jadwal."' and kode_lokasi='".$kode_lokasi."'");
                        
                DB::connection($this->db)->commit();
                $success['status'] = "SUCCESS";
                $success['no_kwitansi'] = $no_bukti;
                $success['message'] = "Data Closing Jadwal berhasil diubah. No Bukti:".$no_bukti;
            }else{
                
                $success['status'] = "FAILED";
                $success['no_kwitansi'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Closing Jadwal gagal diubah ".$e;
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
            $del1 = DB::connection($this->db)->update("delete from trans_m where no_bukti='".$no_bukti."' and kode_lokasi ='".$kode_lokasi."'");
            $del2 = DB::connection($this->db)->update("delete from trans_j where no_bukti='".$no_bukti."' and kode_lokasi ='".$kode_lokasi."'");
            $del3 = DB::connection($this->db)->update("delete from dgw_closing_d where no_closing='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");						
            $del4 = DB::connection($this->db)->update("update dgw_jadwal set no_closing='-',kurs_closing=0 where no_closing='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");	

            $success['status'] = true;
            $success['message'] = "Data Closing Jadwal berhasil dihapus ";
            DB::connection($this->db)->commit();
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Closing Jadwal gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
