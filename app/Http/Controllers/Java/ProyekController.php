<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProyekController extends Controller {
    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnikProyek($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_proyek from java_proyek where no_proyek ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function isUnikKontrak($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_kontrak from java_proyek where no_kontrak ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function checkProyek(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_proyek from java_proyek where no_proyek ='".$request->query('kode')."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $success['status'] = false;
        }else{
            $success['status'] = true;
        }

        return response()->json($success, $this->successStatus);
    }

    public function checkKontrak(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_kontrak from java_proyek where no_kontrak ='".$request->query('kode')."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $success['status'] = false;
        }else{
            $success['status'] = true;
        }

        return response()->json($success, $this->successStatus);
    }

    public function getCustomer() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_cust)){
                if($request->kode_cust != "" ){

                    $filter = " and a.kode_cust='$request->kode_cust' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select kode_cust, nama 
            from java_cust where kode_lokasi='".$kode_lokasi."' $filter";

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

            if(isset($request->no_proyek)){
                if($request->no_proyek == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_proyek='$request->no_proyek' ";
                }
                $sql= "select a.no_proyek, a.keterangan, a.kode_cust, a.no_kontrak, a.tgl_mulai, convert(varchar(10), a.tgl_selesai, 120) as tgl_selesai, a.nilai, a.ppn, a.status_ppn,
                b.nama as nama, c.file_dok, a.flag_aktif 
                from java_proyek a 
                inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
                left join java_dok c on a.no_proyek=c.no_bukti and a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select no_proyek, no_kontrak, convert(varchar(10), tgl_selesai, 120) as tgl_selesai, nilai,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_proyek
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
            'no_kontrak' => 'required',
            'keterangan' => 'required',
            'kode_cust' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'nilai' => 'required',
            'ppn' => 'required',
            'status_ppn' => 'required',
            'periode' => 'required',
            'file' => 'mimes:jpeg,png|max:2048'
        ]);
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnikKontrak($request->no_kontrak, $kode_lokasi) && $this->isUnikProyek($request->no_proyek, $kode_lokasi)) {
                $insert = "insert into java_proyek(no_proyek, kode_lokasi, keterangan, kode_cust, no_kontrak, tgl_selesai, tgl_mulai, nilai, ppn, status_ppn, periode, flag_aktif, tgl_input)
                values ('".$request->no_proyek."', '".$kode_lokasi."', '".$request->keterangan."', '".$request->kode_cust."', '".$request->no_kontrak."',
                '".$request->tgl_selesai."', '".$request->tgl_mulai."','".$request->nilai."', '".$request->ppn."', '".$request->status_ppn."', '".$request->periode."', '".$request->status."', getdate())";

                DB::connection($this->sql)->insert($insert);

                if($request->hasfile('file')){
                    $file = $request->file('file');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('java/'.$foto)){
                        Storage::disk('s3')->delete('java/'.$foto);
                    }
                    Storage::disk('s3')->put('java/'.$foto,file_get_contents($file));

                    $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                    values ('".$request->no_proyek."', '$kode_lokasi', '$foto', '-', '$foto', 'KWI')";

                    DB::connection($this->sql)->insert($insertFile);
                }

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->no_proyek;
                $success['message'] = "Data Proyek berhasil disimpan";
            } else {
                DB::connection($this->sql)->rollback();
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Proyek atau No Kontrak sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function storeTest(Request $request) {
        $this->validate($request, [
            'no_proyek' => 'required',
            'no_kontrak' => 'required',
            'keterangan' => 'required',
            'kode_cust' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'nilai' => 'required',
            'ppn' => 'required',
            'status_ppn' => 'required',
            'periode' => 'required',
        ]);
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnikKontrak($request->no_kontrak, $kode_lokasi) && $this->isUnikProyek($request->no_proyek, $kode_lokasi)) {
                $insert = "insert into java_proyek(no_proyek, kode_lokasi, keterangan, kode_cust, no_kontrak, tgl_selesai, tgl_mulai, nilai, ppn, status_ppn, periode, flag_aktif, tgl_input)
                values ('".$request->no_proyek."', '".$kode_lokasi."', '".$request->keterangan."', '".$request->kode_cust."', '".$request->no_kontrak."',
                '".$request->tgl_selesai."', '".$request->tgl_mulai."','".$request->nilai."', '".$request->ppn."', '".$request->status_ppn."', '".$request->periode."', '".$request->status."', getdate())";

                DB::connection($this->sql)->insert($insert);

                $arr_foto = array();
                $arr_jenis = array();
                $arr_no_urut = array();
                $arr_nama_dok = array();
                $cek = $request->file;

                if(!empty($cek)) {
                    if(count($request->file) > 0) {
                        if(isset($request->file('file')[$i])){ 
                            $file = $request->file('file')[$i];
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            Storage::disk('s3')->put('java/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                            $arr_jenis[] = $request->jenis[$i];
                            $arr_no_urut[] = $request->no_dok[$i];
                            $arr_nama_dok[] = $request->nama_dok[$i];
                        }
                    }
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values ('".$request->no_proyek."', '$kode_lokasi', '".$arr_foto[$i]."', '".$arr_no_urut[$i]."', '".$arr_nama_dok[$i]."', '".$arr_jenis[$i]."')";
                            DB::connection($this->sql)->insert($insertFile); 
                        }
                    }
                }
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->no_proyek;
                $success['message'] = "Data Proyek berhasil disimpan";
            } else {
                DB::connection($this->sql)->rollback();
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Proyek atau No Kontrak sudah ada di database!";
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
            'no_proyek' => 'required',
            'no_kontrak' => 'required',
            'keterangan' => 'required',
            'kode_cust' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'nilai' => 'required',
            'ppn' => 'required',
            'status_ppn' => 'required',
            'periode' => 'required',
            'file' => 'mimes:jpeg,png|max:2048'
        ]);
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->sql)->table('java_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            $insert = "insert into java_proyek(no_proyek, kode_lokasi, keterangan, kode_cust, no_kontrak, tgl_selesai, tgl_mulai, nilai, ppn, status_ppn, periode, flag_aktif, tgl_input)
            values ('".$request->no_proyek."', '".$kode_lokasi."', '".$request->keterangan."', '".$request->kode_cust."', '".$request->no_kontrak."',
            '".$request->tgl_selesai."', '".$request->tgl_mulai."','".$request->nilai."', '".$request->ppn."', '".$request->status_ppn."', '".$request->periode."', '".$request->status."', getdate())";

            DB::connection($this->sql)->insert($insert);

            if($request->hasfile('file')) {
                $file = $request->file('file');
                    
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('java/'.$foto)){
                    Storage::disk('s3')->delete('java/'.$foto);
                }
                Storage::disk('s3')->put('java/'.$foto,file_get_contents($file));
                
                DB::connection($this->sql)->table('java_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_proyek)
                ->delete();

                $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                values ('".$request->no_proyek."', '$kode_lokasi', '$foto', '-', '$foto', 'KWI')";

                DB::connection($this->sql)->insert($insertFile);
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_proyek;
            $success['message'] = "Data Proyek berhasil disimpan";
            
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
                'no_proyek' => 'required'
            ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_dok from java_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='".$request->no_proyek."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                $foto = $res[0]['file_dok'];
                if($foto != ""){
                    Storage::disk('s3')->delete('java/'.$foto);
                }
            }

            DB::connection($this->sql)->table('java_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_proyek)
            ->delete();

            DB::connection($this->sql)->table('java_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Proyek berhasil dihapus";
            
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