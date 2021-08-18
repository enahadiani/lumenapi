<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RabProyekController extends Controller {

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

    public function getProyek() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "select no_proyek, keterangan, nilai from java_proyek a 
            where not exists (select no_proyek from java_rab_m b where a.no_proyek=b.no_proyek) 
            and a.kode_lokasi = '$kode_lokasi'";

            $res = DB::connection($this->sql)->select($select);
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

            if(isset($request->no_rab)){
                if($request->no_rab == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_rab='$request->no_rab' ";
                }
                $sql= "select a.no_proyek, b.keterangan, a.nilai_anggaran, b.nilai, b.no_kontrak 
                from java_rab_m a inner join java_proyek b on a.no_proyek=b.no_proyek and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ";
                $detail = "select no, keterangan, jumlah, satuan, harga from java_rab_d where kode_lokasi = '$kode_lokasi' and no_rab = '$request->no_rab'";
                
                $file = "select a.file_dok, a.no_urut, a.nama, a.jenis, b.nama
                from java_dok a inner join java_jenis b on a.jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '$request->no_rab'";
                $file = DB::connection($this->sql)->select($file);
                $file = json_decode(json_encode($file),true);
                $success['file'] = $file;

                $det = DB::connection($this->sql)->select($detail);
                $det = json_decode(json_encode($det),true);
                $success['detail'] = $det;
            }else{
                $sql = "select no_rab, no_proyek, tanggal, nilai_anggaran,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_rab_m
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
            'no_proyek' => 'required',
            'nilai_anggaran' => 'required'
        ]);
        
        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tanggal = date('Y-m-d');
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $per = substr($periode, 2, 4);
            $no_rab = $this->generateKode('java_rab_m', 'no_rab', $kode_lokasi."-AGR$per".".", '00001');

            $insertM = "insert into java_rab_m (no_rab, kode_lokasi, no_proyek, tanggal, nilai_anggaran, tgl_input)
            values (?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_rab,
                $kode_lokasi,
                $request->input('no_proyek'),
                $tanggal,
                $request->input('nilai_anggaran'),
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
                    for($i=0; $i<count($arr_no_urut);$i++) {
                        $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                        values (?, ?, ?, ?, ?, ?)";
                        DB::connection($this->sql)->insert($insertFile, [
                            $no_rab,
                            $kode_lokasi,
                            $arr_foto[$i],
                            $arr_no_urut[$i],
                            $arr_nama_dok[$i],
                            $arr_jenis[$i]
                        ]); 
                    }
                }
            }

            if(!empty($request->input('nomor'))) {
                $jumlah = $request->input('jumlah');
                $satuan = $request->input('satuan');
                $harga  = $request->input('harga');
                $nomor     = $request->input('nomor');
                $keterangan = $request->input('keterangan');

                for($i=0;$i<count($request->nomor);$i++) {
                    $insertD = "insert into java_rab_d (no_rab, kode_lokasi, jumlah, satuan, harga, no, keterangan)
                    values (?, ?, ?, ?, ?, ?, ?)";

                    DB::connection($this->sql)->insert($insertD, [
                        $no_rab,
                        $kode_lokasi,
                        $jumlah[$i],
                        $satuan[$i],
                        $harga[$i],
                        $nomor[$i],
                        $keterangan[$i]
                    ]);
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $no_rab;
            $success['message'] = "Data Anggaran Project berhasil disimpan";

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
            'no_proyek' => 'required',
            'nilai_anggaran' => 'required'
        ]);
            
        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tanggal = date('Y-m-d');
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $per = substr($periode, 2, 4);
            $no_rab = $request->no_rab;

            DB::connection($this->sql)->table('java_rab_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_rab', $no_rab)
            ->delete();

            DB::connection($this->sql)->table('java_rab_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_rab', $no_rab)
            ->delete();

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
                    ->where('no_bukti', $no_rab)
                    ->delete();
                    
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values (?, ?, ?, ?, ?, ?)";
                            DB::connection($this->sql)->insert($insertFile, [
                                $no_rab,
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

            $insertM = "insert into java_rab_m (no_rab, kode_lokasi, no_proyek, tanggal, nilai_anggaran, tgl_input)
            values (?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_rab,
                $kode_lokasi,
                $request->input('no_proyek'),
                $tanggal,
                $request->input('nilai_anggaran'),
            ]);

            if(!empty($request->input('nomor'))) {
                $jumlah = $request->input('jumlah');
                $satuan = $request->input('satuan');
                $harga  = $request->input('harga');
                $nomor     = $request->input('nomor');
                $keterangan = $request->input('keterangan');

                for($i=0;$i<count($request->nomor);$i++) {
                    $insertD = "insert into java_rab_d (no_rab, kode_lokasi, jumlah, satuan, harga, no, keterangan)
                    values (?, ?, ?, ?, ?, ?, ?)";

                    DB::connection($this->sql)->insert($insertD, [
                        $no_rab,
                        $kode_lokasi,
                        $jumlah[$i],
                        $satuan[$i],
                        $harga[$i],
                        $nomor[$i],
                        $keterangan[$i]
                    ]);
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $no_rab;
            $success['message'] = "Data Anggaran Project berhasil disimpan";

            return response()->json($success, $this->successStatus);  
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function destroy(Request $request) {
        $this->validate($request, [
            'no_rab' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_rab = $request->no_rab;

            $sql = "select file_dok from java_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='".$no_rab."'";
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
            ->where('no_bukti', $no_rab)
            ->delete();

            DB::connection($this->sql)->table('java_rab_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_rab', $no_rab)
            ->delete();

            DB::connection($this->sql)->table('java_rab_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_rab', $no_rab)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Anggaran Project berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Anggaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}

?>