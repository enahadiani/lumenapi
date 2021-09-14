<?php
namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LaporanBebanController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function DataPosisiPertanggunganPB(Request $r) {
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, a.posted, a.nilai, 
            d.no_dokumen AS no_dpc, ISNULL(j.jum_dok,0) AS jum_dok,
            CASE a.progress WHEN '0' THEN 'Pengajuan PB' 
            WHEN 'D' THEN 'Ver Dok'
            WHEN '1' THEN 'Ver Akun'
            WHEN 'C' THEN 'Return Ver Dok'
            WHEN 'V' THEN 'Return Ver Akun'
            WHEN '2' THEN 'SPB'
            WHEN '3' THEN 'Dibayar'
            WHEN 'R' THEN 'Return Ver Dok'
            WHEN 'K' THEN 'Return Ver Pajak'
            END AS progress ,a.kode_pp, b.nama AS nama_pp, a.no_ver, CONVERT(varchar,c.tanggal,103) AS tgl_ver, 
            a.no_verdok, CONVERT(varchar,e.tanggal,103) AS tgl_verdok, a.no_kas, 
            CONVERT(varchar,g.tanggal,103) AS tgl_kas, a.no_spb, CONVERT(varchar,d.tanggal,103) AS tgl_spb, a.no_fisik, 
            CONVERT(varchar,h.tanggal,103) AS tgl_fisik, a.no_pajak, CONVERT(varchar,i.tanggal,103) AS tgl_pajak
            FROM pbh_pb_m a 
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN pbh_ver_m c ON a.no_ver=c.no_ver AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN spb_m d ON a.no_spb=d.no_spb AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN pbh_ver_m e ON a.no_verdok=e.no_ver AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN kas_m g ON a.no_kas=g.no_kas AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN pbh_ver_m h ON a.no_fisik=h.no_ver AND a.kode_lokasi=h.kode_lokasi
            LEFT JOIN pbh_ver_m i ON a.no_pajak=i.no_ver AND a.kode_lokasi=i.kode_lokasi
            LEFT JOIN ( 
                SELECT b.no_bukti, b.kode_lokasi, COUNT(b.no_gambar) AS jum_dok
                FROM pbh_pb_m a
                INNER JOIN pbh_dok b ON a.no_pb=b.no_bukti AND a.kode_lokasi=b.kode_lokasi
                $where AND a.modul IN ('PBBAU','PBADK')
                GROUP BY b.no_bukti, b.kode_lokasi
            ) j ON a.no_pb=j.no_bukti AND a.kode_lokasi=j.kode_lokasi
            $where AND a.modul IN ('PBBAU','PBADK')
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

    public function DataPB(Request $r) {
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, a.nilai, a.tanggal, 
            h.logo, h.alamat, h.kota, a.nik_user, a.nik_app, f.nama AS nama_user, g.nama AS nama_app, 
            SUBSTRING(a.periode,1,4) AS tahun
            FROM pbh_pb_m a 
            LEFT JOIN karyawan f ON a.nik_user=f.nik AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan g ON a.nik_app=g.nik AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN lokasi h ON a.kode_lokasi=h.kode_lokasi
            $where AND a.modul IN ('PBBAU','PBADK')
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            $no_pb = "";
            $tahun = "";
            $i=0;
            foreach($res1 as $row) { 
                if($i == 0) {
                    $no_pb = "'".$row['no_pb']."'";
                    $tahun = "'".$row['tahun']."'";
                } else {
                    $no_pb .= ", '".$row['no_pb']."'";
                    $tahun .= ", '".$row['tahun']."'";
                }
                $i++;
            }

            $select2 = "SELECT a.no_pb, a.kode_akun, a.kode_drk, a.kode_pp, a.kode_lokasi,
            b.nama AS nama_pp, c.nama AS nama_akun, d.nama AS nama_drk, ISNULL(a.nilai,0) AS nilai
            FROM (SELECT a.no_pb, a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk, SUM(a.nilai) as nilai
            FROM pbh_pb_j a 
            WHERE a.no_pb IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."'
            GROUP BY a.no_pb, a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk
            UNION ALL
            SELECT a.no_ptg, a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk, SUM(a.nilai) as nilai
            FROM ptg_j a
            WHERE a.no_ptg in ($no_pb) and a.kode_lokasi='".$kode_lokasi."' AND a.jenis='Beban'
            GROUP BY a.no_ptg, a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk
            )a
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi 
            INNER JOIN masakun c ON a.kode_akun=c.kode_akun AND a.kode_lokasi=c.kode_lokasi 
            LEFT JOIN drk d ON a.kode_drk=d.kode_drk AND a.kode_lokasi=d.kode_lokasi AND d.tahun IN ($tahun) 
            ORDER BY a.kode_akun";

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