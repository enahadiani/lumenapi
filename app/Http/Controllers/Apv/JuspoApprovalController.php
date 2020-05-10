<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class JuspoApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function sendMail($email,$to_name,$data){
        try {

            
            $template_data = array("name"=>$to_name,"body"=>$data);
            Mail::send('mail', $template_data,
            function ($message) use ($email) {
                $message->to($email)
                ->subject('Pengajuan Justifikasi Kebutuhan (SAI LUMEN)');
            });
            
            return array('status' => 200, 'msg' => 'Sent successfully');
        } catch (Exception $ex) {
            return array('status' => 200, 'msg' => 'Something went wrong, please try later.');
        }  
    }

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection('sqlsrv2')->select("select a.kode_jab
            from apv_karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection('sqlsrv2')->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,a.tanggal
            from apv_pesan a
            left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            where a.kode_lokasi='$kode_lokasi' and b.status='2' and a.modul='JP' and b.kode_jab='".$kode_jab."' and b.nik= '$nik_user'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPengajuan()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection('sqlsrv2')->select("select a.kode_jab
            from apv_karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection('sqlsrv2')->select("select b.no_bukti,b.no_juskeb,b.no_dokumen,b.kode_pp,b.waktu,b.kegiatan,b.dasar,b.nilai
            from apv_flow a
            inner join apv_juspo_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.kode_jab='".$kode_jab."' and a.nik= '$nik_user'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['kode_jab'] = $kode_jab;
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
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
            'tanggal' => 'required',
            'no_aju' => 'required',
            'status' => 'required',
            'keterangan' => 'required',
            'no_urut' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $token_player = array();
            $token_player2 = array();

            $ins = DB::connection('sqlsrv2')->insert('insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values (?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$request->input('keterangan'),$request->input('tanggal'),$request->input('no_urut'),$request->input('status'),'JP']);

            $upd =  DB::connection('sqlsrv2')->table('apv_flow')
            ->where('no_bukti', $no_bukti)    
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_urut', $request->input('no_urut'))
            ->update(['status' => $request->input('status'),'tgl_app'=>$request->input('tanggal')]);

            $max = DB::connection('sqlsrv2')->select("select max(no_urut) as nu from apv_flow where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi' 
            ");
            $max = json_decode(json_encode($max),true);

            $min = DB::connection('sqlsrv2')->select("select min(no_urut) as nu from apv_flow where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi' 
            ");
            $min = json_decode(json_encode($min),true);

            if($request->status == 2){ // APPROVE
                $nu = $request->no_urut+1;

                $upd2 =  DB::connection('sqlsrv2')->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>$request->input('tanggal')]);
                
                //send to App selanjutnya
                if($request->no_urut != $max[0]['nu']){

                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,c.token,b.nik
                    from apv_flow a
                    left join apv_karyawan b on a.kode_jab=b.kode_jab 
                    left join api_token_auth c on b.nik=c.nik and b.kode_lokasi=c.kode_lokasi
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu and a.kode_lokasi='$kode_lokasi'";

                    $rs = DB::connection('sqlsrv2')->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $token_player = array();
                        for($i=0;$i<count($rs);$i++){
    
                            $no_telp = $rs[0]["no_telp"];
                            $nik_app1 = $rs[0]["nik"];
                            array_push($token_player,$rs[$i]['token']);
                            
                        }
                        $title = "Approval Pengajuan Justifikasi Pengadaan";
                        $content = "[Approval] Pengajuan Justifikasi Pengadaan ".$no_bukti." telah di approve oleh $nik_user , Menunggu approval anda.";
                        // $notif1 = sendNotif($title,$content,$token_player);
                        // $wa1 = sendWA($no_telp,$content);
                        $psn = "Menunggu approval $nik_app1 ";
                        $exec_notif = array();
                        for($t=0;$t<count($token_player);$t++){

                            $insert[$t] = DB::connection('sqlsrv2')->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?,?) ",[$kode_lokasi,$nik_app1,$token_player[$t],$title,$content,date('Y-m-d'),'-']);

                        }
                    }

                    $upd3 =  DB::connection('sqlsrv2')->table('apv_juspo_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $request->status]);
                }else{
                    $upd3 =  DB::connection('sqlsrv2')->table('apv_juspo_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'S']);
                    $psn = "Approver terakhir";
                }

                //send to nik buat
                $sqlbuat = "
                select distinct isnull(c.no_telp,'-') as no_telp,d.token,b.nik_buat
                from apv_flow a
                inner join apv_juspo_m b on a.no_bukti=b.no_bukti 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                inner join api_token_auth d on c.nik=d.nik and c.kode_lokasi=d.kode_lokasi
                where a.no_bukti='".$no_bukti."' and a.kode_lokasi='$kode_lokasi' ";
                $rs2 = DB::connection('sqlsrv2')->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $token_player2 = array();
                    for($i=0;$i<count($rs2);$i++){

                        $no_telp2 = $rs2[0]["no_telp"];
                        $nik_buat = $rs2[0]['nik_buat'];
                        array_push($token_player2,$rs2[$i]['token']);
                        
                    }
                    $title = "Approval Pengajuan Justifikasi Pengadaan";
                    $content = "[Approval] Pengajuan Justifikasi Pengadaan ".$no_bukti." anda telah di approve oleh $nik_user. ".$psn;
                    // $notif2 = sendNotif($title,$content,$token_player);
                    // $wa2 = sendWA($no_telp2,$content);
                    $exec_notif2 = array();
                    for($t=0;$t<count($token_player2);$t++){

                        $insert2[$t] = DB::connection('sqlsrv2')->insert(" insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_buat,$token_player2[$t],$title,$content,date('Y-m-d'),'-']);

                    }
                    
                }
                $success['approval'] = "Approve";
            }else{
                $nu=$request->no_urut-1;

                $upd2 =  DB::connection('sqlsrv2')->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>NULL]);


                if(intval($request->no_urut) != intval($min[0]['nu'])){
                    //send to approver sebelumnya
                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,c.token,b.nik
                    from apv_flow a
                    left join apv_karyawan b on a.kode_jab=b.kode_jab 
                    left join api_token_auth c on b.nik=c.nik and b.kode_lokasi=c.kode_lokasi
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu and a.kode_lokasi='$kode_lokasi' ";
                    $rs = DB::connection('sqlsrv2')->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $token_player = array();
                        for($i=0;$i<count($rs);$i++){
                            
                            $no_telp = $rs[0]["no_telp"];
                            $nik_app1 = $rs[0]["nik"];
                            array_push($token_player,$rs[$i]['token']);
                            
                        }
                        $title = "Approval Pengajuan Justifikasi Pengadaan";
                        $content = "[Return] Pengajuan Justifikasi Pengadaan ".$no_bukti." telah di return oleh $nik_user";
                        // $notif1 = sendNotif($title,$content,$token_player);
                        // $wa1 = sendWA($no_telp,$content);
                        // $psn = "Menunggu approval $nik_app1 ";
                        $exec_notif = array();
                        for($t=0;$t<count($token_player);$t++){
                            $insert[$t] = DB::connection('sqlsrv2')->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_app1,$token_player[$t],$title,$content,date('Y-m-d'),'-']);
                            
                        }
                    }
                    $upd3 =  DB::connection('sqlsrv2')->table('apv_juspo_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $upd3 =  DB::connection('sqlsrv2')->table('apv_juspo_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select distinct isnull(c.no_telp,'-') as no_telp,d.token,b.nik_buat
                from apv_flow a
                inner join apv_juspo_m b on a.no_bukti=b.no_bukti 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                inner join api_token_auth d on c.nik=d.nik and c.kode_lokasi=d.kode_lokasi
                where a.no_bukti='".$no_bukti."' ";
                $rs2 = DB::connection('sqlsrv2')->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $token_player2 = array();
                    for($i=0;$i<count($rs2);$i++){

                        $no_telp2 = $rs2[0]["no_telp"];
                        $nik_buat = $rs2[0]["nik_buat"];
                        array_push($token_player2,$rs2[$i]['token']);
                        
                    }
                    $title = "Approval Pengajuan Justifikasi Pengadaan";
                    $content = "[Return] Pengajuan Justifikasi Pengadaan ".$no_bukti." anda telah di direturn oleh $nik_user. ";
                    // $notif2 = sendNotif($title,$content,$token_player2);
                    // $wa2 = sendWA($no_telp2,$content);
                    $exec_notif2 = array();
                    for($t=0;$t<count($token_player2);$t++){

                        $insert[$t] = DB::connection('sqlsrv2')->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_buat,$token_player2[$t],$title,$content,date('Y-m-d'),'-']);

                    }
                }
                $success['approval'] = "Return";
            }
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Approval Justifikasi Pengadaan berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            $success['nik_buat'] = $nik_buat;
            $success['nik_app'] = $nik_user;
            $success['nik_app_next'] = $nik_app1;
            $success['token_players_app'] = $token_player;
            $success['token_players_buat'] = $token_player2;
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Approval Justifikasi Pengadaan gagal disimpan ".$e;
            $success['no_aju'] = "";
            $success['nik_buat'] = "";
            $success['nik_app'] = "";
            $success['nik_app_next'] = "";
            $success['token_players_app'] = [];
            $success['token_players_buat'] = [];
            $success['approval'] = "Failed";
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($no_aju)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,b.no_juskeb,b.no_dokumen,b.kode_pp,b.waktu,b.kegiatan,b.dasar,b.nilai,a.no_urut,c.nama as nama_pp
            from apv_flow a
            inner join apv_juspo_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join apv_pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang,harga,jumlah,nilai from apv_juspo_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju'  order by no_urut";					
            $res2 = DB::connection('sqlsrv2')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select a.no_bukti,a.nama,a.file_dok from apv_juskeb_dok a inner join apv_juspo_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and b.no_bukti='$no_aju'  order by a.no_urut";
            $res3 = DB::connection('sqlsrv2')->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
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
    public function update(Request $request, $no_bukti)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($no_bukti)
    {
        //
    }

    public function getStatus()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select status, nama from apv_status where kode_lokasi='$kode_lokasi' and status in ('2','3')
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
