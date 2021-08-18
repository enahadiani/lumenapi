<?php

namespace App\Http\Controllers\RKAP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class PengajuanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'oracleaws';
    public $guard = 'adminoci';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function isUnik($isi,$kode_lokasi,$kode_pp){
        
        $auth = DB::connection($this->db)->select("select no_dokumen from agg_rkm_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function cekAjuAktif($kode_lokasi){
        
        $auth = DB::connection($this->db)->select("select * from agg_rkm_m where flag_aktif='1' and kode_lokasi='$kode_lokasi' and progress <> 'S' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function isUnik2($isi,$kode_lokasi,$kode_pp,$no_bukti){
        
        $auth = DB::connection($this->db)->select("select no_dokumen from agg_rkm_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."' and no_bukti <> '$no_bukti' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    // function sendMail($email,$to_name,$data){
    //     try {

            
    //         $template_data = array("name"=>$to_name,"body"=>$data);
    //         Mail::send('mail', $template_data,
    //         function ($message) use ($email) {
    //             $message->to($email)
    //             ->subject('Pengajuan Justifikasi Kebutuhan (SAI LUMEN)');
    //         });
            
    //         return array('status' => 200, 'msg' => 'Sent successfully');
    //     } catch (Exception $ex) {
    //         return array('status' => 200, 'msg' => 'Something went wrong, please try later.');
    //     }  
    // }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select nvl(max(substr($kolom_acuan, -".strlen($str_format)."))+1,1) as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function generateKode2($tabel, $kolom_acuan, $prefix, $str_format, $prefix2, $tahun,$kode_lokasi){
        $query = DB::connection($this->db)->select("select max(right($kolom_acuan, ".strlen($str_format)."))+1 as id from $tabel where $kolom_acuan like '%$prefix2%' and substring(convert(varchar(10),tanggal,121),1,4) = '$tahun' and kode_lokasi='$kode_lokasi' ");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function generateDok(Request $request){
        if($dt =  Auth::guard($this->guard)->user()){
            $nik_log= $dt->nik;
            $kode_lok_log= $dt->kode_lokasi;
        }

        $format = $this->reverseDate($request->tanggal,"-","-")."/".$request->kode_pp."/".$request->kode_dam."/";
        $format2 = "/".$request->kode_pp."/".$request->kode_dam."/";
        $tahun = substr($request->tanggal,0,4);
        $no_dokumen = $this->generateKode2("agg_rkm_m", "no_dokumen", $format, "00001", $format2,$tahun,$kode_lok_log);
        return $no_dokumen;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_dokumen,a.kode_pp,a.komentar,a.tanggal,a.keterangan,p.nama as nama_pp,
            case when a.progress='R' then 'Return' when a.progress = 'S' then 'Finish' else nvl(x.nama_jab,'-') 
            end as posisi,a.progress
            from agg_rkm_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_bukti=x.no_bukti
						where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik' and a.flag_aktif=1
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

    public function getDataBox()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select count(*) as selesai 
            from agg_rkm_m where progress ='S' and nik_buat='$nik' and kode_lokasi='$kode_lokasi'
            ");
            $res2 = DB::connection($this->db)->select("
            select count(*) as sedang 
            from agg_rkm_m 
            where progress not in ('S','R') and nik_buat='$nik' and kode_lokasi='$kode_lokasi'
            ");
            $res3 = DB::connection($this->db)->select("
            select count(*) as perlu
            from agg_rkm_m a
            inner join apv_flow b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
            where b.status=1 and b.nik='$nik' and a.kode_lokasi='$kode_lokasi'
            ");
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['selesai'] = $res[0]->selesai;
            }else{
                $success['selesai'] = 0;
            }

            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['sedang'] = $res2[0]->sedang;
            }else{
                $success['sedang'] = 0;
            }

            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['perlu'] = $res3[0]->perlu;
            }else{
                $success['perlu'] = 0;
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataPerbaikan()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select b.* from agg_rkm_m a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.nik_buat='$nik' and a.progress='R' and b.no_urut = 0 and b.status = 3 and b.modul ='AJU' and a.kode_lokasi='$kode_lokasi'
            order by b.tanggal desc
            ");

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
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function AjuDraft()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_pp,a.komentar,a.tanggal,a.keterangan,p.nama as nama_pp,
            case when a.progress='R' then 'Return' when a.progress = 'S' then 'Finish' else nvl(x.nama_jab,'-') 
            end as posisi,a.progress
            from agg_rkm_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_bukti=x.no_bukti
			where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik' and nvl(a.flag_draft,'-') = '1' and a.flag_aktif=1
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

    public function AjuSedang()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_pp,a.komentar,a.tanggal,a.keterangan,p.nama as nama_pp,
            case when a.progress='R' then 'Return' when a.progress = 'S' then 'Finish' else nvl(x.nama_jab,'-') 
            end as posisi,a.progress
            from agg_rkm_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_bukti=x.no_bukti
            where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik' and nvl(a.flag_draft,'-') <> '1' and a.progress not in ('R','S') and a.flag_aktif=1
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


    public function AjuSelesai()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.kode_pp,a.komentar,a.tanggal,a.keterangan,p.nama as nama_pp,
            case when a.progress='R' then 'Return' when a.progress = 'S' then 'Finish' else nvl(x.nama_jab,'-') 
            end as posisi,a.progress
            from agg_rkm_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_bukti=x.no_bukti
						where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik' and nvl(a.flag_draft,'-') <> '1' and a.progress in ('S') and a.flag_aktif=1
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

    public function getDokumen(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(strpos($request->no_bukti, "APP") || substr($request->no_bukti,0,3) == "APP"){

                $get =  DB::connection($this->db)->select("select no_juskeb from apv_juspo_m
                where no_bukti='$request->no_bukti' and kode_lokasi='$kode_lokasi'
                ");
                if(count($get) > 0){
                    $no_bukti = $get[0]->no_juskeb;
                }else{
                    $no_bukti = "-";
                }
            }else{
                $no_bukti = $request->no_bukti;
            }
            $success['cek'] = strpos($request->no_bukti, "APP");

            $success['no_bukti'] = $no_bukti;
            $res = DB::connection($this->db)->select("select no_bukti,nama,jenis,'".url('api/apv/storage')."/'+file_dok as url from agg_rkm_dok
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'
            order by no_bukti,no_urut,jenis
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


    public function getFinish()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_dokumen,a.kode_pp,to_char(a.tanggal,'DD/MM/YYYY') as tanggal,a.keterangan,p.nama as nama_pp,a.kode_dam,a.progress,'-' as posisi
            from agg_rkm_m_h a
            left join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )x on a.no_bukti=x.no_bukti
			where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik_user' and a.flag_aktif=1
                                       
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

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
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
            'keterangan' => 'required|max:300',
            'komentar' => 'required|max:150',
            'kode_pp'=>'required',
            'nama'=> 'required|array',
            'dam'=> 'required|array',
            'nama_dok'=>'array',
            'flag_draft' => 'required',
            'file.*'=>'file|max:10240'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->cekAjuAktif($kode_lokasi)){
                if($this->isUnik($request->no_dokumen,$kode_lokasi,$request->kode_pp)){
    
                    $arr_foto = array();
                    $arr_nama = array();
                    $i=0;
                    if($request->hasfile('file'))
                    {
                        foreach($request->file('file') as $file)
                        {                
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('local')->exists('rkap/'.$foto)){
                                Storage::disk('local')->delete('rkap/'.$foto);
                            }
                            Storage::disk('local')->put('rkap/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                            $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                            $i++;
                        }
                    }
        
                    $periode = date('Ym');
                    $no_bukti = $this->generateKode("agg_rkm_m", "no_bukti", $kode_lokasi."-AGG".substr($periode,2,4).".", "0001");
    
                    $no_dokumen = $request->no_dokumen;
    
                    $ins = DB::connection($this->db)->insert("insert into agg_rkm_m (no_bukti,komentar,kode_pp,keterangan,nik_buat,kode_lokasi,progress,tanggal,flag_draft,flag_aktif) values ('".$no_bukti."','$request->komentar','".$request->kode_pp."','".$request->keterangan."','".$nik."','".$kode_lokasi."','A',current_date,'$request->flag_draft',1) ");
        
                    if(count($request->nama) > 0){
                        for($i=0; $i<count($request->nama);$i++){
                            $tmp = explode(" - ",$request->dam[$i]);
                            $kode_dam = $tmp[0];
                            $ins2[$i] = DB::connection($this->db)->insert("insert into agg_rkm_d (kode_lokasi,no_bukti,no_urut,nama,kode_dam) values ('".$kode_lokasi."','".$no_bukti."','".$i."','".$request->nama[$i]."','$kode_dam') ");
                        }
                    }
        
                    if(count($arr_nama) > 0){
                        for($i=0; $i<count($arr_nama);$i++){
                            $ins3[$i] = DB::connection($this->db)->insert("insert into agg_rkm_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i],"AJU"]); 
                        }
                    }
    
                     $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp,nvl(c.email,'-') as email
                        from apv_role a
                        inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                        inner join karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.modul='AJU' and a.kode_pp='$request->kode_pp'
                        order by b.no_urut";
        
                    $role = DB::connection($this->db)->select($sql);
                    $role = json_decode(json_encode($role),true);
                    $token_player = array();
                    
                    for($i=0;$i<count($role);$i++){
                        
                        if($i == 0){
                            $prog = 1;
                            $no_telp = $role[$i]["no_telp"];
                            $app_nik = $role[$i]["nik"];
                            $app_email = $role[$i]["email"];
                        }else{
                            $prog = 0;
                            $app_nik = "-";
                        }
                        $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,nik) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,$role[$i]["nik"]]);
                    }
                    
                    if(isset($app_email) && $app_email != "-" && $request->flag_draft == "0"){
        
                        // $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan $no_bukti berhasil dikirim, menunggu verifikasi");
                        // $pesan_body = "Pengajuan $no_bukti menunggu approval anda";
                        $tanggal = date('Y-m-d');
                        $pesan_body = '
                        Pengajuan '.$no_bukti.' berikut menunggu approval Anda:
                        <div class="row">
                        <div class="col-12 text-center" style="border-bottom:3px solid black;">
                        <h3>PENGAJUAN RKM</h3>
                        </div>    
                        <div class="col-12 my-3 text-center">
                        <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                        <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($tanggal,5, 2))).' '.substr($tanggal,0,4).'</h5>     
                        </div>
                        <div class="col-12">
                        <table class="table table-condensed table-bordered">
                        <tbody>
                        <tr>
                        <td style="width: 5%;">1</td>
                        <td style="width: 25%;">UNIT KERJA</td>
                        <td>:'.$request->kode_pp.'</td>
                        </tr>
                        <tr>
                        <td style="width: 5%;">3</td>
                        <td style="width: 25%;">DESKRIPSI</td>
                        <td>:'.$request->keterangan.'</td>
                        </tr> 
                        <tr>
                        <td style="width: 5%;">2</td>
                        <td style="width: 25%;">KOMENTAR</td>
                        <td>:'.$request->komentar.'</td>
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
                        <th class="" style="width: 20%;border:1px solid black;">Kode</th> 
                        <th class="" style="width: 75%;border:1px solid black;">DAM</th> 
                        </tr>    
                        </thead>
                        <tbody>';
                        $no=1;
                        for($i=0; $i<count($request->nama);$i++){
                            $pesan_body .='
                            <tr>
                            <td style="border:1px solid black;">'.$no.'</td>
                            <td style="border:1px solid black;">'.$request->nama[$i].'</td>
                            <td style="border:1px solid black;">'.$request->dam[$i].'</td>
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
                        $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                        
                        // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                        $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
            
                        $success['no_pooling'] = $no_pool;
                        $msg_email = "";
                    }else{
                        $msg_email = "";
                    }
                    
                    DB::connection($this->db)->commit();
    
                    $success['nik_app'] = (isset($role[0]["nik"]) ? $role[0]["nik"] : '-');
                    $success['status'] = true;
                    $success['message'] = "Data Pengajuan berhasil disimpan. No Bukti:".$no_bukti.$msg_email;
                    $success['no_aju'] = $no_bukti;
                  
                }else{
                    
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                    $success['no_aju'] = '-';
                    $success['nik_app'] = '-';
                }
            }else{
                
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = "Error : Tidak dapat mengajukan RKM. Masih ada data pengajuan yang aktif";
                $success['no_aju'] = '-';
                $success['nik_app'] = '-';
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan gagal disimpan ".$e;
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

            $no_bukti = $request->no_bukti;
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.komentar,a.kode_pp,a.keterangan,to_char(a.tanggal,'DD/MM/YYYY') as tanggal,b.nama as nama_pp
            from agg_rkm_m a 
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.nama as nama_rkm,a.kode_dam||' - '||b.nama as dam 
            from agg_rkm_d a
            inner join agg_dam b on a.kode_dam=b.kode_dam and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti' 
            order by a.no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from agg_rkm_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' and jenis='AJU' order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
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
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'keterangan' => 'required|max:300',
            'komentar' => 'required|max:150',
            'kode_pp'=>'required',
            'nama'=> 'required|array',
            'dam'=> 'required|array',
            'nama_dok'=>'array',
            'flag_draft' => 'required',
            'file.*'=>'file|max:10240'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

                $no_bukti = $request->no_bukti;
                $no_dokumen = $request->no_dokumen;
                $arr_foto = array();
                $arr_nama = array();
                $arr_foto2 = array();
                $arr_nama2 = array();
                $arr_jenis_dok = array();
                $i=0;
                $cek = $request->file;
                //cek upload file tidak kosong
                if(!empty($cek)){

                    if(count($request->nama_file) > 0){
                        //looping berdasarkan nama dok
                        for($i=0;$i<count($request->nama_file);$i++){
                            //cek row i ada file atau tidak
                            if(isset($request->file('file')[$i])){
                                $file = $request->file('file')[$i];

                                //kalo ada cek nama sebelumnya ada atau -
                                if($request->nama_file_seb[$i] != "-"){
                                    //kalo ada hapus yang lama
                                    Storage::disk('local')->delete('rkap/'.$request->nama_file_seb[$i]);
                                }
                                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $foto = $nama_foto;
                                if(Storage::disk('local')->exists('rkap/'.$foto)){
                                    Storage::disk('local')->delete('rkap/'.$foto);
                                }
                                Storage::disk('local')->put('rkap/'.$foto,file_get_contents($file));
                                $arr_foto[] = $foto;
                            }else{
                                $arr_foto[] = $request->nama_file_seb[$i];
                            }     
                            $arr_nama[] = $request->input('nama_file')[$i];
                            $arr_nama2[] = count($request->nama_file).'|'.$i.'|'.isset($request->file('file')[$i]);
                        }
    
                        $del3 = DB::connection($this->db)->table('agg_rkm_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                    }
                }

                // CEK PERNAH APPROVAL (RETURN)
                $sql = "select a.progress
                    from agg_rkm_m a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
    
                $cek = DB::connection($this->db)->select($sql);
                if(count($cek) > 0){
                    if($cek[0]->progress == "R"){
                        $periode = date('Ym');
                        $no_bukti_baru = $this->generateKode("agg_rkm_m", "no_bukti", $kode_lokasi."-AGG".substr($periode,2,4).".", "0001");
                        $ins_m_h = $ins = DB::connection($this->db)->insert("INSERT INTO AGG_RKM_M_H (NO_BUKTI,KODE_LOKASI,KODE_PP,TANGGAL,KETERANGAN,NIK_BUAT,NO_DOKUMEN,NO_REF,PROGRESS,KOMENTAR)
                        select NO_BUKTI,KODE_LOKASI,KODE_PP,TANGGAL,KETERANGAN,NIK_BUAT,NO_DOKUMEN,'$no_bukti_baru' as NO_REF,'A' as PROGRESS,KOMENTAR from AGG_RKM_M 
                        where NO_BUKTI='$no_bukti' and KODE_LOKASI='$kode_lokasi' ");
                        
                        $ins_m_d = $ins = DB::connection($this->db)->insert("insert into agg_rkm_d_h
                        select * from agg_rkm_d where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
                       
                        $del = DB::connection($this->db)->table('agg_rkm_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $del2 = DB::connection($this->db)->table('agg_rkm_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $no_bukti = $no_bukti_baru;

                    }else{
                        $del = DB::connection($this->db)->table('agg_rkm_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $del2 = DB::connection($this->db)->table('agg_rkm_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        $no_bukti = $request->no_bukti;
                    }

                }else{
                    $del = DB::connection($this->db)->table('agg_rkm_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                    $del2 = DB::connection($this->db)->table('agg_rkm_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                    $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                    $no_bukti = $request->no_bukti;
                }

                $ins = DB::connection($this->db)->insert("insert into agg_rkm_m (no_bukti,komentar,kode_pp,keterangan,nik_buat,kode_lokasi,progress,tanggal,flag_draft,flag_aktif) values ('".$no_bukti."','$request->komentar','".$request->kode_pp."','".$request->keterangan."','".$nik."','".$kode_lokasi."','A',current_date,'$request->flag_draft',1) ");
    
                if(count($request->nama) > 0){
                    for($i=0; $i<count($request->nama);$i++){
                        $tmp = explode(" - ",$request->dam[$i]);
                        $kode_dam = $tmp[0];
                        $ins2[$i] = DB::connection($this->db)->insert("insert into agg_rkm_d (kode_lokasi,no_bukti,no_urut,nama,kode_dam) values ('".$kode_lokasi."','".$no_bukti."','".$i."','".$request->nama[$i]."','$kode_dam') ");
                    }
                }
    
                if(count($arr_nama) > 0){
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins3[$i] = DB::connection($this->db)->insert("insert into agg_rkm_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i],"AJU"]); 
                    }
                }

                $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp,nvl(c.email,'-') as email
                    from apv_role a
                    inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                    inner join karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.modul='AJU' and a.kode_pp='$request->kode_pp'
                    order by b.no_urut";
    
                $role = DB::connection($this->db)->select($sql);
                $role = json_decode(json_encode($role),true);
                $token_player = array();
                
                for($i=0;$i<count($role);$i++){
                    
                    if($i == 0){
                        $prog = 1;
                        $no_telp = $role[$i]["no_telp"];
                        $app_nik = $role[$i]["nik"];
                        $app_email = $role[$i]["email"];
                    }else{
                        $prog = 0;
                        $app_nik = "-";
                    }
                    $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,nik) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,$role[$i]["nik"]]);
                }
                
                if(isset($app_email) && $app_email != "-" && $request->flag_draft == "0"){
    
                    // $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan $no_bukti berhasil dikirim, menunggu verifikasi");
                    
                    $tanggal = date('Y-m-d');
                    $pesan_body = '
                    Pengajuan '.$no_bukti.' berikut menunggu approval Anda:
                    <div class="row">
                    <div class="col-12 text-center" style="border-bottom:3px solid black;">
                    <h3>PENGAJUAN RKM</h3>
                    </div>    
                    <div class="col-12 my-3 text-center">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DATA RKM</u></h6>  
                    <h5 style="font-weight: bold; font-size: 13px;" class ="text-center" id="tanggal-print">Tanggal : '.substr($tanggal,8, 2).' '.$this->getNamaBulan(intval(substr($tanggal,5, 2))).' '.substr($tanggal,0,4).'</h5>     
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered">
                    <tbody>
                    <tr>
                    <td style="width: 5%;">1</td>
                    <td style="width: 25%;">UNIT KERJA</td>
                    <td>:'.$request->kode_pp.'</td>
                    </tr>
                    <tr>
                    <td style="width: 5%;">3</td>
                    <td style="width: 25%;">DESKRIPSI</td>
                    <td>:'.$request->keterangan.'</td>
                    </tr> 
                    <tr>
                    <td style="width: 5%;">2</td>
                    <td style="width: 25%;">KOMENTAR</td>
                    <td>:'.$request->komentar.'</td>
                    </tr>  
                    </tbody>
                    </table>
                    </div>
                    <div class="col-12">
                    <h6 style="font-weight: bold; font-size: 13px;"># <u>DETAIL RKM</u></h6>  
                    <table class="table table-bordered table-condensed" style="border:1px solid black;border-collapse:collapse">
                    <thead>
                    <tr>
                    <th class="" style="border:1px solid black;width: 5%;">No</th>    
                    <th class="" style="border:1px solid black;width: 20%;">Nama</th> 
                    <th class="" style="border:1px solid black;width: 75%;">DAM</th> 
                    </tr>    
                    </thead>
                    <tbody>';
                    $no=1;
                    for($i=0; $i<count($request->nama);$i++){
                        $pesan_body .='
                        <tr>
                        <td style="border:1px solid black;">'.$no.'</td>
                        <td style="border:1px solid black;">'.$request->nama[$i].'</td>
                        <td style="border:1px solid black;">'.$request->dam[$i].'</td>
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
                    $periode = substr($periode,2,4);
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    
                    // $inspool= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('-','$app_email','".htmlspecialchars($pesan_body)."','0',current_date,NULL,'EMAIL','$no_pool') ");

                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,current_date,?,?,?)', ['-',$app_email,htmlspecialchars($pesan_body),'0',NULL,'EMAIL',$no_pool]);
        
                    $success['no_pooling'] = $no_pool;
                    $msg_email = "";
                }else{
                    $msg_email = "";
                }
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Pengajuan berhasil diubah. No Bukti:".$no_bukti.$msg_email;
                $success['no_aju'] = $no_bukti;
                $success['nik_app'] =  $success['nik_app'] = (isset($role[0]["nik"]) ? $role[0]["nik"] : '-');
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Kebutuhan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
            $del = DB::connection($this->db)->table('agg_rkm_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('agg_rkm_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $sql3="select no_bukti,nama,file_dok from agg_rkm_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){

                    Storage::disk('local')->delete('rkap/'.$res3[$i]['file_dok']);
                }
            }

            $del3 = DB::connection($this->db)->table('agg_rkm_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del5 = DB::connection($this->db)->table('apv_pesan')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pengadaan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengadaan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getHistory(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql = "
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'green' as color,d.nik,d.nama as nama_nik
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            inner join karyawan d on a.nik=d.nik and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            order by id ";
            
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

    public function getHistoryHis(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql = "
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'green' as color,d.nik,d.nama as nama_nik
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            inner join karyawan d on a.nik=d.nik and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            order by id ";
            
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
    public function cekAksesForm(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            date_default_timezone_set('Asia/Jakarta');
            $tgl = date('Y-m-d H:i:s');

            $sql = "SELECT * FROM AGG_MODUL WHERE modul='$request->modul' and to_date('".$tgl."','YYYY-MM-DD HH24:MI:SS') BETWEEN TGL_AWAL and TGL_SELESAI ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $sql = "SELECT * FROM AGG_MODUL where modul='$request->modul' ";
                $res2 = DB::connection($this->db)->select($sql);
                if(count($res2) > 0){
                    $success['message'] = "Akses Form berbatas waktu. Form hanya dapat diakses pada tanggal ".$res2[0]->tgl_awal." s.d ".$res2[0]->tgl_selesai ;
                }else{
                    $success['message'] = "Form dilock!";
                }
                $success['status'] = false;
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
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql="select a.no_bukti,a.no_dokumen, to_char(a.tanggal,'DD/MM/YYYY') as tanggal,a.keterangan,a.kode_pp,b.nama as nama_pp,a.komentar
            from agg_rkm_m a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.nama,a.kode_dam||' - '||b.nama as dam
            from agg_rkm_d a    
            inner join agg_dam b on a.kode_dam=b.kode_dam and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,to_char(a.tanggal,'DD/MM/YYYY') as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
			from agg_rkm_m a
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(to_char(e.tanggal,'DD/MM/YYYY'),'-') as tanggal,nvl(to_char(e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, nvl(to_char(e.id),a.no_urut) as urut
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			order by nu,urut
            ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPreviewHis(Request $request)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql="select a.no_bukti,a.no_dokumen, to_char(a.tanggal,'DD/MM/YYYY') as tanggal,a.keterangan,a.kode_pp,b.nama as nama_pp,a.komentar
            from agg_rkm_m_h a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.nama,a.kode_dam||' - '||b.nama as dam
            from agg_rkm_d_h a  
            inner join agg_dam b on a.kode_dam=b.kode_dam and a.kode_lokasi=b.kode_lokasi  
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,to_char(a.tanggal,'DD/MM/YYYY') as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
			from agg_rkm_m_h a
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(to_char(e.tanggal,'DD/MM/YYYY'),'-') as tanggal,nvl(to_char(e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, nvl(to_char(e.id),a.no_urut) as urut
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			order by nu,urut
            ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPreview2($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_dokumen, convert(varchar(10),a.tanggal,121) as tanggal,a.kegiatan,a.waktu,a.dasar,a.nilai,a.kode_pp,b.nama as nama_pp,a.kode_kota,c.nama as nama_kota,a.pemakai as pic 
            from agg_rkm_m a
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.barang,a.barang_klp,a.jumlah,a.harga,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp 
            from agg_rkm_d a        
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi=b.kode_lokasi    
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            // $sql3="select a.kode_role,a.kode_jab,a.no_urut,b.nama as nama_jab,c.nik,c.nama as nama_kar,nvl(convert(varchar,a.tgl_app,103),'-') as tanggal
            // from apv_flow a
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' 
            // order by a.no_urut";
            
            // $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-3 as nu
			// from agg_rkm_m a
            // inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			// inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			// union all
			// select 'Diverifikasi oleh' as ket,c.kode_jab,a.nik_ver as nik, c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,d.tanggal,103),'-') as tanggal,nvl(d.no_bukti,'-') as no_app,case d.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,-2 as nu
			// from agg_rkm_m a
            // inner join apv_karyawan c on a.nik_ver=c.nik and a.kode_lokasi=c.kode_lokasi
			// inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			// left join apv_ver_m d on a.no_bukti=d.no_juskeb and a.kode_lokasi=d.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			// union all
			// select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,a.tgl_app,103),'-') as tanggal,nvl(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu
            // from apv_flow a
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			// left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			// union all
            // select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,a.tgl_app,103),'-') as tanggal,nvl(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,a.no_urut as nu
            // from apv_flow a
			// inner join apv_juspo_m f on a.no_bukti=f.no_bukti and a.kode_lokasi=f.kode_lokasi
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			// left join (SELECT no_bukti,kode_lokasi,MAX(id) as maxid
            //             FROM apv_pesan
            //             GROUP BY no_bukti,kode_lokasi
            //             ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
			// left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            // where a.kode_lokasi='$kode_lokasi' and f.no_juskeb='$no_bukti'
			// order by nu
			
            // ";
            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
			from agg_rkm_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diverifikasi oleh' as ket,c.kode_jab,a.nik_ver as nik, c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,d.tanggal,103),'-') as tanggal,nvl(d.no_bukti,'-') as no_app,case d.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,-3 as nu, nvl(d.no_bukti,'X') as urut
			from agg_rkm_m a
            inner join apv_karyawan c on a.nik_ver=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join apv_ver_m d on a.no_bukti=d.no_juskeb and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,e.tanggal,103),'-') as tanggal,nvl(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, nvl(convert(varchar,e.id),'X') as urut
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
            select 'Diapprove oleh' as ket,c.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,a.tanggal,103),'-') as tanggal,nvl(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-1 as nu, nvl(convert(varchar,e.id),'X') as urut
            from apv_juspo_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
            inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and e.modul='PO' 
            where a.kode_lokasi='$kode_lokasi' and a.no_juskeb='$no_bukti'
			union all
            select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,nvl(convert(varchar,e.tanggal,103),'-') as tanggal,nvl(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,a.no_urut as nu, nvl(convert(varchar,e.id),'X') as urut
            from apv_flow a
			inner join apv_juspo_m f on a.no_bukti=f.no_bukti and a.kode_lokasi=f.kode_lokasi
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
			left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and e.modul <> 'PO' and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and f.no_juskeb='$no_bukti'
			order by urut,nu
            ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKota(Request $request)
    {
        $this->validate($request,[
            'kode_pp' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select kode_kota,nama from apv_kota where kode_lokasi='".$kode_lokasi."' and kode_pp='$request->kode_pp' ";
            
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

    public function getDAM(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            if(isset($request->kode_dam) && $request->kode_dam != ""){
                $filter = " and a.kode_dam='$request->kode_dam' ";
            }else{
                $filter = "";
            }

            $res = DB::connection($this->db)->select("select a.kode_dam,a.nama
            from agg_dam a
            where a.kode_lokasi='".$kode_lokasi."' $filter
            ");
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

    public function getRKM(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            if(isset($request->kode_rkm) && $request->kode_rkm != ""){
                $filter = " and a.kode_rkm='$request->kode_rkm' ";
            }else{
                $filter = "";
            }

            $res = DB::connection($this->db)->select("select a.kode_rkm,a.nama
            from agg_rkm a
            where a.kode_lokasi='".$kode_lokasi."' $filter
            ");
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
