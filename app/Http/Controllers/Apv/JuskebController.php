<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class JuskebController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    
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

            $res = DB::connection('sqlsrv2')->select("select a.no_bukti,a.no_dokumen,a.kode_pp,convert(varchar,a.waktu,103) as waktu,a.kegiatan,case a.progress when 'S' then 'FINISH' when 'F' then 'Return Verifikasi' when 'R' then 'Return Approval' else isnull(b.nama_jab,'-') end as posisi,a.nilai,a.progress
            from apv_juskeb_m a
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1'
                    )b on a.no_bukti=b.no_bukti
            where a.kode_lokasi='".$kode_lokasi."'  and a.nik_buat='".$nik_user."'
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
            'no_dokumen' => 'required',
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

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            if($request->hasfile('file'))
            {
                foreach($request->file('file') as $file)
                {                
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('local')->exists($foto)){
                        Storage::disk('local')->delete($foto);
                    }
                    Storage::disk('local')->put($foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama_file')[$i];
                    $i++;
                }
            }

            $no_bukti = $this->generateKode("apv_juskeb_m", "no_bukti", "APV-", "0001");
            
            $ins = DB::connection('sqlsrv2')->insert('insert into apv_juskeb_m (no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_dokumen'),$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tanggal'),'A']);

            $barang = $request->input('barang');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juskeb_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai) values (?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i]));
                }
            }

            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i]]); 
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK'
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
                $ins4[$i] = DB::connection('sqlsrv2')->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,sts_ver) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,0]);
            }
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Kebutuhan berhasil disimpan";
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
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
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nilai,convert(varchar(10),tanggal,121) as tanggal from apv_juskeb_m where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' ";
            
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select no_bukti,barang,harga,jumlah,nilai from apv_juskeb_d where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";					
            $res2 = DB::connection('sqlsrv2')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,nama,file_dok from apv_juskeb_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by no_urut";
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
            'no_dokumen' => 'required',
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

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            if($request->hasfile('file'))
            {
                foreach($request->file('file') as $file)
                {                
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('local')->exists($foto)){
                        Storage::disk('local')->delete($foto);
                    }
                    Storage::disk('local')->put($foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama_file')[$i];
                    $i++;
                }
            }

            $del = DB::connection('sqlsrv2')->table('apv_juskeb_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection('sqlsrv2')->table('apv_juskeb_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection('sqlsrv2')->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del4 = DB::connection('sqlsrv2')->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            

            
            $ins = DB::connection('sqlsrv2')->insert('insert into apv_juskeb_m (no_bukti,no_dokumen,kode_pp,waktu,kegiatan,dasar,nik_buat,kode_lokasi,nilai,tanggal,progress) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->input('no_dokumen'),$request->input('kode_pp'),$request->input('waktu'),$request->input('kegiatan'),$request->input('dasar'),$nik_user,$kode_lokasi,$request->input('total_barang'),$request->input('tanggal'),'A']);

            $barang = $request->input('barang');
            $harga = $request->input('harga');
            $qty = $request->input('qty');
            $subtotal = $request->input('subtotal');

            if(count($barang) > 0){
                for($i=0; $i<count($barang);$i++){
                    $ins2[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juskeb_d (kode_lokasi,no_bukti,barang,harga,jumlah,no_urut,nilai) values (?, ?, ?, ?, ?, ?, ?) ", array($kode_lokasi,$no_bukti,$barang[$i],$harga[$i],$qty[$i],$i,$subtotal[$i]));
                }
            }

            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection('sqlsrv2')->insert("insert into apv_juskeb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok) values (?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i]]); 
                }
            }

            $sql = "select a.kode_role,b.kode_jab,b.no_urut,c.nik,c.no_telp
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and ".$request->input('total_barang')." between a.bawah and a.atas and a.modul='JK'
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
                $ins4[$i] = DB::connection('sqlsrv2')->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,sts_ver) values (?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$role[$i]['kode_role'],$role[$i]['kode_jab'],$i,$prog,0]);
            }
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Justifikasi Kebutuhan berhasil diubah";
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
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
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_juskeb_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection('sqlsrv2')->table('apv_juskeb_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection('sqlsrv2')->table('apv_juskeb_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del4 = DB::connection('sqlsrv2')->table('apv_flow')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            

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

}
