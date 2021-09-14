<?php
namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LaporanImprestFundController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function DataImburseIF(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_pb');
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, i.kode_pp, 
            b.nama AS nama_pp, a.nilai, a.nik_user, a.tanggal, i.kode_akun, c.nama AS nama_akun, 
            i.kode_drk, e.nama AS nama_drk, h.logo, h.alamat, a.nik_user, a.nik_app, f.nama AS nama_user, 
            g.nama AS nama_app
            FROM pbh_pb_m a 
            INNER JOIN pbh_pb_j i ON a.no_pb=i.no_pb AND a.kode_lokasi=i.kode_lokasi
            INNER JOIN pp b ON i.kode_pp=b.kode_pp AND i.kode_lokasi=b.kode_lokasi
            INNER JOIN masakun c ON i.kode_akun=c.kode_akun AND i.kode_lokasi=c.kode_lokasi
            LEFT JOIN drk e ON i.kode_drk=e.kode_drk AND i.kode_lokasi=e.kode_lokasi AND e.tahun='2020'
            LEFT JOIN karyawan f ON a.nik_user=f.nik AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan g ON a.nik_app=g.nik AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN lokasi h ON a.kode_lokasi=h.kode_lokasi
            $where AND a.modul IN ('IFREIM','IFCLOSE')
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_pb = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $no_pb = "'".$row['no_pb']."'";
                } else {
                    $no_pb .= ", '".$row['no_pb']."'";
                }
                $i++;
            }

            $select2 = "SELECT no_bukti, no_rek, nama_rek, bank, 
            nilai + ISNULL(pajak,0) AS nilai, ISNULL(pajak,0) AS pajak, nilai AS netto 
            FROM pbh_rek 
            WHERE no_bukti IN ($no_pb)  AND kode_lokasi='".$kode_lokasi."' 
            ORDER BY no_rek";

            $res2 = DB::connection($this->db)->select($select2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function DataPembukaanIF(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_kas');
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

            $select1 = "SELECT a.no_kas, a.no_dokumen, a.periode, a.tanggal, CONVERT(varchar,a.tanggal,103) as tanggal1,
            a.keterangan, a.nik_buat, b.nama AS nama_buat, b.jabatan AS jabatan_buat, a.nik_app, 
            c.nama AS nama_setuju, c.jabatan AS jabatan_setuju, d.kota, a.nilai, d.logo, d.alamat
            FROM kas_m a 
            INNER JOIN lokasi d ON a.kode_lokasi=d.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_buat=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_app=c.nik AND a.kode_lokasi=c.kode_lokasi
            $where";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_kas = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $no_kas = "'".$row['no_kas']."'";
                } else {
                    $no_kas .= ", '".$row['no_kas']."'";
                }
                $i++;
            }

            $select2 = "SELECT a.no_kas, a.kode_akun, b.nama, a.keterangan, a.kode_pp, a.kode_drk, a.kode_cf,
            CASE dc WHEN 'D' THEN nilai ELSE 0 END AS debet,
            CASE dc WHEN 'C' THEN nilai ELSE 0 end as kredit
            FROM kas_j a 
            INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
			WHERE a.no_kas IN ($no_kas)  AND a.kode_lokasi='".$kode_lokasi."'
			ORDER BY a.no_urut ";

            $res2 = DB::connection($this->db)->select($select2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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