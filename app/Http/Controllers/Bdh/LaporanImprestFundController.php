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

    public function DataKartuIF(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'nik');
            $db_col_name = array('a.periode', 'a.nik');
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

            $select1 = "SELECT distinct a.nik, b.nama, a.kode_pp, c.nama as nama_pp, a.periode 
            FROM if_nik a 
            INNER JOIN karyawan b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            INNER JOIN pp c ON a.kode_pp=c.kode_pp AND a.kode_lokasi=c.kode_lokasi
            $where
            ORDER BY a.nik";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0) {
                $nik = "";
                $tahun = "";
                $i=0;
                foreach($res1 as $row) { 
                    if($i == 0) {
                        $nik = "'".$row['nik']."'";
                        $tahun = "'".substr($row['periode'], 0, 4)."'";
                    } else {
                        $nik .= ", '".$row['nik']."'";
                        $tahun .= ", '".substr($row['periode'], 0, 4)."'";
                    }
                    $i++;
                }

                $select2 = "SELECT a.nik, a.no_kas AS no_bukti, b.tanggal, b.keterangan, b.nilai AS debet, 0 AS kredit, 
                CONVERT(varchar,b.tanggal,103) AS tgl, 1 AS nu 
                FROM if_nik a
                INNER JOIN kas_m b ON a.no_kas=b.no_kas AND a.kode_lokasi=b.kode_lokasi AND SUBSTRING(a.periode,1,4) IN ($tahun)
                WHERE a.nik IN ($nik) AND a.kode_lokasi='".$kode_lokasi."'
                UNION ALL
                SELECT a.nik_user AS nik, a.no_pb as no_bukti, a.tanggal, a.keterangan, 0 as debet, a.nilai as kredit,
                CONVERT(varchar,a.tanggal,103) as tgl, 2 as nu 
                FROM pbh_pb_m a
                WHERE a.nik_user IN ($nik) AND a.kode_lokasi='".$kode_lokasi."' AND a.nilai<>0 
                AND SUBSTRING(a.periode,1,4) IN ($tahun) AND a.modul='IFREIM'
                UNION ALL
                SELECT a.nik_user AS nik, a.no_pb as no_bukti, b.tanggal, a.keterangan, a.nilai AS debet, 0 AS kredit,
                CONVERT(varchar,b.tanggal,103) as tgl, 3 AS nu
                FROM pbh_pb_m a
                INNER JOIN kas_m b ON a.no_kas=b.no_kas AND a.kode_lokasi=b.kode_lokasi
                WHERE a.nik_user IN ($nik) AND a.kode_lokasi='".$kode_lokasi."' AND a.no_kas<>'-' 
                AND SUBSTRING(a.periode,1,4) IN ($tahun) AND a.modul='IFREIM'
                ORDER BY tanggal";

                $res2 = DB::connection($this->db)->select($select2);
                $res2 = json_decode(json_encode($res2),true);
            }

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

    public function DataPosisiImburseIF(Request $r) {
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

            $select1 = "SELECT a.no_pb, z.kode_project, CONVERT(varchar,a.tanggal,103) AS tgl, 
            a.keterangan, a.posted, a.nilai, d.no_dokumen AS no_dpc, ISNULL(j.jum_dok,0) AS jum_dok,
            CASE a.progress WHEN '0' THEN 'Pengajuan PB'
            WHEN 'D' THEN 'Ver Dok'
            WHEN '1' THEN 'Ver Akun'
            WHEN 'C' THEN 'Return Ver Dok'
            WHEN 'V' THEN 'Return Ver Akun'
            WHEN '2' THEN 'SPB'
            WHEN '3' THEN 'Dibayar'
            END AS progress, a.kode_pp, b.nama AS nama_pp, a.no_ver, CONVERT(varchar,c.tanggal,103) AS tgl_ver, 
            a.no_verdok, CONVERT(varchar,e.tanggal,103) AS tgl_verdok, a.no_kas, 
            CONVERT(varchar,g.tanggal,103) AS tgl_kas, a.no_spb, 
            CONVERT(varchar,d.tanggal,103) AS tgl_spb, a.no_fisik, 
            CONVERT(varchar,h.tanggal,103) AS tgl_fisik, a.no_pajak, 
            CONVERT(varchar,i.tanggal,103) AS tgl_pajak
            FROM pbh_pb_m a 
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            INNER JOIN hutang_m z ON a.no_pb=z.no_hutang AND a.kode_lokasi=z.kode_lokasi
            LEFT JOIN pbh_ver_m c ON a.no_ver=c.no_ver AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN spb_m d ON a.no_spb=d.no_spb AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN pbh_ver_m e ON a.no_verdok=e.no_ver AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN kas_m g ON a.no_kas=g.no_kas AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN pbh_ver_m h ON a.no_fisik=h.no_ver AND a.kode_lokasi=h.kode_lokasi
            LEFT JOIN pbh_ver_m i ON a.no_pajak=i.no_ver AND a.kode_lokasi=i.kode_lokasi
            left join (
                SELECT b.no_bukti, b.kode_lokasi, COUNT(b.no_gambar) AS jum_dok 
                FROM pbh_pb_m a
                INNER JOIN pbh_dok b ON a.no_pb=b.no_bukti AND a.kode_lokasi=b.kode_lokasi
                $where AND a.modul IN ('IFREIM','IFCLOSE')
                GROUP BY b.no_bukti, b.kode_lokasi
            ) j ON a.no_pb=j.no_bukti AND a.kode_lokasi=j.kode_lokasi
            $where AND a.modul IN ('IFREIM','IFCLOSE')
            ORDER BY a.no_pb";

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
            $where AND a.modul='IFREIM'
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0) { 
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
            }

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

            if(count($res1) > 0) { 
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
            }

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