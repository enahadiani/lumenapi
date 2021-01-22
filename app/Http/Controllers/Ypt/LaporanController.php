<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    public function sendMail(Request $request){
        $this->validate($request,[
            'email' => 'required'
        ]);
  
        $email = $request->email;
        try {
            $rs = $this->getNrcLajur($request);
            $res = json_decode(json_encode($rs),true);
            $data_array = $res["original"]["success"]["data"];
            Mail::to($email)->send(new LaporanNrcLajur($data_array));
            
            return response()->json(array('status' => true, 'message' => 'Sent successfully'), $this->successStatus); 
        } catch (Exception $ex) {
            return response()->json(array('status' => false, 'message' => 'Something went wrong, please try later.'), $this->successStatus); 
        } 
    }

    

    function getGlReportBukuBesar(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $get = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            if($periode == ""){
                $periode = date('Ym');
            }

            $sql="exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);

            $tmp = "";
            if (isset($request->jenis) && $request->jenis == "Tidak")
            {
                $tmp =" and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }
            
            $sql="select a.kode_lokasi,a.kode_akun,a.kode_pp,a.nama,a.so_awal,a.periode,b.nama as nama_pp
            from glma_pp_tmp a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.nik_user='$nik_user' and a.kode_lokasi='$kode_lokasi'  $tmp
            order by a.kode_akun
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql2="select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $filter order by a.no_bukti ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!"; 
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['sql'] = $sql;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportNeracaLajur(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $get = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            if($periode == ""){
                $periode = date('Ym');
            }

            $sql="exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,a.nama,a.kode_pp,b.nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from glma_pp_tmp a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            $filter and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
            if($request->input('trail') != ""){

                if ($request->input('trail') =="1")
                {
                    $sql = "select a.kode_akun,a.nama,a.kode_lokasi,a.kode_pp,c.nama as nama_pp,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
                if ($request->input('trail')=="2")
                {
                    $sql = "select a.kode_akun,a.nama,a.kode_pp,c nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportLabaRugi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            $kode_fs=$request->input('kode_fs');

            $sql="exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);
            $success['sql'] = $sql;
            $sql="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                        case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
            $success['sql2'] = $sql;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBuktiJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul','no_bukti');
            $db_col_name = array('a.periode','a.modul','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }


            $sql="select a.no_bukti,a.keterangan,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,
                        a.nik1,a.nik2,b.nama as nama1,c.nama as nama2
                from trans_m a 
                left join karyawan b on a.nik1=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi
                $where order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul','no_bukti');
            $db_col_name = array('a.periode','a.modul','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
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

    function getBukuBesar(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun');
            $db_col_name = array('a.periode','a.kode_akun');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            $sqlex="exec sp_glma_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->db)->update($sqlex);

            $tmp = "";
            if (isset($request->mutasi[1]) && $request->mutasi[1] == "Tidak")
            {
                $tmp =" and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }
            
            $sql="select a.kode_lokasi,a.kode_akun,b.nama,a.so_awal,a.periode
                from glma_tmp a
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where and a.nik_user='$nik_user' $tmp
                order by a.kode_akun ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $where .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql2="select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!"; 
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNrcLajur(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            // for($i = 0; $i<count($col_array); $i++){
            //     if($request->input($col_array[$i]) !=""){
            //         $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
            //     }
            // }
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            //$sqlex="exec sp_glma_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            //$res = DB::connection($this->db)->update($sqlex);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,b.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from exs_glma a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where   $mutasi
            order by a.kode_akun ";
            if(isset($request->trail[1])){
                if($request->input('trail')[1] != ""){
    
                    if ($request->input('trail')[1] == "1")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from exs_glma a
                        inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where  $mutasi
                        order by a.kode_akun";
                    }
                    if ($request->input('trail')[1] == "2")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from exs_glma a
                        inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where $mutasi
                        order by a.kode_akun";
                    }
                }
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = $sql;
				$success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNrcLajurGrid(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi' ";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            //$sqlex="exec sp_glma_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            //$res = DB::connection($this->db)->update($sqlex);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,b.nama,a.kode_pp,a.so_awal,a.debet,a.kredit,a.so_akhir,a.kode_induk 
            from exs_glma_lap a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where ";
            if(isset($request->trail[1])){
                if($request->input('trail')[1] != ""){
    
                    if ($request->input('trail')[1] == "1")
                    {
                        $sql = "select a.kode_akun,b.nama,a.kode_pp,a.so_awal,a.debet,a.kredit,a.so_akhir,a.kode_induk 
                        from exs_glma_lap a
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        $where ";
                    }
                    if ($request->input('trail')[1] == "2")
                    {
                        $sql = "select a.kode_akun,b.nama,a.kode_pp,a.so_awal,a.debet,a.kredit,a.so_akhir,a.kode_induk 
                        from exs_glma_lap a
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        $where ";
                    }
                }
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNrcLajurJejer(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            // for($i = 0; $i<count($col_array); $i++){
            //     if($request->input($col_array[$i]) !=""){
            //         $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
            //     }
            // }
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            //$sqlex="exec sp_glma_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            //$res = DB::connection($this->db)->update($sqlex);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,b.nama,a.kode_lokasi,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9
            from exs_glma_jejer a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where   $mutasi
            order by a.kode_akun ";
            if(isset($request->trail[1])){
                if($request->input('trail')[1] != ""){
    
                    if ($request->input('trail')[1] == "1")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9
                        from exs_glma_jejer a
                        inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where  $mutasi
                        order by a.kode_akun";
                    }
                    if ($request->input('trail')[1] == "2")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9
                        from exs_glma_jejer a
                        inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where $mutasi
                        order by a.kode_akun";
                    }
                }
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getNrcLajurPp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            // for($i = 0; $i<count($col_array); $i++){
            //     if($request->input($col_array[$i]) !=""){
            //         $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
            //     }
            // }
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            //$sqlex="exec sp_glma_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            //$res = DB::connection($this->db)->update($sqlex);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,b.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from exs_glma_pp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where   $mutasi
            order by a.kode_akun ";
            if(isset($request->trail[1])){
                if($request->input('trail')[1] != ""){
    
                    if ($request->input('trail')[1] == "1")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from exs_glma_pp a
                        inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where  $mutasi
                        order by a.kode_akun";
                    }
                    if ($request->input('trail')[1] == "2")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from exs_glma_pp a
                        inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where $mutasi
                        order by a.kode_akun";
                    }
                }
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getNeraca(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql= "exec sp_neraca2_dw '$kode_fs','A','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user';
            ";
            $res = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2="select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->db)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode="";
            if ($periode > $periode_aktif)
            {
                $nama_periode="<br>(UnClosing)";
            }

            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

           
            // $sql3="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
            //     from exs_neraca a
            //     $where and a.modul='A' 
            //     union all
            //     select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
            //     from exs_neraca a
            //     $where and a.modul='P'  ";
            $sql3 = "select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe,a.kode_induk
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='A'
            union all
            select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe,a.kode_induk
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='P' 
            order by a.kode_neraca";

            $nama="";
           
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['res']=$res;
                $success['sql3'] = $sql3;
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

    function getNeracaJamkespen(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql= "exec sp_neraca2_dw '$kode_fs','A','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user';
            ";
            $res = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2="select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->db)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode="";
            if ($periode > $periode_aktif)
            {
                $nama_periode="<br>(UnClosing)";
            }

            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

           
            // $sql3="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
            //     from exs_neraca a
            //     $where and a.modul='A' 
            //     union all
            //     select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
            //     from exs_neraca a
            //     $where and a.modul='P'  ";
            $sql3 = "select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='A'
            union all
            select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='P' 
            order by a.kode_neraca";

            $nama="";
           
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['res']=$res;
                $success['sql3'] = $sql3;
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

    function getNeracaJejer(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];

            //$sql= "exec sp_neraca_dw '$kode_fs','A','K','$level','$periode','$kode_lokasi','$nik_user' ";
            //$res = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2="select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->db)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode="";
            if ($periode > $periode_aktif)
            {
                $nama_periode="<br>(UnClosing)";
            }

           
            $sql3="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
                from exs_neraca_jejer a
                $where and a.modul='A' 
                union all
                select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
                from exs_neraca_jejer a
                $where and a.modul='P'  ";

            $nama="";
           
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['res']=$res;
                $success['sql3'] = $sql3;
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


    function getNeracaPp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs','kode_pp');
            $db_col_name = array('a.periode','a.kode_fs','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];

            //$sql= "exec sp_neraca_dw '$kode_fs','A','K','$level','$periode','$kode_lokasi','$nik_user' ";
            //$res = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2="select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->db)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode="";
            if ($periode > $periode_aktif)
            {
                $nama_periode="<br>(UnClosing)";
            }

           
            $sql3="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
                from exs_neraca_pp a
                $where and a.modul='A' 
                union all
                select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.n1,a.n2,a.n3,a.n4
                from exs_neraca_pp a
                $where and a.modul='P'  ";

            $nama="";
           
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['sql3'] = $sql3;
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getLabaRugi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user'; ";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='L'
            order by a.rowindex  ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            
            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiJejer(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode');
            $kode_fs=$request->input('kode_fs');

            //$sql="exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            //$res = DB::connection($this->db)->update($sql);
            //$success['sql'] = $sql;
            
            $sql="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,
                        a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9
                from exs_neraca_jejer a
                $where and a.modul='L' 
                order by a.rowindex ";
            //$success['sql2'] = $sql;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getLabaRugiPp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs','kode_pp');
            $db_col_name = array('a.periode','a.kode_fs','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];

            //$sql="exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            //$res = DB::connection($this->db)->update($sql);
            //$success['sql'] = $sql;
            
            $sql="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,
                        case a.jenis_akun when  'Pendapatan' then -a.n1 else a.n1 end as n1,
                        case a.jenis_akun when  'Pendapatan' then -a.n2 else a.n2 end as n2,
                        case a.jenis_akun when  'Pendapatan' then -a.n3 else a.n3 end as n3,
                        case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end as n4
                from exs_neraca_pp a
                $where and a.modul='L' 
                order by a.rowindex ";
            //$success['sql2'] = $sql;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getPerubahanAsetNeto(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_dw '$kode_fs','A','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user';";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='A'
            order by a.rowindex  ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            
            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getAsetNeto(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_dw '$kode_fs','A','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user';";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select a.kode_neraca,a.nama,a.n1,a.n2,a.level_spasi,a.tipe
            from neraca_tmp a
            where a.nik_user='$nik_user' and a.kode_fs='$kode_fs' and a.modul='A'
            order by a.rowindex  ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            
            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getArusKas(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_aruskas2 '$kode_lokasi','$kode_fs','$periode','".$tahunseb.$bln."','$nik_user';";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi, 
            case jenis_akun when  'Beban' then -n1 else -n1 end as n1,
            case jenis_akun when  'Beban' then -n2 else -n2 end as n2
            from neraca_tmp 
            where nik_user='$nik_user' and kode_fs='$kode_fs' 
            order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            
            $get = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_awal'] = $get[0]->tglakhir;
            
            $get2 = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahunseb."-".$bln."-01')+1,0)) ,112),7,2) as tglakhir");
            $success['tgl_akhir'] = $get2[0]->tglakhir;

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
    // LAPORAN ANGGARAN
    
    function getLabaRugiAgg(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            // $sql="exec sp_neraca2_gar_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            // $res = DB::connection($this->db)->update($sql);
            
            $sql2="
            select a.kode_neraca,a.kode_fs,kode_lokasi,a.nama,a.tipe,a.level_spasi,a.level_lap,
            case a.jenis_akun when  'Pendapatan' then -a.n1 else a.n1 end as n1,
            case a.jenis_akun when  'Pendapatan' then -a.n2 else a.n2 end as n2,
            case a.jenis_akun when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end as n4, a.rowindex
            from exs_neraca a
			$where and a.modul='L'
			order by a.rowindex";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiAggDetail(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;

            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            $id = isset($request->id) ? $request->id : '-';
            $periode = $request->periode[1];

            $sql = "select b.kode_fs,a.kode_akun,a.kode_akun+' - '+c.nama as nama,
            case c.jenis when 'Pendapatan' then -a.n1 else a.n1 end as n1, 
            case c.jenis when 'Pendapatan' then -a.n2 else a.n2 end as n2, 
            case c.jenis when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case c.jenis when 'Pendapatan' then -a.n4 else a.n4 end as n4,3 as level_spasi
            from exs_glma_gar a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            $where and b.kode_neraca='$id' and 
            (a.n1<>0 or a.n2<>0 or a.n3<>0 or a.n4<>0 or a.n5<>0 or a.n6<>0 or a.n7<>0 or a.n8<>0 or a.n9<>0)
            order by a.kode_akun" ;

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                $success["auth_status"] = 2;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiAggDir(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_gar_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
            case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
            case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2,
            case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
            case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
            from neraca_tmp 
            where modul='L' and nik_user='$nik_user' 
            order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiFak(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_gar_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
            case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
            case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2,
            case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
            case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
            from neraca_tmp 
            where modul='L' and nik_user='$nik_user' 
            order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiAggFak(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_gar_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
            case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
            case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2,
            case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
            case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
            from neraca_tmp 
            where modul='L' and nik_user='$nik_user' 
            order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiAggProdi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_gar_dw '$kode_fs','L','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->db)->update($sql);
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
            case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
            case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2,
            case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
            case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
            from neraca_tmp 
            where modul='L' and nik_user='$nik_user' 
            order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNeraca2(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('periode','kode_fs');
            $where = "where kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            // $sql="exec sp_neraca2_dw '$kode_fs','A','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";
            // $res = DB::connection($this->db)->update($sql);
            
            $sql="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,jenis_akun,level_spasi,n4 as n1,n5 as n2,rowindex 
                from exs_neraca 
                $where and modul='A'
			order by rowindex ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,jenis_akun,level_spasi,n4 as n1,n5 as n2,rowindex 
                from exs_neraca 
                $where and modul='P'
			order by rowindex ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res = array_merge($res,$res2);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNeraca2Detail(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;

            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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

            $id = isset($request->id) ? $request->id : '-';

            $sql = "select b.kode_fs,a.kode_akun,a.kode_akun+' - '+c.nama as nama,
            case c.jenis when 'Pendapatan' then -a.n4 else a.n4 end as n1, 
            case c.jenis when 'Pendapatan' then -a.n5 else a.n5 end as n2,3 as level_spasi
            from exs_glma_gar a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            $where and b.kode_neraca='$id' and 
            (a.n4<>0 or a.n5<>0)
            order by a.kode_akun" ;

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                $success["auth_status"] = 2;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    function getInvestasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="exec sp_neraca2_gar_dw '$kode_fs','N','S','1','$periode','".$tahunseb.$bln."','$kode_lokasi','$nik_user' ";	
            $res = DB::connection($this->db)->update($sql);
		
		    $sql2 = "select kode_neraca,kode_fs,kode_lokasi,tipe,nama,tipe,level_spasi,n3,n4,n1,n2
			from neraca_tmp 
			where modul='A' and nik_user='$nik_user' 
			order by rowindex ";
            $res = DB::connection($this->db)->select($sql2);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
