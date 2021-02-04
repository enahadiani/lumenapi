<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanKasBankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    function getBuktiJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_bukti');
            $db_col_name = array('a.periode','a.no_bukti');
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
                        a.nik1,a.nik2,b.nama as nama1,c.nama as nama2,d.nama as nama_lokasi,d.kota,a.no_ref1
                from trans_m a 
                left join karyawan b on a.nik1=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi
                inner join lokasi d on a.kode_lokasi=d.kode_lokasi
                $where and a.modul in ('KB','KBSPB','KBSPBPJ') order by a.no_bukti ";
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $i=0;
            foreach($rs as $row){

                if($i == 0){
                    $nb .= "'$row->no_bukti'";
                }else{
                    $nb .= ","."'$row->no_bukti'";
                }
                $i++;
            }

            $filter_nb = ($nb != "" ? " and no_bukti in ($nb) " : "" );

            $sql=" select a.no_bukti,a.kode_akun,b.nama as nama_akun,a.kode_pp,a.nilai,a.keterangan,isnull(a.debet,0) as debet,isnull(a.kredit,0) as kredit,a.kode_curr,c.nama as nama_pp
            from (
                select no_bukti,kode_lokasi,kode_akun,kode_pp,dc,nilai,keterangan,case when dc='D' then nilai else 0 end as debet,case when dc='C' then nilai else 0 end as kredit,kode_curr
                from trans_j
                where kode_lokasi='$kode_lokasi' and modul in ('KB','KBSPB','KBSPBPJ') $filter_nb 
            )a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            order by a.dc desc
            ";

                
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
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
            
            $col_array = array('periode','no_bukti');
            $db_col_name = array('a.periode','a.no_bukti');
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

            $sql=" select a.no_bukti,a.no_dokumen,c.nama,convert(varchar,a.tanggal,103) as tanggal,a.modul,a.form,
                b.kode_akun,b.kode_pp,b.kode_drk,a.posted,b.keterangan,case when b.dc='D' then b.nilai else 0 end as debet,case when b.dc='C' then b.nilai else 0 end as kredit 
                from trans_m a 
                inner join trans_j b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
                inner join masakun c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi 
                $where  and a.modul in ('KB','KBSPB','KBSPBPJ') order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
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
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBukuKas(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun');
            $db_col_name = array('a.periode','a.kode_akun');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $whereper = "where a.kode_lokasi ='$kode_lokasi' ";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                        if($col_array[$i] == "periode"){
                            $whereper .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                        }
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                        if($col_array[$i] == "periode"){
                            $whereper .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                        }
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
                        if($col_array[$i] == "periode"){
                            $whereper .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                        }
                    }
                }
            }
            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            $sqlex="exec sp_kas_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
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
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);
            $akun = "";
            $i=0;
            foreach($rs as $row){

                if($i == 0){
                    $akun .= "'$row->kode_akun'";
                }else{
                    $akun .= ","."'$row->kode_akun'";
                }
                $i++;
            }

            $filter_akun = ($akun != "" ? " and a.kode_akun in ($akun) " : "" );

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $where .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql2="select a.no_bukti,a.no_dokumen,convert(varchar,a.tanggal,103) as tgl,a.kode_akun,a.kode_pp,a.kode_drk,a.keterangan,b.modul,b.form,
            case when a.dc='D' then nilai else 0 end as debet,
            case when a.dc='C' then nilai else 0 end as kredit 
            from trans_j a 
            inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            $whereper $filter_akun order by a.tanggal,dc desc  ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
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

    function getSaldoKB(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
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

            $sqlex="exec sp_kas_dw_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->db)->update($sqlex);

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
            from glma_tmp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
           
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
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
    

}
