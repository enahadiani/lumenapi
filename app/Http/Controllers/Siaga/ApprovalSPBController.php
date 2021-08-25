<?php

namespace App\Http\Controllers\Siaga;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class ApprovalSPBController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    function sendMail($email,$to_name,$data){
        try {
            $template_data = array("name"=>$to_name,"body"=>$data);
            Mail::send('mail', $template_data,
            function ($message) use ($email) {
                $message->to($email)
                ->subject('Pengajuan (SAI LUMEN)');
            });
            
            return array('status' => 200, 'msg' => 'Sent successfully');
        } catch (Exception $ex) {
            return array('status' => 200, 'msg' => 'Something went wrong, please try later.');
        }  
    }

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
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select a.kode_jab
            from apv_karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'APPROVE' else 'REJECT' end as status
            from apv_pesan a
			inner join gr_spb2_m c on a.no_bukti=c.no_spb and a.kode_lokasi=c.kode_lokasi 
            left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            where a.kode_lokasi='$kode_lokasi' and b.kode_jab='".$kode_jab."' and b.nik= '$nik_user' 
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
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select a.kode_jab
            from apv_karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection($this->db)->select("
			select b.no_spb as no_bukti,b.nilai,convert(varchar,b.tanggal,103)  as tanggal,b.keterangan
            from apv_flow a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.kode_jab='".$kode_jab."' and a.nik= '$nik_user'
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
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
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
            'no_aju' => 'required|max:20',
            'status' => 'required|max:1',
            'keterangan' => 'required|max:150',
            'no_urut' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            // $get = DB::connection($this->db)->select("select isnull(max(id)+1,1) as no_app from apv_pesan where kode_lokasi='$kode_lokasi' ");
            // $no_app = (isset($get[0]->no_app) ? $get[0]->no_app : 1);

            $no_bukti = $request->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $nik_app = $nik_user;
            $token_player = array();
            $token_player2 = array();
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('".$no_bukti."','".$kode_lokasi."','".$request->input('keterangan')."','".$request->input('tanggal')."','".$request->input('no_urut')."','".$request->input('status')."','AJUSPB') ");

            $upd =  DB::connection($this->db)->table('apv_flow')
            ->where('no_bukti', $no_bukti)    
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_urut', $request->input('no_urut'))
            ->update(['status' => $request->input('status'),'tgl_app'=>$request->input('tanggal')]);

            $max = DB::connection($this->db)->select("select max(no_urut) as nu from apv_flow where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi' 
            ");
            $max = json_decode(json_encode($max),true);

            $min = DB::connection($this->db)->select("select min(no_urut) as nu from apv_flow where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi' 
            ");
            $min = json_decode(json_encode($min),true);

            if($request->status == 2){
                $nu = $request->no_urut+1;

                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>$request->input('tanggal')]);
                
                //send to App selanjutnya
                if($request->no_urut != $max[0]['nu']){

                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,b.nik,isnull(b.email,'-') as email
                    from apv_flow a
                    left join apv_karyawan b on a.kode_jab=b.kode_jab 
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu and a.kode_lokasi='$kode_lokasi'";

                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $no_telp = $rs[0]["no_telp"];
                        $nik_app1 = $rs[0]["nik"];$app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }

                    $upd3 =  DB::connection($this->db)->table('gr_spb2_m')
                    ->where('no_spb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => '1']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table('gr_spb2_m')
                    ->where('no_spb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'S']);

                    $psn = "Approver terakhir";
                }

                // //send to nik buat
                $sqlbuat = "
                select isnull(c.no_telp,'-') as no_telp,b.nik_user as nik_buat,isnull(c.email,'-') as email
                from gr_spb2_m b 
                inner join apv_karyawan c on b.nik_user=c.nik 
                where b.no_spb='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $no_telp2 = $rs2[0]["no_telp"];
                    $nik_buat = $rs2[0]['nik_buat'];
                    if(intval($request->no_urut) == intval($max[0]['nu'])){
                        $app_email2 = $rs2[0]['email'];
                    }
                }else{
                    $no_telp2 = "-";
                    $nik_buat = "-";
                }
                $success['approval'] = "Approve";
                $request->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga-spb',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                        $success['no_pooling'] = $no_pool;
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                    $msg_email = "";
                }

                if(isset($app_email2) && $app_email2 != "-"){
                    $pesan_header = "Pengajuan $no_bukti Anda telah diapprove oleh $nik_user, berikut ini rinciannya:";
                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga-spb',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email2,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                        $success['no_pooling2'] = $no_pool;
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                        $msg_email = "";
                }

            }else{
                $nu=$request->no_urut-1;

                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>NULL]);


                if(intval($request->no_urut) != intval($min[0]['nu'])){
                    // //send to approver sebelumnya
                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,b.nik,isnull(b.email,'-') as email
                    from apv_flow a
                    left join apv_karyawan b on a.kode_jab=b.kode_jab 
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu and a.kode_lokasi='$kode_lokasi' ";
                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $no_telp = $rs[0]["no_telp"];
                        $nik_app1 = $rs[0]["nik"];$app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }
                    $upd3 =  DB::connection($this->db)->table('gr_spb2_m')
                    ->where('no_spb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table('gr_spb2_m')
                    ->where('no_spb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select isnull(c.no_telp,'-') as no_telp,b.nik_user as nik_buat,isnull(c.email,'-') as email
                from gr_spb2_m b
                inner join apv_karyawan c on b.nik_user=c.nik 
                where b.no_spb='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $no_telp2 = $rs2[0]["no_telp"];
                    $nik_buat = $rs2[0]["nik_buat"];
                    if(intval($request->no_urut) == intval($min[0]['nu'])){
                        $app_email2 = $rs2[0]['email'];
                    }
                }else{
                    $no_telp2 = "-";
                    $nik_buat = "-";
                }

                $request->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga-spb',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                        $success['no_pooling'] = $no_pool;
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                    $msg_email = "";
                }

                if(isset($app_email2) && $app_email2 != "-"){
                    $pesan_header = "Pengajuan $no_bukti Anda telah direturn oleh $nik_user, berikut ini rinciannya:";
                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga-spb',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email2,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                        $success['no_pooling2'] = $no_pool;
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                        $msg_email = "";
                }
                
                $success['approval'] = "Return";
            }

            DB::connection($this->db)->commit();
            
            $success['status'] = true;
            $success['message'] = "Data Approval Pengajuan berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            $success['nik_buat'] = $nik_buat;
            $success['nik_app1'] = $nik_app1;
            $success['nik_app'] = $nik_app;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Approval Pengajuan gagal disimpan ".$e;
            $success['no_aju'] = "";
            $success['nik_buat'] = "-";
            $success['nik_app1'] = "-";
            $success['nik_app'] = "-";
            $success['approval'] = "Failed";
            DB::connection($this->db)->rollback();
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
        try {
            $no_aju = $request->no_aju;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,b.nilai,b.tanggal,b.keterangan,a.no_urut,b.nama
            from apv_flow a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_beban,convert(varchar,a.tanggal,103) as tanggal,a.modul,b.kode_pp+' - '+b.nama as pp,a.keterangan,a.nilai_curr as nilai,e.catatan,convert(varchar,a.tgl_input,103) as tgl_input,a.nik_user,e.no_bukti as no_aju,convert(varchar,e.tgl_input,103) as tgl_ver,a.kode_curr 
            from gr_beban_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join gr_app_m e on a.no_beban=e.no_app and a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_spb='$no_aju' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            // $sql3="select no_spb,no_gambar,nu,kode_jenis,no_ref from gr_pb_dok where kode_lokasi='".$kode_lokasi."' and no_spb='$no_aju' order by nu";
            // $res3 = DB::connection($this->db)->select($sql3);
            // $res3 = json_decode(json_encode($res3),true);

            $sql4 = "
			select convert(varchar,e.id) as id,a.no_spb,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from gr_spb2_m a
            inner join apv_pesan e on a.no_spb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_spb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
			order by id2
	        ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                // $success['data_dokumen'] = $res3;
                $success['data_histori'] = $res4;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                // $success['data_dokumen'] = [];
                $success['data_histori'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select status, nama from apv_status where kode_lokasi='$kode_lokasi' and status in ('2','3')
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
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPreview(Request $request)
    {
        try {
            
            $no_bukti = $request->id;
            $id = $request->jenis;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($id == "default"){
                $rs = DB::connection($this->db)->select("select max(id) as id
                from apv_pesan a
                left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
                where a.kode_lokasi='$kode_lokasi' and a.modul='AJUSPB' and b.nik= '$nik_user' and a.no_bukti='$no_bukti'");
                $id = $rs[0]->id;
            }else{
                $id = $id;
            }

            $sql="select a.id,a.no_bukti,a.tanggal,b.nama,b.nilai,b.keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = 'R' then 'Return' end as status
            from apv_pesan a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join apv_flow e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut and a.kode_lokasi=e.kode_lokasi
            where a.no_bukti='$no_bukti' and a.modul='AJUSPB' and a.id='$id' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
