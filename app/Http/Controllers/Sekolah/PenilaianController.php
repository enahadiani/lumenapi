<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exports\NilaiExport;
use App\Imports\NilaiImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\NilaiTmp;

class PenilaianController extends Controller
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
                'notification'              => array (
                    "body" => $data["message"],
                    "title" => $data["title"]
                ),
                'data'              => $data,
                "android" => array (
                    "ttl" => "86400s",
                    "notification" => array (
                        "click_action" => $data["click_action"]
                        )
                    ),
                    
                );
		}else{

			$post = array(
				'registration_ids'  => $ids,
				'notification'              => array (
					"body" => $data["message"],
					"title" => $data["title"]
				),
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

            if(isset($request->kode_ta)){
                $filter .= " and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_sem)){
                $filter .= " and a.kode_sem='$request->kode_sem' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,a.nu,a.tgl_input
            ,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status
            from sis_nilai_m a
            where a.kode_lokasi='".$kode_lokasi."' and a.nik_user='$nik' $filter");
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

    public function listUpload(Request $request)
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

            if(isset($request->kode_ta)){
                $filter .= " and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_sem)){
                $filter .= " and a.kode_sem='$request->kode_sem' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,a.nu,a.tgl_input
            ,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, isnull(round((CAST (c.jum as float) / b.jum)*100,1),0) as persen
            from sis_nilai_m a
			inner join (select no_bukti,kode_lokasi,kode_pp, count(nis) as jum 
				from sis_nilai 
				group by no_bukti,kode_lokasi,kode_pp
			) b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
			left join (select no_bukti,kode_lokasi,kode_pp, count(nis) as jum 
				from sis_nilai_dok 
				group by no_bukti,kode_lokasi,kode_pp
			) c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='".$kode_lokasi."' and a.nik_user='$nik' $filter");
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_ta' => 'required',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis'=>'required',
            'kode_kd'=>'required',
            'nama_kd' => 'required',
            'pelaksanaan' => 'required',
            'nis'=>'required|array',
            'nilai'=>'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(count($request->nis) > 0){
                
                date_default_timezone_set("Asia/Bangkok");
                $per = date('ym');
                $no_bukti = $this->generateKode("sis_nilai_m", "no_bukti", $kode_lokasi."-NIL".$per.".", "00001");
                $strSQL = "select COUNT(*)+1 as jumlah from sis_nilai_m where kode_ta= '".$request->kode_ta."' and kode_sem= '".$request->kode_sem."' and kode_kelas= '".$request->kode_kelas."' and kode_matpel= '".$request->kode_matpel."' and kode_jenis= '".$request->kode_jenis."' and kode_pp='$request->kode_pp'";	
                $res = DB::connection($this->db)->select($strSQL);
                $res = json_decode(json_encode($res),true);
            	if(count($res) > 0){
                    $no_urut = $res[0]['jumlah'];
                }else{
                    $no_urut = 1;
                }                

                $ins = DB::connection($this->db)->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp,kode_kd,nama_kd,pelaksanaan) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$request->kode_pp,$request->kode_kd, $request->nama_kd,$request->pelaksanaan));

                $arr_id = array();
                for($i=0;$i<count($request->nis);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));                    
                }  
                
                $cek = DB::connection($this->db)->select("select nama from sis_matpel where kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' and kode_matpel='$request->kode_matpel' ");
                $cek = json_decode(json_encode($cek),true);
            	if(count($cek) > 0){
                    $nama_matpel = $cek[0]['nama'];
                }else{
                    $nama_matpel = '-';
                }

                $cek2 = DB::connection($this->db)->select("select nama from sis_jenisnilai where kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' and kode_jenis='$request->kode_jenis' ");
                $cek2 = json_decode(json_encode($cek2),true);
            	if(count($cek2) > 0){
                    $nama_jenis = $cek2[0]['nama'];
                }else{
                    $nama_jenis = '-';
                }

                $request->request->add([
                    'jenis' => 'Kelas',
                    'judul' => 'Penilaian Siswa',
                    'kontak' => $request->kode_kelas,
                    'kode_matpel' => $request->kode_matpel,
                    'tipe' => 'nilai',
                    'pesan' => 'Nilai '.$nama_jenis.' mata pelajaran '.$nama_matpel.' sudah bisa dilihat.',
                    'tipe' => 'nilai',
                    'ref1' => $no_bukti
                ]);

                $notif = app('App\Http\Controllers\Sekolah\PesanController')->store($request);
                $notif = json_decode(json_encode($notif),true);
                
                $success['notif'] = $notif['original'];
                DB::connection($this->db)->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil disimpan.";
            }else{
                $sts = true;
                $no_bukti = "-";
                $msg = "Data Penilaian gagal disimpan. Detail Penilaian tidak valid";
            }
            
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,b.nama as nama_pp,c.nama as nama_ta,d.nama as nama_kelas,f.nama as nama_jenis,g.nama as nama_matpel,isnull(j.jumlah,0)+1 as jumlah,a.nama_kd,a.kode_kd,a.pelaksanaan
            from sis_nilai_m a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                left join sis_ta c on a.kode_ta=c.kode_ta and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                left join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
                left join sis_jenisnilai f on a.kode_jenis=f.kode_jenis and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
                left join sis_matpel g on a.kode_matpel=g.kode_matpel and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
                left join sis_kd h on a.kode_kd=h.kode_kd and a.kode_lokasi=h.kode_lokasi and a.kode_pp=h.kode_pp
                left join ( select kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi,COUNT(*) as jumlah from sis_nilai_m 
                    where no_bukti <> '$request->no_bukti'
                    group by kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi
                    ) j on a.kode_ta=j.kode_ta and a.kode_lokasi=j.kode_lokasi and a.kode_pp=j.kode_pp and a.kode_jenis=j.kode_jenis and a.kode_sem=j.kode_sem and a.kode_matpel=j.kode_matpel and a.kode_kelas=j.kode_kelas
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp'  ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("select a.nis,a.nilai,b.nama from sis_nilai a inner join sis_siswa b on a.nis=b.nis where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp'  ");
            $res2 = json_decode(json_encode($res2),true);

            // $res3 = DB::connection($this->db)->select("select 
            // a.no_bukti,a.kode_lokasi,a.file_dok,a.no_urut,a.nama,a.kode_pp,a.nis from sis_nilai_dok a where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' ");
            // $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                // $success['data_dok'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                // $success['data_dok'] = [];
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
            'kode_ta' => 'required',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis'=>'required',
            'kode_kd'=>'required',
            'nama_kd'=>'required',
            'pelaksanaan'=>'required',
            'nis'=>'required|array',
            'nilai'=>'required|array',
            'nama_dok'=>'array|max:100'
        ]);
        
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->nis) > 0){
                date_default_timezone_set("Asia/Bangkok");
                $no_bukti = $request->no_bukti;
                $strSQL = "select nu as jumlah from sis_nilai_m where no_bukti='$no_bukti' ";	
                $cek = DB::connection($this->db)->select($strSQL);
                if(count($cek) > 0){
                    $no_urut = $cek[0]->jumlah;
                }else{
                    $no_urut = 1;
                }

                $del = DB::connection($this->db)->table('sis_nilai_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
    
                $del2 = DB::connection($this->db)->table('sis_nilai')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

                $ins = DB::connection($this->db)->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp,kode_kd,nama_kd,pelaksanaan) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$request->kode_pp,$request->kode_kd,$request->nama_kd,$request->pelaksanaan));

                $arr_id = array();
                for($i=0;$i<count($request->nis);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                }  

                $cek = DB::connection($this->db)->select("select nama from sis_matpel where kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' and kode_matpel='$request->kode_matpel' ");
                $cek = json_decode(json_encode($cek),true);
            	if(count($cek) > 0){
                    $nama_matpel = $cek[0]['nama'];
                }else{
                    $nama_matpel = '-';
                }

                $cek2 = DB::connection($this->db)->select("select nama from sis_jenisnilai where kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' and kode_jenis='$request->kode_jenis' ");
                $cek2 = json_decode(json_encode($cek2),true);
            	if(count($cek2) > 0){
                    $nama_jenis = $cek2[0]['nama'];
                }else{
                    $nama_jenis = '-';
                }

                $request->request->add([
                    'jenis' => 'Kelas',
                    'judul' => 'Update Penilaian Siswa',
                    'kontak' => $request->kode_kelas,
                    'kode_matpel' => $request->kode_matpel,
                    'tipe' => 'nilai',
                    'pesan' => 'Koreksi untuk nilai '.$nama_jenis.' mata pelajaran '.$nama_matpel.' sudah bisa dilihat.',
                    'tipe' => 'nilai',
                    'ref1' => $no_bukti
                ]);

                $notif = app('App\Http\Controllers\Sekolah\PesanController')->store($request);
                $notif = json_decode(json_encode($notif),true);
                
                $success['notif'] = $notif['original'];
                DB::connection($this->db)->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil diubah.";
            }else{
                $sts = true;
                $no_bukti = "-";
                $msg = "Data Penilaian gagal diubah. Detail Penilaian tidak valid";
            }
            
            $success['no_bukti'] = $no_bukti;
            $success['status'] = $sts;
            $success['message'] = $msg;
     
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            
            $del = DB::connection($this->db)->table('sis_nilai_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $del2 = DB::connection($this->db)->table('sis_nilai')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $sql3="select no_bukti,nama,file_dok from sis_nilai_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }
            }

            $del3 = DB::connection($this->db)->table('sis_nilai_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Penilaian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function loadSiswa(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nis,nama from sis_siswa where kode_kelas = '".$request->kode_kelas."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."'");
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

    public function validateNIS($nis,$kode_lokasi,$kode_pp,$kode_kelas){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select nis from sis_siswa where nis='$nis' and kode_lokasi='$kode_lokasi' and kode_pp = '$kode_pp' and kode_kelas ='$kode_kelas'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "NIS $nis tidak valid. ";
        }

        return $keterangan;

    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required',
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('sis_nilai_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->where('kode_pp', $request->kode_pp)->delete();

            $per = date('ym');

            $no_bukti = $this->generateKode("sis_nilai_m", "no_bukti", $kode_lokasi."-NIL".$per.".", "00001");

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new NilaiImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateNIS($row[0],$kode_lokasi,$request->kode_pp,$request->kode_kelas);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $x[] = NilaiTmp::create([
                        'no_bukti' => '-',
                        'nis' => strval($row[0]),
                        'nilai' => floatval($row[2]),
                        'kode_pp' => $request->kode_pp,
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $request->nik_user,
                        'status' => $sts,
                        'keterangan' => $ket,
                        'nu' => $no
                    ]);
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
            'kode_lokasi' => 'required',
            'kode_pp' => 'required',
            'nik' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $request->nik_user;
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        $kode_pp = $request->kode_pp;
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new NilaiExport($nik_user,$kode_lokasi,$kode_pp,$request->type,$request->kode_kelas,$request->kode_sem,$request->kode_jenis,$request->kode_matpel,$request->kode_kd), 'Nilai_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new NilaiExport($nik_user,$kode_lokasi,$kode_pp,$request->type,$request->kode_kelas,$request->kode_sem,$request->kode_jenis,$request->kode_matpel,$request->kode_kd), 'Nilai_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function getNilaiTmp(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_pp' => 'required'
        ]);

        $nik_user = $request->nik_user;
        $kode_pp = $request->kode_pp;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.nis,b.nama,a.nilai
            from sis_nilai_tmp a
            inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'  order by a.nu");
            $res= json_decode(json_encode($res),true);

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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function getKD(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_matpel) && $request->kode_matpel != ""){
                $filter .= " and a.kode_matpel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kelas) && $request->kode_kelas != ""){
                $filter .= " and c.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kd) && $request->kode_kd != ""){
                $filter .= " and a.kode_kd='$request->kode_kd' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_sem) && $request->kode_sem != ""){
                $filter .= " and a.kode_sem='$request->kode_sem' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_kd,a.nama
            from sis_kd a
            inner join sis_tingkat b on a.kode_tingkat=b.kode_tingkat and a.kode_lokasi=b.kode_lokasi
            inner join sis_kelas c on b.kode_tingkat=c.kode_tingkat and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' 
            $filter ");
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

    public function getPenilaianKe(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select COUNT(*)+1 as jumlah from sis_nilai_m where kode_ta= '".$request->kode_ta."' and kode_sem= '".$request->kode_sem."' and kode_kelas= '".$request->kode_kelas."' and kode_matpel= '".$request->kode_matpel."' and kode_jenis= '".$request->kode_jenis."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."' ");
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jumlah'] = $res[0]->jumlah;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Success!";
                $success['jumlah'] = 0;
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function showDokUpload(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $kode_pp = $request->kode_pp;

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,b.nama as nama_pp,c.nama as nama_ta,d.nama as nama_kelas,f.nama as nama_jenis,g.nama as nama_matpel,isnull(j.jumlah,0)+1 as jumlah,h.nama as nama_kd,a.kode_kd,a.pelaksanaan
            from sis_nilai_m a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                left join sis_ta c on a.kode_ta=c.kode_ta and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                left join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
                left join sis_jenisnilai f on a.kode_jenis=f.kode_jenis and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
                left join sis_matpel g on a.kode_matpel=g.kode_matpel and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
                left join sis_kd h on a.kode_kd=h.kode_kd and a.kode_lokasi=h.kode_lokasi and a.kode_pp=h.kode_pp
                left join ( select kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi,COUNT(*) as jumlah from sis_nilai_m 
                    where no_bukti <> '$no_bukti'
                    group by kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi
                    ) j on a.kode_ta=j.kode_ta and a.kode_lokasi=j.kode_lokasi and a.kode_pp=j.kode_pp and a.kode_jenis=j.kode_jenis and a.kode_sem=j.kode_sem and a.kode_matpel=j.kode_matpel and a.kode_kelas=j.kode_kelas
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti' and a.kode_pp='$kode_pp'  ");
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.nis,c.nama,isnull(c.file_dok,'-') as fileaddres,b.nama as nama_siswa
            from sis_nilai a 
            inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join sis_nilai_dok c on a.no_bukti=c.no_bukti and a.nis=c.nis and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' and a.kode_pp='$kode_pp' ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['data_dokumen'] = $res2;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function storeDokumen(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required|max:10',
            'no_bukti' => 'required|max:20'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $kode_pp = $request->kode_pp;
            $arr_foto = array();
            $arr_nis = array();
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
                            $arr_nis[] = $request->nis[$i];
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                            $arr_nis[] = $request->nis[$i];
                        }     
                    }
                    
                    $del3 = DB::connection($this->db)->table('sis_nilai_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $kode_pp)->delete();
                }

                if(count($arr_nis) > 0){
                    for($i=0; $i<count($arr_nis);$i++){
                        $ins3[$i] = DB::connection($this->db)->insert("insert into sis_nilai_dok (no_bukti,kode_lokasi,file_dok,no_urut,nama,kode_pp,nis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','-','$kode_pp','".$arr_nis[$i]."') "); 
                    }
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Data Dokumen berhasil diupload.";
                    $success['no_bukti'] = $no_bukti;
                    $success['nis'] = $request->nis;
                }
                else{
                    $success['status'] = true;
                    $success['message'] = "Data Dokumen berhasil gagal diupload. Dokumen file tidak valid. (2)";
                    $success['no_bukti'] = $no_bukti;
                }
            }else{
                $success['status'] = true;
                $success['message'] = "Data Dokumen berhasil gagal diupload. Dokumen file tidak valid. (3)";
                $success['no_bukti'] = $no_bukti;
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumenn gagal diupload ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function deleteDokumen(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'nis' => 'required',
            'kode_pp' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,nama,file_dok from sis_nilai_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp' and nis='$request->nis' ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection($this->db)->table('sis_nilai_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen Penilaian berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen Penilaian gagal dihapus.";
            }

            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function sendNotif(Request $request)
	{
		$this->validate($request,[
			"token" => 'required|max:300',
			"data" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

        try{
            $token = $request->token;
            if($token != "-"){
                
				$payload = $request->data;
                $res = $this->gcm($token,$payload);
                $hasil= json_decode($res,true);
                $success['hasil'] = $hasil;
                if(isset($hasil['success'])){
                    if($hasil['failure'] > 0){
                        $sts = 0;
                    }else{
                        $sts = 1;
                    }
                    
                    $success['status'] = true;
                    $success['message'] = "Sukses";
                }else{
                    $success['status'] = false;
                    $success['message'] = "Gagal";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Gagal";
            }
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}
    
    
    public function getMatpel(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kelas)){
                $filter .= "and a.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_matpel)){
                $filter .= "and a.kode_matpel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select distinct a.kode_matpel,b.nama 
            from sis_guru_matpel_kelas a
            inner join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_pp=b.kode_pp
            where a.nik='$nik' $filter ");
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

    public function getKelas(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kelas)){
                $filter .= "and a.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select distinct a.kode_kelas,b.nama 
            from sis_guru_matpel_kelas a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_pp=b.kode_pp
            where a.nik='$nik' $filter ");
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

    public function getSiswa(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->nis)){
                $filter .= "and a.nis='$request->nis' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.nis,a.nama 
            from sis_siswa a where a.kode_lokasi='$kode_lokasi' $filter ");
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



}
