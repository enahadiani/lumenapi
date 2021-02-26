<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
            'nilai_anggaran' => 'required',
            'nomor' => 'required|array',
            'keterangan' => 'required|array',
            'jumlah' => 'required|array',
            'satuan' => 'required|array',
            'harga' => 'required|array'
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

            $insertM = "insert into java_rab_m (no_rab, kode_lokasi, no_proyek, tanggal, tgl_input, nilai_anggaran)
            values ('$no_rab', '$kode_lokasi', '$request->no_proyek', '$tanggal', getdate(), '$request->nilai_anggaran')";
            DB::connection($this->sql)->insert($insertM);

            $jumlah = $request->input('jumlah');
            $satuan = $request->input('satuan');
            $harga  = $request->input('harga');
            $nomor     = $request->input('nomor');
            $keterangan = $request->input('keterangan');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_rab_d (no_rab, kode_lokasi, jumlah, satuan, harga, no, keterangan)
                values ('$no_rab', '$kode_lokasi', '".$jumlah[$i]."', '".$satuan[$i]."', '".$harga[$i]."', 
                '".$nomor[$i]."', '".$keterangan[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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
            'nilai_anggaran' => 'required',
            'nomor' => 'required|array',
            'keterangan' => 'required|array',
            'jumlah' => 'required|array',
            'satuan' => 'required|array',
            'harga' => 'required|array'
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

            $insertM = "insert into java_rab_m (no_rab, kode_lokasi, no_proyek, tanggal, tgl_input, nilai_anggaran)
            values ('$no_rab', '$kode_lokasi', '$request->no_proyek', '$tanggal', getdate(), '$request->nilai_anggaran')";
            DB::connection($this->sql)->insert($insertM);
            
            $jumlah = $request->input('jumlah');
            $satuan = $request->input('satuan');
            $harga  = $request->input('harga');
            $nomor     = $request->input('nomor');
            $keterangan = $request->input('keterangan');

            for($i=0;$i<count($request->nomor);$i++) {
                $insertD = "insert into java_rab_d (no_rab, kode_lokasi, jumlah, satuan, harga, no, keterangan)
                values ('$no_rab', '$kode_lokasi', '".$jumlah[$i]."', '".$satuan[$i]."', '".$harga[$i]."', 
                '".$nomor[$i]."', '".$keterangan[$i]."')";

                DB::connection($this->sql)->insert($insertD);
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