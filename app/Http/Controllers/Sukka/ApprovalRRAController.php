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

class ApprovalRRAController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function terbilang($int) {
        $angka = [
            "",
            "satu",
            "dua",
            "tiga",
            "empat",
            "lima",
            "enam",
            "tujuh",
            "delapan",
            "sembilan",
            "sepuluh",
            "sebelas",
        ];
        if ($int < 12) return " " .$angka[$int];
        else if ($int < 20) return $this->terbilang($int - 10) ." belas ";
        else if ($int < 100)
            return $this->terbilang($int / 10) ." puluh " .$this->terbilang($int % 10);
        else if ($int < 200) return "seratus" .$this->terbilang($int - 100);
        else if ($int < 1000)
            return $this->terbilang($int / 100) ." ratus " .$this->terbilang($int % 100);
        else if ($int < 2000) return "seribu" .$this->terbilang($int - 1000);
        else if ($int < 1000000)
            return $this->terbilang($int / 1000) ." ribu " .$this->terbilang($int % 1000);
        else if ($int < 1000000000)
            return $this->terbilang($int / 1000000) ." juta " .$this->terbilang($int % 1000000);
        else if ($int < 1000000000000)
            return (
                $this->terbilang($int / 1000000) ." milyar " .$this->terbilang($int % 1000000000)
            );
        else if ($int >= 1000000000000)
            return (
                $this->terbilang($int / 1000000).
                " trilyun ".
                $this->terbilang($int % 1000000000000)
            );
    }
    
    function getNamaBulan($no_bulan) {
        switch ($no_bulan) {
            case 1:
            case "1":
            case "01":
                $bulan = "Januari";
                break;
            case 2:
            case "2":
            case "02":
                $bulan = "Februari";
                break;
            case 3:
            case "3":
            case "03":
                $bulan = "Maret";
                break;
            case 4:
            case "4":
            case "04":
                $bulan = "April";
                break;
            case 5:
            case "5":
            case "05":
                $bulan = "Mei";
                break;
            case 6:
            case "6":
            case "06":
                $bulan = "Juni";
                break;
            case 7:
            case "7":
            case "07":
                $bulan = "Juli";
                break;
            case 8:
            case "8":
            case "08":
                $bulan = "Agustus";
                break;
            case 9:
            case "9":
            case "09":
                $bulan = "September";
                break;
            case 10:
            case "10":
            case "10":
                $bulan = "Oktober";
                break;
            case 11:
            case "11":
            case "11":
                $bulan = "November";
                break;
            case 12:
            case "12":
            case "12":
                $bulan = "Desember";
                break;
            default:
                $bulan = null;
        }
    
        return $bulan;
    }

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

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,case when a.status = '2' then 'Approved' else 'Returned' end as status,c.nilai,c.due_date,'RRA' as modul,c.kode_pp,d.nama as nama_pp,c.no_dokumen
            from apv_pesan a
            inner join apv_pdrk_m c on a.no_bukti=c.no_pdrk 
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
            $sql = "select b.no_pdrk as no_bukti,b.kode_pp,b.nik_buat,convert(varchar,b.tanggal,103) as tanggal,b.keterangan,p.nama as nama_pp,b.periode,k.nama as nama_buat
            from apv_flow a
            inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk 
            left join pp p on b.kode_pp=p.kode_pp 
            left join karyawan k on b.nik_buat=k.nik 
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
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values (?, ?, ?, getdate(), ?, ?, ?)", array($no_bukti,$kode_lokasi,$r->input('keterangan'),$r->input('no_urut'),$r->input('status'),'RRA'));

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

                    $upd3 =  DB::connection($this->db)->table('apv_pdrk_m')
                    ->where('no_pdrk', $no_bukti)    
                    ->update(['progress' => '1']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table('apv_pdrk_m')
                    ->where('no_pdrk', $no_bukti)    
                    ->update(['progress' => 'P']);

                    $psn = "Approver terakhir";
                }

                // //send to nik buat
                $sqlbuat = "
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from apv_pdrk_m b 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_pdrk='".$no_bukti."' ";
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
                $result = app('App\Http\Controllers\Sukka\LaporanController')->getRRAForm($r);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-rra-sukka',$result['original'])->render();
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
                        $html = view('email-rra-sukka',$result['original'])->render();
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

                $title = "RRA";
                $subtitle = "Approval RRA";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di approve oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di approve oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'Juskeb','-',0,0]);

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
                    $upd3 =  DB::connection($this->db)->table('apv_pdrk_m')
                    ->where('no_pdrk', $no_bukti)    
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table('apv_pdrk_m')
                    ->where('no_pdrk', $no_bukti)    
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from apv_pdrk_m b
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_pdrk='".$no_bukti."' ";
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
                $result = app('App\Http\Controllers\Sukka\LaporanController')->getRRAForm($r);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di return oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-rra-sukka',$result['original'])->render();
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
                        $html = view('email-rra-sukka',$result['original'])->render();
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

                $title = "RRA";
                $subtitle = "Return RRA";
                $content = "Pengajuan dengan no transaksi ".$no_bukti." telah di return oleh ".$nik_app." , menunggu approval anda.";

                $content2 = "Pengajuan dengan no transaksi ".$no_bukti." Anda telah di return oleh ".$nik_app;

                $periode = substr(date('Ym'),2,4);
                $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                $success['no_pesan'] = $no_pesan;
                
                $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app1,'-',$no_bukti,'Juskeb','-',0,0]);

            }

            $success['app_email2'] = (isset($app_email2) ? $app_email2 : 'none');
            $success['app_email'] = (isset($app_email) ? $app_email : 'none');
            $success['sqlbuat'] = (isset($sqlbuat) ? $sqlbuat : 'none');
            $success['sqlapp'] = (isset($sqlapp) ? $sqlapp : 'none');
            $success['result'] = (isset($result) ? $result : []);
            DB::connection($this->db)->commit();
            
            $success['status'] = true;
            $success['message'] = "Data Approval RRA berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_aju'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Approval RRA gagal disimpan ".$e;
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

            $sql = "select b.no_pdrk as no_bukti,b.kode_pp,b.nik_buat,convert(varchar,b.tanggal,103) as tanggal,b.keterangan,p.nama as nama_pp,b.periode,k.nama as nama_buat,b.jenis_agg,a.no_urut
            from apv_flow a
            inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk 
            left join pp p on b.kode_pp=p.kode_pp 
            left join karyawan k on b.nik_buat=k.nik 
            where a.status='1' and a.no_bukti= '$no_aju' 
            ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql4 = "
			select * from (
                select convert(varchar,e.id) as id,a.no_pdrk as no_bukti,case e.status when '2' then 'Approved' when '3' then 'Returned' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tgl,e.tanggal  
                from apv_pdrk_m a
                inner join apv_pesan e on a.no_pdrk=e.no_bukti 
                inner join apv_flow c on e.no_bukti=c.no_bukti and e.no_urut=c.no_urut
                left join apv_karyawan f on c.nik=f.nik 
                where a.no_pdrk='$no_aju' 
            ) a order by id2,tanggal
	        ";
            $res2 = DB::connection($this->db)->select($sql4);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if($res[0]['jenis_agg'] == "ANGGARAN"){
                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --,dbo.fn_saldoGarTW(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,substring(a.periode,1,4),case substring(a.periode,5,2) when '01' then 'TW1' when '04' then 'TW2' when '07' then 'TW3' when '10' then 'TW4' end,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$no_aju."' and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --dbo.fn_saldoGarTW(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,substring(a.periode,1,4),case substring(a.periode,5,2) when '01' then 'TW1' when '04' then 'TW2' when '07' then 'TW3' when '10' then 'TW4' end,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$no_aju."' and a.dc ='D'";
                }else{
                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --dbo.fn_saldoRilis(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$no_aju."' and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai, 0 as saldo
                    --dbo.fn_saldoRilis(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$no_aju."' and a.dc ='D'";
                }
                $rsd = DB::connection($this->db)->select($strd);
                $resd = json_decode(json_encode($rsd),true);

                $rst = DB::connection($this->db)->select($strt);
                $rest = json_decode(json_encode($rst),true);

                $strdok = "select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres
                from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '".$no_aju."' order by a.nu";
                $rsdok = DB::connection($this->db)->select($strdok);
                $resdok = json_decode(json_encode($rsdok),true);

                $success['status'] = true;
                $success['data'] = $res; 
                $success['detail_beri'] = $resd;
                $success['detail_terima'] = $rest;
                $success['dokumen'] = $resdok;
                $success['data_detail'] = $res2;
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
                where a.modul='RRA' and b.nik= '$nik_user' and a.no_bukti='$no_bukti'");
                $id = $rs[0]->id;
            }else{
                $id = $id;
            }

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = '3' then 'Returned' end as status
            from apv_pesan a
            inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk 
            inner join pp c on b.kode_pp=c.kode_pp 
            inner join apv_flow e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
            where a.no_bukti='$no_bukti' and a.modul='RRA' and a.id='$id' ";
            
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
                                'subject' => 'Approval RRA',
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
