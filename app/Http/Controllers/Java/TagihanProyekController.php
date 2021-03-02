<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

    public function getProyek() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "select no_proyek, keterangan from java_proyek where
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

            if(isset($request->no_tagihan)){
                if($request->no_tagihan == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_tagihan='$request->no_tagihan' ";
                }
                $sql= "select a.no_tagihan, convert(varchar(10), tanggal, 120) as tanggal, a.kode_cust, a.nilai, a.biaya_lain, a.pajak, a.uang_muka, 
                a.keterangan, a.no_proyek, b.keterangan, c.nama 
                from java_tagihan a 
                inner join java_proyek b on a.no_proyek=b.no_proyek and a.kode_lokasi=b.kode_lokasi
                inner join java_cust c on a.kode_cust=c.kode_cust and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
                $detail = "select no, item, harga from java_tagihan_detail where kode_lokasi = '$kode_lokasi' and no_tagihan = '$request->no_tagihan'";
                
                $det = DB::connection($this->sql)->select($detail);
                $det = json_decode(json_encode($det),true);
                $success['detail'] = $det;
            }else{
                $sql = "select no_tagihan, no_proyek, convert(varchar(10), tanggal, 120) as tanggal, nilai,
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
            'nomor' => 'required|array',
            'item' => 'required|array',
            'harga' => 'required|array'
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
            tgl_input, biaya_lain, pajak, uang_muka, kode_cust)
            values ('$no_tagihan', '$kode_lokasi', '$request->no_proyek', '$request->tanggal', 
            '$request->keterangan', '$request->nilai', getdate(), '$request->biaya_lain', '$request->pajak', 
            '$request->uang_muka', '$request->kode_cust')";
            DB::connection($this->sql)->insert($insertM);

            $harga  = $request->input('harga');
            $nomor  = $request->input('nomor');
            $item = $request->input('item');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_tagihan_detail (no_tagihan, kode_lokasi, no, item, harga)
                values ('$no_tagihan', '$kode_lokasi', '".$nomor[$i]."', '".$item[$i]."', '".$harga[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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
            'kode_cust' => 'required',
            'nomor' => 'required|array',
            'item' => 'required|array',
            'harga' => 'required|array'
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
            tgl_input, biaya_lain, pajak, uang_muka, kode_cust)
            values ('$no_tagihan', '$kode_lokasi', '$request->no_proyek', '$request->tanggal', 
            '$request->keterangan', '$request->nilai', getdate(), '$request->biaya_lain', '$request->pajak', 
            '$request->uang_muka', '$request->kode_cust')";
            DB::connection($this->sql)->insert($insertM);

            $harga  = $request->input('harga');
            $nomor  = $request->input('nomor');
            $item = $request->input('item');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_tagihan_detail (no_tagihan, kode_lokasi, no, item, harga)
                values ('$no_tagihan', '$kode_lokasi', '".$nomor[$i]."', '".$item[$i]."', '".$harga[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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