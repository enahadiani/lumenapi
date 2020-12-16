<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class JuskebApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'silo';
    public $db = 'dbsilo';

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

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.kegiatan,a.tanggal,isnull(c.kode_kota,'-') as kode_kota,isnull(d.nama,'-') as nama_kota,c.nilai,case when a.status = '2' then 'APPROVE' else 'REJECT' end as status
            from apv_pesan a
			inner join apv_juskeb_m c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi
			left join apv_kota d on c.kode_kota=d.kode_kota and c.kode_lokasi=d.kode_lokasi and c.kode_pp=d.kode_pp
            left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            where a.kode_lokasi='$kode_lokasi' and b.status='2' and b.kode_jab='".$kode_jab."' and b.nik= '$nik_user' and a.modul='JK' 
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

            $res = DB::connection($this->db)->select("select b.no_bukti,b.no_dokumen,b.kode_pp,b.waktu,b.kegiatan,b.dasar,b.nilai,b.kode_kota,isnull(c.nama,'-') as nama_kota,p.nama as nama_pp
            from apv_flow a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on b.kode_kota=c.kode_kota and b.kode_lokasi=c.kode_lokasi and b.kode_pp=c.kode_pp
            inner join apv_pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.sts_ver='1' and a.kode_jab='".$kode_jab."' and a.nik= '$nik_user'
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
            $no_bukti = $request->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $token_player = array();
            $token_player2 = array();
            $ins = DB::connection($this->db)->insert('insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values (?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$request->input('keterangan'),$request->input('tanggal'),$request->input('no_urut'),$request->input('status'),'JK']);

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
                    select isnull(b.no_telp,'-') as no_telp,c.token,b.nik
                    from apv_flow a
                    left join apv_karyawan b on a.kode_jab=b.kode_jab 
                    left join api_token_auth c on b.nik=c.nik and b.kode_lokasi=c.kode_lokasi
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu and a.kode_lokasi='$kode_lokasi'";

                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $token_player = array();
                        for($i=0;$i<count($rs);$i++){
    
                            $no_telp = $rs[0]["no_telp"];
                            $nik_app1 = $rs[0]["nik"];
                            array_push($token_player,$rs[$i]['token']);
                            
                        }
                        $title = "Approval Pengajuan Justifikasi Kebutuhan";
                        $content = "[Approval] Pengajuan Justifikasi Kebutuhan ".$no_bukti." telah di approve oleh $nik_user , Menunggu approval anda.";
                        // $notif1 = sendNotif($title,$content,$token_player);
                        // $wa1 = sendWA($no_telp,$content);
                        $psn = "Menunggu approval $nik_app1 ";
                        $exec_notif = array();
                        for($t=0;$t<count($token_player);$t++){

                            $insert[$t] = DB::connection($this->db)->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?,?) ",[$kode_lokasi,$nik_app1,$token_player[$t],$title,$content,date('Y-m-d'),'-']);

                        }
                    }

                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $request->status]);
                }else{
                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'S']);
                    $psn = "Approver terakhir";
                }

                //send to nik buat
                $sqlbuat = "
                select distinct isnull(c.no_telp,'-') as no_telp,d.token,b.nik_buat
                from apv_flow a
                inner join apv_juskeb_m b on a.no_bukti=b.no_bukti 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                left join api_token_auth d on c.nik=d.nik and c.kode_lokasi=d.kode_lokasi
                where a.no_bukti='".$no_bukti."' and a.kode_lokasi='$kode_lokasi' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $token_player2 = array();
                    for($i=0;$i<count($rs2);$i++){

                        $no_telp2 = $rs2[0]["no_telp"];
                        $nik_buat = $rs2[0]['nik_buat'];
                        array_push($token_player2,$rs2[$i]['token']);
                        
                    }
                    $title = "Approval Pengajuan Justifikasi Kebutuhan";
                    $content = "[Approval] Pengajuan Justifikasi Kebutuhan ".$no_bukti." anda telah di approve oleh $nik_user. ".$psn;
                    // $notif2 = sendNotif($title,$content,$token_player2);
                    // $wa2 = sendWA($no_telp2,$content);
                    $exec_notif2 = array();
                    for($t=0;$t<count($token_player2);$t++){

                        $insert2[$t] = DB::connection($this->db)->insert(" insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_buat,$token_player2[$t],$title,$content,date('Y-m-d'),'-']);

                    }
                    
                }
                $success['approval'] = "Approve";

            }else{
                $nu=$request->no_urut-1;

                $upd2 =  DB::connection($this->db)->table('apv_flow')
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
                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $token_player = array();
                        for($i=0;$i<count($rs);$i++){
                            
                            $no_telp = $rs[0]["no_telp"];
                            $nik_app1 = $rs[0]["nik"];
                            array_push($token_player,$rs[$i]['token']);
                            
                        }
                        $title = "Approval Pengajuan Justifikasi Kebutuhan";
                        $content = "[Return] Pengajuan Justifikasi Kebutuhan ".$no_bukti." telah di return oleh $nik_user";
                        // $notif1 = sendNotif($title,$content,$token_player);
                        // $wa1 = sendWA($no_telp,$content);
                        // $psn = "Menunggu approval $nik_app1 ";
                        $exec_notif = array();
                        for($t=0;$t<count($token_player);$t++){
                            $insert[$t] = DB::connection($this->db)->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_app1,$token_player[$t],$title,$content,date('Y-m-d'),'-']);
                            
                        }
                    }
                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select distinct isnull(c.no_telp,'-') as no_telp,d.token,b.nik_buat
                from apv_flow a
                inner join apv_juskeb_m b on a.no_bukti=b.no_bukti 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                left join api_token_auth d on c.nik=d.nik and c.kode_lokasi=d.kode_lokasi
                where a.no_bukti='".$no_bukti."' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $token_player2 = array();
                    for($i=0;$i<count($rs2);$i++){

                        $no_telp2 = $rs2[0]["no_telp"];
                        $nik_buat = $rs2[0]["nik_buat"];
                        array_push($token_player2,$rs2[$i]['token']);
                        
                    }
                    $title = "Approval Pengajuan Justifikasi Kebutuhan";
                    $content = "[Return] Pengajuan Justifikasi Kebutuhan ".$no_bukti." anda telah di direturn oleh $nik_user. ";
                    // $notif2 = sendNotif($title,$content,$token_player2);
                    // $wa2 = sendWA($no_telp2,$content);
                    $exec_notif2 = array();
                    for($t=0;$t<count($token_player2);$t++){

                        $insert[$t] = DB::connection($this->db)->insert("insert into apv_notif_m (kode_lokasi,nik,token,title,isi,tgl_input,kode_pp) values (?, ?, ?, ?, ?, ?, ?) ",[$kode_lokasi,$nik_buat,$token_player2[$t],$title,$content,date('Y-m-d'),'-']);

                    }
                }
                
                $success['approval'] = "Return";
            }

            DB::connection($this->db)->commit();
            
            $rsi = DB::connection($this->db)->select("select a.nik,b.nama,b.id_device
            from apv_flow a
            inner join apv_karyawan b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti='$no_bukti' and a.status='1' ");
            if(count($rsi) > 0){
                $success['id_device_app'] = $rsi[0]->id_device; 
                $success['nik_device_app'] = $rsi[0]->nik; 
            }else{
                $success['id_device_app'] = '-'; 
                $success['nik_device_app'] = '-'; 
            }
            
            $success['status'] = true;
            $success['message'] = "Data Approval Justifikasi Kebutuhan berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            $success['nik_buat'] = $nik_buat;
            $success['nik_app'] = $nik_user;
            $success['nik_app_next'] = $nik_app1;
            $success['token_players_app'] = $token_player;
            $success['token_players_buat'] = $token_player2;
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Approval Justifikasi Kebutuhan gagal disimpan ".$e;
            $success['no_aju'] = "";
            $success['nik_buat'] = "";
            $success['nik_app'] = "";
            $success['nik_app_next'] = "";
            $success['token_players_app'] = [];
            $success['token_players_buat'] = [];
            $success['approval'] = "Failed";
            DB::connection($this->db)->rollback();
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
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $cek = "select no_bukti from apv_juspo_m where no_juskeb = '$no_aju' ";
            $rs = DB::connection($this->db)->select($cek);
            $rs = json_decode(json_encode($rs),true);
            if(count($rs)> 0){
                $no_juspo = $rs[0]['no_bukti'];
            }else{
                $no_juspo = "-";
            }

            $sql="select a.no_bukti,b.no_dokumen,b.kode_pp,b.kode_kota,b.waktu,b.kegiatan,b.dasar,b.nilai,a.no_urut,c.nama as nama_pp,d.nama as nama_kota
            from apv_flow a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join apv_pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            left join apv_kota d on b.kode_kota=d.kode_kota and b.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.barang_klp,a.barang,a.harga,a.jumlah,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp from apv_juskeb_d a 
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_aju'  order by a.no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju' and jenis='PO' order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql6="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju' and jenis='PBD' order by no_urut";
            $res6 = DB::connection($this->db)->select($sql6);
            $res6 = json_decode(json_encode($res6),true);

            // $sql4="select a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama,g.nama as nama_jab,isnull(convert(varchar,a.tanggal,103),'-') as tgl,isnull(f.foto,'-') as foto  
            // from apv_juskeb_m a
            // inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            // inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
			// inner join apv_jab g on f.kode_jab=g.kode_jab and f.kode_lokasi=g.kode_lokasi
            // where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi'
			// union all
			// select a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,g.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tgl,isnull(f.foto,'-') as foto  
            // from apv_juskeb_m a
            // inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            // inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            // inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
			// inner join apv_jab g on f.kode_jab=g.kode_jab and f.kode_lokasi=g.kode_lokasi
            // where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi' ";
            $sql4 = "select e.no_bukti as id,a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama,-3 as no_urut,-4 as id2 
            from apv_juskeb_m a
            inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi'
			union all
			select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from apv_juskeb_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi' 
            union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_juspo' and a.kode_lokasi='$kode_lokasi'
			union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,a.nik_buat as nik,f.nama,-1 as no_urut,e.id as id2
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on a.nik_buat=f.nik and a.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_juspo' and a.kode_lokasi='$kode_lokasi' and e.modul='PO'
			order by id2
	        ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $sql5="select a.no_bukti,count(barang_klp) as jum_klp,sum(grand_total) as tot_barang,count(jumlah) as jum_barang  
            from apv_juskeb_d a 
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi'
            group by a.no_bukti";
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_total'] = $res5;
                $success['data_dokumen'] = $res3;
                $success['data_dokumen2'] = $res6;
                $success['data_histori'] = $res4;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_total'] = [];
                $success['data_dokumen'] = [];
                $success['data_dokumen2'] = [];
                $success['data_histori'] = [];
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

    public function getPreview($no_bukti,$id)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($id == "default"){
                $rs = DB::connection($this->db)->select("select max(id) as id
                from apv_pesan a
                left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
                where a.kode_lokasi='$kode_lokasi' and a.modul='JK' and b.nik= '$nik_user' and a.no_bukti='$no_bukti'");
                $id = $rs[0]->id;
            }else{
                $id = $id;
            }

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.kegiatan,b.nilai,e.nik,convert(varchar,a.tanggal,105) as tgl,case when a.status = '2' then 'Approved' when a.status = 'R' then 'Return' end as status
            from apv_pesan a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join apv_pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            inner join apv_flow e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut and a.kode_lokasi=e.kode_lokasi
            where a.no_bukti='$no_bukti' and a.modul='JK' and a.id='$id' ";
            
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
