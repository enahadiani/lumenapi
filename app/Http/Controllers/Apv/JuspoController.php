<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class JuspoController extends Controller
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

            $res = DB::connection('sqlsrv2')->select("select a.no_bukti,a.no_juskeb,a.no_dokumen,a.kode_pp,convert(varchar,a.waktu,103) as waktu,a.kegiatan,case a.progress when 'S' then 'FINISH' else isnull(b.nama_jab,'-') end as posisi,a.nilai,a.progress
            from apv_juspo_m a
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1'
                    )b on a.no_bukti=b.no_bukti
            where a.kode_lokasi='".$kode_lokasi."'  and a.nik_buat='$nik_user'
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

            $res = DB::connection('sqlsrv2')->select("select a.no_bukti,a.no_dokumen,a.kode_pp,convert(varchar,a.waktu,103) as waktu,a.kegiatan,a.nilai,a.progress
            from apv_juskeb_m a 
            left join apv_juspo_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.progress='S'  and isnull(b.no_bukti,'-') = '-'
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
            'tgl_aju' => 'required',
            'no_dokumen' => 'required',
            'no_aju' => 'required',
            'kode_pp' => 'required',
            'waktu' => 'required',
            'kegiatan' => 'required',
            'dasar' => 'required',
            'total_barang' => 'required',
            'barang.*'=> 'required',
            'harga.*'=> 'required',
            'qty.*'=> 'required',
            'subtotal.*'=> 'required',
            'nama_file.*'=>'required',
            'file.*'=>'file|max:3072'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = generateKode("apv_juspo_m", "no_bukti", "APP-", "0001");
            
            $ins = DB::connection('sqlsrv2')->insert('insert into apv_juspo_m (no_bukti,no_juskeb,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_aju'),$request->input('no_dokumen'),$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tgl_aju'),'A',$request->input('tanggal')]);

            $barang = $request->input('barang');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juspo_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai) values (?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i]));
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JP'
            order by b.no_urut";

            $role = DB::connection('sqlsrv2')->select($sql);
            $role = json_decode(json_encode($role),true);
            $token_player = array();
            
            for($i=0;$i<count($role);$i++){
                
                if($i == 0){
                    $prog = 1;
                    $rst = DB::connection('sqlsrv2')->select("select token from api_token_auth where nik='".$role[$i]["nik"]."' ");
                    $rst = json_decode(json_encode($rst),true);
                    for($t=0;$t<count($rst);$t++){
                        array_push($token_player,$rst[$t]["token"]);
                    }
                    $no_telp = $role[$i]["no_telp"];
                    $app_nik=$role[$i]["nik"];
                }else{
                    $prog = 0;
                }
                $ins4[$i] = DB::connection('sqlsrv2')->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog]);
            }
            
            DB::connection('sqlsrv2')->commit();
            if(isset($request->email) && isset($request->nama_email)){

                $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Pengadaan kebutuhan $no_bukti berhasil dikirim, menunggu approval $app_nik");
                $msg_email = " Email: ".$mail['msg'];
            }else{
                $msg_email = "";
            }
            
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Pengadaan berhasil disimpan. No Bukti:".$no_bukti.$msg_email;
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Pengadaan gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }		
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_bukti,no_juskeb,no_dokumen,kode_pp,waktu,kegiatan,dasar,nilai,convert(varchar(10),tgl_input,121) as tgl_input, convert(varchar(10),tanggal,121) as tgl_juskeb from apv_juspo_m where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang,harga,jumlah,nilai from apv_juspo_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";					
            $res2 = DB::connection('sqlsrv2')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select a.no_bukti,a.nama,a.file_dok from apv_juskeb_dok a inner join apv_juspo_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and b.no_bukti='$no_bukti'  order by a.no_urut";
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

    public function getDetailJuskeb($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nilai,convert(varchar(10),tanggal,121) as tanggal from apv_juskeb_m where kode_lokasi='".$kode_lokasi."' and no_bukti='$id'  ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang,harga,jumlah,nilai from apv_juskeb_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$id'  order by no_urut";					
            $res2 = DB::connection('sqlsrv2')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$id'  order by no_urut";
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
        $this->validate($request, [
            'tanggal' => 'required',
            'tgl_aju' => 'required',
            'no_dokumen' => 'required',
            'no_aju' => 'required',
            'kode_pp' => 'required',
            'waktu' => 'required',
            'kegiatan' => 'required',
            'dasar' => 'required',
            'total_barang' => 'required',
            'barang.*'=> 'required',
            'harga.*'=> 'required',
            'qty.*'=> 'required',
            'subtotal.*'=> 'required',
            'nama_file.*'=>'required',
            'file.*'=>'file|max:3072'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection('sqlsrv2')->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection('sqlsrv2')->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection('sqlsrv2')->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            
            $ins = DB::connection('sqlsrv2')->insert('insert into apv_juspo_m (no_bukti,no_juskeb,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_aju'),$request->input('no_dokumen'),$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tgl_aju'),'A',$request->input('tanggal')]);

            $barang = $request->input('barang');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juspo_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai) values (?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i]));
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JP'
            order by b.no_urut";

            $role = DB::connection('sqlsrv2')->select($sql);
            $role = json_decode(json_encode($role),true);
            $token_player = array();
            
            for($i=0;$i<count($role);$i++){
                
                if($i == 0){
                    $prog = 1;
                    $rst = DB::connection('sqlsrv2')->select("select token from api_token_auth where nik='".$role[$i]["nik"]."' ");
                    $rst = json_decode(json_encode($rst),true);
                    for($t=0;$t<count($rst);$t++){
                        array_push($token_player,$rst[$t]["token"]);
                    }
                    $no_telp = $role[$i]["no_telp"];
                    $app_nik=$role[$i]["nik"];
                }else{
                    $prog = 0;
                }
                $ins4[$i] = DB::connection('sqlsrv2')->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog]);
            }
            
            DB::connection('sqlsrv2')->commit();
            if(isset($request->email) && isset($request->nama_email)){

                $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Pengadaan kebutuhan $no_bukti berhasil dikirim, menunggu approval $app_nik");
                $msg_email = " Email: ".$mail['msg'];
            }else{
                $msg_email = "";
            }
            
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Pengadaan berhasil disimpan. No Bukti:".$no_bukti.$msg_email;
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Pengadaan gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }		
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($no_bukti)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection('sqlsrv2')->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection('sqlsrv2')->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Kebutuhan berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Kebutuhan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getHistory($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,b.keterangan,b.tanggal,c.nama
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
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

    public function getPreview($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_juskeb,a.no_dokumen, convert(varchar(10),a.tanggal,121) as tanggal,a.kegiatan,a.waktu,a.dasar,a.nilai,a.kode_pp,b.nama as nama_pp 
            from apv_juspo_m a
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.barang,a.jumlah,a.harga,a.nilai 
            from apv_juspo_d a            
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'";					
            $res2 = DB::connection('sqlsrv2')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select a.kode_role,a.kode_jab,a.no_urut,b.nama as nama_jab,c.nik,c.nama as nama_kar,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'";
            $res3 = DB::connection('sqlsrv2')->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_app'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_app'] = [];
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
