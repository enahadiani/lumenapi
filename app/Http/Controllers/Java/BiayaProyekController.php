<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BiayaProyekController extends Controller {
    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function isUnikDokumen($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_dokumen from java_beban where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function getCust() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_cust)){
                if($request->kode_cust != "" ){

                    $filter = " and kode_cust='$request->kode_cust' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select select kode_cust, nama from java_cust 
            where kode_lokasi='".$kode_lokasi."' $filter";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getVendor() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_vendor)){
                if($request->kode_vendor != "" ){

                    $filter = " and kode_vendor='$request->kode_vendor' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select kode_vendor, nama from java_vendor 
            where kode_lokasi='".$kode_lokasi."' $filter";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getProyek(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->no_proyek)){
                if($request->no_proyek != "" ){

                    $filter = " and a.no_proyek='$request->no_proyek' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select a.no_proyek,a.keterangan,isnull(c.nilai,0)-isnull(d.nilai,0) as saldo
            from java_proyek a
            left join (select b.no_proyek,b.kode_lokasi,sum(a.jumlah*a.harga) as nilai
                        from java_rab_d a
                        inner join java_rab_m b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='".$kode_lokasi."'
                        group by b.no_proyek,b.kode_lokasi
                        )c on a.no_proyek=c.no_proyek and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
                        from java_beban  a
                        where a.kode_lokasi='".$kode_lokasi."'
                        group by a.no_proyek,a.kode_lokasi
                        )d on a.no_proyek=d.no_proyek and a.kode_lokasi=d.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."' and a.kode_cust = '$request->kode_cust'";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function index(Request $request) {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_bukti)){
                if($request->no_bukti == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_bukti='$request->no_bukti' ";
                }
                $sql = "select a.no_bukti, a.keterangan, a.no_dokumen, a.kode_vendor, a.kode_cust,
                convert(varchar(10), a.tanggal, 120) as tanggal, a.nilai, a.status,
                b.nama as nama_vendor, c.nama as nama_customer, a.no_rab, a.no_proyek, f.keterangan as keterangan_proyek,
                isnull(d.nilai,0)-isnull(e.nilai,0) as saldo
                from java_beban a inner join java_vendor b on a.kode_vendor=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
                inner join java_cust c on a.kode_cust=c.kode_cust and a.kode_lokasi=c.kode_lokasi
                inner join java_proyek f on a.no_proyek=f.no_proyek and a.kode_lokasi = f.kode_lokasi
                left join (select b.no_proyek,b.kode_lokasi,sum(a.jumlah*a.harga) as nilai
                        from java_rab_d a
                        inner join java_rab_m b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='".$kode_lokasi."'
                        group by b.no_proyek,b.kode_lokasi
                        )d on a.no_proyek=d.no_proyek and a.kode_lokasi=d.kode_lokasi
                left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
                        from java_beban  a
                        where a.kode_lokasi='".$kode_lokasi."'
                        group by a.no_proyek,a.kode_lokasi
                        )e on a.no_proyek=e.no_proyek and a.kode_lokasi=e.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter";

                $file = "select a.file_dok, a.no_urut, a.nama, a.jenis, b.nama
                from java_dok a inner join java_jenis b on a.jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '$request->no_bukti'";
                $file = DB::connection($this->sql)->select($file);
                $file = json_decode(json_encode($file),true);
                $success['file'] = $file;
            }else{
                $sql = "select no_bukti, no_proyek, keterangan, nilai, status as status_bayar, no_rab,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_beban
                where kode_lokasi= '$kode_lokasi'";
            }

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function store(Request $request) {
        $this->validate($request, [
            'tanggal' => 'required',
            'kode_vendor' => 'required',
            'kode_cust' => 'required',
            'nilai' => 'required',
            'status' => 'required',
            'no_proyek' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'no_rab' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tanggal = $request->tanggal;
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $per = substr($periode, 2, 4);
            $no_bukti = $this->generateKode('java_beban', 'no_bukti', $kode_lokasi."-BBN$per".".", '00001');

            if($this->isUnikDokumen($request->no_dokumen, $kode_lokasi)) {
                $insert = "insert into java_beban(no_bukti, kode_lokasi, tanggal, keterangan, no_dokumen, kode_vendor, nilai, status, no_proyek, kode_cust, no_rab, tgl_input)
                values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";

                DB::connection($this->sql)->insert($insert, [
                    $no_bukti,
                    $kode_lokasi,
                    $request->input('tanggal'),
                    $request->input('keterangan'),
                    $request->input('no_dokumen'),
                    $request->input('kode_vendor'),
                    $request->input('nilai'),
                    $request->input('status'),
                    $request->input('no_proyek'),
                    $request->input('kode_cust'),
                    $request->input('no_rab'),
                ]);

                $arr_foto = array();
                $arr_jenis = array();
                $arr_no_urut = array();
                $arr_nama_dok = array();
                $cek = $request->file;

                if(!empty($cek)) {
                    if(count($request->file) > 0) {
                        for($i=0;$i<count($request->jenis);$i++){ 
                            if(isset($request->file('file')[$i])){ 
                                $file = $request->file('file')[$i];
                                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $foto = $nama_foto;
                                Storage::disk('s3')->put('java/'.$foto,file_get_contents($file));
                                $arr_foto[] = $foto;
                                $arr_jenis[] = $request->jenis[$i];
                                $arr_no_urut[] = $request->no_urut[$i];
                                $arr_nama_dok[] = $request->nama_dok[$i];
                            }
                        }
                    }
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values (?, ?, ?, ?, ?, ?)";
                            DB::connection($this->sql)->insert($insertFile, [
                                $no_bukti,
                                $kode_lokasi,
                                $arr_foto[$i],
                                $arr_no_urut[$i],
                                $arr_nama_dok[$i],
                                $arr_jenis[$i]
                            ]); 
                        }
                    }
                }

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $no_bukti;
                $success['message'] = "Data Biaya Proyek berhasil disimpan";
            } else {
                DB::connection($this->sql)->rollback();
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request) {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'kode_vendor' => 'required',
            'kode_cust' => 'required',
            'nilai' => 'required',
            'status' => 'required',
            'no_proyek' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'no_rab' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;
            $arr_foto = array();
            $arr_jenis = array();
            $arr_no_urut = array();
            $arr_nama_dok = array();
            $cek = $request->file;

            DB::connection($this->sql)->table('java_beban')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $insert = "insert into java_beban(no_bukti, kode_lokasi, tanggal, keterangan, no_dokumen, kode_vendor, nilai, status, no_proyek, kode_cust, no_rab, tgl_input)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";

            DB::connection($this->sql)->insert($insert, [
                $no_bukti,
                $kode_lokasi,
                $request->input('tanggal'),
                $request->input('keterangan'),
                $request->input('no_dokumen'),
                $request->input('kode_vendor'),
                $request->input('nilai'),
                $request->input('status'),
                $request->input('no_proyek'),
                $request->input('kode_cust'),
                $request->input('no_rab'),
            ]);

            if(!empty($cek)) {
                if(count($request->file) > 0) { 
                    for($i=0;$i<count($request->jenis);$i++){ 
                        if(isset($request->file('file')[$i])){ 
                            $file = $request->file('file')[$i];
                            $fileName = $file->getClientOriginalName();
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('java/'.$request->nama_file_seb[$i]);
                            }
                            if($fileName == 'empty.jpg') {
                                $arr_foto[] = $request->nama_file_seb[$i];
                                $arr_jenis[] = $request->jenis[$i];
                                $arr_no_urut[] = $request->no_urut[$i];
                                $arr_nama_dok[] = $request->nama_dok[$i];
                            } else {
                                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $foto = $nama_foto;
                                if(Storage::disk('s3')->exists('java/'.$foto)){
                                    Storage::disk('s3')->delete('java/'.$foto);
                                }
                                Storage::disk('s3')->put('java/'.$foto,file_get_contents($file));
                                $arr_foto[] = $foto;
                                $arr_jenis[] = $request->jenis[$i];
                                $arr_no_urut[] = $request->no_urut[$i];
                                $arr_nama_dok[] = $foto;
                            }
                        }
                    }
                    DB::connection($this->sql)->table('java_dok')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_bukti', $no_bukti)
                    ->delete();
                    
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values (?, ?, ?, ?, ?, ?)";
                            DB::connection($this->sql)->insert($insertFile, [
                                $no_bukti,
                                $kode_lokasi,
                                $arr_foto[$i],
                                $arr_no_urut[$i],
                                $arr_nama_dok[$i],
                                $arr_jenis[$i]
                            ]); 
                        }
                    }
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $no_bukti;
            $success['message'] = "Data Biaya Proyek berhasil disimpan";
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function destroy(Request $request) {
        DB::connection($this->sql)->beginTransaction();
        try {
            $this->validate($request, [
                'no_bukti' => 'required'
            ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_dok from java_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='".$request->no_bukti."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                for($i=0;$i<count($res);$i++) {
                    $foto = $res[$i]['file_dok'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('java/'.$foto);
                    }
                }
            }

            DB::connection($this->sql)->table('java_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->sql)->table('java_beban')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Biaya Proyek berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}

?>