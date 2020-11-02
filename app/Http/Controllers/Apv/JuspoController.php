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

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function generateKode2($tabel, $kolom_acuan, $prefix, $str_format, $prefix2, $tahun,$kode_lokasi){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '%$prefix2%' and substring(convert(varchar(10),tanggal,121),1,4) = '$tahun' and kode_lokasi='$kode_lokasi' ");
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

        $format = $this->reverseDate($request->tanggal,"-","-")."/".$request->kode_pp."/".$request->kode_kota."/";
        $format2 = "/".$request->kode_pp."/".$request->kode_kota."/";
        $tahun = substr($request->tanggal,0,4);
        $no_dokumen = $this->generateKode2("apv_juspo_m", "no_dokumen", $format, "00001", $format2,$tahun,$kode_lok_log);
        return $no_dokumen;
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

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_juskeb,a.no_dokumen,a.kode_pp,convert(varchar,a.waktu,103) as waktu,a.kegiatan,case a.progress when 'S' then 'FINISH' else isnull(b.nama_jab,'-') end as posisi,a.nilai,a.progress,p.nama as nama_pp
            from apv_juspo_m a
            inner join apv_pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1'
                    )b on a.no_bukti=b.no_bukti 
            where a.kode_lokasi='".$kode_lokasi."' and a.nik_buat='$nik_user' and a.progress not in ('R','A')
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
            // $sql = "case isnull(b.nilai,0) when 0 then a.nilai else isnull(b.nilai,0) end as nilai";
            $sql = "select a.no_bukti,a.no_dokumen,a.kode_pp,case isnull(b.nilai,0) when 0 then a.nilai else isnull(b.nilai,0) end as nilai,convert(varchar,a.waktu,103) as waktu,a.kegiatan,a.progress,case isnull(b.progress,'-')  when 'R' then 'REVISI' when 'A' then 'Approval Pusat 1' else '-' end as status,isnull(b.no_bukti,'-') as id,p.nama as nama_pp,a.pemakai as pic
            from apv_juskeb_m a
            inner join apv_pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi 
            left join apv_juspo_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            where (a.kode_lokasi='$kode_lokasi' and a.progress='S') and (isnull(b.no_bukti,'-') = '-' OR b.progress in ('R','A','Z'))
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
            'no_dokumen' => 'required|max:50',
            'no_aju' => 'required|max:20',
            'kode_pp' => 'required|max:10',
            'kode_kota' => 'required|max:20',
            'waktu' => 'required',
            'kegiatan' => 'required|max:300',
            'dasar' => 'required|max:500',
            'total_barang' => 'required',
            'barang'=> 'required|array|max:150',
            'barang_klp'=> 'required|array|max:20',
            'harga'=> 'required|array',
            'qty'=> 'required|array',
            'subtotal'=> 'required|array',
            'ppn'=> 'required|array',
            'grand_total'=> 'required|array',
            'status' => 'required',
            'keterangan' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            //cek udah pernah pengadaan belum
            $cek = DB::connection($this->db)->select("select no_bukti,no_dokumen from apv_juspo_m where no_juskeb='$request->no_aju' and kode_lokasi='$kode_lokasi' ");
            if(count($cek) > 0 ){
                $no_bukti = $cek[0]->no_bukti;
                $no_dokumen = $cek[0]->no_dokumen;

                $del = DB::connection($this->db)->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $del2 = DB::connection($this->db)->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $del3 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            }else{
                
                $no_bukti = $this->generateKode("apv_juspo_m", "no_bukti", "APP-", "0001");
                $format = $this->reverseDate($request->waktu,"-","-")."/".$request->kode_pp."/".$request->kode_kota."/";
                $format2 = "/".$request->kode_pp."/".$request->kode_kota."/";
                $tahun = substr($request->tanggal,0,4);
                $no_dokumen = $this->generateKode2("apv_juspo_m", "no_dokumen", $format, "00001", $format2,$tahun,$kode_lokasi);
            }

            $inshis = DB::connection($this->db)->insert("insert into apv_juspo_his (no_juspo,keterangan,status,kode_lokasi,nik_user,tgl_input) values ('$no_bukti','$request->keterangan','$request->status','$kode_lokasi','$nik_user',getdate())");

            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('$no_bukti','$kode_lokasi','$request->keterangan',getdate(),-1,'$request->status','PO') ");

            //cek status
            if($request->status == 2){
                $progress = "A";
            }else{
                //update juskeb m 
                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $request->no_aju)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', 0)
                ->update(['status' => '1','tgl_app'=>NULL]);
                $upd2 =  DB::connection($this->db)->table('apv_juskeb_m')
                ->where('no_bukti', $request->no_aju)    
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['progress' => 'V']);
                $progress = "Z";
            }


            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            $no_aju = $request->input('no_aju');

            if($request->hasfile('file'))
            {
                foreach($request->file('file') as $file)
                {                
                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('apv/'.$foto)){
                        Storage::disk('s3')->delete('apv/'.$foto);
                    }
                    Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama_file')[$i];
                    $i++;
                }
                
            }
                
            $ins = DB::connection($this->db)->insert('insert into apv_juspo_m (no_bukti,no_juskeb,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,tgl_input,kode_kota) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_aju'),$no_dokumen,$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tgl_aju'),$progress,$request->input('tanggal'),$request->kode_kota]);

            $barang = $request->input('barang');
            $barang_klp = $request->input('barang_klp');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');
            $ppn = $request->input('ppn');
            $grand_total = $request->input('grand_total');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert("insert into apv_juspo_d (kode_lokasi,no_bukti,barang,barang_klp,harga,jumlah,no_urut,nilai,ppn,grand_total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$barang_klp[$i],$harga[$i],$qty[$i],$i,$subtotal[$i],$ppn[$i],$grand_total[$i]));
                }
            }

            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_aju,$arr_nama[$i],$i,$arr_foto[$i]]); 
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JP' 
            order by b.no_urut,a.kode_role";

            $role = DB::connection($this->db)->select($sql);
            $role = json_decode(json_encode($role),true);
            $token_player = array();
            
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

                if($request->status != 2){
                    $prog = 0;
                }

                $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,nik) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,$role[$i]["nik"]]);
            }
            
            DB::connection($this->db)->commit();
            if(isset($request->email) && isset($request->nama_email)){

                $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Pengadaan kebutuhan $no_bukti berhasil dikirim, menunggu approval $app_nik");
                $msg_email = " Email: ".$mail['msg'];
            }else{
                $msg_email = "";
            }
            

            $token_players = array();
            $rst = DB::connection($this->db)->select("select a.nik_buat,b.token 
            from apv_juspo_m a
            inner join api_token_auth b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti='$no_bukti' ");
            $rst = json_decode(json_encode($rst),true);
            for($t=0;$t<count($rst);$t++){
                array_push($token_players,$rst[$t]["token"]);
            }

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
            $success['message'] = "Data Justifikasi Pengadaan berhasil disimpan. No Bukti:".$no_bukti.$msg_email;
            $success['no_aju'] = $no_bukti;
            $success['no_juskeb'] = $request->no_aju;
            $success['token_players'] = $token_players;
            $success['arr_nama'] = $arr_nama;
            $success['arr_foto'] = $arr_foto;
          
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Pengadaan gagal disimpan ".$e;
            $success['no_aju'] = '';
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
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_juskeb,a.no_dokumen,a.kode_pp,a.kode_kota,a.waktu,a.kegiatan,a.dasar,a.nilai,convert(varchar(10),a.tgl_input,121) as tgl_input, convert(varchar(10),a.tanggal,121) as tgl_juskeb,b.nama as nama_pp,c.nama as nama_klp,d.pemakai as pic
            from apv_juspo_m a 
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            inner join apv_juskeb_m d on a.no_juskeb=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res)> 0){
                $no_aju = $res[0]['no_juskeb'];
            }else{
                $no_aju = "-";
            }

            $sql2="select a.no_bukti,a.barang,a.barang_klp,a.harga,a.jumlah,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp 
            from apv_juspo_d a 
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi =b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti'  order by a.no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select a.no_bukti,a.nama,a.file_dok from apv_juskeb_dok a inner join apv_juspo_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and b.no_bukti='$no_bukti'  order by a.no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            // $sql4="
            
            // select e.id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut 
            // from apv_juspo_m a
            // inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            // inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            // inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            // where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi'
            // order by id,no_urut ";
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
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi'
			union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,a.nik_buat as nik,f.nama,-1 as no_urut,e.id as id2
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on a.nik_buat=f.nik and a.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi' and e.modul='PO'
			order by id2
	        ";
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

    public function getDetailJuskeb($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_dokumen,a.kode_pp,a.kode_kota,a.waktu,a.kegiatan,a.dasar,a.nilai,convert(varchar(10),a.tanggal,121) as tanggal,a.pemakai as pic
            from apv_juskeb_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti'  ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.barang,a.barang_klp,a.harga,a.jumlah,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp from apv_juskeb_d a left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi =b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti'  order by a.no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            
            $sql="select a.no_bukti
            from apv_juspo_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_juskeb='$no_bukti' ";
            
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            if(count($rs)> 0){
                $no_po = $rs[0]['no_bukti'];
            }else{
                $no_po = "-";
            }

            $sql4 = "select e.no_bukti as id,a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama,-3 as no_urut,-4 as id2 
            from apv_juskeb_m a
            inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi'
			union all
			select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from apv_juskeb_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi' 
            union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2 
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_po' and a.kode_lokasi='$kode_lokasi'
			union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,a.nik_buat as nik,f.nama,-1 as no_urut,e.id as id2
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on a.nik_buat=f.nik and a.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_po' and a.kode_lokasi='$kode_lokasi' and e.modul='PO'
			order by id2
	        ";
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
        $this->validate($request, [
            'tanggal' => 'required',
            'tgl_aju' => 'required',
            'no_dokumen' => 'required|max:50',
            'no_aju' => 'required|max:20',
            'kode_pp' => 'required|max:10',
            'kode_kota' => 'required|max:20',
            'waktu' => 'required',
            'kegiatan' => 'required|max:300',
            'dasar' => 'required|max:500',
            'total_barang' => 'required',
            'barang'=> 'required|array|max:150',
            'barang_klp'=> 'required|array|max:20',
            'harga'=> 'required|array',
            'qty'=> 'required|array',
            'subtotal'=> 'required|array',
            'ppn'=> 'required|array',
            'grand_total'=> 'required|array',
            'status' => 'required',
            'keterangan' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            $no_aju = $request->input('no_aju');

            if($request->hasfile('file'))
            {
                foreach($request->file('file') as $file)
                {                
                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('apv/'.$foto)){
                        Storage::disk('s3')->delete('apv/'.$foto);
                    }
                    Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama_file')[$i];
                    $i++;
                }
                
            }

            $del = DB::connection($this->db)->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            $inshis = DB::connection($this->db)->insert("insert into apv_juspo_his (no_juspo,keterangan,status,kode_lokasi,nik_user,tgl_input) values ('$no_bukti','$request->keterangan','$request->status','$kode_lokasi','$nik_user',getdate())");

            $ins = DB::connection($this->db)->insert("insert into apv_pesan (no_bukti,kode_lokasi,keterangan,tanggal,no_urut,status,modul) values ('$no_bukti','$kode_lokasi','$request->keterangan',getdate(),-1,'$request->status','PO') ");

            //cek status
            if($request->status == 2){
                $progress = "A";
            }else{
                //update juskeb m 
                $upd2 =  DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $request->no_aju)    
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_urut', 0)
                ->update(['status' => '1','tgl_app'=>NULL]);
                $upd2 =  DB::connection($this->db)->table('apv_juskeb_m')
                ->where('no_bukti', $request->no_aju)    
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['progress' => 'V']);
                $progress = "Z";
            }
            
            $ins = DB::connection($this->db)->insert('insert into apv_juspo_m (no_bukti,no_juskeb,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,tgl_input,kode_kota) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_aju'),$request->input('no_dokumen'),$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tgl_aju'),$progress,$request->input('tanggal'),$request->kode_kota]);

            $barang = $request->input('barang');
            $barang_klp = $request->input('barang_klp');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');
            $ppn = $request->input('ppn');
            $grand_total = $request->input('grand_total');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert("insert into apv_juspo_d (kode_lokasi,no_bukti,barang,barang_klp,harga,jumlah,no_urut,nilai,ppn,grand_total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$barang_klp[$i],$harga[$i],$qty[$i],$i,$subtotal[$i],$ppn[$i],$grand_total[$i]));
                }
            }

            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_aju,$arr_nama[$i],$i,$arr_foto[$i]]); 
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JP' 
            order by b.no_urut,a.kode_role";

            $role = DB::connection($this->db)->select($sql);
            $role = json_decode(json_encode($role),true);
            $token_player = array();
            
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

                if($request->status != 2){
                    $prog = 0;
                }
                $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,nik) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,$role[$i]["nik"]]);
            }
            
            DB::connection($this->db)->commit();
            if(isset($request->email) && isset($request->nama_email)){

                $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Pengadaan kebutuhan $no_bukti berhasil dikirim, menunggu approval $app_nik");
                $msg_email = " Email: ".$mail['msg'];
            }else{
                $msg_email = "";
            }

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
            
            $success['no_aju'] = $no_bukti;
            $success['no_juskeb'] = $request->no_aju;
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Pengadaan berhasil diubah. No Bukti:".$no_bukti.$msg_email;
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Pengadaan gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Kebutuhan berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Kebutuhan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getHistory($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $cek = "select no_juskeb from apv_juspo_m where no_bukti = '$no_bukti' ";
            $rs = DB::connection($this->db)->select($cek);
            $rs = json_decode(json_encode($rs),true);
            if(count($rs)> 0){
                $no_aju = $rs[0]['no_juskeb'];
            }else{
                $no_aju = "-";
            }

            // $sql="select b.id,a.no_bukti,b.keterangan,b.tanggal,c.nama
            // from apv_flow a
            // inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            // left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' 
            // ";
            			

            $sql = "select b.no_bukti,b.keterangan,b.tanggal,c.nama,-1 as id,case b.status when 'V' then 'APPROVE' when 'F' then 'RETURN' else '-' end as status,'green' as color
            from apv_juskeb_m a
            inner join apv_ver_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan d on b.nik_user=d.nik and b.kode_lokasi=d.kode_lokasi
            left join apv_jab c on d.kode_jab=c.kode_jab and d.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju'
            union all
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'green' as color
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_aju'
            union all 
            select a.no_bukti,a.keterangan,a.tanggal,c.nama,a.id,case a.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'blue' as color
            from apv_pesan a
            inner join apv_juspo_m d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            left join apv_karyawan c on d.nik_buat=c.nik and d.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and d.no_juskeb='$no_aju' and a.modul='PO'
            union all 
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'blue' as color
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            inner join apv_juspo_m d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and d.no_juskeb='$no_aju'
            order by id ";
            
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

    public function getPreview($no_bukti,$no_juskeb)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_juskeb,a.no_dokumen, convert(varchar(10),a.tanggal,121) as tanggal,a.kegiatan,a.waktu,a.dasar,a.nilai,a.kode_pp,b.nama as nama_pp,a.kode_kota,c.nama as nama_kota,d.pemakai as pic 
            from apv_juspo_m a
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            inner join apv_juskeb_m d on a.no_juskeb=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.barang,a.barang_klp,a.jumlah,a.harga,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp 
            from apv_juspo_d a
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi=b.kode_lokasi            
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            // $sql3="select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,-1 as nu,'-' as no_app,'-' as status
			// from apv_juspo_m a
            // inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			// inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			// union all
			// select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal,a.no_urut as nu,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status
            // from apv_flow a
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			// left join (select no_bukti,no_urut,max(id) as maxid
			// from apv_pesan 
			// group by no_bukti,no_urut) d on a.no_bukti=d.no_bukti and a.no_urut=d.no_urut
			// left join apv_pesan e on d.no_bukti=e.no_bukti and d.no_urut=e.no_urut and d.maxid=e.id
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            // order by nu";
            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_juskeb'
			union all
			select 'Diverifikasi oleh' as ket,c.kode_jab,a.nik_ver as nik, c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,d.tanggal,103),'-') as tanggal,isnull(d.no_bukti,'-') as no_app,case d.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,-3 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_ver=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join apv_ver_m d on a.no_bukti=d.no_juskeb and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_juskeb'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			left join (SELECT no_bukti,kode_lokasi,MAX(id) as maxid
                        FROM apv_pesan
                        GROUP BY no_bukti,kode_lokasi
                        ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
			left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_juskeb'
			union all
            select 'Diapprove oleh' as ket,c.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tanggal,103),'-') as tanggal,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-1 as nu
            from apv_juspo_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
            inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join (SELECT no_bukti,kode_lokasi,MAX(id) as maxid
                        FROM apv_pesan
						where modul = 'PO'
                        GROUP BY no_bukti,kode_lokasi
                        ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
			left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            where a.kode_lokasi='$kode_lokasi' and a.no_juskeb='$no_juskeb'
			union all
            select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,a.no_urut as nu
            from apv_flow a
			inner join apv_juspo_m f on a.no_bukti=f.no_bukti and a.kode_lokasi=f.kode_lokasi
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
			left join (SELECT no_bukti,no_urut,kode_lokasi,MAX(id) as maxid
                        FROM apv_pesan
						where modul <> 'PO'
                        GROUP BY no_bukti,no_urut,kode_lokasi
                        ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.no_urut=d.no_urut
			left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            where a.kode_lokasi='$kode_lokasi' and f.no_juskeb='$no_juskeb'
			order by nu
			
            ";
            $res3 = DB::connection($this->db)->select($sql3);
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
