<?php

namespace App\Http\Controllers\Sukka;

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
use Queue;

class ApprovalJuskebController extends Controller
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

    public function index(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($r->no_bukti) && $r->no_bukti != ""){
                $filter .= " and c.no_bukti='$r->no_bukti' ";
            }

            if(isset($r->start_date) && $r->start_date != "" && isset($r->end_date) && $r->end_date != ""){
                $filter .= " and a.tanggal between '$r->start_date' and '$r->end_date' ";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'Approved' else 'Returned' end as status,c.nilai,c.due_date,'Juskeb' as modul,c.kode_pp,d.nama as nama_pp,c.no_dokumen
            from apv_pesan a
            inner join apv_juskeb_m c on a.no_bukti=c.no_bukti 
            left join apv_flow b on a.no_bukti=b.no_bukti and c.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            inner join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
            where b.nik= '$nik_user' $filter
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPengajuan(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($r->no_bukti) && $r->no_bukti != ""){
                $filter .= " and b.no_bukti='$r->no_bukti' ";
            }

            if(isset($r->start_date) && $r->start_date != "" && isset($r->end_date) && $r->end_date != ""){
                $filter .= " and b.tanggal between '$r->start_date' and '$r->end_date' ";
            }
            $sql = "select b.no_bukti,b.kode_pp,b.jenis,convert(varchar,b.tanggal,103) as tanggal,b.kegiatan,p.nama as nama_pp,b.nilai,b.periode
            from apv_flow a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti 
            left join pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
            where a.status='1' and a.nik= '$nik_user' $filter
            ";
            $success['sql'] = $sql;
            $res = DB::connection($this->db)->select($sql);

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
     * @param  \Illuminate\Http\Request  $r
     * @return \Illuminate\Http\Response
     */
    public function store(Request $r)
    {
        $this->validate($r, [
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

            date_default_timezone_set('Asia/Jakarta');            
            $tgl = date('Y-m-d H:i:s');

            $no_bukti = $r->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $nik_app = $nik_user;
            $token_player = array();
            $token_player2 = array();
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values (?, ?, ?, getdate(), ?, ?, ?)", array($no_bukti,$kode_lokasi,$r->input('keterangan'),$r->input('no_urut'),$r->input('status'),'AJU'));

            $upd =  DB::connection($this->db)->table('apv_flow')
            ->where('no_bukti', $no_bukti)    
            ->where('no_urut', $r->input('no_urut'))
            ->update(['status' => $r->input('status'),'tgl_app'=>$tgl]);

            $max = DB::connection($this->db)->select("
            select max(a.no_urut) as nu from apv_flow a
            where a.no_bukti='$no_bukti' 
            ");
            $max = json_decode(json_encode($max),true);

            $min = DB::connection($this->db)->select("select min(a.no_urut) as nu from apv_flow a
            where a.no_bukti='$no_bukti' 
            ");
            $min = json_decode(json_encode($min),true);

            if($r->status == 2){
                $nu = $r->no_urut+1;

                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>$tgl]);
                
                //send to App selanjutnya
                if($r->no_urut != $max[0]['nu']){

                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,b.nik,isnull(b.email,'-') as email
                    from apv_flow a
                    left join apv_karyawan b on a.nik=b.nik 
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu ";

                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $no_telp = $rs[0]["no_telp"];
                        $nik_app1 = $rs[0]["nik"];
                        $app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }

                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->update(['progress' => '1']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->update(['progress' => 'J']);

                    $psn = "Approver terakhir";
                }

                // //send to nik buat
                $sqlbuat = "
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from apv_juskeb_m b 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_bukti='".$no_bukti."' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $no_telp2 = $rs2[0]["no_telp"];
                    $nik_buat = $rs2[0]['nik_buat'];
                    if(intval($r->no_urut) == intval($max[0]['nu'])){
                        $app_email2 = $rs2[0]['email'];
                    }
                }else{
                    $no_telp2 = "-";
                    $nik_buat = "-";
                }
                $success['approval'] = "Approve";
                
                $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                $result = app('App\Http\Controllers\Sukka\LaporanController')->getAjuForm($r);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $subject = "Approval Juskeb";
                        $html = view('email-sukka',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool,subject) values (?,?,?,?,getdate(),?,?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool,$subject]);
                        // $success['no_pooling'] = $no_pool;
                        Queue::push(new \App\Jobs\SendSukkaEmailJob($no_pool,$this->db));
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
                        $html = view('email-sukka',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $subject = "Approval Juskeb";
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool,subject) values (?,?,?,?,getdate(),?,?,?, ?)', ['-',$app_email2,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool,$subject]);
                        // $success['no_pooling2'] = $no_pool;
                        
                        Queue::push(new \App\Jobs\SendSukkaEmailJob($no_pool,$this->db));
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                        $msg_email = "";
                }

                $title = "Juskeb";
                $subtitle = "Approval Juskeb";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di approve oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di approve oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti",$kode_lokasi."-PN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'Juskeb','-',0,0]);

                Queue::push(new \App\Jobs\SendSukkaNotifJob($no_pesan,$this->db));
            }else{
                $nu=$r->no_urut-1;

                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $no_bukti)    
                ->where('no_urut', $nu)
                ->update(['status' => '1','tgl_app'=>NULL]);


                if(intval($r->no_urut) != intval($min[0]['nu'])){
                    // //send to approver sebelumnya
                    $sqlapp="
                    select isnull(b.no_telp,'-') as no_telp,b.nik,isnull(b.email,'-') as email
                    from apv_flow a
                    left join apv_karyawan b on a.nik=b.nik 
                    where a.no_bukti='".$no_bukti."' and a.no_urut=$nu ";
                    $rs = DB::connection($this->db)->select($sqlapp);
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs)>0){
                        $no_telp = $rs[0]["no_telp"];
                        $nik_app1 = $rs[0]["nik"];
                        $app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }
                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table('apv_juskeb_m')
                    ->where('no_bukti', $no_bukti)    
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from apv_juskeb_m b
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_bukti='".$no_bukti."' ";
                $rs2 = DB::connection($this->db)->select($sqlbuat);
                $rs2 = json_decode(json_encode($rs2),true);
                if(count($rs2)>0){
                    $no_telp2 = $rs2[0]["no_telp"];
                    $nik_buat = $rs2[0]["nik_buat"];
                    if(intval($r->no_urut) == intval($min[0]['nu'])){
                        $app_email2 = $rs2[0]['email'];
                    }
                }else{
                    $no_telp2 = "-";
                    $nik_buat = "-";
                }

                $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                $result = app('App\Http\Controllers\Sukka\LaporanController')->getAjuForm($r);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di return oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $subject = "Return Juskeb";
                        $html = view('email-sukka',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool,subject) values (?,?,?,?,getdate(),?,?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool,$subject]);
                        // $success['no_pooling'] = $no_pool;
                        Queue::push(new \App\Jobs\SendSukkaEmailJob($no_pool,$this->db));
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
                        $subject = "Return Juskeb";
                        $html = view('email-sukka',$result['original'])->render();
                        $periode = substr(date('Ym'),2,4);
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool,subject) values (?,?,?,?,getdate(),?,?,?,?)', ['-',$app_email2,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool,$subject]);
                        // $success['no_pooling2'] = $no_pool;
                        Queue::push(new \App\Jobs\SendSukkaEmailJob($no_pool,$this->db));
                        $msg_email = "";
                    }else{
                        $msg_email = "Form Aju Kosong";
                    }
                }else{
                        $msg_email = "";
                }
                
                $success['approval'] = "Return";

                $title = "Juskeb";
                $subtitle = "Return Juskeb";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di return oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di return oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti",$kode_lokasi."-PN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'Juskeb','-',0,0]);

                Queue::push(new \App\Jobs\SendSukkaNotifJob($no_pesan,$this->db));

            }

            $success['app_email2'] = (isset($app_email2) ? $app_email2 : 'none');
            $success['app_email'] = (isset($app_email) ? $app_email : 'none');
            $success['sqlbuat'] = (isset($sqlbuat) ? $sqlbuat : 'none');
            $success['sqlapp'] = (isset($sqlapp) ? $sqlapp : 'none');
            $success['result'] = (isset($result) ? $result : []);
            DB::connection($this->db)->commit();
            
            $success['status'] = true;
            $success['message'] = "Data Approval Juskeb berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Approval Juskeb gagal disimpan ".$e;
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

    public function show(Request $r)
    {
        try {
            $no_aju = $r->no_aju;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,b.jenis,b.kode_pp,b.tanggal,b.kegiatan,a.no_urut,c.nama as nama_pp,b.nilai,b.periode,b.latar,b.aspek,b.spesifikasi,b.rencana,d.nama as nama_terima,e.nama as nama_beri,b.lok_terima,b.lok_donor
            from apv_flow a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            left join lokasi d on b.lok_terima=d.kode_lokasi
            left join lokasi e on b.lok_donor=e.kode_lokasi
            where a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql4 = "
			select * from (
                select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from apv_juskeb_m a
                inner join apv_pesan e on a.no_bukti=e.no_bukti 
                inner join apv_flow c on e.no_bukti=c.no_bukti and e.no_urut=c.no_urut
                left join apv_karyawan f on c.nik=f.nik 
                where a.no_bukti='$no_aju' 
            ) a order by id2,tanggal
	        ";
            $res2 = DB::connection($this->db)->select($sql4);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                    from apv_juskeb_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_bukti='".$no_aju."' and a.dc ='C'";
                
                $rsd = DB::connection($this->db)->select($strd);
                $resd = json_decode(json_encode($rsd),true);

                $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                    from apv_juskeb_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_bukti='".$no_aju."' and a.dc ='D'";
                
                $rst = DB::connection($this->db)->select($strt);
                $rest = json_decode(json_encode($rst),true);

                $strdok = "select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres
                from apv_juskeb_dok a 
                inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '".$no_aju."'  order by a.nu";
                $rsdok = DB::connection($this->db)->select($strdok);
                $resdok = json_decode(json_encode($rsdok),true);

                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['detail_beri'] = $resd;
                $success['detail_terima'] = $rest;
                $success['dokumen'] = $resdok;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['detail_beri'] = [];
                $success['detail_terima'] = [];
                $success['dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_detail'] = [];
            $success['detail_beri'] = [];
            $success['detail_terima'] = [];
            $success['dokumen'] = [];
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
     * @param  \Illuminate\Http\Request  $r
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $r, $no_bukti)
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

    public function getPreview(Request $r)
    {
        try {
            
            $no_bukti = $r->id;
            $id = $r->jenis;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($id == "default"){
                $rs = DB::connection($this->db)->select("select max(id) as id
                from apv_pesan a
                left join apv_flow b on a.no_bukti=b.no_bukti and a.no_urut=b.no_urut
                where a.modul='AJU' and b.nik= '$nik_user' and a.no_bukti='$no_bukti'");
                $id = $rs[0]->id;
            }else{
                $id = $id;
            }

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.kegiatan as keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = '3' then 'Returned' end as status
            from apv_pesan a
            inner join apv_juskeb_m b on a.no_bukti=b.no_bukti 
            inner join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            inner join apv_flow e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
            where a.no_bukti='$no_bukti' and a.modul='AJU' and a.id='$id' ";
            
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

    public function sendNotifikasi(Request $r)
    {
        $this->validate($r,[
            "no_pooling" => 'required'
        ]);

        if($auth =  Auth::guard($this->guard)->user()){
            $nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
        }

        DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();
            $res = DB::connection($this->db)->select("select no_hp,pesan,jenis,email from pooling where flag_kirim=0 and no_pool ='$r->no_pooling'  ");
            if(count($res) > 0){
                $msg = "";
                $sts = false;
                foreach($res as $row){
                    if($row->jenis == "EMAIL") {
                        $credentials = base64_encode('api:'.config('services.mailgun.secret'));
                        $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
                        $response = $client->request('POST',  $domain,[
                            'headers' => [
                                'Authorization' => 'Basic '.$credentials
                            ],
                            'form_params' => [
                                'from' => 'devsaku5@gmail.com',
                                'to' => $row->email,
                                'subject' => 'Approval Juskeb',
                                'html' => htmlspecialchars_decode($row->pesan)
                            ]
                        ]);
                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);
                            if(isset($data["id"])){
                                $success['data2'] = $data;

                                $updt =  DB::connection($this->db)->table('pooling')
                                ->where('no_pool', $r->no_pooling)    
                                ->where('jenis', 'EMAIL')
                                ->where('flag_kirim', 0)
                                ->update(['tgl_kirim' => Carbon::now()->timezone("Asia/Jakarta"), 'flag_kirim' => 1]);

                                
                                DB::connection($this->db)->commit();
                                $sts = true;
                                $msg .= $data['message'];
                            }
                        }
                    }
                    
                }

                $success['message'] = $msg;
                $success['status'] = $sts;
            }else{
                $success['message'] = "Data pooling tidak valid";
                $success['status'] = false;
            }
            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            
            DB::connection($this->db)->rollback();
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json($data, 500);
        }
    }

}
