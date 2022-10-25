<?php

namespace App\Http\Controllers\Siaga;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Carbon\Carbon;
use Log;

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
            from karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'Approved' else 'Returned' end as status,'Beban' as modul
            from apv_pesan a
			inner join gr_spb2_m c on a.no_bukti=c.no_spb and a.kode_lokasi=c.kode_lokasi 
            left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            where a.kode_lokasi='$kode_lokasi' and b.nik= '$nik_user' 
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

    public function getPengajuan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $get = DB::connection($this->db)->select("select a.kode_jab
            // from karyawan a
            // where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            // ");
            // $get = json_decode(json_encode($get),true);
            // if(count($get) > 0){
            //     $kode_jab = $get[0]['kode_jab'];
            // }else{
            //     $kode_jab = "";
            // }

            $filter = "";
            if(isset($request->no_spb) && $request->no_spb != ""){
                $filter .= " and b.no_spb='$request->no_spb' ";
            }

            if(isset($request->start_date) && $request->start_date != "" && isset($request->end_date) && $request->end_date != ""){
                $filter .= " and b.tanggal between '$request->start_date' and '$request->end_date' ";
            }
            
            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == "Beban"){

                    $res = DB::connection($this->db)->select("
                    select b.no_spb as no_bukti,b.nilai,convert(varchar,b.tanggal,103)  as tanggal,b.keterangan
                    from apv_flow a
                    inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.nik= '$nik_user' $filter
                    ");
                    $res = json_decode(json_encode($res),true);
                }else{
                    $res = DB::connection($this->db)->select("select * from gr_pb_m where modul='Panjar' ");
                }
            }else{
                $res = DB::connection($this->db)->select("
                    select b.no_spb as no_bukti,b.nilai,convert(varchar,b.tanggal,103)  as tanggal,b.keterangan
                    from apv_flow a
                    inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.nik= '$nik_user' $filter
                    ");
                $res = json_decode(json_encode($res),true);
            }

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
        if($request->status == "2"){
            $this->validate($request, [
                'tanggal' => 'required',
                'no_aju' => 'required|max:20',
                'status' => 'required|max:1',
                'keterangan' => 'max:150',
                'no_urut' => 'required'
            ]);
        }else{
            $this->validate($request, [
                'tanggal' => 'required',
                'no_aju' => 'required|max:20',
                'status' => 'required|max:1',
                'keterangan' => 'required|max:150',
                'no_urut' => 'required'
            ]);
        }

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            // $get = DB::connection($this->db)->select("select isnull(max(id)+1,1) as no_app from apv_pesan where kode_lokasi='$kode_lokasi' ");
            // $no_app = (isset($get[0]->no_app) ? $get[0]->no_app : 1);
            date_default_timezone_set('Asia/Jakarta');
            $tgl = date('Y-m-d H:i:s');

            $keterangan = isset($request->keterangan) ? $request->input('keterangan') : '-';

            $no_bukti = $request->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $nik_app = $nik_user;
            $token_player = array();
            $token_player2 = array();
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('".$no_bukti."','".$kode_lokasi."','".$keterangan."','".$tgl."','".$request->input('no_urut')."','".$request->input('status')."','AJUSPB') ");

            $upd =  DB::connection($this->db)->table('apv_flow')
            ->where('no_bukti', $no_bukti)    
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_urut', $request->input('no_urut'))
            ->update(['status' => $request->input('status'),'tgl_app'=>$tgl]);

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
                ->update(['status' => '1','tgl_app'=>$tgl]);
                
                //send to App selanjutnya
                if($request->no_urut != $max[0]['nu']){

                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,b.nik,isnull(b.email,'-') as email
                    from apv_flow a
                    left join karyawan b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi
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
                inner join karyawan c on b.nik_user=c.nik 
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
                $title = "SPB";
                $subtitle = "Approval Pengajuan SPB";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di approve oleh ".$nik_app." , menunggu approval anda.";

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'SPB','-',0,0]);

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
                    left join karyawan b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi
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
                inner join karyawan c on b.nik_user=c.nik 
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
                    $pesan_header = "Pengajuan $no_bukti berikut telah di return oleh $nik_user, menunggu approval Anda:";

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
                $title = "SPB";
                $subtitle = "Return Pengajuan SPB";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di return oleh ".$nik_app." , menunggu approval anda.";
                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'SPB','-',0,0]);
            }

            DB::connection($this->db)->commit();
            
            $success['status'] = true;
            $success['message'] = "Data Approval Pengajuan berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Approval Pengajuan gagal disimpan ".$e;
            $success['no_aju'] = "";
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
			select convert(varchar,e.id) as id,a.no_spb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl  
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

    public function detailHistory(Request $request)
    {
        $this->validate($request,[
            'no_aju' => 'required',
            'id' => 'required',
        ]);
        
        try {
            $no_aju = $request->no_aju;
            $id = $request->id;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,b.nilai,b.tanggal,b.keterangan,a.no_urut,b.nama,case d.status when '2' then 'Approved' when '3' then 'Returned' else 'In Progress' end as status,convert(varchar,a.tgl_app,103) as tgl_app
            from apv_flow a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join apv_pesan d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.no_urut=d.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and d.id='$id' ";
            
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
			select convert(varchar,e.id) as id,a.no_spb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl  
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

            $sql="select a.id,a.no_bukti,a.tanggal,b.nama,b.nilai,b.keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = '3' then 'Returned' end as status
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

    public function getPreviewAju(Request $request)
    {
        try {
            
            $no_bukti = $request->no_bukti;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql="select a.no_spb,a.kode_lokasi,a.periode,a.tanggal,a.keterangan,a.kode_lokasi,f.kota,a.nilai,a.nama,a.alamat,
            a.nik_user,b.nama as nama_user,a.nik_bdh,c.nama as nama_bdh,a.nik_ver,d.nama as nama_ver,cat_pajak,cat_bdh,
            convert(varchar(20),a.tanggal,103) as tgl,f.kota, a.rek, a.jtran, a.bank, a.norek, a.alrek,a.no_po,a.no_dok,
            convert(varchar(20),a.tgl_po,103) as tgl_po,convert(varchar(20),a.tgl_dok,103) as tgl_dok,isnull(e.pph,0) as pph,
            a.nilai+isnull(e.pph,0)-isnull(g.ppn,0) as tagihan,isnull(g.ppn,0) as ppn,a.kode_curr,h.nama as nama_curr,'-' as tahun,'-' as tgl_ba,'-' as no_ba,'-' as no_ref
            from gr_spb2_m a
            inner join lokasi f on a.kode_lokasi=f.kode_lokasi
            left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
            left join karyawan c on a.nik_bdh=c.nik and a.kode_lokasi=c.kode_lokasi
            left join karyawan d on a.nik_ver=d.nik and a.kode_lokasi=d.kode_lokasi 
            inner join curr h on a.kode_curr=h.kode_curr
            left join (select b.no_spb,a.kode_lokasi,sum(a.nilai) as pph
            from gr_beban_j a
            inner join gr_beban_m b on a.no_beban=b.no_beban and a.kode_lokasi=b.kode_lokasi
            where a.kode_akun='2103.03'
            group by b.no_spb,a.kode_lokasi
            )	e	on a.no_spb=e.no_spb and a.kode_lokasi=e.kode_lokasi
            left join (select b.no_spb,a.kode_lokasi,sum(a.nilai) as ppn
            from gr_beban_j a
            inner join gr_beban_m b on a.no_beban=b.no_beban and a.kode_lokasi=b.kode_lokasi
            where a.kode_akun='1107.07'
            group by b.no_spb,a.kode_lokasi
            )	g	on a.no_spb=g.no_spb and a.kode_lokasi=g.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_spb='$no_bukti'
            order by a.no_spb
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select * from (
                select 'Dibuat oleh' as ket,c.kode_jab,a.nik_user as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
                from gr_spb2_m a
                inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi
                left join kug_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.no_spb='$no_bukti'
                union all
                select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,case a.no_urut when 1 then 'Mgr Kug' when 2 then 'VP Kug' when 3 then 'Dir Kug' else '-' end as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut
                from apv_flow a
                inner join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
                left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
                where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            ) a 
            order by a.urut
            ";
            $res3 = DB::connection($this->db)->select($sql);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['histori'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['histori'] = [];
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
