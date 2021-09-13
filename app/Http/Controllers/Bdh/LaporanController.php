<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LaporanController extENDs Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function DataJurnalFinalPertanggungPanjar(Request $r) {
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

            $select1 = "SELECT DISTINCT a.no_kas, a.no_dokumen, a.periode, a.tanggal, 
            convert(varchar,a.tanggal,103) as tanggal1,a.keterangan,a.kode_lokasi, 
            a.nik_buat, b.nama as nama_buat, b.jabatan as jabatan_buat, a.nik_app, c.nama as nama_setuju, 
            c.jabatan as jabatan_setuju, d.kota
            FROM kas_m a 
            INNER JOIN lokasi d ON a.kode_lokasi=d.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_buat=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_app=c.nik AND a.kode_lokasi=c.kode_lokasi
            $where
            ORDER BY a.no_kas";

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

            $select2 = "SELECT a.no_kas, a.kode_akun, b.nama, a.keterangan, a.kode_pp, a.kode_drk, 
            CASE dc WHEN 'D' THEN nilai ELSE 0 END AS debet, 
            CASE dc WHEN 'C' THEN nilai ELSE 0 END AS kredit
            FROM kas_j a 
            INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
			WHERE a.no_kas IN ($no_kas) AND a.kode_lokasi='".$kode_lokasi."'
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

    

    public function DataTransferBank(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_spb');
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

            $select1 = "SELECT a.no_spb, a.periode, a.tanggal, a.keterangan, f.kota,a.nilai, 
            CONVERT(varchar, a.tanggal, 103) AS tgl_spb, f.logo, f.nama AS nama_lokasi, 
            a.nik_buat,b.nama AS nama_buat, b.jabatan AS jab_buat, 
            a.nik_fiat,c.nama AS nama_fiat,c.jabatan AS jab_fiat, a.nik_bdh,d.nama AS nama_bdh, 
            d.jabatan AS jab_bdh
            FROM spb_m a 
            INNER JOIN lokasi f ON a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_buat=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_fiat=c.nik AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN karyawan d ON a.nik_bdh=d.nik AND a.kode_lokasi=d.kode_lokasi
            $where
            ORDER BY a.no_spb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_spb = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $no_spb = "'".$row['no_spb']."'";
                } else {
                    $no_spb .= ", '".$row['no_spb']."'";
                }
                $i++;
            }

            $select2 = "SELECT a.no_spb, a.no_pb, 
            CASE WHEN SUBSTRING(b.no_rek,1,1) = '0' THEN ''''+ b.no_rek ELSE  ''''+ b.no_rek END AS no_rek, 
            b.nilai, b.nama_rek, b.bank, a.keterangan AS berita
            FROM pbh_pb_m a 
            INNER JOIN pbh_rek b ON a.no_pb=b.no_bukti AND a.kode_lokasi=b.kode_lokasi
            WHERE a.no_spb IN ($no_spb) AND a.kode_lokasi='".$kode_lokasi."'
            ORDER BY b.bank, b.nama_rek";

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

    public function DataPembayaran(Request $r) {
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

            $select1 = "SELECT a.no_kas, a.no_dokumen, a.kode_lokasi, a.periode, a.tanggal, 
            CONVERT(varchar,a.tanggal,103) AS tanggal1, a.keterangan, d.nama as nama_lokasi, 
            a.nik_buat, b.nama as nama_buat, b.jabatan as jabatan_buat, a.nik_app, c.nama as nama_setuju, 
            c.jabatan as jabatan_setuju, d.kota, a.nilai, d.logo, d.alamat
            FROM kas_m a 
            INNER JOIN lokasi d ON a.kode_lokasi=d.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_buat=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_app=c.nik AND a.kode_lokasi=c.kode_lokasi
            $where";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_kas = "";
            $tahun = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $tahun = "'".substr($row['periode'], 0, 4)."'";
                    $no_kas = "'".$row['no_kas']."'";
                } else {
                    $tahun = ", '".substr($row['periode'], 0, 4)."'";
                    $no_kas .= ", '".$row['no_kas']."'";
                }
                $i++;
            }

            $select2 = "SELECT a.no_kas, a.kode_akun, a.no_dokumen, b.nama, a.keterangan, a.kode_pp, a.kode_drk, a.kode_cf, 
            isnull(c.nama,'-') as nama_drk, 
            CASE dc WHEN 'D' THEN nilai ELSE 0 END AS debet, 
            CASE dc when 'C' THEN nilai ELSE 0 END AS kredit
            FROM kas_j a 
            INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
			LEFT JOIN drk c ON a.kode_drk=c.kode_drk AND a.kode_lokasi=c.kode_lokasi AND c.tahun in ($tahun)
			WHERE a.no_kas IN ($no_kas) AND a.kode_lokasi='".$kode_lokasi."'
			ORDER BY a.no_dokumen,a.no_urut ";

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

    public function DataSPB(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_spb');
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

            $select1 = "SELECT a.no_spb, a.periode, a.tanggal, a.keterangan, f.kota, a.nilai, a.nik_user, 
            b.nama AS nama_user, a.nik_bdh, c.nama AS nama_bdh, a.nik_sah AS nik_ver, d.nama AS nama_ver, a.nik_fiat, 
            e.nama AS nama_fiat, CONVERT(varchar,a.tanggal,103) AS tgl, f.logo, f.alamat, f.nama AS nama_lokasi, f.kota
            FROM spb_m a 
            INNER JOIN lokasi f ON a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_user=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_bdh=c.nik AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN karyawan d ON a.nik_sah=d.nik AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN karyawan e ON a.nik_fiat=e.nik AND a.kode_lokasi=e.kode_lokasi
            $where";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_spb = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $no_spb = "'".$row['no_spb']."'";
                } else {
                    $no_spb .= ", '".$row['no_spb']."'";
                }
                $i++;
            }

            $select2 = "SELECT a.no_pb, a.tanggal, a.keterangan, CONVERT(varchar,a.tanggal,103) AS tgl, 
            ISNULL(b.nilai,0) + ISNULL(b.nilai2,0) AS nilai, ISNULL(b.npajak,0) AS npajak, 
            (ISNULL(b.nilai,0) + ISNULL(b.nilai2,0)) - ISNULL(b.npajak,0) AS netto
            FROM pbh_pb_m a 
            LEFT JOIN (SELECT a.no_pb,a.kode_lokasi,
                SUM(CASE WHEN a.kode_akun IN ('1132103','2121101','2121102','4960001','2121103','2121107','2121105') AND a.dc='C' THEN a.nilai ELSE 0 END) AS npajak, 
                SUM(CASE WHEN a.kode_akun NOT IN ('1132103','2121101','2121102','4960001','2121103','2121107','2121105')  THEN a.nilai ELSE 0 END) AS nilai,
                SUM(CASE WHEN a.kode_akun IN ('1132103','2121101','2121102','4960001','2121103','2121107','2121105') AND a.dc='D' then a.nilai ELSE 0 END) AS nilai2
                FROM pbh_pb_j a
                inner join pbh_pb_m b on a.no_pb=b.no_pb AND a.kode_lokasi=b.kode_lokasi
                WHERE b.no_spb IN ($no_spb) AND b.kode_lokasi='".$kode_lokasi."'
                GROUP BY a.no_pb,a.kode_lokasi
            ) b ON a.no_pb=b.no_pb AND a.kode_lokasi=b.kode_lokasi
            WHERE a.no_spb IN ($no_spb) AND a.kode_lokasi='".$kode_lokasi."'
            ORDER BY a.no_pb";

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

    public function DataVerifikasi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_ver');
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

            $select = "SELECT a.no_ver, a.no_bukti, CONVERT(varchar,a.tanggal,103) AS tgl, a.periode, a.catatan, a.no_bukti,
            a.tanggal, b.kode_akun, b.kode_pp, b.kode_drk, c.nama AS nama_akun, f.keterangan, f.nilai, g.nama, 
            d.nama AS nama_pp,h.kota,
            CASE WHEN a.status ='1' THEN 'APPROVE'
                    WHEN a.status ='D' THEN 'APPROVE'
            else 'RETURN' END AS status ,
            CASE WHEN a.form='VERDOK' THEN 'VERIFIKASI DOKUMEN'
            else 'VERIFIKASI AKUN' END AS nama_ver
            FROM pbh_ver_m a
            LEFT JOIN pbh_pb_j b ON a.no_bukti=b.no_pb AND a.kode_lokasi=b.kode_lokasi AND b.dc='D'
            LEFT JOIN masakun c ON b.kode_akun=c.kode_akun AND b.kode_lokasi=c.kode_lokasi 
            LEFT JOIN pp d ON b.kode_pp=d.kode_pp AND b.kode_lokasi=d.kode_lokasi
            LEFT JOIN drk e ON b.kode_drk=e.kode_drk AND b.kode_lokasi=e.kode_lokasi AND SUBSTRING(b.periode,1,4)=e.tahun
            LEFT JOIN pbh_pb_m f ON b.no_pb=f.no_pb AND b.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan g ON a.nik_user=g.nik AND a.kode_lokasi=g.kode_lokasi
            INNER JOIN lokasi h ON a.kode_lokasi=h.kode_lokasi
            $where
            ORDER BY a.no_ver";

            $res = DB::connection($this->db)->select($select);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_rab'] = [];
                $success['data_beban'] = [];
                $success['sql'] = $sql;
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
