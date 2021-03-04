<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanProyekController extends Controller {

    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getKartuProyek(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_proyek');
            $db_col_name = array('a.no_proyek');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $proyek = "select a.no_proyek, a.no_kontrak, convert(varchar,tgl_mulai,103) as tgl_mulai, 
            convert(varchar,tgl_selesai,103) as tgl_selesai, a.keterangan,b.nama as nama_cust
            from java_proyek a
            inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            $filter";

            $res1 = DB::connection($this->sql)->select($proyek);
            $res1 = json_decode(json_encode($res1),true);

            $rab = "select b.jumlah, b.satuan, b.harga, b.keterangan
            from  java_rab_m a
            inner join java_rab_d b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
            $filter
            order by b.no";

            $res2 = DB::connection($this->sql)->select($rab);
            $res2 = json_decode(json_encode($res2),true);

            $beban = "select a.no_bukti, a.no_dokumen, convert(varchar,tanggal,103) as tgl, a.keterangan, 
            b.nama as nama_vendor, a.nilai,a.status
            from java_beban a
            inner join java_vendor b on a.kode_vendor=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
            $filter";

            $res3 = DB::connection($this->sql)->select($beban);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_rab'] = $res2;
                $success['data_beban'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_rab'] = [];
                $success['data_beban'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function convertBilangan($nilai) {
		$nilai = abs($nilai);
		$huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = $this->convertBilangan($nilai - 10). " Belas";
		} else if ($nilai < 100) {
			$temp = $this->convertBilangan($nilai/10)." Puluh". $this->convertBilangan($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " seratus" . $this->convertBilangan($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = $this->convertBilangan($nilai/100) . " Ratus" . $this->convertBilangan($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " seribu" . $this->convertBilangan($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = $this->convertBilangan($nilai/1000) . "Ribu" . $this->convertBilangan($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->convertBilangan($nilai/1000000) . " Juta" . $this->convertBilangan($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000) . " Milyar" . $this->convertBilangan(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000000) . " Trilyun" . $this->convertBilangan(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    
    function bilanganAngka($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim($this->convertBilangan($nilai));
		} else {
			$hasil = trim($this->convertBilangan($nilai));
		}     		
		return $hasil." "."Rupiah";
	}

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
    }
}
?>