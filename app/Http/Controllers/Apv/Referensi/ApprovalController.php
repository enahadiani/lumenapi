<?php

namespace App\Http\Controllers\Apv\Referensi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class ApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'oracleaws';
    public $guard = 'adminoci';

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
    }

    // function sendMail($email,$to_name,$data){
    //     try {
    //         $template_data = array("name"=>$to_name,"body"=>$data);
    //         Mail::send('mail', $template_data,
    //         function ($message) use ($email) {
    //             $message->to($email)
    //             ->subject('Pengajuan (SAI LUMEN)');
    //         });
            
    //         return array('status' => 200, 'msg' => 'Sent successfully');
    //     } catch (Exception $ex) {
    //         return array('status' => 200, 'msg' => 'Something went wrong, please try later.');
    //     }  
    // }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format, $iteration = 1){
        $query = DB::connection($this->db)->select("select nvl(max(substr($kolom_acuan, -".strlen($str_format)."))+".$iteration.",".$iteration.") as id from $tabel where $kolom_acuan like '$prefix%'");
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

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_urut,a.id,a.keterangan,c.keterangan as deskripsi,a.tanggal,nvl(c.kode_dam,'-') as kode_dam,nvl(d.nama,'-') as nama_dam,case when a.status = '2' then 'APPROVE' else 'REJECT' end as status
            from apv_pesan a
			inner join agg_rkm_m c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi
			left join agg_dam d on c.kode_dam=d.kode_dam and c.kode_lokasi=d.kode_lokasi 
            left join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            where a.kode_lokasi='$kode_lokasi' and b.kode_jab='".$kode_jab."' and b.nik= '$nik_user' and a.modul='AJU' 
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
            from karyawan a
            where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_jab = $get[0]['kode_jab'];
            }else{
                $kode_jab = "";
            }

            $res = DB::connection($this->db)->select("select b.no_bukti,b.kode_pp,to_char(b.tanggal,'DD/MM/YYYY')  as tanggal,b.keterangan,b.komentar,p.nama as nama_pp
            from apv_flow a
            inner join agg_rkm_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.status='1' and a.kode_jab='".$kode_jab."' and a.nik= '$nik_user' and b.flag_draft <> '1'
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

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $get = DB::connection($this->db)->select("select nvl(max(id)+1,1) as no_app from apv_pesan where kode_lokasi='$kode_lokasi' ");
            $no_app = (isset($get[0]->no_app) ? $get[0]->no_app : 1);

            $no_bukti = $request->input('no_aju');
            $nik_buat = "";
            $nik_app1 = "";
            $nik_app = $nik_user;
            $token_player = array();
            $token_player2 = array();
            $ins = DB::connection($this->db)->insert("insert into apv_pesan (id,no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('".$no_app."','".$no_bukti."','".$kode_lokasi."','".$request->input('keterangan')."','".$request->input('tanggal')."','".$request->input('no_urut')."','".$request->input('status')."','AJU') ");

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
                    select nvl(b.no_telp,'-') as no_telp,b.nik,nvl(b.email,'-') as email
                    from apv_flow a
                    left join karyawan b on a.kode_jab=b.kode_jab 
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

                    $upd3 =  DB::connection($this->db)->table('agg_rkm_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $request->status]);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";

                    $upd3 =  DB::connection($this->db)->table('agg_rkm_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'S']);

                    // $instorkm = DB::connection($this->db)->insert("
                    // insert into agg_rkm(kode_rkm,kode_lokasi,tahun,nama,flag_aktif,kode_program,kode_pp,jenis)
                    // select a.kode_rkm2,a.kode_lokasi,TO_CHAR(CURRENT_DATE,'YYYY') as tahun,a.nama,1 as flag_aktif,'-' as kode_program,b.kode_pp,'-' as jenis
                    // from agg_rkm_d a
                    // inner join agg_rkm_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                    // where a.no_bukti='".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."' ");

                    $getd = DB::connection($this->db)->select("select a.kode_lokasi,TO_CHAR(CURRENT_DATE,'YYYY') as tahun,a.nama,1 as flag_aktif,'-' as kode_program,b.kode_pp,a.kode_dam,'-' as jenis,a.no_urut
                    from agg_rkm_d a
                    inner join agg_rkm_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."'
                    order by a.no_urut");
                    if(count($getd) > 0){
                        foreach($getd as $row){
                            $kode_rkm = $this->generateKode("agg_rkm", "kode_rkm", $kode_lokasi."-RKM".substr($periode,2,4).".", "0001");
                            $insdtorkm[] = DB::connection($this->db)->insert("
                            insert into agg_rkm(kode_rkm,kode_lokasi,tahun,nama,flag_aktif,kode_program,kode_pp,jenis) values ('".$kode_rkm."','".$kode_lokasi."','".date('Y')."','".$row->nama."','1','".$row->kode_dam."','".$row->kode_pp."','-') ");
                            $updrkmd[] = DB::connection($this->db)->update("update agg_rkm_d set kode_rkm_akhir='".$kode_rkm."' where no_bukti='$no_bukti' and nama='".$row->nama."' and no_urut='".$row->no_urut."' ");
                        }
                    }
                    $psn = "Approver terakhir";

                    
                }
                
                // //send to nik buat
                $sqlbuat = "
                select nvl(c.no_telp,'-') as no_telp,b.nik_buat,nvl(c.email,'-') as email
                from agg_rkm_m b 
                inner join karyawan c on b.nik_buat=c.nik 
                where b.no_bukti='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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

                $row1 = DB::connection($this->db)->select("select * from agg_rkm_m where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi'");
                $row2 = DB::connection($this->db)->select("select * from agg_rkm_d where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi'");
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah di approve oleh $nik_user, menunggu approval Anda:";
                    // $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan $no_bukti berhasil dikirim, menunggu verifikasi");
                    // $pesan_body = "Pengajuan $no_bukti menunggu approval anda";
                    
                    $pesan_body = $pesan_header.'
                    <div class="row">
                    <div class="col-12 text-center" style="border-bottom:3px solid black;">
                    <h3>PENGAJUAN RKM</h3>
                    </div>    
                    <div class="col-12 my-3 text-center">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                    <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($row1[0]->tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($row1[0]->tanggal,5, 2))).' '.substr($row1[0]->tanggal,0,4).'</h5>     
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered">
                    <tbody>
                    <tr>
                    <td style="width: 5%;">1</td>
                    <td style="width: 25%;">UNIT KERJA</td>
                    <td>:'.$row1[0]->kode_pp.'</td>
                    </tr>
                    <tr>
                    <td style="width: 5%;">3</td>
                    <td style="width: 25%;">DESKRIPSI</td>
                    <td>:'.$row1[0]->keterangan.'</td>
                    </tr> 
                    <tr>
                    <td style="width: 5%;">2</td>
                    <td style="width: 25%;">KOMENTAR</td>
                    <td>:'.$row1[0]->komentar.'</td>
                    </tr>    
                    </tbody>
                    </table>
                    </div>
                    <div class="col-12">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DETAIL RKM</u></h6>  
                    <table class="table table-bordered table-condensed" style="border:1px solid black;border-collapse:collapse">
                    <thead>
                    <tr>
                    <th class="" style="width: 5%;border:1px solid black;">No</th>    
                    <th class="" style="width: 45%;border:1px solid black;">Nama RKM</th> 
                    <th class="" style="width: 50%;border:1px solid black;">DAM</th> 
                    </tr>    
                    </thead>
                    <tbody>';
                    $no=1;
                    
                    for($i=0; $i<count($row2);$i++){
                        $pesan_body .='
                        <tr>
                        <td style="border:1px solid black;">'.$no.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->nama.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->kode_dam.'</td>
                        </tr>
                        ';
                        $no++;
                    }
                    $pesan_body .='
                    </tbody>    
                    </table>
                    </div>
                    </div>
                    ';
                    $periode = date('Ym');
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    
                    // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
        
                    $success['no_pooling'] = $no_pool;
                    $msg_email = "";
                }else{
                    $msg_email = "";
                }

                if(isset($app_email2) && $app_email2 != "-"){
                    $pesan_header = "Pengajuan $no_bukti Anda telah diapprove oleh $nik_user, berikut ini rinciannya:";
                    $pesan_body = $pesan_header.'
                    <div class="row">
                    <div class="col-12 text-center" style="border-bottom:3px solid black;">
                    <h3>PENGAJUAN RKM</h3>
                    </div>    
                    <div class="col-12 my-3 text-center">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                    <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($row1[0]->tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($row1[0]->tanggal,5, 2))).' '.substr($row1[0]->tanggal,0,4).'</h5>     
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered">
                    <tbody>
                    <tr>
                    <td style="width: 5%;">1</td>
                    <td style="width: 25%;">UNIT KERJA</td>
                    <td>:'.$row1[0]->kode_pp.'</td>
                    </tr>
                    <tr>
                    <td style="width: 5%;">3</td>
                    <td style="width: 25%;">DESKRIPSI</td>
                    <td>:'.$row1[0]->keterangan.'</td>
                    </tr> 
                    <tr>
                    <td style="width: 5%;">2</td>
                    <td style="width: 25%;">KOMENTAR</td>
                    <td>:'.$row1[0]->komentar.'</td>
                    </tr>    
                    </tbody>
                    </table>
                    </div>
                    <div class="col-12">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DETAIL RKM</u></h6>  
                    <table class="table table-bordered table-condensed" style="border:1px solid black;border-collapse:collapse">
                    <thead>
                    <tr>
                    <th class="" style="width: 5%;border:1px solid black;">No</th>    
                    <th class="" style="width: 20%;border:1px solid black;">Nama RKM</th> 
                    <th class="" style="width: 20%;border:1px solid black;">DAM</th> 
                    </tr>    
                    </thead>
                    <tbody>';
                    $no=1;
                    
                    for($i=0; $i<count($row2);$i++){
                        $pesan_body .='
                        <tr>
                        <td style="border:1px solid black;">'.$no.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->nama.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->kode_dam.'</td>
                        </tr>
                        ';
                        $no++;
                    }
                    $pesan_body .='
                    </tbody>    
                    </table>
                    </div>
                    </div>
                    ';
                    $periode = date('Ym');
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    
                    // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email2','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email2,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
        
                    $success['no_pooling2'] = $no_pool;
                    $msg_email = "";
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
                    select nvl(b.no_telp,'-') as no_telp,b.nik,nvl(b.email,'-') as email
                    from apv_flow a
                    left join karyawan b on a.kode_jab=b.kode_jab 
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
                    $upd3 =  DB::connection($this->db)->table('agg_rkm_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'B']);
                }else{
                    $no_telp = "-";
                    $nik_app1 = "-";
                    $upd3 =  DB::connection($this->db)->table('agg_rkm_m')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => 'R']);
                }
                //send to nik buat

                $sqlbuat="
                select nvl(c.no_telp,'-') as no_telp,b.nik_buat,nvl(c.email,'-') as email
                from agg_rkm_m b
                inner join karyawan c on b.nik_buat=c.nik 
                where b.no_bukti='".$no_bukti."' and b.kode_lokasi='$kode_lokasi' ";
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

                $row1 = DB::connection($this->db)->select("select * from agg_rkm_m where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi'");
                $row2 = DB::connection($this->db)->select("select * from agg_rkm_d where no_bukti='".$no_bukti."' and kode_lokasi='$kode_lokasi'");
                if(isset($app_email) && $app_email != "-"){
                    $pesan_header = "Pengajuan $no_bukti berikut telah direturn oleh $nik_user :";
                    // $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan $no_bukti berhasil dikirim, menunggu verifikasi");
                    // $pesan_body = "Pengajuan $no_bukti menunggu approval anda";
                    
                    $pesan_body = $pesan_header.'
                    <div class="row">
                    <div class="col-12 text-center" style="border-bottom:3px solid black;">
                    <h3>PENGAJUAN RKM</h3>
                    </div>    
                    <div class="col-12 my-3 text-center">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                    <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($row1[0]->tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($row1[0]->tanggal,5, 2))).' '.substr($row1[0]->tanggal,0,4).'</h5>     
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered">
                    <tbody>
                    <tr>
                    <td style="width: 5%;">1</td>
                    <td style="width: 25%;">UNIT KERJA</td>
                    <td>:'.$row1[0]->kode_pp.'</td>
                    </tr>
                    <tr>
                    <td style="width: 5%;">3</td>
                    <td style="width: 25%;">DESKRIPSI</td>
                    <td>:'.$row1[0]->keterangan.'</td>
                    </tr> 
                    <tr>
                    <td style="width: 5%;">2</td>
                    <td style="width: 25%;">KOMENTAR</td>
                    <td>:'.$row1[0]->komentar.'</td>
                    </tr>    
                    </tbody>
                    </table>
                    </div>
                    <div class="col-12">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DETAIL RKM</u></h6>  
                    <table class="table table-bordered table-condensed" style="border:1px solid black;border-collapse:collapse">
                    <thead>
                    <tr>
                    <th class="" style="width: 5%;border:1px solid black;">No</th>    
                    <th class="" style="width: 45%;border:1px solid black;">Nama RKM</th> 
                    <th class="" style="width: 50%;border:1px solid black;">DAM</th> 
                    </tr>    
                    </thead>
                    <tbody>';
                    $no=1;
                    
                    for($i=0; $i<count($row2);$i++){
                        $pesan_body .='
                        <tr>
                        <td style="border:1px solid black;">'.$no.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->nama.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->kode_dam.'</td>
                        </tr>
                        ';
                        $no++;
                    }
                    $pesan_body .='
                    </tbody>    
                    </table>
                    </div>
                    </div>
                    ';
                    $periode = date('Ym');
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    
                    // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
        
                    $success['no_pooling'] = $no_pool;
                    $msg_email = "";
                }else{
                    $msg_email = "";
                }

                if(isset($app_email2) && $app_email2 != "-"){
                    $pesan_header = "Pengajuan $no_bukti Anda telah direturn oleh $nik_user, berikut ini rinciannya:";
                    $pesan_body = $pesan_header.'
                    <div class="row">
                    <div class="col-12 text-center" style="border-bottom:3px solid black;">
                    <h3>PENGAJUAN RKM</h3>
                    </div>    
                    <div class="col-12 my-3 text-center">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                    <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($row1[0]->tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($row1[0]->tanggal,5, 2))).' '.substr($row1[0]->tanggal,0,4).'</h5>     
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered">
                    <tbody>
                    <tr>
                    <td style="width: 5%;">1</td>
                    <td style="width: 25%;">UNIT KERJA</td>
                    <td>:'.$row1[0]->kode_pp.'</td>
                    </tr>
                    <tr>
                    <td style="width: 5%;">3</td>
                    <td style="width: 25%;">DESKRIPSI</td>
                    <td>:'.$row1[0]->keterangan.'</td>
                    </tr> 
                    <tr>
                    <td style="width: 5%;">2</td>
                    <td style="width: 25%;">KOMENTAR</td>
                    <td>:'.$row1[0]->komentar.'</td>
                    </tr>    
                    </tbody>
                    </table>
                    </div>
                    <div class="col-12">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DETAIL RKM</u></h6>  
                    <table class="table table-bordered table-condensed" style="border:1px solid black;border-collapse:collapse">
                    <thead>
                    <tr>
                    <th class="" style="width: 5%;border:1px solid black;">No</th>    
                    <th class="" style="width: 45%;border:1px solid black;">Nama RKM</th> 
                    <th class="" style="width: 50%;border:1px solid black;">DAM</th> 
                    </tr>    
                    </thead>
                    <tbody>';
                    $no=1;
                    
                    for($i=0; $i<count($row2);$i++){
                        $pesan_body .='
                        <tr>
                        <td style="border:1px solid black;">'.$no.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->nama.'</td>
                        <td style="border:1px solid black;">'.$row2[$i]->kode_dam.'</td>
                        </tr>
                        ';
                        $no++;
                    }
                    $pesan_body .='
                    </tbody>    
                    </table>
                    </div>
                    </div>
                    ';
                    $periode = date('Ym');
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    
                    // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email2','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email2,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
        
                    $success['no_pooling2'] = $no_pool;
                    $msg_email = "";
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

            $sql="select a.no_bukti,b.no_dokumen,b.kode_pp,b.komentar,b.tanggal,b.keterangan,a.no_urut,c.nama as nama_pp
            from apv_flow a
            inner join agg_rkm_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju' and a.status='1' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.nama,a.kode_rkm_akhir,a.kode_dam||' - '||b.nama as dam 
            from agg_rkm_d a 
            inner join agg_dam b on a.kode_dam=b.kode_dam and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_aju'  order by a.no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from agg_rkm_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju' and jenis='AJU' order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "
			select to_char(e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from agg_rkm_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi' 
			order by id2
	        ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $sql5="select a.no_bukti,count(kode_rkm_akhir) as jum_rkm
            from agg_rkm_d a 
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi'
            group by a.no_bukti";
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $i = 1; $c=0;
                $periode = date('Ym');
                if(count($res2) > 0){
                    foreach($res2 as $row){
                        $kode_rkm = $this->generateKode("agg_rkm", "kode_rkm", $kode_lokasi."-RKM".substr($periode,2,4).".", "0001",$i);
                        $res2[$c]['kode_rkm_akhir'] = $kode_rkm;
                        $i++;
                        $c++;
                    }
                }
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

            $sql="select a.id,a.no_bukti,a.tanggal,b.kode_pp,c.nama as nama_pp,b.keterangan,e.nik,to_char(a.tanggal,'DD/MM/YYYY') as tgl,case when a.status = '2' then 'Approved' when a.status = '3' then 'Return' end as status
            from apv_pesan a
            inner join agg_rkm_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
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

     // SEND EMAIL
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
                                 'subject' => 'Pengajuan RKM',
                                 'html' => htmlspecialchars_decode($row->pesan)
                             ]
                         ]);
                         if ($response->getStatusCode() == 200) { // 200 OK
                             $response_data = $response->getBody()->getContents();
                             $data = json_decode($response_data,true);
                             if(isset($data["id"])){
                                 $success['data2'] = $data;
                                 DB::connection($this->db)->update("update pooling set tgl_kirim=current_date,flag_kirim=1 where flag_kirim=0 and no_pool ='$request->no_pooling' and jenis='EMAIL'
                                 ");
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
