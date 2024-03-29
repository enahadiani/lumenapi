<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PembayaranProyekController extends Controller {

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

    public function getTagihan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "select no_tagihan, ((nilai*(pajak/100) + biaya_lain + nilai) - uang_muka) as sisa_bayar 
            from java_tagihan where
            kode_lokasi = '$kode_lokasi' and kode_cust = '$request->kode_cust'";

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

    public function getBank() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "select kode_bank, nama from java_bank where
            kode_lokasi = '$kode_lokasi'";

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

            if(isset($request->no_bayar)){
                if($request->no_bayar == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_bayar='$request->no_bayar' ";
                }
                $sql= "select a.no_bayar, convert(varchar(10), a.tanggal, 120) as tanggal, a.kode_cust, 
                a.nilai, a.biaya_lain, a.jenis, a.keterangan, b.nama, a.kode_bank, c.nama as nama_bank 
                from java_bayar a 
                inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
                inner join java_bank c on a.kode_bank=c.kode_bank and a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
                
                $detail = "select no, no_tagihan, nilai_bayar, no_dokumen, nilai_tagihan 
                from java_bayar_detail where kode_lokasi = '$kode_lokasi' and no_bayar = '$request->no_bayar'";
                
                $det = DB::connection($this->sql)->select($detail);
                $det = json_decode(json_encode($det),true);
                $success['detail'] = $det;

                $file = "select a.file_dok, a.no_urut, a.nama, a.jenis, b.nama
                from java_dok a inner join java_jenis b on a.jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '$request->no_bayar'";
                $file = DB::connection($this->sql)->select($file);
                $file = json_decode(json_encode($file),true);
                $success['file'] = $file;
            }else{
                $sql = "select no_bayar, convert(varchar(10), tanggal, 120) as tanggal, keterangan, nilai,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_bayar
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
            'kode_cust' => 'required',
            'kode_bank' => 'required',
            'jenis' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'biaya_lain' => 'required',
            'nomor' => 'required|array',
            'no_tagihan' => 'required|array',
            'no_dokumen' => 'required|array',
            'nilai_bayar' => 'required|array',
            'nilai_tagihan' => 'required|array'
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
            $no_bayar = $this->generateKode('java_bayar', 'no_bayar', $kode_lokasi."-BYR$per".".", '00001');

            $insertM = "insert into java_bayar (no_bayar, kode_lokasi, tanggal, keterangan, nilai, kode_bank, 
            kode_cust, jenis, biaya_lain, tgl_input)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_bayar,
                $kode_lokasi,
                $request->input('tanggal'),
                $request->input('keterangan'),
                $request->input('nilai'),
                $request->input('kode_bank'),
                $request->input('kode_cust'),
                $request->input('jenis'),
                $request->input('biaya_lain'),
            ]);

            $nilai_bayar  = $request->input('nilai_bayar');
            $nilai_tagihan  = $request->input('nilai_tagihan');
            $nomor  = $request->input('nomor');
            $no_tagihan = $request->input('no_tagihan');
            $no_dokumen = $request->input('no_dokumen');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_bayar_detail (no_bayar, kode_lokasi, no, no_tagihan, nilai_bayar, no_dokumen, nilai_tagihan)
                values (?, ?, ?, ?, ?, ?, ?)";

                DB::connection($this->sql)->insert($insertD, [
                    $no_bayar,
                    $kode_lokasi,
                    $nomor[$i],
                    $no_tagihan[$i],
                    $nilai_bayar[$i],
                    $no_dokumen[$i],
                    $nilai_tagihan[$i]
                ]);
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
                            $no_bayar,
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
            $success['kode'] = $no_bayar;
            $success['message'] = "Data Pembayaran Project berhasil disimpan";

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
            'no_bayar' => 'required',
            'tanggal' => 'required',
            'kode_cust' => 'required',
            'kode_bank' => 'required',
            'jenis' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'biaya_lain' => 'required',
            'nomor' => 'required|array',
            'no_tagihan' => 'required|array',
            'no_dokumen' => 'required|array',
            'nilai_bayar' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bayar = $request->no_bayar;

            DB::connection($this->sql)->table('java_bayar')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bayar', $no_bayar)
            ->delete();

            DB::connection($this->sql)->table('java_bayar_detail')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bayar', $no_bayar)
            ->delete();

            $insertM = "insert into java_bayar (no_bayar, kode_lokasi, tanggal, keterangan, nilai, kode_bank, 
            kode_cust, jenis, biaya_lain, tgl_input)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())";
            DB::connection($this->sql)->insert($insertM, [
                $no_bayar,
                $kode_lokasi,
                $request->input('tanggal'),
                $request->input('keterangan'),
                $request->input('nilai'),
                $request->input('kode_bank'),
                $request->input('kode_cust'),
                $request->input('jenis'),
                $request->input('biaya_lain'),
            ]);

            $nilai_bayar  = $request->input('nilai_bayar');
            $nilai_tagihan  = $request->input('nilai_tagihan');
            $nomor  = $request->input('nomor');
            $no_tagihan = $request->input('no_tagihan');
            $no_dokumen = $request->input('no_dokumen');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_bayar_detail (no_bayar, kode_lokasi, no, no_tagihan, nilai_bayar, no_dokumen, nilai_tagihan)
                values (?, ?, ?, ?, ?, ?, ?)";

                DB::connection($this->sql)->insert($insertD, [
                    $no_bayar,
                    $kode_lokasi,
                    $nomor[$i],
                    $no_tagihan[$i],
                    $nilai_bayar[$i],
                    $no_dokumen[$i],
                    $nilai_tagihan[$i]
                ]);
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
                    ->where('no_bukti', $no_bayar)
                    ->delete();
                    
                    if(count($arr_no_urut) > 0){
                        for($i=0; $i<count($arr_no_urut);$i++){
                            $insertFile = "insert into java_dok(no_bukti, kode_lokasi, file_dok, no_urut, nama, jenis)
                            values (?, ?, ?, ?, ?, ?)";
                            DB::connection($this->sql)->insert($insertFile, [
                                $no_bayar,
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
            $success['kode'] = $no_bayar;
            $success['message'] = "Data Pembayaran Project berhasil disimpan";

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
            'no_bayar' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bayar = $request->no_bayar;

            $sql = "select file_dok from java_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='".$no_bayar."'";
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
            ->where('no_bukti', $no_bayar)
            ->delete();

            DB::connection($this->sql)->table('java_bayar')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bayar', $no_bayar)
            ->delete();

            DB::connection($this->sql)->table('java_bayar_detail')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bayar', $no_bayar)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran Project berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}

?>