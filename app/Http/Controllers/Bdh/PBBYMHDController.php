<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PBBYMHDController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi ='$kode_lokasi' ");
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

    function doCekPeriode($periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[$periode_aktif]";
                }else{
                    $perNext = $this->nextNPeriode($periode,1);
                    if($perNext == "1"){
                        $perValid = true;
                        $msg = "ok";
                    }else{
                        $perValid = false;
                        $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh melebihi periode aktif sistem.[$periode_aktif]";
                    }
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
            $no_bukti = $this->generateKode("pbh_pb_m", "no_pb", $kode_lokasi."-PB".substr($periode,2,4).".", "0001");

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

            $sql="select a.no_pb,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,a.keterangan,a.nilai, 
            case a.progress when '0' then 'input' 
            				  when 'R' then 'return dokumen' 
            				  when 'K' then 'return pajak' 
            				  when 'V' then 'return verifikasi' 					 
            end as status 
            from pbh_pb_m a 					 					 
            where a.kode_lokasi='".$kode_lokasi."' 
            and a.modul = 'PBBMHD' and a.progress in ('0','R','K','V') and a.kode_pp in (select kode_pp from karyawan_pp where nik='".$nik."' and kode_lokasi='".$kode_lokasi."') 
            order by a.no_pb desc";

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
            $success['data'] = [];
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
            'due_date' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'no_bmhd' => 'required',
            'nilai_selesai' => 'required',
            'nik_buat' => 'required',
            'nik_tahu' => 'required',
            'nik_ver' => 'required',
            'atensi' => 'required|array',
            'bank' => 'required|array',
            'nama_rek' => 'required|array',
            'no_rek' => 'required|array',
            'bruto' => 'required|array',
            'potongan' => 'required|array',
            'netto' => 'required|array',
            'kode_akun' => 'array',
            'kode_pp' => 'array',
            'kode_drk' => 'array',
            'dc' => 'array',
            'keterangan' => 'array',
            'nilai' => 'array',
            'kode_akun_agg' => 'array',
            'kode_pp_agg' => 'array',
            'kode_drk_agg' => 'array',
            'saldo_awal_agg' => 'array',
            'nilai_agg' => 'array',
            'saldo_akhir_agg' => 'array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("pbh_pb_m", "no_pb", $kode_lokasi."-PB".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $get = DB::connection($this->db)->select("select c.nama,a.kode_akun,a.nilai-isnull(b.bayar,0) as saldo 
                from bmhd_m a 
                inner join vendor c on a.kode_vendor=c.kode_vendor and a.kode_lokasi=c.kode_lokasi 
                left join (
                        select no_bmhd,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar  
                           from bmhd_bayar where kode_lokasi='".$kode_lokasi."' and no_aju<>'".$no_bukti."' group by no_bmhd,kode_lokasi 
                           ) b on a.no_bmhd=b.no_bmhd and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' ");
                if(count($get) > 0){
                    $saldo = floatval($get[0]->saldo);
                    $akunBMHD = $get[0]->kode_akun;
                }else{
                    $saldo = 0;
                    $akunBMHD = '-';
                }

                $j = 0;
                $total_rek = 0; $total_d = 0; $total_k =0;
                if(count($request->atensi) > 0){
                    for ($i=0; $i<count($request->atensi); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PBBMHD',$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],
                        $request->atensi[$i],floatval($request->bruto[$i]),floatval($request->potongan[$i]),floatval($request->netto[$i])));
                        $total_rek+= +floatval($request->netto[$i]);
                        
                    }
                }

                $insjj = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,-1,$akunBMHD,$request->deskripsi,'D',floatval($request->nilai_selesai),$kode_pp,'-',$kode_lokasi,'PBBMHD','BMHD',$periode,$nik,'IDR',1));

                if(isset($request->kode_akun) && count($request->kode_akun) > 0){
                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PBBMHD','TAMBAH',$periode,$nik,'IDR',1));
                        if($request->dc[$i] == "D"){
                            $total_d+= +floatval($request->nilai[$i]);
                        }else{
                            $total_d-= +floatval($request->nilai[$i]);
                        }
                        
                    }
                }

                $totalDC = $total_d + floatval($request->nilai_selesai);

                if(floatval($request->nilai_selesai) > floatval($saldo)){
                    $msg = "Transaksi tidak valid. Nilai Penyelesaian melebihi Saldo";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($request->nilai_selesai > 0){
                        
                        if($totalDC != $total_rek){
                            $msg = "Transaksi tidak valid. Total Jurnal dan TotalNet Rekening tidak sama";
                            DB::connection($this->db)->rollback();
                            $success['status'] = false;
                            $success['no_bukti'] = "-";
                            $success['message'] = $msg;   
                        }else{

                            $insbyr = DB::connection($this->db)->insert("insert into bmhd_bayar (no_aju,no_bmhd,kode_lokasi,akun_bmhd,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,dc,kode_vendor,sts_pajak,pajak) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_bmhd,$kode_lokasi,$akunBMHD,$request->deskripsi,$kode_pp,'PBBMHD',$periode,'IDR',1,floatval($request->nilai_selesai),'D','-','-',0));

                            $insm = DB::connection($this->db)->insert("insert into pbh_pb_m (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver) values (?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$nik,$request->tanggal,
                            $request->due_date,$request->deskripsi,floatval($totalDC),'PBBMHD','0',$kode_pp,$request->nik_buat,$request->nik_tahu,$request->no_bmhd,'-','-','-',$kode_pp,$kode_lokasi,floatval($totalDC),'X','-','-','-','-','-','-',$request->nik_ver));
    
                            
                            $total_agg = 0; $sts_agg = true; $msg_agg ="";
                            if (isset($request->kode_akun_agg) && count($request->kode_akun_agg) > 0){
                                for ($i=0; $i < count($request->kode_akun_agg);$i++){
                                    if (floatval($request->nilai_agg[$i]) > 0) {
                                        $DC = "D"; 
                                        $nilai = floatval($request->nilai_agg[$i]);
                                    } else {
                                        $DC = "C";
                                        $nilai = floatval($request->nilai_agg[$i]) * -1;
                                    }
    
                                    if(floatval($request->nilai_agg[$i]) > 0 && (floatval($request->nilai_agg[$i]) > floatval($request->saldo_awal_agg[$i]))){
                                        $sts_agg = false;
                                        $msg_agg .= "Transaksi tidak valid. Saldo Anggaran Akun ".$request->kode_akun_agg[$i]." tidak mencukupi. [Baris : ".($i+1)."] , silahkan melakukan RRA dari menu anggaran";
                                        break;
                                    }
                                    DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,'PBBMHD',$kode_lokasi,$request->kode_akun_agg[$i],$request->kode_pp_agg[$i],$request->kode_drk_agg[$i],$periode,$periode,$DC,floatval($request->saldo_akhir_agg[$i]),$nilai));
    
                                    if($DC == "D"){
                                        $total_agg+= floatval($request->nilai[$i]);
                                    }
                                }
                            }

                            $arr_dok = array();
                            $arr_jenis = array();
                            $arr_no_urut = array();
                            $i=0;
                            $cek = $request->file_dok;
                            if(!empty($cek)){
                                if(count($request->nama_file_seb) > 0){
                                    //looping berdasarkan nama dok
                                    for($i=0;$i<count($request->nama_file_seb);$i++){
                                        //cek row i ada file atau tidak
                                        if(isset($request->file('file_dok')[$i])){
                                            $file = $request->file('file_dok')[$i];
                                            //kalo ada cek nama sebelumnya ada atau -
                                            if($request->nama_file_seb[$i] != "-"){
                                                //kalo ada hapus yang lama
                                                Storage::disk('s3')->delete('bdh/'.$request->nama_file_seb[$i]);
                                            }
                                            $nama_dok = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                            $dok = $nama_dok;
                                            if(Storage::disk('s3')->exists('bdh/'.$dok)){
                                                Storage::disk('s3')->delete('bdh/'.$dok);
                                            }
                                            Storage::disk('s3')->put('bdh/'.$dok,file_get_contents($file));
                                            $arr_dok[] = $dok;
                                            $arr_jenis[] = $request->kode_jenis[$i];
                                            $arr_no_urut[] = $request->no_urut[$i];
                                        }else if($request->nama_file_seb[$i] != "-"){
                                            $arr_dok[] = $request->nama_file_seb[$i];
                                            $arr_jenis[] = $request->kode_jenis[$i];
                                            $arr_no_urut[] = $request->no_urut[$i];
                                        }     
                                    }
                                    
                                    $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                                }
                
                                if(count($arr_no_urut) > 0){
                                    for($i=0; $i<count($arr_no_urut);$i++){
                                        $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) 
                                        values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'PBBMHD',$no_bukti)); 
                                    }
                                }
                            }
    
                            
                            if(!$sts_agg){
                                DB::connection($this->db)->rollback();
                                $success['status'] = true;
                                $success['no_bukti'] = "-";
                                $success['message'] = $msg_agg;
                            }else{
                                DB::connection($this->db)->commit();
                                $success['status'] = true;
                                $success['no_bukti'] = $no_bukti;
                                $success['message'] = "Data Jurnal BYMHD berhasil disimpan";
                            }
                        }

                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Nilai Penyelesaian tidak boleh kurang dari atau sama dengan nol";
                    }
                }
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal BYMHD gagal disimpan ".$e;
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
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'due_date' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'no_bmhd' => 'required',
            'nilai_selesai' => 'required',
            'nik_buat' => 'required',
            'nik_tahu' => 'required',
            'nik_ver' => 'required',
            'atensi' => 'required|array',
            'bank' => 'required|array',
            'nama_rek' => 'required|array',
            'no_rek' => 'required|array',
            'bruto' => 'required|array',
            'potongan' => 'required|array',
            'netto' => 'required|array',
            'kode_akun' => 'array',
            'kode_pp' => 'array',
            'kode_drk' => 'array',
            'dc' => 'array',
            'keterangan' => 'array',
            'nilai' => 'array',
            'kode_akun_agg' => 'array',
            'kode_pp_agg' => 'array',
            'kode_drk_agg' => 'array',
            'saldo_awal_agg' => 'array',
            'nilai_agg' => 'array',
            'saldo_akhir_agg' => 'array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $del = DB::connection($this->db)->table('pbh_pb_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('pbh_pb_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('pbh_rek')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $del5 = DB::connection($this->db)->table('bmhd_bayar')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_aju', $no_bukti)
                ->delete();

                $del6 = DB::connection($this->db)->table('angg_r')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $get = DB::connection($this->db)->select("select c.nama,a.kode_akun,a.nilai-isnull(b.bayar,0) as saldo 
                from bmhd_m a 
                inner join vendor c on a.kode_vendor=c.kode_vendor and a.kode_lokasi=c.kode_lokasi 
                left join (
                        select no_bmhd,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar  
                           from bmhd_bayar where kode_lokasi='".$kode_lokasi."' and no_aju<>'".$no_bukti."' group by no_bmhd,kode_lokasi 
                           ) b on a.no_bmhd=b.no_bmhd and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' ");
                if(count($get) > 0){
                    $saldo = floatval($get[0]->saldo);
                    $akunBMHD = $get[0]->kode_akun;
                }else{
                    $saldo = 0;
                    $akunBMHD = '-';
                }

                $j = 0;
                $total_rek = 0; $total_d = 0; $total_k =0;
                if(count($request->atensi) > 0){
                    for ($i=0; $i<count($request->atensi); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PBBMHD',$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],
                        $request->atensi[$i],floatval($request->bruto[$i]),floatval($request->potongan[$i]),floatval($request->netto[$i])));
                        $total_rek+= +floatval($request->netto[$i]);
                        
                    }
                }

                $insjj = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,-1,$akunBMHD,$request->deskripsi,'D',floatval($request->nilai_selesai),$kode_pp,'-',$kode_lokasi,'PBBMHD','BMHD',$periode,$nik,'IDR',1));

                if(isset($request->kode_akun) && count($request->kode_akun) > 0){
                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PBBMHD','TAMBAH',$periode,$nik,'IDR',1));
                        if($request->dc[$i] == "D"){
                            $total_d+= +floatval($request->nilai[$i]);
                        }else{
                            $total_d-= +floatval($request->nilai[$i]);
                        }
                        
                    }
                }

                $totalDC = $total_d + floatval($request->nilai_selesai);

                if(floatval($request->nilai_selesai) > floatval($saldo)){
                    $msg = "Transaksi tidak valid. Nilai Penyelesaian melebihi Saldo";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($request->nilai_selesai > 0){
                        
                        if($totalDC != $total_rek){
                            $msg = "Transaksi tidak valid. Total Jurnal dan TotalNet Rekening tidak sama";
                            DB::connection($this->db)->rollback();
                            $success['status'] = false;
                            $success['no_bukti'] = "-";
                            $success['message'] = $msg;   
                        }else{

                            $insbyr = DB::connection($this->db)->insert("insert into bmhd_bayar (no_aju,no_bmhd,kode_lokasi,akun_bmhd,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,dc,kode_vendor,sts_pajak,pajak) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_bmhd,$kode_lokasi,$akunBMHD,$request->deskripsi,$kode_pp,'PBBMHD',$periode,'IDR',1,floatval($request->nilai_selesai),'D','-','-',0));

                            $insm = DB::connection($this->db)->insert("insert into pbh_pb_m (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver) values (?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$nik,$request->tanggal,
                            $request->due_date,$request->deskripsi,floatval($totalDC),'PBBMHD','0',$kode_pp,$request->nik_buat,$request->nik_tahu,$request->no_bmhd,'-','-','-',$kode_pp,$kode_lokasi,floatval($totalDC),'X','-','-','-','-','-','-',$request->nik_ver));
    
                            
                            $total_agg = 0; $sts_agg = true; $msg_agg ="";
                            if (isset($request->kode_akun_agg) && count($request->kode_akun_agg) > 0){
                                for ($i=0; $i < count($request->kode_akun_agg);$i++){
                                    if (floatval($request->nilai_agg[$i]) > 0) {
                                        $DC = "D"; 
                                        $nilai = floatval($request->nilai_agg[$i]);
                                    } else {
                                        $DC = "C";
                                        $nilai = floatval($request->nilai_agg[$i]) * -1;
                                    }
    
                                    if(floatval($request->nilai_agg[$i]) > 0 && (floatval($request->nilai_agg[$i]) > floatval($request->saldo_awal_agg[$i]))){
                                        $sts_agg = false;
                                        $msg_agg .= "Transaksi tidak valid. Saldo Anggaran Akun ".$request->kode_akun_agg[$i]." tidak mencukupi. [Baris : ".($i+1)."] , silahkan melakukan RRA dari menu anggaran";
                                        break;
                                    }
                                    DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,'PBBMHD',$kode_lokasi,$request->kode_akun_agg[$i],$request->kode_pp_agg[$i],$request->kode_drk_agg[$i],$periode,$periode,$DC,floatval($request->saldo_akhir_agg[$i]),$nilai));
    
                                    if($DC == "D"){
                                        $total_agg+= floatval($request->nilai[$i]);
                                    }
                                }
                            }

                            $arr_dok = array();
                            $arr_jenis = array();
                            $arr_no_urut = array();
                            $i=0;
                            $cek = $request->file_dok;
                            if(!empty($cek)){
                                if(count($request->nama_file_seb) > 0){
                                    //looping berdasarkan nama dok
                                    for($i=0;$i<count($request->nama_file_seb);$i++){
                                        //cek row i ada file atau tidak
                                        if(isset($request->file('file_dok')[$i])){
                                            $file = $request->file('file_dok')[$i];
                                            //kalo ada cek nama sebelumnya ada atau -
                                            if($request->nama_file_seb[$i] != "-"){
                                                //kalo ada hapus yang lama
                                                Storage::disk('s3')->delete('bdh/'.$request->nama_file_seb[$i]);
                                            }
                                            $nama_dok = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                            $dok = $nama_dok;
                                            if(Storage::disk('s3')->exists('bdh/'.$dok)){
                                                Storage::disk('s3')->delete('bdh/'.$dok);
                                            }
                                            Storage::disk('s3')->put('bdh/'.$dok,file_get_contents($file));
                                            $arr_dok[] = $dok;
                                            $arr_jenis[] = $request->kode_jenis[$i];
                                            $arr_no_urut[] = $request->no_urut[$i];
                                        }else if($request->nama_file_seb[$i] != "-"){
                                            $arr_dok[] = $request->nama_file_seb[$i];
                                            $arr_jenis[] = $request->kode_jenis[$i];
                                            $arr_no_urut[] = $request->no_urut[$i];
                                        }     
                                    }
                                    
                                    $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                                }
                
                                if(count($arr_no_urut) > 0){
                                    for($i=0; $i<count($arr_no_urut);$i++){
                                        $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) 
                                        values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'PBBMHD',$no_bukti)); 
                                    }
                                }
                            }
    
                            
                            if(!$sts_agg){
                                DB::connection($this->db)->rollback();
                                $success['status'] = true;
                                $success['no_bukti'] = "-";
                                $success['message'] = $msg_agg;
                            }else{
                                DB::connection($this->db)->commit();
                                $success['status'] = true;
                                $success['no_bukti'] = $no_bukti;
                                $success['message'] = "Data Jurnal BYMHD berhasil diubah";
                            }
                        }

                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Nilai Penyelesaian tidak boleh kurang dari atau sama dengan nol";
                    }
                }
            
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
                            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Jurnal BYMHD gagal diubah ".$e;
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
            if(isset($request->periode) && $request->periode != ""){
                $periode = $request->periode;
            }else{
                $periode = date('Ym');
            }
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $del = DB::connection($this->db)->table('pbh_pb_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('pbh_pb_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('pbh_rek')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $del5 = DB::connection($this->db)->table('bmhd_bayar')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_aju', $no_bukti)
                ->delete();

                $del6 = DB::connection($this->db)->table('angg_r')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $res = DB::connection($this->db)->select("select * from pbh_dok where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
                $res = json_decode(json_encode($res),true);
                for($i=0;$i<count($res);$i++){
                    if(Storage::disk('s3')->exists('bdh/'.$res[$i]['no_gambar'])){
                        Storage::disk('s3')->delete('bdh/'.$res[$i]['no_gambar']);
                    }
                }

                $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();


                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Jurnal BYMHD berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal BYMHD gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function cekBudget(Request $request)
    {
        $this->validate($request,[
            'kode_akun_agg' => 'required|array',
            'kode_pp_agg' => 'required|array',
            'kode_drk_agg' => 'required|array',
            'nilai_agg' => 'required|array',
            'periode' => 'required',
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            $nilai = 0; $total = 0;
            $sls = 0;
            $result = array();
			for ($i=0;$i < count($request->kode_akun_agg);$i++){
                
                $strSQL = "select dbo.fn_cekagg3('".$request->kode_pp_agg[$i]."','".$kode_lokasi."','".$request->kode_akun_agg[$i]."','".$request->kode_drk_agg[$i]."','".$periode."','".$request->no_bukti."') as gar ";			
                $res = DB::connection($this->db)->select($strSQL);
				if (count($res) > 0){
					$line = $res[0];
                    if($line->gar != ""){
                        $data = explode(";",$line->gar);					
                        $so_awal = floatval($data[0]) - floatval($data[1]);
                        $so_akhir = $so_awal - floatval($request->nilai_agg[$i]);

                    }else{
                        $so_awal = 0;
                        $so_akhir = $so_awal - floatval($request->nilai_agg[$i]);
                    }
				}else{
                    $so_awal = 0;
					$so_akhir = $so_awal - floatval($request->nilai_agg[$i]);
                }

                $hasil = array(
                    'kode_akun_agg' => $request->kode_akun_agg[$i],
                    'kode_pp_agg' => $request->kode_pp_agg[$i],
                    'kode_drk_agg' => $request->kode_drk_agg[$i],
                    'so_awal_agg' => $so_awal,
                    'nilai_agg' => $request->nilai_agg[$i],
                    'so_akhir_agg' => $so_akhir,
                );
                $result[] = $hasil;
			}
            
            if(count($result) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $result;
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
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select b.nilai,a.keterangan,a.no_dokumen,a.modul,a.due_date,a.tanggal,a.nik_tahu,a.nik_app,a.nik_ver,a.no_hutang 
            from pbh_pb_m a inner join bmhd_bayar b on a.no_pb=b.no_aju and a.kode_lokasi=b.kode_lokasi	
            where a.no_pb = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."'";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $strrek = "select * from pbh_rek a where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' and modul<>'PINBUK-C' ";
                $rsrek = DB::connection($this->db)->select($strrek);
                $resrek = json_decode(json_encode($rsrek),true);

                $strj = "select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                from pbh_pb_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 		
                                left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 							
                where a.jenis='BEBAN' and a.no_pb = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.no_urut";
                $rsj = DB::connection($this->db)->select($strj);
                $resj = json_decode(json_encode($rsj),true);

                $strdok = "select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
                $rsdok = DB::connection($this->db)->select($strdok);
                $resdok = json_decode(json_encode($rsdok),true);

                $strctt = "select distinct convert(varchar,tanggal,103) as tgl,tanggal 
                from pbh_ver_m 
                where no_bukti='".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."' 
                order by convert(varchar,tanggal,103) desc";
                $rsctt = DB::connection($this->db)->select($strctt);
                $resctt = json_decode(json_encode($rsctt),true);
                if(count($resctt) >0){
                    for($i=0;$i<count($resctt);$i++){

                        $str = "select catatan,no_ver, convert(varchar,tanggal,103) as tgl,tanggal, convert(varchar,tgl_input,108) as jam,nik_user 
                        from pbh_ver_m 
                        where no_bukti='".$request->no_bukti."' and tanggal='".$resctt[$i]['tanggal']."' and kode_lokasi='".$kode_lokasi."' 
                        order by tanggal desc,convert(varchar,tgl_input,108) desc ";
                        $rsd = DB::connection($this->db)->select($str);
                        $resctt[$i]['detail'] = json_decode(json_encode($rsd),true);
                    }
                }

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_rek'] = $resrek;
                $success['detail_jurnal'] = $resj;
                $success['detail_dok'] = $resdok;
                $success['detail_catatan'] = $resctt;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_rek'] = [];
                $success['detail_jurnal'] = [];
                $success['detail_dok'] = [];
                $success['detail_catatan'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_rek'] = [];
            $success['detail_jurnal'] = [];
            $success['detail_dok'] = [];
            $success['detail_catatan'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNoBMHD(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.no_bmhd,a.keterangan 
            from bmhd_m a left join (
                    select no_bmhd,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar  
            		 from bmhd_bayar where kode_lokasi='".$kode_lokasi."' 
                     group by no_bmhd,kode_lokasi 
            		) b on a.no_bmhd=b.no_bmhd and a.kode_lokasi=b.kode_lokasi 
            where a.modul='BMHD' and a.kode_lokasi='".$kode_lokasi."' and a.nilai > isnull(b.bayar,0)";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadData(Request $request)
    {
        $this->validate($request,[
            'no_bukti'=>'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select c.nama,a.kode_akun,a.nilai-isnull(b.bayar,0) as saldo 
            from bmhd_m a 
            inner join vendor c on a.kode_vendor=c.kode_vendor and a.kode_lokasi=c.kode_lokasi 
            left join (
                    select no_bmhd,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar  
            		   from bmhd_bayar where kode_lokasi='".$kode_lokasi."' and no_aju<>'".$request->no_bukti."' group by no_bmhd,kode_lokasi 
            		   ) b on a.no_bmhd=b.no_bmhd and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkun(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select distinct a.kode_akun,a.nama from masakun a
                inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag in ('062','004')							
                inner join anggaran_d c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                inner join karyawan_pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi and d.nik='".$nik."' 
            where a.block= '0' and a.kode_lokasi = '".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            if ($status_admin == "A") {
                $strPP = "select a.kode_pp,a.nama from pp a where a.flag_aktif= '1' and a.kode_lokasi = '".$kode_lokasi."'";
                
            }
            else {
                $strPP = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where b.nik='".$nik."' and a.flag_aktif= '1' and a.kode_lokasi = '".$kode_lokasi."'";
            }
            
            $rs = DB::connection($this->db)->select($strPP);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDRK(Request $request)
    {
        $this->validate($request,[
            'kode_akun' => 'required',
            'periode' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $vUnion = "";
            $data = DB::connection($this->db)->select("select status_gar from masakun where kode_akun='".$request->kode_akun."' and kode_lokasi='".$kode_lokasi."'");
            if(count($data) > 0){
                $line = $data[0];							
                if ($line->status_gar != "1") $vUnion = " union select '-','-' "; 
                
            }
            $strSQL="select distinct a.kode_drk, a.nama from drk a inner join anggaran_d b on a.kode_drk=b.kode_drk where a.tahun=substring(b.periode,1,4) and b.periode like '".$request->periode."%' and b.kode_akun='".$request->kode_akun."' and b.kode_pp = '".$request->kode_pp."' and a.kode_lokasi='".$kode_lokasi."' ".$vUnion;

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function getNIKBuat(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select distinct a.nik, a.nama from karyawan a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='".$nik."' where a.flag_aktif='1' and a.kode_lokasi='".$kode_lokasi."'";
          
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getNIKTahu(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($getPP) > 0){
                $kode_pp = $getPP[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $strSQL = "select nik, nama from karyawan where kode_pp ='".$kode_pp."' and flag_aktif='1' and kode_lokasi='".$kode_lokasi."'";
          
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getNIKVer(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $getSPRO = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('NIKVER') and kode_lokasi = '".$kode_lokasi."'");
            if(count($getSPRO) > 0){
                $line = $getSPRO[0];
                if ($line->kode_spro == "NIKVER") $cb_ver = $line->flag;
            }else{
                $cb_ver = "-";
            }         
            $success['nik_default'] = $cb_ver;
            $strSQL = "select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='$kode_lokasi' ";
          
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
