<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class JuskebController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'silo';
    public $db = 'dbsilo';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function isUnik($isi,$kode_lokasi,$kode_pp){
        
        $auth = DB::connection($this->db)->select("select no_dokumen from apv_juskeb_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function isUnik2($isi,$kode_lokasi,$kode_pp,$no_bukti){
        
        $auth = DB::connection($this->db)->select("select no_dokumen from apv_juskeb_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."' and no_bukti <> '$no_bukti' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

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

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
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
        $no_dokumen = $this->generateKode2("apv_juskeb_m", "no_dokumen", $format, "00001", $format2,$tahun,$kode_lok_log);
        return $no_dokumen;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_dokumen,a.kode_pp,a.waktu,a.kegiatan,
            a.nilai,
            case when a.progress = 'A' then 'Verifikasi' 
            when a.progress='F' then 'Return Verifikasi' 
            when a.progress='R' then 'Return Approval' 
            when a.progress not in ('R','S','F') then isnull(x.nama_jab,'-')
            when a.progress = 'S' and isnull(c.progress,'-') ='-' then 'Finish Kebutuhan' 
            when a.progress = 'S' and c.progress ='S' then 'Finish Pengadaan' 
            when a.progress = 'S' and c.progress ='R' then 'Return Approval' 
            when a.progress = 'S' and c.progress not in ('R','S') then isnull(y.nama_jab,'-')
            end as posisi,a.progress,isnull(z.nilai,0) as nilai_finish
            from apv_juskeb_m a
            left join (SELECT no_juskeb,kode_lokasi,tanggal,MAX(no_bukti) as MaxVer
                        FROM apv_ver_m
                        GROUP BY no_juskeb,kode_lokasi,tanggal
                        ) b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            left join apv_juspo_m c on a.no_bukti=c.no_juskeb and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )x on a.no_bukti=x.no_bukti
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )y on c.no_bukti=y.no_bukti
            left join apv_juspo_m z on a.no_bukti=z.no_juskeb and a.kode_lokasi=z.kode_lokasi
			where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik_user'  and isnull(c.progress,'-')  <> 'S'
                                       
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


    public function getJuskebFinish()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.no_dokumen,a.kode_pp,a.waktu,a.kegiatan,
            a.nilai,
            case when a.progress = 'A' then 'Verifikasi' 
            when a.progress='F' then 'Return Verifikasi' 
            when a.progress='R' then 'Return Approval' 
            when a.progress not in ('R','S','F') then isnull(x.nama_jab,'-')
            when a.progress = 'S' and isnull(c.progress,'-') ='-' then 'Finish Kebutuhan' 
            when a.progress = 'S' and c.progress ='S' then 'Finish Pengadaan' 
            when a.progress = 'S' and c.progress ='R' then 'Return Approval' 
            when a.progress = 'S' and c.progress not in ('R','S') then isnull(y.nama_jab,'-')
            end as posisi,a.progress,isnull(z.nilai,0) as nilai_finish
            from apv_juskeb_m a
            left join (SELECT no_juskeb,kode_lokasi,tanggal,MAX(no_bukti) as MaxVer
                        FROM apv_ver_m
                        GROUP BY no_juskeb,kode_lokasi,tanggal
                        ) b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            left join apv_juspo_m c on a.no_bukti=c.no_juskeb and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )x on a.no_bukti=x.no_bukti
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )y on c.no_bukti=y.no_bukti
            left join apv_juspo_m z on a.no_bukti=z.no_juskeb and a.kode_lokasi=z.kode_lokasi
			where a.kode_lokasi='$kode_lokasi' and a.nik_buat='$nik_user' and c.progress  = 'S'
                                       
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
            'no_dokumen' => 'required|max:50',
            'kode_pp' => 'required|max:10',
            'kode_kota' => 'required|max:20',
            'nik_ver' => 'required|max:20',
            'waktu' => 'required',
            'kegiatan' => 'required|max:300',
            'dasar' => 'required|max:500',
            'pemakai' => 'required',
            'total_barang' => 'required',
            'barang'=> 'required|array|max:150',
            'barang_klp'=> 'required|array|max:30',
            'harga'=> 'required|array',
            'qty'=> 'required|array',
            'subtotal'=> 'required|array',
            'ppn'=> 'required|array',
            'grand_total'=> 'required|array',
            'nama_dok'=>'array|max:100',
            'file.*'=>'file|max:10240'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

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
                        if(Storage::disk('s3')->exists('apv/'.$foto)){
                            Storage::disk('s3')->delete('apv/'.$foto);
                        }
                        Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                        $arr_foto[] = $foto;
                        $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                        $i++;
                    }
                }
    
                $no_bukti = $this->generateKode("apv_juskeb_m", "no_bukti", "APV-", "0001");
                $format = $this->reverseDate($request->tanggal,"-","-")."/".$request->kode_pp."/".$request->kode_kota."/";
                $format2 = "/".$request->kode_pp."/".$request->kode_kota."/";
                $tahun = substr($request->tanggal,0,4);
                $no_dokumen = $this->generateKode2("apv_juskeb_m", "no_dokumen", $format, "00001", $format2,$tahun,$kode_lokasi);
                
                if(isset($request->kode_divisi)){
                    $kode_divisi = $request->kode_divisi;
                }else{
                    
                    $kode_divisi = '-';
                }

                $ins = DB::connection($this->db)->insert('insert into apv_juskeb_m (no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,kode_kota,kode_divisi,nik_ver,pemakai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$no_dokumen,$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tanggal'),'A',$request->kode_kota,$kode_divisi,$request->nik_ver,$request->pemakai]);
    
                $barang = $request->input('barang');
                $harga = $request->input('harga');
                $qty = $request->input('qty');
                $subtotal = $request->input('subtotal');
    
                if(count($barang) > 0){
                    for($i=0; $i<count($barang);$i++){
                        $ins2[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai,barang_klp,ppn,grand_total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i],$request->barang_klp[$i],$request->ppn[$i],$request->grand_total[$i]));
                    }
                }
    
                if(count($arr_nama) > 0){
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins3[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i]]); 
                    }
                }

                if($request->kode_pp == "7"){

                    $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
                    from apv_role a
                    inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                    inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK' and a.kode_pp='$request->kode_pp' and c.id_kota = '$request->kode_kota' and c.kode_divisi = '$kode_divisi'
                    order by b.no_urut";
                }else{

                    $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
                    from apv_role a
                    inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                    inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK' and a.kode_pp='$request->kode_pp'
                    order by b.no_urut";
                }    
    
                $role = DB::connection($this->db)->select($sql);
                $role = json_decode(json_encode($role),true);
                $token_player = array();
                
                for($i=0;$i<count($role);$i++){
                    
                    if($i == 0){
                        $prog = 1;
                        // $rst = DB::connection($this->db)->select("select token from api_token_auth where nik='".$role[$i]["nik"]."' ");
                        // $rst = json_decode(json_encode($rst),true);
                        // for($t=0;$t<count($rst);$t++){
                        //     array_push($token_player,$rst[$t]["token"]);
                        // }
                        $no_telp = $role[$i]["no_telp"];
                        $app_nik = $role[$i]["nik"];
                    }else{
                        $prog = 0;
                    }
                    $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,sts_ver,nik) values (?, ?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,0,$role[$i]["nik"]]);
                }
                
                DB::connection($this->db)->commit();
                if(isset($request->email) && isset($request->nama_email)){
    
                    $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Justifikasi kebutuhan $no_bukti berhasil dikirim, menunggu verifikasi");
                    $msg_email = " Email: ".$mail['msg'];
                }else{
                    $msg_email = "";
                }
    
                $token_players = array();
                // $rst = DB::connection($this->db)->select("select a.nik_buat,b.token 
                // from apv_juskeb_m a
                // inner join api_token_auth b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
                // where a.no_bukti='$no_bukti' ");
                // $rst = json_decode(json_encode($rst),true);
                // for($t=0;$t<count($rst);$t++){
                //     array_push($token_players,$rst[$t]["token"]);
                // }

                $success['nik_ver'] = $request->nik_ver;
                $success['status'] = true;
                $success['message'] = "Data Justifikasi Kebutuhan berhasil disimpan. No Bukti:".$no_bukti.$msg_email;
                $success['no_aju'] = $no_bukti;
                $success['token_players'] = $token_players;
              
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                $success['no_aju'] = '-';
                $success['token_players'] = [];
                $success['nik_ver'] = '-';
            }

            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Kebutuhan gagal disimpan ".$e;
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

            $cek = "select no_bukti from apv_juspo_m where no_juskeb = '$no_bukti' ";
            $rs = DB::connection($this->db)->select($cek);
            $rs = json_decode(json_encode($rs),true);
            if(count($rs)> 0){
                $no_juspo = $rs[0]['no_bukti'];
            }else{
                $no_juspo = "-";
            }
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_bukti,no_dokumen,kode_pp,kode_kota,kode_divisi,waktu,kegiatan,dasar,nilai,convert(varchar(10),tanggal,121) as tanggal,nik_ver from apv_juskeb_m where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang_klp,barang,harga,jumlah,nilai,ppn,grand_total from apv_juskeb_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            // $sql4="select a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama 
            // from apv_juskeb_m a
            // inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            // inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
            // where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi'
			// union all
			// select a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama 
            // from apv_juskeb_m a
            // inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            // inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            // inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            // where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi' 
            // union all
            // select a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama 
            // from apv_juspo_m a
            // inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            // inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            // inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            // where a.no_juskeb='$no_bukti' and a.kode_lokasi='$kode_lokasi' ";
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
            where a.no_bukti='$no_juspo' and a.kode_lokasi='$kode_lokasi'
			union all
select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,a.nik_buat as nik,f.nama,-1 as no_urut,e.id as id2
            from apv_juspo_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on a.nik_buat=f.nik and a.kode_lokasi=f.kode_lokasi
            where a.no_bukti='$no_juspo' and a.kode_lokasi='$kode_lokasi' and e.modul='PO'
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
            'no_dokumen' => 'required|max:50',
            'kode_pp' => 'required|max:10',
            'kode_kota' => 'required|max:20',
            'nik_ver' => 'required|max:20',
            'waktu' => 'required',
            'kegiatan' => 'required|max:300',
            'dasar' => 'required|max:500',
            'total_barang' => 'required',
            'barang'=> 'required|array|max:150',
            'barang_klp'=> 'required|array|max:30',
            'harga'=> 'required|array',
            'qty'=> 'required|array',
            'subtotal'=> 'required|array',
            'ppn'=> 'required|array',
            'grand_total'=> 'required|array',
            'nama_dok'=>'array|max:100',
            'file.*'=>'file|max:10240'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if($this->isUnik($request->no_dokumen,$kode_lokasi,$request->kode_pp,$no_bukti)){

                $arr_foto = array();
                $arr_nama = array();
                $arr_foto2 = array();
                $arr_nama2 = array();
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
                                    Storage::disk('s3')->delete('apv/'.$request->nama_file_seb[$i]);
                                }
                                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $foto = $nama_foto;
                                if(Storage::disk('s3')->exists('apv/'.$foto)){
                                    Storage::disk('s3')->delete('apv/'.$foto);
                                }
                                Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                                $arr_foto[] = $foto;
                            }else{
                                $arr_foto[] = $request->nama_file_seb[$i];
                            }     
                            $arr_nama[] = $request->input('nama_file')[$i];
                            $arr_nama2[] = count($request->nama_file).'|'.$i.'|'.isset($request->file('file')[$i]);
                        }
    
                        $del3 = DB::connection($this->db)->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                    }
                }

                // if($request->hasfile('file'))
                // {
                //     foreach($request->file('file') as $file)
                //     {                
                //         $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                //         $foto = $nama_foto;
                //         if(Storage::disk('s3')->exists('apv/'.$foto)){
                //             Storage::disk('s3')->delete('apv/'.$foto);
                //         }
                //         Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                //         $arr_foto[] = $foto;
                //         $arr_nama[] = $request->input('nama_file')[$i];
                //         $i++;
                //     }

                //     $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
                //     $res3 = DB::connection($this->db)->select($sql3);
                //     $res3 = json_decode(json_encode($res3),true);

                //     if(count($res3) > 0){
                //         for($i=0;$i<count($res3);$i++){

                //             Storage::disk('s3')->delete('apv/'.$res3[$i]['file_dok']);
                //         }
                //     }

                //     $del3 = DB::connection($this->db)->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                // }

                $del = DB::connection($this->db)->table('apv_juskeb_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $del2 = DB::connection($this->db)->table('apv_juskeb_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                
                if(isset($request->kode_divisi)){
                    $kode_divisi = $request->kode_divisi;
                }else{
                    
                    $kode_divisi = '-';
                }

                $ins = DB::connection($this->db)->insert('insert into apv_juskeb_m (no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress,kode_kota,kode_divisi,nik_ver) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->no_dokumen,$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tanggal'),'A',$request->kode_kota,$kode_divisi,$request->nik_ver]);

                $barang = $request->input('barang');
                $harga = $request->input('harga');
                $qty = $request->input('qty');
                $subtotal = $request->input('subtotal');

                if(count($barang) > 0){
                    for($i=0; $i<count($barang);$i++){
                        $ins2[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai,barang_klp,ppn,grand_total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i],$request->barang_klp[$i],$request->ppn[$i],$request->grand_total[$i]));
                    }
                }

                if(count($arr_nama) > 0){
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins3[$i] = DB::connection($this->db)->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i]]); 
                    }
                }

                if($request->kode_pp == "7"){

                    $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
                    from apv_role a
                    inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                    inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK' and a.kode_pp='$request->kode_pp' and c.id_kota = '$request->kode_kota' and c.kode_divisi='$kode_divisi'
                    order by b.no_urut";
                }else{

                    $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
                    from apv_role a
                    inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
                    inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK' and a.kode_pp='$request->kode_pp'
                    order by b.no_urut";
                }

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
                    $ins4[$i] = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,sts_ver,nik) values (?, ?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,0,$role[$i]["nik"]]);
                }
                
                DB::connection($this->db)->commit();
                if(isset($request->email) && isset($request->nama_email)){

                    $mail = $this->sendMail($request->email,$request->nama_email,"Pengajuan Justifikasi kebutuhan $no_bukti berhasil dikirim, menunggu verifikasi");
                    $msg_email = " Email: ".$mail['msg'];
                }else{
                    $msg_email = "";
                }
                
                $token_players = array();
                $rst = DB::connection($this->db)->select("select a.nik_buat,b.token 
                from apv_juskeb_m a
                inner join api_token_auth b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti='$no_bukti' ");
                $rst = json_decode(json_encode($rst),true);
                for($t=0;$t<count($rst);$t++){
                    array_push($token_players,$rst[$t]["token"]);
                }
                
                $success['status'] = true;
                $success['message'] = "Data Justifikasi Kebutuhan berhasil diubah. No Bukti:".$no_bukti.$msg_email;
                $success['no_aju'] = $no_bukti;
                $success['token_players'] = $token_players;
                $success['nik_ver'] = $request->nik_ver;
                $success['file'] = $request->file;
                $success['nama_file'] = $request->nama_file;
                $success['nama_file_seb'] = $request->nama_file_seb;
                $success['arr_nama'] = $arr_nama;
                $success['arr_foto'] = $arr_foto;
                $success['arr_nama2'] = $arr_nama2;

            // }else{
            //     $success['status'] = false;
            //     $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
            //     $success['no_aju'] = '-';
            //     $success['token_players'] = [];
            // }
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Justifikasi Kebutuhan gagal diubah ".$e;
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
            
            $del = DB::connection($this->db)->table('apv_juskeb_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('apv_juskeb_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){

                    Storage::disk('s3')->delete('apv/'.$res3[$i]['file_dok']);
                }
            }

            $del3 = DB::connection($this->db)->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del4 = DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del5 = DB::connection($this->db)->table('apv_pesan')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            
            $del11 = DB::connection($this->db)->table('apv_ver_m')->where('kode_lokasi', $kode_lokasi)->where('no_juskeb', $no_bukti)->delete();

            $sql6="select no_bukti from apv_juspo_m where kode_lokasi='".$kode_lokasi."' and no_juskeb='$no_bukti' ";
            $res6 = DB::connection($this->db)->select($sql6);
            if(count($res6) > 0){
                $no_juspo = $res6[0]->no_bukti;
                $del6 =  DB::connection($this->db)->table('apv_juspo_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_juspo)->delete();
                $del7 =  DB::connection($this->db)->table('apv_juspo_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_juspo)->delete();
                $del8 =  DB::connection($this->db)->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_juspo)->delete();
                $del9 =  DB::connection($this->db)->table('apv_pesan')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_juspo)->delete();
                $del10 =  DB::connection($this->db)->table('apv_juspo_his')->where('kode_lokasi', $kode_lokasi)->where('no_juspo', $no_juspo)->delete();
            }

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

            $sql = "select b.no_bukti,b.keterangan,b.tanggal,c.nama,-1 as id,case b.status when 'V' then 'APPROVE' when 'F' then 'RETURN' else '-' end as status,'green' as color
            from apv_juskeb_m a
            inner join apv_ver_m b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan d on b.nik_user=d.nik and b.kode_lokasi=d.kode_lokasi
            left join apv_jab c on d.kode_jab=c.kode_jab and d.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            union all
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'green' as color
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            union all 
            select a.no_bukti,a.keterangan,a.tanggal,c.nama,a.id,case a.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'blue' as color
            from apv_pesan a
            inner join apv_juspo_m d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            left join apv_karyawan c on d.nik_buat=c.nik and d.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and d.no_juskeb='$no_bukti' and a.modul='PO'
            union all 
            select a.no_bukti,b.keterangan,b.tanggal,c.nama,b.id,case b.status when '2' then 'APPROVE' when '3' then 'RETURN' else '-' end as status,'blue' as color
            from apv_flow a
            inner join apv_pesan b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.no_urut=b.no_urut
            inner join apv_juspo_m d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and d.no_juskeb='$no_bukti'
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

    public function getPreview($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_dokumen, convert(varchar(10),a.tanggal,121) as tanggal,a.kegiatan,a.waktu,a.dasar,a.nilai,a.kode_pp,b.nama as nama_pp,a.kode_kota,c.nama as nama_kota 
            from apv_juskeb_m a
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.barang,a.barang_klp,a.jumlah,a.harga,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp 
            from apv_juskeb_d a        
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi=b.kode_lokasi    
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            // $sql3="select a.kode_role,a.kode_jab,a.no_urut,b.nama as nama_jab,c.nik,c.nama as nama_kar,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal
            // from apv_flow a
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' 
            // order by a.no_urut";
            
            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diverifikasi oleh' as ket,c.kode_jab,a.nik_ver as nik, c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,d.tanggal,103),'-') as tanggal,isnull(d.no_bukti,'-') as no_app,case d.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,-3 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_ver=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join apv_ver_m d on a.no_bukti=d.no_juskeb and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
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
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
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
            where a.kode_lokasi='$kode_lokasi' and a.no_juskeb='$no_bukti'
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
            where a.kode_lokasi='$kode_lokasi' and f.no_juskeb='$no_bukti'
			order by nu
			
            ";
            // $backup = "select 'Diapprove oleh' as ket,c.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-1 as nu
            // from apv_juspo_m a 
            // inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
            // inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			// left join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and e.modul = 'PO'
            // where a.kode_lokasi='$kode_lokasi' and a.no_juskeb='APV-0023'";
            $res3 = DB::connection($this->db)->select($sql3);
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

    public function getPreview2($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.no_dokumen, convert(varchar(10),a.tanggal,121) as tanggal,a.kegiatan,a.waktu,a.dasar,a.nilai,a.kode_pp,b.nama as nama_pp,a.kode_kota,c.nama as nama_kota 
            from apv_juskeb_m a
            left join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.no_urut,a.barang,a.barang_klp,a.jumlah,a.harga,a.nilai,a.ppn,a.grand_total,b.nama as nama_klp 
            from apv_juskeb_d a        
            left join apv_klp_barang b on a.barang_klp=b.kode_barang and a.kode_lokasi=b.kode_lokasi    
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            // $sql3="select a.kode_role,a.kode_jab,a.no_urut,b.nama as nama_jab,c.nik,c.nama as nama_kar,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal
            // from apv_flow a
            // inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            // inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' 
            // order by a.no_urut";
            
            $sql3 = "select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-3 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diverifikasi oleh' as ket,c.kode_jab,a.nik_ver as nik, c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,d.tanggal,103),'-') as tanggal,isnull(d.no_bukti,'-') as no_app,case d.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,-2 as nu
			from apv_juskeb_m a
            inner join apv_karyawan c on a.nik_ver=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
			left join apv_ver_m d on a.no_bukti=d.no_juskeb and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-1 as nu
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi and a.nik=c.nik
			left join (SELECT no_bukti,kode_lokasi,MAX(id) as maxid
                        FROM apv_pesan
                        GROUP BY no_bukti,kode_lokasi
                        ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
			left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
            select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,a.tgl_app,103),'-') as tanggal,isnull(convert(varchar,d.maxid),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,a.no_urut as nu
            from apv_flow a
			inner join apv_juspo_m f on a.no_bukti=f.no_bukti and a.kode_lokasi=f.kode_lokasi
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
			left join (SELECT no_bukti,kode_lokasi,MAX(id) as maxid
                        FROM apv_pesan
                        GROUP BY no_bukti,kode_lokasi
                        ) d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
			left join apv_pesan e on d.no_bukti=e.no_bukti and d.kode_lokasi=e.kode_lokasi and d.maxid=e.id 
            where a.kode_lokasi='$kode_lokasi' and f.no_juskeb='$no_bukti'
			order by nu
			
            ";
            $res3 = DB::connection($this->db)->select($sql3);
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

    public function getDivisi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $sql = "select kode_divisi from apv_karyawan where nik='$nik_user' ";
            $cek = DB::connection($this->db)->select($sql);
            if(count($cek) > 0){
                $kode_divisi = $cek[0]->kode_divisi;
            }else{
                $kode_divisi = "-";
            }

            if($status_admin == "U"){
                $sql="select kode_divisi,nama from apv_divisi where kode_lokasi='".$kode_lokasi."' and kode_divisi='$kode_divisi'  ";
            }else{

                $sql="select kode_divisi,nama from apv_divisi where kode_lokasi='".$kode_lokasi."'  ";
            }
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['kode_divisi'] = $kode_divisi;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['kode_divisi'] = $kode_divisi;
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getNIKVerifikasi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                if($request->kode_pp != ""){
                    $filter .= " and a.kode_pp='$request->kode_pp' ";
                }
            }

            $sql = "select a.kode_role,a.kode_pp,b.kode_jab,c.nik,c.nama 
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            where a.modul='JV' $filter";
            $cek = DB::connection($this->db)->select($sql);
            if(count($cek) > 0){
                $nik_ver = $cek[0]->nik;
            }else{
                $nik_ver = "-";
            }

            // $sql="select a.nik,a.nama from apv_karyawan a where a.kode_lokasi='".$kode_lokasi."' $filter  ";
            
            // $res = DB::connection($this->db)->select($sql);
            // $res = json_decode(json_encode($res),true);
            
            if(count($cek) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $cek;
                $success['nik_ver'] = $nik_ver;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['nik_ver'] = $nik_ver;
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getNIKVerifikasi2(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $filter = "";
            if(isset($request->kode_kota)){
                if($request->kode_kota != ""){
                    $filter .= " and a.id_kota='$request->kode_kota' ";
                }
            }

            if(isset($request->kode_divisi)){
                if($request->kode_divisi != ""){
                    $filter .= " and a.kode_divisi='$request->kode_divisi' ";
                }
            }

            $sql = "select a.nik,a.nama
			from apv_karyawan a
			where a.kode_jab like 'JV%' $filter";
            $cek = DB::connection($this->db)->select($sql);
            if(count($cek) > 0){
                $nik_ver = $cek[0]->nik;
            }else{
                $nik_ver = "-";
            }
            
            if(count($cek) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $cek;
                $success['nik_ver'] = $nik_ver;
                $success['sql'] = $sql;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['nik_ver'] = $nik_ver;
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBarangKlp(Request $request)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_barang)){
                if($request->kode_barang == "all" || $request->kode_barang == ""){
                    $filter .= "";
                }else{

                    $filter .= " and kode_barang='$request->kode_barang' ";
                }
            }else{
                $filter .= "";
            }


            $sql="select kode_barang,nama from apv_klp_barang where kode_lokasi='".$kode_lokasi."' and jenis='A' $filter";
            
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
