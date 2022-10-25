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

class Approval2Controller extends Controller
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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->no_pb) && $request->no_pb != ""){
                $filter .= " and a.no_bukti='$request->no_pb' ";
            }

            if(isset($request->start_date) && $request->start_date != "" && isset($request->end_date) && $request->end_date != ""){
                $filter .= " and a.tanggal between '$request->start_date' and '$request->end_date' ";
            }

            if(isset($request->jenis) && $request->jenis != ""){
                $filter .= " and a.modul='$request->jenis' ";
            }

            $filter = $filter != "" ? "where ".substr($filter,4) : "";

            $res = DB::connection($this->db)->select("select a.* from (
                select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'Approved' else 'Returned' end as status,c.nilai,c.due_date,case 
                when c.modul = 'AJU2' OR c.modul = 'AJU' then 'Beban'
                when c.modul = 'PJAJU2' OR c.modul = 'PJAJU' then 'Panjar' else '-'
                end as modul,c.kode_pp,d.nama as nama_pp,c.no_dokumen,c.tanggal as tgl_aju
                from apv_pesan a
                inner join gr_pb_m c on a.no_bukti=c.no_pb and a.kode_lokasi=c.kode_lokasi 
                left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
                inner join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and b.nik= '$nik_user'
                union all
                select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'Approved' else 'Returned' end as status,c.nilai,c.tanggal as due_date,'SPB' as modul,'-' as kode_pp,'-' as nama_pp,'-' as no_dokumen,c.tanggal as tgl_aju
                from apv_pesan a
                inner join gr_spb2_m c on a.no_bukti=c.no_spb and a.kode_lokasi=c.kode_lokasi 
                left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
                where a.kode_lokasi='$kode_lokasi' and b.nik= '$nik_user'
            ) a
            $filter
            order by a.tanggal desc    
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

    public function getPengajuan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->no_pb) && $request->no_pb != ""){
                $filter .= " and a.no_bukti='$request->no_pb' ";
            }

            if(isset($request->start_date) && $request->start_date != "" && isset($request->end_date) && $request->end_date != ""){
                $filter .= " and a.tanggal between '$request->start_date' and '$request->end_date' ";
            }

            if(isset($request->jenis) && $request->jenis != ""){
                $filter .= " and a.modul='$request->jenis' ";
            }

            $filter = $filter != "" ? "where ".substr($filter,4) : "";
            
            $res = DB::connection($this->db)->select("select a.* from (
                select b.no_pb as no_bukti,b.no_dokumen,b.kode_pp,convert(varchar,b.tanggal,103) as tanggal,b.keterangan,p.nama as nama_pp,b.nilai,b.due_date,
                case when b.modul = 'AJU2' OR b.modul = 'AJU' then 'Beban'
                when b.modul = 'PJAJU2' OR b.modul = 'PJAJU' then 'Panjar' else '-'
                end as modul,b.tanggal as tgl
                from apv_flow a
                inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
                inner join pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.nik= '$nik_user'
                union all
                select b.no_spb as no_bukti,'-' as no_dokumen,'-' as kode_pp,convert(varchar,b.tanggal,103)  as tanggal,b.keterangan,'-' as nama_pp,b.nilai,b.tanggal as due_date,'SPB' as modul,b.tanggal as tgl
                from apv_flow a
                inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.nik= '$nik_user'
            ) a
            $filter
            order by a.tanggal desc
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

            $modul = isset($request->modul) ? $request->modul : 'Beban';

            if($modul == "Beban"){
                $form = "AJU";
                $tabel = "gr_pb_m";
                $progapp_awal = "S";
                $progapp_akhir = "1";
                $col_nik = "nik_buat";
                $col_nobukti = "no_pb";
                $email = "email-siaga";
            }else if($modul == "Panjar"){
                $form = "PJAJU";
                $tabel = "gr_pb_m";
                $progapp_awal = "S";
                $progapp_akhir = "1";
                $col_nik = "nik_buat";
                $col_nobukti = "no_pb";
                $email = "email-siaga";
            }else{
                $form = "AJUSPB";
                $tabel = "gr_spb2_m";
                $progapp_awal = "1";
                $progapp_akhir = "S";
                $col_nik = "nik_user";
                $col_nobukti = "no_spb";
                $email = "email-siaga-pb";
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
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('".$no_bukti."','".$kode_lokasi."','".$keterangan."','".$tgl."','".$request->input('no_urut')."','".$request->input('status')."','$form') ");

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
                        $nik_app1 = $rs[0]["nik"];
                        $app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }

                    $upd3 =  DB::connection($this->db)->table($tabel)
                    ->where($col_nobukti, $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $progapp_awal]);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table($tabel)
                    ->where($col_nobukti, $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $progapp_akhir]);

                    $psn = "Approver terakhir";
                }

                // //send to nik buat
                $sqlbuat = "
                select isnull(c.no_telp,'-') as no_telp,b.$col_nik as nik_buat,isnull(c.email,'-') as email
                from $tabel b 
                inner join karyawan c on b.$col_nik=c.nik 
                where b.$col_nobukti='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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
                if($modul == "SPB"){
                    $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
                
                }else{

                    $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
                }
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view($email,$result['original'])->render();
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
                        $html = view($email,$result['original'])->render();
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

                $title = "$modul";
                $subtitle = "Approval Pengajuan $modul";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di approve oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di approve oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,$modul,'-',0,0]);

                // $no_pesan2 = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                // $success['no_pesan2'] = $no_pesan2;
                
                // $inspesan2= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content2,$nik_buat,'-',$no_bukti,'Beban','-',0,0]);

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
                        $nik_app1 = $rs[0]["nik"];
                        $app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }
                    $upd3 =  DB::connection($this->db)->table($tabel)
                    ->where($col_nobukti, $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table($tabel)
                    ->where($col_nobukti, $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select isnull(c.no_telp,'-') as no_telp,b.$col_nik as nik_buat,isnull(c.email,'-') as email
                from $tabel b
                inner join karyawan c on b.$col_nik=c.nik 
                where b.$col_nobukti='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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
                if($modul == "SPB"){
                    $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
                }else{
                    $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
                }
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di return oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view($email,$result['original'])->render();
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
                        $html = view($email,$result['original'])->render();
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

                $title = "$modul";
                $subtitle = "Return Pengajuan $modul";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di return oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di return oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,$modul,'-',0,0]);

                // $no_pesan2 = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                // $success['no_pesan2'] = $no_pesan2;
                
                // $inspesan2= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan2,$kode_lokasi,$title,$subtitle,$content2,$nik_buat,'-',$no_bukti,$modul,'-',0,0]);

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

            $sql="select a.no_bukti,b.no_dokumen,b.kode_pp,b.tanggal,b.keterangan,a.no_urut,c.nama as nama_pp,b.nilai,b.due_date,case when b.modul = 'AJU2' OR b.modul = 'AJU' then 'Beban'
            when b.modul = 'PJAJU2' OR b.modul = 'PJAJU' then 'Panjar' else '-'
            end as modul
            from apv_flow a
            inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            left join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1'
            union all
            select a.no_bukti,'-' as no_dokumen,'-' as kode_pp,b.tanggal,b.keterangan,a.no_urut,'-' as nama_pp,b.nilai,b.tanggal as due_date,'SPB' as modul
            from apv_flow a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1'
            ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($res[0]['modul'] == "SPB"){
                $sql2 = "select a.no_po,a.tgl_po,a.no_dok,a.tgl_dok,a.cat_pajak,a.cat_bdh,a.nama as kepada,a.alamat,a.keterangan as ket_bayar,a.jtran as jenis_trans,a.bank,a.norek,a.alrek as alamat_rek
                from gr_spb2_m a
                where a.no_spb = '$no_aju' and a.kode_lokasi='$kode_lokasi' ";
            }else{
                $sql2="select a.no_pb,a.nama_brg,a.satuan,a.jumlah,a.harga,a.nu 
                from gr_pb_boq a 
                where a.kode_lokasi='".$kode_lokasi."' and a.no_pb='$no_aju' order by a.nu";					
            }

            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);


            $url = config('services.api.doc_url_siaga');
            $sql3="select no_pb,no_gambar,nu,kode_jenis,no_ref,'".$url."'+no_gambar as file_dok from gr_pb_dok where kode_lokasi='".$kode_lokasi."' and no_pb='$no_aju' order by nu";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "
			select * from (
                select convert(varchar,e.id) as id,a.no_pb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from (
                    select case when a.modul = 'AJU2' OR a.modul = 'AJU' then 'Beban'
                    when a.modul = 'PJAJU2' OR a.modul = 'PJAJU' then 'Panjar' else '-'
                    end as modul,a.no_pb, a.kode_lokasi 
                    from gr_pb_m a
                    union all
                    select 'SPB' as modul,a.no_spb, a.kode_lokasi 
                    from gr_spb2_m a
                ) a
                inner join apv_pesan e on a.no_pb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
                inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
                left join karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
                where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
                union all
                select convert(varchar,e.id) as id,a.no_pb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik_user,f.nama,e.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from (
                    select case when a.modul = 'AJU2' OR a.modul = 'AJU' then 'Beban'
                    when a.modul = 'PJAJU2' OR a.modul = 'PJAJU' then 'Panjar' else '-'
                    end as modul,a.no_pb, a.kode_lokasi 
                    from gr_pb_m a
                    union all
                    select 'SPB' as modul,a.no_spb, a.kode_lokasi 
                    from gr_spb2_m a
                ) a
                inner join apv_pesan e on a.no_pb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
                inner join gr_app_m c on e.no_ref=c.no_app and e.kode_lokasi=c.kode_lokasi 
                left join karyawan f on c.nik_user=f.nik and c.kode_lokasi=f.kode_lokasi
                where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
            ) a order by id2,tanggal
	        ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $sql5="select a.no_pb,count(*) as jum_brg
            from gr_pb_boq a 
            where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi'
            group by a.no_pb";
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_total'] = $res5;
                $success['data_dokumen'] = $res3;
                $success['data_histori'] = $res4;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_total'] = [];
                $success['data_dokumen'] = [];
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

    public function cekBudget(Request $request){
        $this->validate($request,[
            'no_pb' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_pb;
            $nilai = 0; $total = 0;
            $sls = 0;
            $result = array();
            
            $sql="select a.no_pb,a.kode_akun,a.nilai,b.nama as nama_akun,c.periode 
            from gr_pb_j a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join gr_pb_m c on a.no_pb=c.no_pb and a.kode_lokasi=c.kode_lokasi
            where a.no_pb='$request->no_pb' and a.kode_lokasi='$kode_lokasi' ";
            $res2 = DB::connection($this->db)->select($sql);
			foreach ($res2 as $row){

				$periode = $row->periode;
                $sql="select dbo.fn_cekagg8('$kode_lokasi','".$row->kode_akun."','".$periode."','".$row->no_pb."') as gar";
                $res = DB::connection($this->db)->select($sql);
				if (count($res) > 0){
					$line = $res[0];
                    if($line->gar != ""){
                        $data = explode(";",$line->gar);					
                        $so_awal = floatval($data[0]) - floatval($data[1]);
                        $so_akhir = $so_awal - floatval($row->nilai);

                    }else{
                        $so_awal = 0;
                        $so_akhir = $so_awal - floatval($row->nilai);
                    }
				}else{
                    $so_awal = 0;
					$so_akhir = $so_awal - floatval($row->nilai);
                }

                $hasil = array(
                    'no_pb' => $row->no_pb,
                    'periode' => $periode,
                    'kode_akun' => $row->kode_akun,
                    'nama_akun' => $row->nama_akun,
                    'saldo_awal' => $so_awal,
                    'pemakaian' => $row->nilai,
                    'saldo_akhir' => $so_akhir,
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
            $success['data'] = [];
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
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

            $sql="select a.no_bukti,b.no_dokumen,b.kode_pp,b.tanggal,b.keterangan,a.no_urut,c.nama as nama_pp,b.nilai,b.due_date,case when b.modul = 'AJU2' OR b.modul = 'AJU' then 'Beban'
            when b.modul = 'PJAJU2' OR b.modul = 'PJAJU' then 'Panjar' else '-'
            end as modul,case d.status when '2' then 'Approved' when '3' then 'Returned' else 'In Progress' end as status,convert(varchar,a.tgl_app,103) as tgl_app
            from apv_flow a
            inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            inner join apv_pesan d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.no_urut=d.no_urut
            left join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and d.id='$id'
            union all
            select a.no_bukti,'-' as no_dokumen,'-' as kode_pp,b.tanggal,b.keterangan,a.no_urut,'-' as nama_pp,b.nilai,b.tanggal as due_date,'SPB' as modul,case d.status when '2' then 'Approved' when '3' then 'Returned' else 'In Progress' end as status,convert(varchar,a.tgl_app,103) as tgl_app
            from apv_flow a
            inner join gr_spb2_m b on a.no_bukti=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join apv_pesan d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.no_urut=d.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and d.id='$id'
            ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_pb,a.nama_brg,a.satuan,a.jumlah,a.harga,a.nu 
            from gr_pb_boq a 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_pb='$no_aju' order by a.nu";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_pb,no_gambar,nu,kode_jenis,no_ref,'https://siaga.simkug.com/server/media/'+no_gambar as file_dok from gr_pb_dok where kode_lokasi='".$kode_lokasi."' and no_pb='$no_aju' order by nu";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "
			select * from (
                select convert(varchar,e.id) as id,a.no_pb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from (
                    select case when a.modul = 'AJU2' OR a.modul = 'AJU' then 'Beban'
                    when a.modul = 'PJAJU2' OR a.modul = 'PJAJU' then 'Panjar' else '-'
                    end as modul,a.no_pb, a.kode_lokasi 
                    from gr_pb_m a
                    union all
                    select 'SPB' as modul,a.no_spb, a.kode_lokasi 
                    from gr_spb2_m a
                ) a
                inner join apv_pesan e on a.no_pb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
                inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
                left join karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
                where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
                union all
                select convert(varchar,e.id) as id,a.no_pb,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik_user,f.nama,e.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from (
                    select case when a.modul = 'AJU2' OR a.modul = 'AJU' then 'Beban'
                    when a.modul = 'PJAJU2' OR a.modul = 'PJAJU' then 'Panjar' else '-'
                    end as modul,a.no_pb, a.kode_lokasi 
                    from gr_pb_m a
                    union all
                    select 'SPB' as modul,a.no_spb, a.kode_lokasi 
                    from gr_spb2_m a
                ) a
                inner join apv_pesan e on a.no_pb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
                inner join gr_app_m c on e.no_ref=c.no_app and e.kode_lokasi=c.kode_lokasi 
                left join karyawan f on c.nik_user=f.nik and c.kode_lokasi=f.kode_lokasi
                where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
            ) a order by id2,tanggal
	        ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $sql5="select a.no_pb,count(*) as jum_brg
            from gr_pb_boq a 
            where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi'
            group by a.no_pb";
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_total'] = $res5;
                $success['data_dokumen'] = $res3;
                $success['data_histori'] = $res4;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_total'] = [];
                $success['data_dokumen'] = [];
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
                where a.kode_lokasi='$kode_lokasi' and a.modul='AJU' and b.nik= '$nik_user' and a.no_bukti='$no_bukti'");
                $id = $rs[0]->id;
            }else{
                $id = $id;
            }

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = '3' then 'Returned' end as status
            from apv_pesan a
            inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            inner join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            inner join apv_flow e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut and a.kode_lokasi=e.kode_lokasi
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

    public function getPreviewAju(Request $request)
    {
        try {
            
            $no_bukti = $request->no_bukti;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql="select a.no_pb ,a.keterangan,a.nik_buat,b.nama as nama_buat,a.atensi as ref1,'Jakarta' as kota,tanggal,convert(varchar(20),a.tanggal,103) as tgl,
            a.nilai,a.kurs,a.nilai,a.nilai_curr,d.nama as nama_curr,a.kode_curr,a.kode_pp,c.nama as nama_pp,a.kode_lokasi,
            a.latar, a.strategis, a.bisnis, a.teknis, a.lain,a.nik_tahu,e.nama as nama_tahu,
            a.nik_sah,a.nik_ver,a.nik_dir,f.nama as nama_sah,g.nama as nama_ver,j.nama as nama_dir,a.jenis,a.jab1,a.jab2,a.jab3,a.jab4,a.jab5,isnull(h.nik,'-') as nik_kirim,isnull(i.email,'-') as email_kirim
            from gr_pb_m a
            inner join karyawan b on a.nik_buat=b.nik
            inner join pp c on a.kode_pp=c.kode_pp
            inner join curr d on a.kode_curr=d.kode_curr
            inner join karyawan e on a.nik_tahu=e.nik
            left join karyawan f on a.nik_sah=f.nik
            left join karyawan g on a.nik_ver=g.nik
            left join karyawan j on a.nik_dir=j.nik
            left join apv_flow h on a.no_pb=h.no_bukti and a.kode_lokasi=h.kode_lokasi and h.status=1
            left join karyawan i on h.nik=i.nik and h.kode_lokasi=i.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_pb,a.nu,a.nama_brg,a.satuan,a.jumlah,a.harga,a.nu
            from gr_pb_boq a   
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql4="select f.ref1,c.no_bukti,c.kode_akun,b.nama as nama_akun,sum(case a.dc when 'D' then a.nilai else -a.nilai end) as nilai,isnull(d.kode_flag,'-') as kode_flag, 
            sum(case when b.jenis='Neraca' and isnull(d.kode_flag,'-')='-' then a.nilai else c.saldo end ) as saldo,
             sum(case when b.jenis='Neraca' and isnull(d.kode_flag,'-')='-' then 0 else c.saldo-a.nilai end ) as sisa 
            from gr_pb_j a 
            inner join gr_pb_m f on a.no_pb=f.no_pb and a.kode_lokasi=f.kode_lokasi
             inner join angg_r c on f.no_pb=c.no_bukti and  a.kode_akun=c.kode_akun
            inner join masakun b on c.kode_akun=b.kode_akun and c.kode_lokasi=b.kode_lokasi
            left join flag_relasi d on c.kode_akun=d.kode_akun and c.kode_lokasi=d.kode_lokasi and d.kode_flag='006'
            where a.no_pb='$no_bukti' and a.kode_lokasi='$kode_lokasi' and c.modul in ('AJU','BMHD','BYRBMHD')
            group by f.ref1,c.no_bukti,c.kode_akun,b.nama,d.kode_flag ";					
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $sql5="select a.no_pb ,a.keterangan,a.nik_buat,b.nama as nama_buat,a.atensi as ref1,'Jakarta' as kota,tanggal,convert(varchar(20),a.tanggal,103) as tgl,
            a.nilai,a.kurs,a.nilai,a.nilai_curr,d.nama as nama_curr,a.kode_curr,a.kode_pp,c.nama as nama_pp,a.kode_lokasi,
                a.latar, a.strategis, a.bisnis, a.teknis, a.lain,a.nik_tahu,e.nama as nama_tahu,
                isnull(f.saldo,0) as saldo,isnull(f.nilai,0) as nilai_gar
            from gr_pb_m a
            inner join karyawan b on a.nik_buat=b.nik
            inner join pp c on a.kode_pp=c.kode_pp
            inner join curr d on a.kode_curr=d.kode_curr
            inner join karyawan e on a.nik_tahu=e.nik
            left join (select no_bukti,kode_lokasi,sum(saldo) as saldo,sum(nilai) as nilai
                    from angg_r
                    group by no_bukti,kode_lokasi
                    )f on a.no_pb=f.no_bukti and a.kode_lokasi=f.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti' ";					
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);

            $sql="select * from (select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,a.jab1 as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut,a.tanggal as tgl
			from gr_pb_m a
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,case a.no_urut when 1 then b.jab2 when 2 then b.jab3 when 3 then b.jab4 when 4 then b.jab5 else '-' end as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
            from apv_flow a
			inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            inner join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,c.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
            from gr_app_m a
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join kug_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			inner join apv_pesan e on a.no_app=e.no_ref and a.kode_lokasi=e.kode_lokasi and e.no_urut=10 
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			) a
			order by a.no_app,a.tgl
            ";
            $res3 = DB::connection($this->db)->select($sql);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res5;
                $success['detail'] = $res2;
                $success['detail_akun'] = $res4;
                $success['histori'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data2'] = [];
                $success['detail'] = [];
                $success['detail_akun'] = [];
                $success['histori'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data2'] = [];
            $success['detail'] = [];
            $success['detail_akun'] = [];
            $success['histori'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function cek(Request $request){
        // EMAIL
        if($data =  Auth::guard($this->guard)->user()){
            $nik_user= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        $request->all();
        
        $request->request->add(['no_bukti' => ["=",$request->no_aju,""]]);
        $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
        $result = json_decode(json_encode($result),true);
        $success['status'] = true;
        if(count($result['original']['data']) > 0){
            $judul = "Pengajuan Nomor : ".$data[0]['no_spb']." berikut menunggu approval Anda";
            $result['original']['judul'] = $judul;
            // $html = view('email-siaga-spb',$result['original'])->render();
            return view('email-siaga-spb',$result['original']);
            // $periode = substr(date('Ym'),2,4);
            // $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
            
            // $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-','enahadiani2@gmail.com',htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
            // $success['no_pooling'] = $no_pool;
        }
        // return response()->json($success, $this->successStatus);
        // END EMAIL
    }

    public function sendNotifikasi(Request $request)
    {
        $this->validate($request,[
            "no_pooling" => 'required'
        ]);

        if($auth =  Auth::guard($this->guard)->user()){
            $nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
        }

        DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();
            $res = DB::connection($this->db)->select("select no_hp,pesan,jenis,email from pooling where flag_kirim=0 and no_pool ='$request->no_pooling'  ");
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
                                'subject' => 'Approval Siaga',
                                'html' => htmlspecialchars_decode($row->pesan)
                            ]
                        ]);
                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);
                            if(isset($data["id"])){
                                $success['data2'] = $data;

                                $updt =  DB::connection($this->db)->table('pooling')
                                ->where('no_pool', $request->no_pooling)    
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

    function sendEmailSaku3(Request $request){

        $this->validate($request,[
            'no_aju' => 'required',
            'email_kirim' => 'required',
            'nik_kirim' => 'required',
            'jenis' => 'required',
            'judul' => 'required',
        ]);
        // EMAIL
        if($data =  Auth::guard($this->guard)->user()){
            $nik_user= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        $request->request->add(['no_bukti' => ["=",$request->no_aju,""]]);
        if($request->jenis == "PB"){

            $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
            $template= 'email-siaga';
        }else if($request->jenis == "SPB"){
            $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormSPB($request);
            $template= 'email-siaga-spb';
        }else{
            $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuFormPJ($request);
            $template= 'email-siaga-panjar';
        }
        $result = json_decode(json_encode($result),true);
        $success['status'] = true;
        DB::connection($this->db)->beginTransaction();
        Log::info('Email saku3 result lap : ');
        Log::info($result['original']);
        if(count($result['original']['data']) > 0){
            $judul = $request->judul;
            $msg = "";
            $result['original']['judul'] = $judul;
            $html = view($template,$result['original'])->render();
            $periode = substr(date('Ym'),2,4);
            $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
            $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$request->email_kirim,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
            $success['no_pooling'] = $no_pool;
            $client = new Client;
            $credentials = base64_encode('api:'.config('services.mailgun.secret'));
            $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
            $response = $client->request('POST',  $domain,[
                'headers' => [
                    'Authorization' => 'Basic '.$credentials
                ],
                'form_params' => [
                    'from' => 'devsaku5@gmail.com',
                    'to' => $request->email_kirim,
                    'subject' => 'Approval Siaga',
                    'html' => $html
                    ]
                ]);
                if ($response->getStatusCode() == 200) { // 200 OK
                    $response_data = $response->getBody()->getContents();
                    $data = json_decode($response_data,true);
                    Log::info("send email saku3 :");
                    Log::info($data);
                    if(isset($data["id"])){
                        $success['data2'] = $data;
                        
                        $updt =  DB::connection($this->db)->table('pooling')
                        ->where('no_pool', $no_pool)    
                        ->where('jenis', 'EMAIL')
                        ->where('flag_kirim', 0)
                        ->update(['tgl_kirim' => Carbon::now()->timezone("Asia/Jakarta"), 'flag_kirim' => 1]);
                        Log::info("update pooling :");
                        Log::info($updt);
                        $sts = true;
                        $msg .= $data['message'];
                    }
                }
                
                DB::connection($this->db)->commit();
                $success['message'] = $msg;
        }else{
            DB::connection($this->db)->rollback();
            Log::info("email siaga : Data tidak ditemukan");
            $success['status'] = false;
            $success['message'] = 'Data tidak ditemukan';
        }
        Log::info("response email siaga lewat saku3:");
        Log::info($success);
        return response()->json($success, $this->successStatus);
        // END EMAIL
    }

    public function cekNIK(Request $r)
    {
        $this->validate($r,[
            'nik' => 'required'
        ]);
        try {
            
            $res = DB::connection($this->db)->select("select a.nik, isnull(b.pin,'-') as pin from karyawan a 
            inner join hakakses b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.nik=?
            ",[$r->input('nik')]);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['message'] = "NIK Cocok!";
                if($res[0]->pin != "-" || $res[0]->pin == ""){
                    $success['status_pin'] = true;
                }else{
                    $success['status_pin'] = false;
                }
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "NIK tidak ditemukan!";
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function inputPIN(Request $r)
    {
        $this->validate($r,[
            'nik' => 'required',
            'pin' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        try {
            
            $upd = DB::connection($this->db)->update("update hakakses set pin=? where nik=?
            ",[$r->input('pin'),$r->input('nik')]);

            $success['status'] = true;
            $success['message'] = "Sukses!";
            DB::connection($this->db)->commit();
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            DB::connection($this->db)->rollback();
            return response()->json($success, $this->successStatus);
        }
        
    }


}
