<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class FilterController extENDs Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function DataPP(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $tahun = substr($r->query('periode'), 0, 4);

            $select = "SELECT a.kode_pp, a.nama
            FROM pp a
            INNER JOIN (
            SELECT a.kode_pp,a.kode_lokasi 
            FROM anggaran_d a
            WHERE substring(a.periode,1,4) =  '".$tahun."' and a.kode_lokasi = '".$kode_lokasi."'
            GROUP BY a.kode_pp, a.kode_lokasi
            ) b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            WHERE a.tipe = 'POSTING' AND a.kode_lokasi = '".$kode_lokasi."'";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNIK(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT a.nik, b.nama FROM if_nik a
            INNER JOIN karyawan b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi 
            WHERE a.kode_lokasi = '".$kode_lokasi."'";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT DISTINCT no_pj, keterangan FROM panjar_m $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriodePanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM panjar_m WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiPB(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT DISTINCT no_pb, keterangan FROM pbh_pb_m $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriodePB(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM pbh_pb_m WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiJurnalFinalPertanggungPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT DISTINCT no_bukti, keterangan FROM gldt $where
            UNION SELECT DISTINCT no_bukti, keterangan FROM gldt_h $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriode(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM periode WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiBayar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT no_kas, keterangan FROM kas_m $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriodeBayar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM kas_m WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiSPB(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT no_spb, keterangan FROM spb_m $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriodeSPB(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM spb_m WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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

    public function DataNoBuktiVerifikasi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='".$kode_lokasi."'";

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

            $select = "SELECT no_ver, catatan AS keterangan FROM pbh_ver_m $where";

            $res = DB::connection($this->db)->select($select);
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

    public function DataPeriodeVerifikasi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $select = "SELECT DISTINCT periode FROM pbh_ver_m WHERE 
            kode_lokasi = '".$kode_lokasi."' ORDER BY periode";

            $res = DB::connection($this->db)->select($select);
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
}
