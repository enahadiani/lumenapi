<?php

namespace App\Http\Controllers\Esaku\Piutang;

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
    public $guard = 'toko';
    public $db = 'tokoaws';

    function filterLap($request,$col_array,$db_col_name,$where,$this_in){
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
                }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    function getCustomer(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust');
            $db_col_name = array('a.kode_cust');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $sql = "select a.kode_cust, a.nama, a.alamat, a.no_tel, a.email, a.npwp, a.pic, a.alamat2
            from cust a
            $where order by a.kode_cust";
			
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
                $success['data']=[];
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

    function getPengakuan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode','no_piutang');
            $db_col_name = array('a.kode_cust','a.periode','a.no_piutang');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $sql = "select a.no_piutang,a.kode_lokasi,a.no_dokumen,convert(varchar,a.tanggal,103) as tgl,a.keterangan,
            a.nilai,a.nilai_ppn,a.nilai+a.nilai_ppn as nilai,a.nilai as tagihan,b.nama as nama_cust
            from piutang_d a
            inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
            inner join trans_m c on a.no_piutang=c.no_bukti and a.kode_lokasi=c.kode_lokasi 
            $where order by a.no_piutang";
			
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
                $success['data']=[];
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

    function getKartuPiutang(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode','no_piutang');
            $db_col_name = array('a.kode_cust','a.periode','a.no_piutang');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $sql = "select a.kode_lokasi,a.no_piutang,a.no_dokumen,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai+a.nilai_ppn as nilai,b.nama as nama_cust
            from piutang_d a
            inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
            inner join trans_m c on a.no_piutang=c.no_bukti and a.kode_lokasi=c.kode_lokasi 
            $where 
            order by a.no_piutang";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar,b.tanggal,103) as tanggal,b.keterangan
                    from piubayar_d a
                    inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_piutang']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."' ";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res),true);
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
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

    function getSaldo(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode','no_piutang');
            $db_col_name = array('a.kode_cust','a.periode','a.no_piutang');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $sql = "select a.modul, a.kode_lokasi,a.no_piutang,a.akun_piutang,a.no_dokumen,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai+a.nilai_ppn as nilai,a.nilai_ppn,a.nilai as tagihan,b.nama as nama_cust,
            isnull(c.nilai,0) as nilai_kas,a.nilai+a.nilai_ppn-isnull(c.nilai,0) as saldo
            from piutang_d a
            inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
            left join (select a.no_piutang,a.kode_lokasi,sum(a.nilai) as nilai
                        from piubayar_d a
                        where a.kode_lokasi='$kode_lokasi' 
                        group by a.no_piutang,a.kode_lokasi
                    )c on a.no_piutang=c.no_piutang and a.kode_lokasi=b.kode_lokasi
            $where order by a.no_piutang";
                    
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
                $success['data']=[];
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

    function getAging(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode','no_piutang','akun_piutang');
            $db_col_name = array('a.kode_cust','a.periode','a.no_piutang','a.akun_piutang');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            $tgl_aging = $request->tgl_aging[1];
            if($tgl_aging == ""){
                $filta = "";
            }else{
                $filta = " and b.tanggal <='$tgl_aging' ";
            }
            $sql = "select a.kode_lokasi,a.keterangan,a.no_dokumen,a.no_piutang,convert(varchar,a.tanggal,103) as tgl,b.nama as nama_cust,a.nilai+a.nilai_ppn as tagihan,
            case when datediff(day,a.tanggal,'$tgl_aging')<=30 then a.nilai+a.nilai_ppn else 0 end as aging1,
            case when datediff(day,a.tanggal,'$tgl_aging') between 31 and 60 then a.nilai+a.nilai_ppn else 0 end as aging2,
            case when datediff(day,a.tanggal,'$tgl_aging') between 61 and 90 then a.nilai+a.nilai_ppn else 0 end as aging3,
            case when datediff(day,a.tanggal,'$tgl_aging')>90 then a.nilai+a.nilai_ppn else 0 end as aging4,
            isnull(c.nilai_kas,0) as nilai_kas,a.nilai+a.nilai_ppn-isnull(c.nilai_kas,0)  as saldo,case a.nilai+a.nilai_ppn-isnull(c.nilai_kas,0) when 0 then 0 else isnull(datediff(day,a.tanggal,'$tgl_aging'),0) end as jum_hari
            from piutang_d a
            inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
            left join (select a.no_piutang,a.kode_lokasi,sum(a.nilai) as nilai_kas
                        from piubayar_d a
                        inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' $filta
                        group by a.no_piutang,a.kode_lokasi
                        )c on a.no_piutang=c.no_piutang and a.kode_lokasi=b.kode_lokasi
            $where  order by a.no_piutang";
                    
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
                $success['data']=[];
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

    function getPiutangJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode','no_piutang');
            $db_col_name = array('a.kode_cust','a.periode','a.no_piutang');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $sql = "select e.nik1,a.no_piutang,a.kode_lokasi,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_lokasi,
            a.nik_user,b.nama as nama_buat,c.nama as nama_setuju, d.kota,a.keterangan,a.no_dokumen 
            from piutang_d a
            inner join lokasi d on d.kode_lokasi = a.kode_lokasi 
            left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
            inner join trans_m e on a.no_piutang=e.no_bukti and a.kode_lokasi=e.kode_lokasi 
            left join karyawan c on e.nik1=c.nik and e.kode_lokasi=c.kode_lokasi 
            $where and e.form in ('AKRU','PIUBILL','UNBILL')
            order by a.no_piutang";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                    from trans_j a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_piutang']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.nu ";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
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

    function getPiutangKas(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_bukti');
            $db_col_name = array('a.periode','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            
            $sql = "select a.no_bukti,a.no_dokumen,a.kode_lokasi,a.periode,a.tanggal,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_lokasi,
            a.nik_user,b.nama as nama_buat,b.jabatan as jabatan_buat,a.nik1,c.nama as nama_setuju,c.jabatan as jabatan_setuju,d.kota
            from trans_m a
            inner join lokasi d on a.kode_lokasi=d.kode_lokasi
            left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
            left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi $where and a.form='KBPIU' ";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                    from trans_j a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_bukti']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.nu ";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
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

   
}
