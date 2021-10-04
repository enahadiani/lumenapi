<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PinBukController extends Controller
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
            case a.progress when '1' then 'input' 					 
            end as status 
            from pbh_pb_m a 					 					 
            where a.kode_lokasi='".$kode_lokasi."' 
            and a.modul = 'PINBUK' and a.progress in ('1') and a.kode_pp in (select kode_pp from karyawan_pp where nik='".$nik."' and kode_lokasi='".$kode_lokasi."') 
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
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'total_pinbuk' => 'required',
            'nik_buat' => 'required|max:20',
            'nik_tahu' => 'required|max:20',
            'nik_ver' => 'required|max:20',
            'atensi' => 'required',
            'rekening_sumber' => 'required',
            'bank_sumber' => 'required',
            'nama_rek_sumber' => 'required',
            'no_rek_sumber' => 'required',
            'kode_akun' => 'required|array',
            'bank' => 'required|array',
            'no_rek' => 'required|array',
            'nama_rek' => 'required|array',
            'nilai' => 'required|array',
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
                
                $j = 0;
                $total = 0;
                if(count($request->kode_akun) > 0){

                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->deskripsi,'D',floatval($request->nilai[$i]),$kode_pp[$i],'-',$kode_lokasi,'PINBUK','TUJUAN',$periode,$nik,'IDR',1));
                        $total+= +floatval($request->nilai[$i]);

                        $insrek[$i] = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PINBUK-D',$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],$request->atensi,floatval($request->nilai[$i]),0,floatval($request->nilai[$i])));
                    }
                }

                if($total != floatval($request->total_pinbuk)){
                    $msg = "Transaksi tidak valid.Total Pinbuk ($request->total_pinbuk) dan Total Detail Rekening ($total) tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                
                    if($total > 0){
    
                        $ins1 = DB::connection($this->db)->insert("insert into pbh_pb_m (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver) values (?, ?, ?, ?, ?,getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$nik,$request->tanggal,$request->due_date,$request->deskripsi,$total,'PINBUK',0,$kode_pp,$request->nik_tahu,$request->nik_buat,'-','-','-','-',$kode_pp,$kode_lokasi,$total,'X','-','-','-','-','-',$request->rekening_sumber,$request->nik_ver));

                        //rek sumber
				        $insrek0 = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai,nu) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PINBUK-C',$request->nama_rek_sumber,$request->no_rek_sumber,$request->bank_sumber,$request->atensi,$total,0,$total,99999));	
                        
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
                                    $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'PBBAU',$no_bukti)); 
                                }
                            }
                        }

                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Pindah Buku berhasil disimpan";
                    }else{
    
                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Pindah Buku tidak boleh kurang dari atau sama dengan nol";
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
            $success['message'] = "Data Pindah Buku gagal disimpan ".$e;
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
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'total_pinbuk' => 'required',
            'nik_buat' => 'required|max:20',
            'nik_tahu' => 'required|max:20',
            'nik_ver' => 'required|max:20',
            'atensi' => 'required',
            'rekening_sumber' => 'required',
            'bank_sumber' => 'required',
            'nama_rek_sumber' => 'required',
            'no_rek_sumber' => 'required',
            'kode_akun' => 'required|array',
            'bank' => 'required|array',
            'no_rek' => 'required|array',
            'nama_rek' => 'required|array',
            'nilai' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('pbh_pb_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $no_bukti)
            ->delete();
            $del2 = DB::connection($this->db)->table('pbh_pb_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $no_bukti)
            ->delete();
            
            $del3 = DB::connection($this->db)->table('angg_r')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $del4 = DB::connection($this->db)->table('pbh_rek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }
                
                $j = 0;
                $total = 0;
                if(count($request->kode_akun) > 0){

                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->deskripsi,'D',floatval($request->nilai[$i]),$kode_pp[$i],'-',$kode_lokasi,'PINBUK','TUJUAN',$periode,$nik,'IDR',1));
                        $total+= +floatval($request->nilai[$i]);

                        $insrek[$i] = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PINBUK-D',$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],$request->atensi,floatval($request->nilai[$i]),0,floatval($request->nilai[$i])));
                    }
                }

                if($total != floatval($request->total_pinbuk)){
                    $msg = "Transaksi tidak valid.Total Pinbuk ($request->total_pinbuk) dan Total Detail Rekening ($total) tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                
                    if($total > 0){
    
                        $ins1 = DB::connection($this->db)->insert("insert into pbh_pb_m (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver) values (?, ?, ?, ?, ?,getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$nik,$request->tanggal,$request->due_date,$request->deskripsi,$total,'PINBUK',0,$kode_pp,$request->nik_tahu,$request->nik_buat,'-','-','-','-',$kode_pp,$kode_lokasi,$total,'X','-','-','-','-','-',$request->rekening_sumber,$request->nik_ver));

                        //rek sumber
				        $insrek0 = DB::connection($this->db)->select("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai,nu) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,'PINBUK-C',$request->nama_rek_sumber,$request->no_rek_sumber,$request->bank_sumber,$request->atensi,$total,0,$total,99999));	
                        
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
                                    $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'PBBAU',$no_bukti)); 
                                }
                            }
                        }

                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Pindah Buku berhasil diubah";
                    }else{
    
                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Pindah Buku tidak boleh kurang dari atau sama dengan nol";
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
            $success['message'] = "Data Pindah Buku gagal diubah ".$e;
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

            $periode = date('Ym');
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
            
                $no_bukti = $request->no_bukti;
                // backup hapus
                $ins = DB::connection($this->db)->insert("insert into pbh_pb_his (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver,nik_del,tgl_del)  
                        select no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver,'".$nik."',getdate() 
                        from pbh_pb_m where no_pb='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");
            
                $del = DB::connection($this->db)->table('pbh_pb_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();
                $del2 = DB::connection($this->db)->table('pbh_pb_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_pb', $no_bukti)
                ->delete();

                $del4 = DB::connection($this->db)->table('pbh_rek')
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
                $success['message'] = "Data Pindah Buku berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pindah Buku gagal dihapus ".$e;
            
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

            $strSQL = "select a.keterangan,a.no_dokumen,a.modul,a.due_date,a.tanggal,a.nik_tahu,a.nik_app,a.nik_ver 
            from pbh_pb_m a 
            where a.no_pb = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' ";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            $strSQL2 = "select * from pbh_rek a where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."'";
            $rs2 = DB::connection($this->db)->select($strSQL2);
            $res2 = json_decode(json_encode($rs2),true);

            $strSQL3 = "select a.kode_akun,b.nama as nama_akun,c.bank,c.no_rek,c.nama_rek,a.nilai 
            from pbh_pb_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                            inner join pbh_rek c on a.no_urut=c.nu and a.kode_lokasi=c.kode_lokasi 	
            where a.jenis='TUJUAN' and a.no_pb = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.no_urut";
            $rs3 = DB::connection($this->db)->select($strSQL3);
            $res3 = json_decode(json_encode($rs3),true);

            $strSQL4 = "select b.kode_jenis,b.nama,a.no_gambar 
            from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
            where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
            $rs4 = DB::connection($this->db)->select($strSQL4);
            $res4 = json_decode(json_encode($rs4),true);

            $strSQL5 = "select distinct convert(varchar,tgl_input,103) as tgl
            from pbh_ver_m 
            where no_bukti='".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."' 
            order by convert(varchar,tgl_input,103) desc";
            $rs5 = DB::connection($this->db)->select($strSQL5);
            $res5 = json_decode(json_encode($rs5),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_rek'] = $res2;
                $success['detail_jurnal'] = $res3;
                $success['detail_dok'] = $res4;
                if(count($res5) > 0){
                    $i=0;
                    foreach($res5 as $row){
                        $sql = "select catatan,no_ver, convert(varchar,tgl_input,103) as tgl,convert(varchar,tgl_input,108) as jam,nik_user 
                        from pbh_ver_m 
                        where no_bukti='".$request->no_bukti."' and convert(varchar,tgl_input,103)='".$row['tgl']."' and kode_lokasi='".$kode_lokasi."' 
                        order by convert(varchar,tgl_input,103) desc,convert(varchar,tgl_input,108) desc ";
                        $rs6 = DB::connection($this->db)->select($sql);
                        $res5[$i]['detail'] = json_decode(json_encode($rs6),true);
                        $i++;
                    }
                }
                $success['detail_catatan'] = $res5;
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

    public function getAkun(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select distinct a.kode_akun,a.nama 
            from masakun a 
            	inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag in ('009') 							
            where a.block= '0' and a.kode_lokasi= '".$kode_lokasi."' ";

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

    public function getRekeningSumber(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag='009' where a.block='0' and a.kode_lokasi = '".$kode_lokasi."'";

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

            $strSQL = "select distinct a.nik, a.nama from karyawan a inner join karyawan_pp b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and b.kode_pp='".$kode_pp."' where a.flag_aktif='1' and a.kode_lokasi = '".$kode_lokasi."'";
          
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
