<?php

namespace App\Http\Controllers\Sekolah;

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
    public $guard = 'siswa';
    public $sql = 'sqlsrvtarbak';

    public function sendMail(Request $request){
        $this->validate($request,[
            'email' => 'required'
        ]);
  
        $email = $request->email;
        try {
            $rs = $this->getNilai($request);
            $res = json_decode(json_encode($rs),true);
            $data_array = $res["original"]["data"];
            $res = Mail::to($email)->send(new LaporanNilai($data_array));
            
            return response()->json(array('status' => true, 'message' => 'Sent successfully','res'=>$res), $this->successStatus); 
        } catch (Exception $ex) {
            return response()->json(array('status' => false, 'message' => 'Something went wrong, please try later.'), $this->successStatus); 
        } 
    }

    function getNilai(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','kode_ta','kode_kelas','kode_matpel');
            $db_col_name = array('a.kode_pp','a.kode_ta','a.kode_kelas','a.kode_matpel');
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

            $sql="select distinct a.kode_pp,a.kode_kelas,a.kode_matpel,a.kode_ta,b.nama as nama_ta,c.nama as nama_matpel,isnull(e.kkm,0) as kkm 
            from sis_nilai_m a
            inner join sis_ta b on a.kode_ta=b.kode_ta and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            inner join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
            left join sis_kkm e on d.kode_tingkat=e.kode_tingkat and a.kode_matpel=e.kode_matpel and a.kode_pp=e.kode_pp and a.kode_lokasi=e.kode_lokasi and a.kode_ta=e.kode_ta
            $where ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $kelas = "";
            $matpel = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $kelas .= "'$row->kode_kelas'";
                    $matpel .= "'$row->kode_matpel'";
                }else{

                    $kelas .= ","."'$row->kode_kelas'";
                    $matpel .= ","."'$row->kode_matpel'";
                }
                $i++;
            }

            // $sql2=" select a.nis, a.nama, a.kode_kelas, a.kode_pp from sis_siswa a 
            // where a.kode_lokasi='$kode_lokasi' and a.kode_pp ='".$request->kode_pp[1]."' and a.kode_kelas in ($kelas) ";
            $sql2 = "select a.nis,a.nama,a.kode_kelas,b.kode_matpel,a.kode_pp,isnull(b.n1,0) as n1,isnull(b.n2,0) as n2,isnull(b.n3,0) as n3
            from sis_siswa a 
            left join (select b.nis,b.kode_lokasi,b.kode_pp,a.kode_matpel,
                   avg(case when a.kode_jenis='PH1' then b.nilai else 0 end) as n1,
                   avg(case when a.kode_jenis='PH2' then b.nilai else 0 end) as n2,
                   avg(case when a.kode_jenis='PH3' then b.nilai else 0 end) as n3
            from sis_nilai_m a
            inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='".$request->kode_pp[1]."' 
            group by b.nis,b.kode_lokasi,b.kode_pp,a.kode_matpel
                    )b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='".$request->kode_pp[1]."' and a.kode_kelas in ($kelas) and b.kode_matpel in ($matpel)
            order by a.nis";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['sql2'] = $sql2;
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

    function getGuruKelas(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','nik_guru');
            $db_col_name = array('a.kode_pp','a.nik');
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

            $sql="select a.nik,a.nama,a.tugas,a.no_hp,dbo.fnGetGuruKelas(a.nik,a.kode_lokasi,a.kode_pp) as kelas,
                    dbo.fnGetGuruMatpel(a.nik,a.kode_lokasi,a.kode_pp) as matpel 
            from sis_guru a
            $where ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!"; 
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['data'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGuruMatpel(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','kode_ta','kode_kelas','kode_matpel','nik_guru');
            $db_col_name = array('c.kode_pp','c.kode_ta','c.kode_kelas','c.kode_matpel','c.nik');
            $where = "where c.kode_lokasi='$kode_lokasi'";
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

            $sql="select distinct substring(a.kode_tingkat,4,1) as kode_tingkat,b.kode_kelas,c.kode_matpel,d.nama as nama_matpel,d.skode,c.nik,e.nama as nama_guru
            from sis_tingkat a
            inner join sis_kelas b on a.kode_tingkat=b.kode_tingkat and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_guru_matpel_kelas c on b.kode_kelas=c.kode_kelas and b.kode_pp=c.kode_pp and b.kode_lokasi=c.kode_lokasi
            inner join sis_matpel d on c.kode_matpel=d.kode_matpel and c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
            inner join sis_guru e on c.nik=e.nik and c.kode_pp=e.kode_pp and c.kode_lokasi=e.kode_lokasi
            $where 
            order by substring(a.kode_tingkat,4,1) ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!"; 
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['data'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getSiswa(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','kode_ta','kode_kelas');
            $db_col_name = array('a.kode_pp','a.kode_ta','a.kode_kelas');
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

            $sql="select a.nis,a.nama
            from sis_siswa a
            $where and a.flag_aktif='1'
            order by a.nis ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!"; 
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['data'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    

}
