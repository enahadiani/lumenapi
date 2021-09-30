<?php
namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LaporanDetailController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    private function convertBilangan($nilai) {
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
			$temp = $this->convertBilangan($nilai/1000) . " Ribu" . $this->convertBilangan($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->convertBilangan($nilai/1000000) . " Juta" . $this->convertBilangan($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000) . " Milyar" . $this->convertBilangan(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000000) . " Trilyun" . $this->convertBilangan(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    
    private function bilanganAngka($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim($this->convertBilangan($nilai));
		} else {
			$hasil = trim($this->convertBilangan($nilai));
		}     		
		return $hasil." "."Rupiah";
	}

    public function DataDokumenPBH(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('no_bukti');
            $db_col_name = array('a.no_bukti');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $select1 = "SELECT a.no_bukti, a.modul, a.no_gambar, a.kode_lokasi 
            FROM pbh_dok a
            $where";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>