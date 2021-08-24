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
			inner join gr_pb_m c on a.no_bukti=c.no_pb and a.kode_lokasi=c.kode_lokasi 
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

            $res = DB::connection($this->db)->select("select b.no_pb as no_bukti,b.no_dokumen,b.kode_pp,convert(varchar,b.tanggal,103)  as tanggal,b.keterangan,p.nama as nama_pp
            from apv_flow a
            inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            inner join apv_pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
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
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('".$no_bukti."','".$kode_lokasi."','".$request->input('keterangan')."','".$request->input('tanggal')."','".$request->input('no_urut')."','".$request->input('status')."','AJU') ");

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
                    left join apv_karyawan b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
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

                    $upd3 =  DB::connection($this->db)->table('gr_pb_m')
                    ->where('no_pb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'S']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table('gr_pb_m')
                    ->where('no_pb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => '1']);

                    $psn = "Approver terakhir";
                }

                // //send to nik buat
                $sqlbuat = "
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from gr_pb_m b 
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_pb='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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
                $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga',$result['original'])->render();
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
                        $html = view('email-siaga',$result['original'])->render();
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
                        $nik_app1 = $rs[0]["nik"];
                        $app_email = $rs[0]["email"];
                    }else{
                        $no_telp = "-";
                        $nik_app1 = "-";
                    }
                    $upd3 =  DB::connection($this->db)->table('gr_pb_m')
                    ->where('no_pb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table('gr_pb_m')
                    ->where('no_pb', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select isnull(c.no_telp,'-') as no_telp,b.nik_buat,isnull(c.email,'-') as email
                from gr_pb_m b
                inner join apv_karyawan c on b.nik_buat=c.nik 
                where b.no_pb='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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
                $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
                $result = json_decode(json_encode($result),true);
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";

                    if(count($result['original']['data']) > 0){
                   
                        $result['original']['judul'] = $pesan_header;
                        $html = view('email-siaga',$result['original'])->render();
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
                        $html = view('email-siaga',$result['original'])->render();
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

            $sql="select a.no_bukti,b.no_dokumen,b.kode_pp,b.tanggal,b.keterangan,a.no_urut,c.nama as nama_pp
            from apv_flow a
            inner join gr_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
            left join apv_pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_pb,a.nama_brg,a.satuan,a.jumlah,a.harga,a.nu 
            from gr_pb_boq a 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_pb='$no_aju' order by a.nu";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_pb,no_gambar,nu,kode_jenis,no_ref from gr_pb_dok where kode_lokasi='".$kode_lokasi."' and no_pb='$no_aju' order by nu";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "
			select convert(varchar,e.id) as id,a.no_pb,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from gr_pb_m a
            inner join apv_pesan e on a.no_pb=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            left join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' 
			order by id2
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

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.keterangan,e.nik,convert(varchar,a.tanggal,103) as tgl,case when a.status = '2' then 'Approved' when a.status = 'R' then 'Return' end as status
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


    function cek(Request $request){
        // EMAIL
        if($data =  Auth::guard($this->guard)->user()){
            $nik_user= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        $request->all();
        $request->request->add(['no_bukti' => ["=",$request->no_aju,""]]);
        $result = app('App\Http\Controllers\Siaga\LaporanController')->getAjuForm($request);
        $result = json_decode(json_encode($result),true);
        $success['status'] = true;
        if(count($result['original']['data']) > 0){
            $judul = "Pengajuan Nomor : ".$data[0]['no_pb']." berikut menunggu approval Anda";
            $result['original']['judul'] = $judul;
            $html = view('email-siaga',$result['original'])->render();
            $periode = substr(date('Ym'),2,4);
            $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
            
            $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-','enahadiani2@gmail.com',htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
            $success['no_pooling'] = $no_pool;
        }
        return response()->json($success, $this->successStatus);
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

}
