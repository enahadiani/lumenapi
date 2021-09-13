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
