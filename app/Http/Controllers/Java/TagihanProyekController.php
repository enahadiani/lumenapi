<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TagihanProyekController extends Controller { 

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

    public function getProyek(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "select no_proyek, keterangan, nilai from java_proyek where
            kode_lokasi = '$kode_lokasi' and kode_cust = '".$request->query('kode_cust')."'";

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

            if(isset($request->no_tagihan)){
                if($request->no_tagihan == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_tagihan='$request->no_tagihan' ";
                }
                $sql= "select a.no_tagihan, convert(varchar(10), tanggal, 120) as tanggal, a.kode_cust, a.nilai, a.biaya_lain, a.pajak, a.uang_muka, 
                a.keterangan, a.no_proyek, b.keterangan as keterangan_proyek, c.nama, b.nilai
                from java_tagihan a 
                inner join java_proyek b on a.no_proyek=b.no_proyek and a.kode_lokasi=b.kode_lokasi
                inner join java_cust c on a.kode_cust=c.kode_cust and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
                $detail = "select no, item, harga from java_tagihan_detail where kode_lokasi = '$kode_lokasi' and no_tagihan = '$request->no_tagihan'";
                
                $det = DB::connection($this->sql)->select($detail);
                $det = json_decode(json_encode($det),true);
                $success['detail'] = $det;

                $file = "select a.file_dok, a.no_urut, a.nama, a.jenis, b.nama
                from java_dok a inner join java_jenis b on a.jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '$request->no_tagihan'";
                $file = DB::connection($this->sql)->select($file);
                $file = json_decode(json_encode($file),true);
                $success['file'] = $file;
            }else{
                $sql = "select no_tagihan, no_proyek, convert(varchar(10), tanggal, 120) as tanggal, (nilai+ biaya_lain + (pajak/100)) as nilai,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_tagihan
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
            'tanggal' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'biaya_lain' => 'required',
            'pajak' => 'required',
            'uang_muka' => 'required',
            'kode_cust' => 'required',
            // 'nomor' => 'required|array',
            // 'item' => 'required|array',
            // 'harga' => 'required|array'
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
            $no_tagihan = $this->generateKode('java_tagihan', 'no_tagihan', $kode_lokasi."-TGH$per".".", '00001');

            $insertM = "insert into java_tagihan (no_tagihan, kode_lokasi, no_proyek, tanggal, keterangan, nilai, 
            biaya_lain, pajak, uang_muka, kode_cust, tgl_input)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_tagihan,
                $kode_lokasi,
                $request->input('no_proyek'),
                $request->input('tanggal'),
                $request->input('keterangan'),
                $request->input('nilai'),
                $request->input('biaya_lain'),
                $request->input('pajak'),
                $request->input('uang_muka'),
                $request->input('kode_cust'),
            ]);

            if(!empty($request->input('nomor'))) { 
                $harga  = $request->input('harga');
                $nomor  = $request->input('nomor');
                $item = $request->input('item');

                for($i=0;$i<count($request->nomor);$i++) {
                    $insertD = "insert into java_tagihan_detail (no_tagihan, kode_lokasi, no, item, harga)
                    values (?, ?, ?, ?, ?)";

                    DB::connection($this->sql)->insert($insertD, [
                        $no_tagihan,
                        $kode_lokasi,
                        $nomor[$i],
                        $item[$i],
                        $harga[$i]
                    ]);
                }
            }

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
                            $no_tagihan,
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
            $success['kode'] = $no_tagihan;
            $success['message'] = "Data Tagihan Project berhasil disimpan";

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
            'no_tagihan' => 'required',
            'no_proyek' => 'required',
            'tanggal' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'biaya_lain' => 'required',
            'pajak' => 'required',
            'uang_muka' => 'required',
            'kode_cust' => 'required'
        ]);
        
        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tanggal = $request->tanggal;
            $no_tagihan = $request->no_tagihan;

            DB::connection($this->sql)->table('java_tagihan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_tagihan', $no_tagihan)
            ->delete();

            DB::connection($this->sql)->table('java_tagihan_detail')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_tagihan', $no_tagihan)
            ->delete();

            $insertM = "insert into java_tagihan (no_tagihan, kode_lokasi, no_proyek, tanggal, keterangan, nilai, 
            biaya_lain, pajak, uang_muka, kode_cust, tgl_input)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_tagihan,
                $kode_lokasi,
                $request->input('no_proyek'),
                $request->input('tanggal'),
                $request->input('keterangan'),
                $request->input('nilai'),
                $request->input('biaya_lain'),
                $request->input('pajak'),
                $request->input('uang_muka'),
                $request->input('kode_cust'),
            ]);

            if(!empty($request->input('nomor'))) { 
                $harga  = $request->input('harga');
                $nomor  = $request->input('nomor');
                $item = $request->input('item');

                for($i=0;$i<count($request->nomor);$i++) {
                    $insertD = "insert into java_tagihan_detail (no_tagihan, kode_lokasi, no, item, harga)
                    values (?, ?, ?, ?, ?)";

                    DB::connection($this->sql)->insert($insertD, [
                        $no_tagihan,
                        $kode_lokasi,
                        $nomor[$i],
                        $item[$i],
                        $harga[$i]
                    ]);
                }
            }

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
                    ->where('no_bukti', $no_tagihan)
                    ->delete();
                    
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values (?, ?, ?, ?, ?, ?)";
                            DB::connection($this->sql)->insert($insertFile, [
                                $no_tagihan,
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
            $success['kode'] = $no_tagihan;
            $success['message'] = "Data Tagihan Project berhasil disimpan";

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
            'no_tagihan' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_tagihan = $request->no_tagihan;

            $sql = "select file_dok from java_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='".$no_tagihan."'";
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
            ->where('no_bukti', $no_tagihan)
            ->delete();

            DB::connection($this->sql)->table('java_tagihan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_tagihan', $no_tagihan)
            ->delete();

            DB::connection($this->sql)->table('java_tagihan_detail')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_tagihan', $no_tagihan)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Tagihan Project berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}

?>