<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
            $select = "select no_tagihan from java_tagihan where
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
                a.nilai, a.biaya_lain, a.jenis, a.keterangan, b.nama 
                from java_bayar a 
                inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
                
                $detail = "select no, no_tagihan, nilai_bayar, no_dokumen 
                from java_bayar_detail where kode_lokasi = '$kode_lokasi' and no_bayar = '$request->no_bayar'";
                
                $det = DB::connection($this->sql)->select($detail);
                $det = json_decode(json_encode($det),true);
                $success['detail'] = $det;
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
            'deskripsi' => 'required',
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

            $tanggal = $request->tanggal;
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $per = substr($periode, 2, 4);
            $no_bayar = $this->generateKode('java_bayar', 'no_bayar', $kode_lokasi."-BYR$per".".", '00001');

            $insertM = "insert into java_bayar (no_bayar, kode_lokasi, tanggal, keterangan, nilai, kode_bank, 
            kode_cust, jenis, biaya_lain, tgl_input)
            values ('$no_bayar', '$kode_lokasi', '$request->tanggal', '$request->keterangan', 
            '$request->nilai', '$request->kode_bank', '$request->kode_cust', '$request->jenis', '$request->biaya_lain', getdate())";
            DB::connection($this->sql)->insert($insertM);

            $nilai_bayar  = $request->input('nilai_bayar');
            $nomor  = $request->input('nomor');
            $no_tagihan = $request->input('no_tagihan');
            $no_dokumen = $request->input('no_dokumen');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_bayar_detail (no_bayar, kode_lokasi, no, no_tagihan, nilai_bayar, no_dokumen)
                values ('$no_bayar', '$kode_lokasi', '".$nomor[$i]."', '".$no_tagihan[$i]."', 
                '".$nilai_bayar[$i]."', '".$no_dokumen[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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
            'deskripsi' => 'required',
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
            values ('$no_bayar', '$kode_lokasi', '$request->tanggal', '$request->keterangan', 
            '$request->nilai', '$request->kode_bank', '$request->kode_cust', '$request->jenis', '$request->biaya_lain', getdate())";
            DB::connection($this->sql)->insert($insertM);

            $nilai_bayar  = $request->input('nilai_bayar');
            $nomor  = $request->input('nomor');
            $no_tagihan = $request->input('no_tagihan');
            $no_dokumen = $request->input('no_dokumen');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_bayar_detail (no_bayar, kode_lokasi, no, no_tagihan, nilai_bayar, no_dokumen)
                values ('$no_bayar', '$kode_lokasi', '".$nomor[$i]."', '".$no_tagihan[$i]."', 
                '".$nilai_bayar[$i]."', '".$no_dokumen[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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