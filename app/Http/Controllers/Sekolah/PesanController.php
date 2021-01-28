<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PesanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }  
    
    function gcm($token,$payload){
		$data = $payload;//array
		
		$ids = $token;//array
		//------------------------------
	    // Replace with real GCM API 
	    // key from Google APIs Console
	    // 
	    // https://code.google.com/apis/console/
	    //------------------------------
		global $apiKey;
		// $apiKey = "AIzaSyARBIGBtlVHp2JlhS3HRaP4IPysLBYwXg8";
		$apiKey = "AAAAC_jHm34:APA91bFF0NUTQZty4hqcR-BtEilaLbGiny584xFIkWBbEz38mPL5iyIMCS2UqI-JCX1SUpBA6v98ETTq0HdtEI1h6e9lUB-LeIO20TvUfYSjvu6QlMRu_C_vDmDZJk3S2VTWogRN51F2";
	    //------------------------------
	    // Define URL to GCM endpoint
	    //------------------------------
	
		$url = 'https://fcm.googleapis.com/fcm/send';
	
	    //------------------------------
	    // Set GCM post variables
	    // (Device IDs and push payload)
		//------------------------------
		if(isset($data['click_action'])){

			$post = array(
                'registration_ids'  => $ids,
                // 'notification'              => array (
                //     "body" => $data["message"],
                //     "title" => $data["title"],
                //     "click_action" => $data["click_action"]
                // ),
                'data'              => $data,
                'priority' => "high",
                'android' => array(
                    "priority" => "high",
                    "ttl" => "86400s",
                    "notification" => array (
                      "click_action" =>  $data["click_action"]
                    )
                ),
             

                // "android" => array (
                //     "ttl" => "86400s",
                //     "notification" => array (
                //         "click_action" => $data["click_action"]
                //     )
                // ),
                    
            );
		}else{

			$post = array(
				'registration_ids'  => $ids,
				// 'notification'              => array (
				// 	"body" => $data["message"],
				// 	"title" => $data["title"]
				// ),
				'data'              => $data
			);
		}
	
	    //------------------------------
	    // Set CURL request headers
	    // (Authentication and type)
	    //------------------------------
	
	    $headers = array( 
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json'
        );
	
	    //------------------------------
	    // Initialize curl handle
	    //------------------------------
	
	    $ch = curl_init();
	
	    //------------------------------
	    // Set URL to GCM endpoint
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_URL, $url );
	
	    //------------------------------
	    // Set request method to POST
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_POST, true );
	
	    //------------------------------
	    // Set our custom headers
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	
	    //------------------------------
	    // Get the response back as 
	    // string instead of printing it
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	    //------------------------------
	    // Set post data as JSON
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
	
	    //------------------------------
	    // Actually send the push!
	    //------------------------------
	
	    $result = curl_exec( $ch );
	
	    //------------------------------
	    // Error? Display it!
	    //------------------------------
	
	    if ( curl_errno( $ch ) )
	    {
	        echo('GCM error: ' . curl_error( $ch ));
	        $status = false;
	    }else $status = true;
	
	    //------------------------------
	    // Close curl handle
	    //------------------------------
	
	    curl_close( $ch );
	
	    //------------------------------
	    // Debug GCM response
	    //------------------------------
		// $rs = error_log($token .":".$status);
	    return $result;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.jenis,a.judul,a.pesan,a.tgl_input, case jenis when 'Semua' then '-' when 'Kelas' then a.kode_kelas when 'Siswa' then a.nis end as kontak,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp,a.tipe,a.kode_matpel,isnull(b.nama,'-') as nama_matpel 
            from sis_pesan_m a
            left join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.nik_user='$nik' $filter");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'jenis' => 'required',
            'judul' => 'required',
            'kode_pp' => 'required',
            'kontak' => 'required',
            'pesan' => 'required',
            'kode_matpel' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $per = date('ym');
            $no_bukti = $this->generateKode("sis_pesan_m", "no_bukti", $kode_lokasi."-PSN".$per.".", "000001");
            
            $arr_foto = array();
            $i=0;
            $cek = $request->file;
            //cek upload file tidak kosong
            if(!empty($cek)){
                
                if(count($request->nama_file_seb) > 0){
                    //looping berdasarkan nama dok
                    for($i=0;$i<count($request->nama_file_seb);$i++){
                        //cek row i ada file atau tidak
                        if(isset($request->file('file')[$i])){
                            $file = $request->file('file')[$i];
                            //kalo ada cek nama sebelumnya ada atau -
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('sekolah/'.$request->nama_file_seb[$i]);
                            }
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                                Storage::disk('s3')->delete('sekolah/'.$foto);
                            }
                            Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                        }     
                    }
                    
                    $del3 = DB::connection($this->db)->table('sis_pesan_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $request->kode_pp)->delete();
                }
                
            }
            
            if($request->jenis == "Siswa"){
                $nis = $request->kontak;
                $kode_kelas = '-';
                $sql = "select a.nik,c.id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                where a.kode_pp='$request->kode_pp' and b.nis='$nis' ";
            }
            else if($request->jenis == "Kelas"){
                $nis = '-';
                $kode_kelas = $request->kontak;
                $sql = "select a.nik,isnull(c.id_device,'-') as id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                where a.kode_pp='$request->kode_pp' and b.kode_kelas='$kode_kelas' ";
            }
            else{
                $nis = "-";
                $kode_kelas = "-";
                $sql = "select a.nik,isnull(c.id_device,'-') as id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                where a.kode_pp='$request->kode_pp' 
                union all
                select a.nik,isnull(c.id_device,'-') as id_device from sis_hakakses a
                inner join sis_guru b on a.nik = b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                where a.kode_pp='$request->kode_pp' ";
            }
            
            $ref1 = (isset($request->ref1) && $request->ref1 != "" ? $request->ref1 : '-');
            $ref2 = (isset($request->ref2) && $request->ref2 != "" ? $request->ref2 : '-');
            $ref3 = (isset($request->ref3) && $request->ref3 != "" ? $request->ref3 : '-');
            $link = (isset($request->link) && $request->link != "" ? $request->link : '-');
            $kode_matpel = (isset($request->kode_matpel) && $request->kode_matpel != "" ? $request->kode_matpel : '-');

            $tipe = (isset($request->tipe) && $request->tipe != "" ? $request->tipe : ($request->jenis == "Semua" ? "notif" : "info"));
            $key = array();
            if($tipe == "notif"){
                $click_action = "notifikasi";
                $key = array(
                    'no_bukti' => $no_bukti,
                    'kode_matpel' => $kode_matpel,
                    'nik' => $nik
                );
            }else if($tipe == "info"){
                $click_action = "informasi";
                $key = array(
                    'no_bukti' => $no_bukti,
                    'kode_matpel' => $kode_matpel,
                    'nik' => $nik
                );
            }else{
                $click_action = "detail_matpel";
                $key = array(
                    'kode_matpel' => $kode_matpel,
                    'nik' => $nik
                );
            }
            
            $ins = DB::connection($this->db)->insert("insert into sis_pesan_m(no_bukti,jenis,nis,kode_akt,kode_kelas,judul,subjudul,pesan,kode_pp,kode_lokasi,ref1,ref2,ref3,link,tipe,tgl_input,nik_user,kode_matpel) values ('$no_bukti','$request->jenis','$nis','-','$kode_kelas','$request->judul','-','$request->pesan','$request->kode_pp','$kode_lokasi','$ref1','$ref2','$ref3','$link','$tipe',getdate(),'$nik','$request->kode_matpel') ");
            
            $ck = DB::connection($this->db)->select($sql);
            $ck = json_decode(json_encode($ck),true);
            $arr_id = array();
            $nik = array();
            if(count($ck) > 0){
                for($i=0;$i<count($ck);$i++){
                    if(!isset($nik[$ck[$i]['nik']])){
                        $ins2[$i] = DB::connection($this->db)->insert("insert into sis_pesan_d(no_bukti,kode_lokasi,kode_pp,sts_read,sts_read_mob,id_device,nik) values ('$no_bukti','$kode_lokasi','$request->kode_pp','0','0','".$ck[$i]['id_device']."','".$ck[$i]['nik']."') ");
                    }
                    
                    if($ck[$i]['id_device'] != "-"){
                        array_push($arr_id,$ck[$i]['id_device']);
                    }
                    
                    $nik[$ck[$i]['nik']] = $ck[$i]['nik'];
                }  
            }
            
            if(count($arr_foto) > 0){
                for($i=0; $i<count($arr_foto);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into sis_pesan_dok (
                        no_bukti,kode_lokasi,file_dok,no_urut,kode_pp) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','$request->kode_pp') "); 
                }
            }
            
            
            $msg_n = "Notif tidak dikirim";
            if(count($arr_id) > 0){
                
                $payload = array(
                    'title' => $request->judul,
                    'message' => $request->pesan,
                    'click_action' => $click_action,
                    'key' => $key
                );
                $res = $this->gcm($arr_id,$payload);
                $hasil= json_decode($res,true);
                $success['hasil'] = $hasil;
                if(isset($hasil['success'])){
                    if($hasil['failure'] > 0){
                        $sts = 0;
                        $msg_n = "Notif gagal dikirim";
                    }else{
                        $msg_n = "Notif berhasil dikirim";
                        $sts = 1;
                    }
                }else{
                    
                    $msg_n = "Notif gagal dikirim";
                }
            }
            
            DB::connection($this->db)->commit();
            $sts = true;
            $msg = "Data Pesan berhasil disimpan. ".$msg_n;
        
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            $success['arr_id'] = $arr_id;
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.jenis,a.no_bukti,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas when 'Semua' then a.kode_pp end as kontak,a.judul,a.pesan,a.kode_pp,a.ref1,a.ref2,a.ref3,a.link,a.tgl_input,a.tipe,a.kode_matpel,isnull(b.nama,'-') as nama_matpel
            from sis_pesan_m a
            left join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select 
            a.no_bukti,a.kode_lokasi,a.file_dok,a.no_urut,a.kode_pp from sis_pesan_dok a where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' ";
            $res3 = DB::connection($this->db)->select($sql2);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_dok'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_dok'] = [];
                $success['sql'] = $sql;
                $success['sql2'] = $sql2;
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
            'jenis' => 'required',
            'judul' => 'required',
            'kode_pp' => 'required',
            'kontak' => 'required',
            'pesan' => 'required',
            // 'tipe' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $per = date('ym');
            $no_bukti = $request->no_bukti;
            
            $arr_foto = array();
            $i=0;
            $cek = $request->file;
            //cek upload file tidak kosong
            if(!empty($cek)){
                
                if(count($request->nama_file_seb) > 0){
                    //looping berdasarkan nama dok
                    for($i=0;$i<count($request->nama_file_seb);$i++){
                        //cek row i ada file atau tidak
                        if(isset($request->file('file')[$i])){
                            $file = $request->file('file')[$i];
                            //kalo ada cek nama sebelumnya ada atau -
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('sekolah/'.$request->nama_file_seb[$i]);
                            }
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                                Storage::disk('s3')->delete('sekolah/'.$foto);
                            }
                            Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                        }     
                    }
                    
                    $del3 = DB::connection($this->db)->table('sis_pesan_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $request->kode_pp)->delete();
                }
                
            }

            $del = DB::connection($this->db)->table('sis_pesan_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $del2 = DB::connection($this->db)->table('sis_pesan_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();
            
            if($request->jenis == "Siswa"){
                $nis = $request->kontak;
                $kode_kelas = '-';
                $sql = "select a.nik,c.id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp 
                where a.kode_pp='$request->kode_pp' and b.nis='$nis' ";
            }
            else if($request->jenis == "Kelas"){
                $nis = '-';
                $kode_kelas = $request->kontak;
                $sql = "select a.nik,c.id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp 
                where a.kode_pp='$request->kode_pp' and b.kode_kelas='$kode_kelas' ";
            }
            else{
                $nis = "-";
                $kode_kelas = "-";
                $sql = "select a.nik,c.id_device from sis_hakakses a
                inner join sis_siswa b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp 
                where a.kode_pp='$request->kode_pp' 
                union all
                select a.nik,c.id_device from sis_hakakses a
                inner join sis_guru b on a.nik = b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.flag_aktif=1
                left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp 
                where a.kode_pp='$request->kode_pp' ";
            }
            
            $ref1 = (isset($request->ref1) && $request->ref1 != "" ? $request->ref1 : '-');
            $ref2 = (isset($request->ref2) && $request->ref2 != "" ? $request->ref2 : '-');
            $ref3 = (isset($request->ref3) && $request->ref3 != "" ? $request->ref3 : '-');
            $link = (isset($request->link) && $request->link != "" ? $request->link : '-');
            
            $tipe = (isset($request->tipe) && $request->tipe != "" ? $request->tipe : ($request->jenis == "Semua" ? "notif" : "info"));

            if($tipe == "notif"){
                $click_action = "open_notifikasi/";
            }else if($tipe == "info"){
                $click_action = "open_informasi/";
            }else{
                $click_action = "open_detail/".$kode_matpel;
            }
            
            $ins = DB::connection($this->db)->insert("insert into sis_pesan_m(no_bukti,jenis,nis,kode_akt,kode_kelas,judul,subjudul,pesan,kode_pp,kode_lokasi,ref1,ref2,ref3,link,tipe,tgl_input,nik_user) values ('$no_bukti','$request->jenis','$nis','-','$kode_kelas','$request->judul','-','$request->pesan','$request->kode_pp','$kode_lokasi','$ref1','$ref2','$ref3','$link','$tipe',getdate(),'$nik') ");
            
            $ck = DB::connection($this->db)->select($sql);
            $ck = json_decode(json_encode($ck),true);
            $nik = array();
            if(count($ck) > 0){
                for($i=0;$i<count($ck);$i++){
                    if(!isset($nik[$ck[$i]['nik']])){
                        $ins2[$i] = DB::connection($this->db)->insert("insert into sis_pesan_d(no_bukti,kode_lokasi,kode_pp,sts_read,sts_read_mob,id_device,nik) values ('$no_bukti','$kode_lokasi','$request->kode_pp','0','0','".$ck[$i]['id_device']."','".$ck[$i]['nik']."') ");
                    }
                    
                    if($ck[$i]['id_device'] != "-"){
                        array_push($arr_id,$ck[$i]['id_device']);
                    }
                    
                    $nik[$ck[$i]['nik']] = $ck[$i]['nik'];
                }  
            }
            
            if(count($arr_foto) > 0){
                for($i=0; $i<count($arr_foto);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into sis_pesan_dok (
                        no_bukti,kode_lokasi,file_dok,no_urut,kode_pp) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','$request->kode_pp') "); 
                }
            }
            
            DB::connection($this->db)->commit();
            $msg_n = "Notif tidak dikirim";
            if(count($arr_id) > 0){
                $payload = array(
                    'title' => $request->judul,
                    'message' => $request->pesan,
                    'click_action' => $click_action
                );
                $res = $this->gcm($arr_id,$payload);
                $hasil= json_decode($res,true);
                $success['hasil'] = $hasil;
                if(isset($hasil['success'])){
                    if($hasil['failure'] > 0){
                        $sts = 0;
                        $msg_n = "Notif gagal dikirim";
                    }else{
                        $msg_n = "Notif berhasil dikirim";
                        $sts = 1;
                    }
                }else{
                    
                    $msg_n = "Notif gagal dikirim";
                }
            }
            $sts = true;
            $msg = "Data Pesan berhasil diubah. ".$msg_n;
        
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal disimpan ".$e;
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
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		
            
            $del = DB::connection($this->db)->table('sis_pesan_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $del2 = DB::connection($this->db)->table('sis_pesan_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $sql3="select no_bukti,file_dok from sis_pesan_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }
            }

            $del3 = DB::connection($this->db)->table('sis_pesan_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pesan berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function deleteDokumen(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required',
            'no_urut' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,file_dok from sis_pesan_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp' and no_urut='$request->no_urut' ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection($this->db)->table('sis_pesan_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('kode_pp', $request->kode_pp)
                ->where('no_urut', $request->no_urut)
                ->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen gagal dihapus.";
            }

            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function historyPesan(Request $request)
    {
        // $this->validate($request,[
        //     'kode_pp' => 'required'
        // ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $sql = "select * from (	select a.*,x.nama,x.foto,convert(varchar,a.tgl_input,103) as tgl, convert(varchar,a.tgl_input,108) as jam from (
                select a.jenis,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas else '-' end as kontak,a.judul,a.pesan,a.kode_pp,a.kode_lokasi,a.tgl_input
                from sis_pesan_m a
                inner join (select jenis,nis,kode_lokasi,kode_pp,max(tgl_input) as tgl_input
                            from sis_pesan_m
                            where tipe in ('info','nilai') and nik_user='$nik' and jenis='Siswa'
                            group by jenis,nis,kode_lokasi,kode_pp) b on a.jenis=b.jenis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.tgl_input=b.tgl_input and a.nis=b.nis
                where a.tipe in ('info','nilai')  and a.nik_user = '$nik'
                ) a
                inner join (select a.nis as kode,a.nama,a.kode_pp,a.kode_lokasi,isnull(b.foto,'-') as foto 
                            from sis_siswa a
                            left join sis_hakakses b on a.nis=b.nik and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                            )x on a.kontak=x.kode and a.kode_lokasi=x.kode_lokasi and a.kode_pp=x.kode_pp
                union all
				select a.*,x.nama,x.foto,convert(varchar,a.tgl_input,103) as tgl, convert(varchar,a.tgl_input,108) as jam from (
                select a.jenis,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas else '-' end as kontak,a.judul,a.pesan,a.kode_pp,a.kode_lokasi,a.tgl_input
                from sis_pesan_m a
                inner join (select jenis,kode_kelas,kode_lokasi,kode_pp,max(tgl_input) as tgl_input
                            from sis_pesan_m
                            where tipe in ('info','nilai') and nik_user='$nik' and jenis='Kelas'
                            group by jenis,kode_kelas,kode_lokasi,kode_pp) b on a.jenis=b.jenis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.tgl_input=b.tgl_input and a.kode_kelas=b.kode_kelas
                where a.tipe in ('info','nilai')  and a.nik_user = '$nik'
                ) a
                inner join (select a.kode_kelas as kode,a.nama,a.kode_pp,a.kode_lokasi,'-' as foto 
                            from sis_kelas a
                            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                            )x on a.kontak=x.kode and a.kode_lokasi=x.kode_lokasi and a.kode_pp=x.kode_pp
                
				) a 
				order by a.tgl_input desc";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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
    
    public function rata2Nilai(Request $request){
        $this->validate($request,[
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $rs = DB::connection($this->db)->select("select a.kode_kd,a.nama
            from sis_kd a
            inner join sis_tingkat b on a.kode_tingkat=b.kode_tingkat and a.kode_lokasi=b.kode_lokasi
            inner join sis_kelas c on b.kode_tingkat=c.kode_tingkat and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$request->kode_pp' and c.kode_kelas='$request->kode_kelas' 
            and a.kode_matpel='$request->kode_matpel'
            order by a.kode_kd
            ");
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['kode_kd']);
                }
            }
            $success['ctg']=$ctg;
            
            $sql2 = "select a.kode_kd,a.nama,b.kode_kelas,a.kode_matpel,isnull(c.rata2,0) as nilai 
            from sis_kd a 
            inner join sis_kelas b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and a.kode_tingkat=b.kode_tingkat
            left join (
                        select a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp,avg(b.nilai) as rata2
                        from sis_nilai_m a
                        inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        where a.kode_pp='$request->kode_pp'
                        group by a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp
            ) c on a.kode_kd=c.kode_kd and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp and a.kode_matpel=c.kode_matpel and b.kode_kelas=c.kode_kelas and a.kode_sem=c.kode_sem
            where a.kode_pp='$request->kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$request->kode_matpel' and b.kode_kelas='$request->kode_kelas'
            order by a.kode_kd";
            $success['sql2'] = $sql2;
            $rs2 = DB::connection($this->db)->select($sql2) ;

            $row = json_decode(json_encode($rs2),true);

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $dt[0] = array();
                for($i=0;$i<count($row);$i++){
                    $dt[0][]=array("y"=>floatval($row[$i]["nilai"]),"kode_kd"=>$row[$i]["kode_kd"]);
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                $success["series"][0]= array(
                    "name"=> 'Rata-rata', "color"=>$color[0],"data"=>$dt[0],"type"=>"spline", "marker"=>array("enabled"=>false)
                );                
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBox(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }else{
                $nik= '';
                $kode_lokasi= '';
                $kode_pp='';
            }

            $rs = DB::connection($this->db)->select("select count(*) as jum from sis_siswa where kode_kelas='$request->kode_kelas' and kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' and flag_aktif=1
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs)> 0){
                $siswa = $rs[0]['jum'];
            }else{
                $siswa = 0;
            }
            
            $success['siswa']=$siswa;

            $rs2 = DB::connection($this->db)->select("select count(distinct b.nis) as jum from sis_nilai_m a
            inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            where a.kode_kelas='$request->kode_kelas' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$request->kode_matpel' and b.nilai = 0
            ");
            $rs2 = json_decode(json_encode($rs2),true);
            
            if(count($rs2)> 0){
                $siswa_tdk = $rs2[0]['jum'];
            }else{
                $siswa_tdk = 0;
            }

            $success['siswa_tdk']=$siswa_tdk;
            
            $rs3 = DB::connection($this->db)->select("
            select count(a.pelaksanaan) as jum
            from sis_nilai_m a
            where a.kode_kelas='$request->kode_kelas' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$request->kode_matpel' and isnull(a.pelaksanaan,'-') = '-'            
            ") ;

            $rs3 = json_decode(json_encode($rs3),true);
            
            if(count($rs3)> 0){
                $pelaksanaan = $rs3[0]['jum'];
            }else{
                $pelaksanaan = 0;
            }

            $success['pelaksanaan'] = $pelaksanaan;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json(['success'=>$success], $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    // MOBILE GURU

    public function getPesanKelas(Request $request)
    {
        $this->validate($request,[
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $sql = "select a.kode_matpel,b.nama,b.skode,isnull(c.jum,0) as jum_pesan, isnull(d.jum,0) as jum_baca, isnull(c.jum,0)- isnull(d.jum,0) as jum_belum
            from sis_guru_matpel_kelas a
            inner join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join (select a.kode_matpel,c.kode_kelas,a.kode_pp,a.kode_lokasi,count(*) as jum
                        from sis_pesan_m a 
                        inner join sis_pesan_d b on a.no_bukti=b.no_bukti and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                        inner join sis_siswa c on b.nik=c.nis and b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi and c.flag_aktif='1'
                        where a.tipe in ('info','nilai')
                        group by a.kode_matpel,c.kode_kelas,a.kode_pp,a.kode_lokasi ) c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp and a.kode_kelas=c.kode_kelas
            left join (select a.kode_matpel,c.kode_kelas,a.kode_pp,a.kode_lokasi,count(*) as jum
                        from sis_pesan_m a 
                        inner join sis_pesan_d b on a.no_bukti=b.no_bukti and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                        inner join sis_siswa c on b.nik=c.nis and b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi and c.flag_aktif='1'
                        where a.tipe in ('info','nilai') and b.sts_read_mob = 1
                        group by a.kode_matpel,c.kode_kelas,a.kode_pp,a.kode_lokasi ) d on a.kode_matpel=d.kode_matpel and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp and a.kode_kelas=d.kode_kelas
            where a.nik='$nik' and a.kode_kelas='$request->kode_kelas' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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

    public function getPesanKelasHistory(Request $request)
    {
        $this->validate($request,[
            'kode_kelas' => 'required',
            'kode_matpel' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            $kode_kelas = $request->kode_kelas;
            $kode_matpel = $request->kode_matpel;
            $sql = "select * from 
            (select a.*,x.nama,x.foto,convert(varchar,a.tgl_input,103) as tgl, convert(varchar,a.tgl_input,108) as jam 
            from (	select a.jenis,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas else '-' end as kontak,a.judul,a.pesan,a.kode_pp,a.kode_lokasi,a.tgl_input
                    from sis_pesan_m a
                    inner join sis_siswa c on a.nis=c.nis and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    inner join (select jenis,nis,kode_lokasi,kode_pp,max(tgl_input) as tgl_input
                                from sis_pesan_m
                                where tipe in ('info','nilai') and nik_user='$nik' and jenis='Siswa'
                                group by jenis,nis,kode_lokasi,kode_pp
                                ) b on a.jenis=b.jenis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.tgl_input=b.tgl_input and a.nis=b.nis
                     where a.tipe in ('info','nilai')  and a.nik_user = '$nik'  and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and c.kode_kelas='$kode_kelas' and a.kode_matpel='$kode_matpel'
                    ) a
            inner join (select a.nis as kode,a.nama,a.kode_pp,a.kode_lokasi,isnull(b.foto,'-') as foto 
                from sis_siswa a
                left join sis_hakakses b on a.nis=b.nik and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1'
            )x on a.kontak=x.kode and a.kode_lokasi=x.kode_lokasi and a.kode_pp=x.kode_pp
            union all
            select a.*,x.nama,x.foto,convert(varchar,a.tgl_input,103) as tgl, convert(varchar,a.tgl_input,108) as jam 
            from (
                select a.jenis,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas else '-' end as kontak,a.judul,a.pesan,a.kode_pp,a.kode_lokasi,a.tgl_input
                from sis_pesan_m a
                inner join (select jenis,kode_kelas,kode_lokasi,kode_pp,max(tgl_input) as tgl_input
                            from sis_pesan_m
                            where tipe in ('info','nilai') and nik_user='$nik' and jenis='Kelas'
                            group by jenis,kode_kelas,kode_lokasi,kode_pp
                            ) b on a.jenis=b.jenis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.tgl_input=b.tgl_input and a.kode_kelas=b.kode_kelas
                where a.tipe in ('info','nilai')  and a.nik_user = '$nik' and a.kode_kelas='$kode_kelas' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$kode_matpel'
            ) a
            inner join (select a.kode_kelas as kode,a.nama,a.kode_pp,a.kode_lokasi,'-' as foto 
                        from sis_kelas a
                        where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                        )x on a.kontak=x.kode and a.kode_lokasi=x.kode_lokasi and a.kode_pp=x.kode_pp
            ) a 
            order by a.tgl_input desc
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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

    public function getPesanKelasDetail(Request $request)
    {
        $this->validate($request,[
            'kode_matpel' => 'required',
            'jenis' => 'required',
            'kontak' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            $sql = "select a.jenis,a.kontak,x.nama,a.judul,a.pesan,a.tgl_input,isnull(b.file_dok,'-')as file_dok from (
                select a.no_bukti,a.jenis,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas else '-' end as kontak,a.judul,a.pesan,a.kode_pp,a.kode_lokasi,a.tgl_input,a.kode_matpel,a.ref1
            from sis_pesan_m a
            ) a
            inner join (select a.nis as kode,a.nama,a.kode_pp,a.kode_lokasi,isnull(b.foto,'-') as foto 
                         from sis_siswa a
                         left join sis_hakakses b on a.nis=b.nik and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                         where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1'
                         union all
                         select a.kode_kelas as kode,a.nama,a.kode_pp,a.kode_lokasi,'-' as foto 
                         from sis_kelas a
                         where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                    )x on a.kontak=x.kode and a.kode_lokasi=x.kode_lokasi and a.kode_pp=x.kode_pp
            left join sis_pesan_dok b on a.no_bukti=b.no_bukti and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kontak='$request->kontak' and a.jenis='$request->jenis' and a.kode_matpel='$request->kode_matpel'
            order by a.tgl_input
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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
