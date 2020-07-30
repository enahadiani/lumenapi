<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class VerifikasiController extends Controller
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
                ->subject('Verifikasi Justifikasi Kebutuhan (SAI LUMEN)');
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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;

            $res = DB::connection($this->db)->select("select b.no_bukti,b.no_dokumen,b.kode_pp,b.waktu,b.kegiatan,b.dasar,b.nilai,b.kode_kota
            from apv_juskeb_m b 
            where b.kode_lokasi='$kode_lokasi' and b.progress in ('A','R') and b.nik_ver='$nik_user'
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

    public function getHistory(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $kode_pp = $request->kode_pp;
            $sql = "select a.no_bukti,a.no_juskeb,case a.status when 'V' then 'Verifikasi' else 'Return' end as status, a.keterangan, convert(varchar,a.tanggal,103) as tanggal 
            from apv_ver_m a
            inner join apv_juskeb_m b on a.no_juskeb=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
            where b.kode_lokasi='$kode_lokasi'  and b.kode_pp='$kode_pp'
            ";
            $res = DB::connection($this->db)->select($sql);
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
                $success['sql'] = $sql;
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
            'no_aju' => 'required',
            'kode_pp' => 'required',
            'status' => 'required',
            'keterangan' => 'required',
            'total_barang' => 'required',
            'file.*'=>'file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("apv_ver_m", "no_bukti", $kode_lokasi."-VER".substr($periode,2,4).".", "0001");

            $ins1 = DB::connection($this->db)->insert("insert into apv_ver_m (no_bukti,kode_lokasi,no_juskeb,status,keterangan,tanggal,nik_user) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$request->no_aju,$request->status,$request->keterangan,$request->tanggal,$nik_user]);

            $upd =  DB::connection($this->db)->table('apv_juskeb_m')
            ->where('no_bukti', $request->input('no_aju'))    
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['progress' => $request->input('status'),'nilai'=>$request->input('total_barang'),'nik_ver'=>$nik_user]);

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            $no_aju = $request->input('no_aju');

            if($request->hasfile('file'))
            {
                foreach($request->file('file') as $file)
                {                
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('apv/'.$foto)){
                        Storage::disk('s3')->delete('apv/'.$foto);
                    }
                    Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama_file')[$i];
                    $i++;
                }
                
                $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju'  order by no_urut";
                $res3 = DB::connection($this->db)->select($sql3);
                $res3 = json_decode(json_encode($res3),true);
                
                if(count($res3) > 0){
                    for($i=0;$i<count($res3);$i++){
                        
                        Storage::disk('s3')->delete('apv/'.$res3[$i]['file_dok']);
                    }
                }
            }

            $barang = $request->input('barang');
            $barang_klp = $request->input('barang_klp');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');
            $ppn = $request->input('ppn');
            $grand_total = $request->input('grand_total');

            if(count($barang) > 0){
                $del2 = DB::connection($this->db)->table('apv_juskeb_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_aju)->delete();
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_d (kode_lokasi,no_bukti,barang_klp,barang,harga,jumlah,no_urut,nilai,ppn,grand_total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_aju,$barang_klp[$i],$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i],$ppn[$i],$grand_total[$i]));
                }
            }

            if(count($arr_nama) > 0){
                $del3 = DB::connection($this->db)->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_aju)->delete();
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_aju,$arr_nama[$i],$i,$arr_foto[$i]]); 
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK' and a.kode_pp='$request->kode_pp'
            order by b.no_urut";

            $role = DB::connection($this->db)->select($sql);
            $role = json_decode(json_encode($role),true);
            $token_player = array();


            $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_aju)->delete();

            for($i=0;$i<count($role);$i++){
                
                if($i == 0){
                    $prog = 1;
                    $rst = DB::connection($this->db)->select("select token from api_token_auth where nik='".$role[$i]["nik"]."' ");
                    $rst = json_decode(json_encode($rst),true);
                    for($t=0;$t<count($rst);$t++){
                        array_push($token_player,$rst[$t]["token"]);
                    }
                    $no_telp = $role[$i]["no_telp"];
                    $app_nik=$role[$i]["nik"];
                }else{
                    $prog = 0;
                }
                $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,sts_ver,nik) values (?, ?, ?, ?, ?, ?, ?, ?) ",[$no_aju,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,1,$role[$i]['nik']]);
            }

            if($request->status == "V"){
                
                $success['status'] = true;
                $success['verifikasi'] = "Approve";
                $success['token_players'] = $token_player;
                $success['no_aju'] = $no_aju;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Verifikasi Justifikasi Kebutuhan berhasil disimpan. No Bukti:".$no_bukti;
                
                
            }else{

                $token_player = array();
                $rst = DB::connection($this->db)->select("select a.nik_buat,b.token 
                from apv_juskeb_m a
                inner join api_token_auth b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti='$request->no_aju' ");
                $rst = json_decode(json_encode($rst),true);
                for($t=0;$t<count($rst);$t++){
                    array_push($token_player,$rst[$t]["token"]);
                }
                $success['token_players'] = $token_player;
                $success['status'] = true;
                $success['verifikasi'] = "Return";
                $success['no_aju'] = $request->no_aju;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Verifikasi Justifikasi Kebutuhan berhasil disimpan. No Bukti:".$no_bukti;
                
            }
            
            DB::connection($this->db)->commit();

            $rsi = DB::connection($this->db)->select("select a.nik,b.nama,b.id_device
            from apv_flow a
            inner join apv_karyawan b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti='$no_bukti' and a.status='1' and a.sts_ver='1' ");
            if(count($rsi) > 0){
                $success['id_device_app'] = $rsi[0]->id_device; 
                $success['nik_device_app'] = $rsi[0]->nik; 
            }else{
                $success['id_device_app'] = '-'; 
                $success['nik_device_app'] = '-'; 
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Verifikasi Justifikasi Kebutuhan gagal disimpan ".$e;
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

            $sql="select b.no_bukti,b.no_dokumen,b.kode_pp,b.waktu,b.kegiatan,b.dasar,b.nilai,b.kode_kota
            from apv_juskeb_m b 
            where b.kode_lokasi='$kode_lokasi' and b.no_bukti='$no_aju' and b.progress in ('A','R') ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang_klp,barang,harga,jumlah,nilai,ppn,grand_total from apv_juskeb_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju'  order by no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_aju'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4="select a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama 
            from apv_juskeb_m a
            inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi'
			union all
			select a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama 
            from apv_juskeb_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_aju' and a.kode_lokasi='$kode_lokasi' ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['data_histori'] = $res4;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
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

            $res = DB::connection($this->db)->select("select status, nama from apv_status where kode_lokasi='$kode_lokasi' and status in ('V','F') 
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

    public function getPreview($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_juskeb,a.tanggal,b.kode_pp,c.nama as nama_pp,b.kegiatan,b.nilai,a.nik_user,convert(varchar,a.tanggal,105) as tgl
            from apv_ver_m a
            inner join apv_juskeb_m b on a.no_juskeb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join apv_pp c on b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi' ";
            
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
