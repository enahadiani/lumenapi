<?php

namespace App\Http\Controllers\Esaku\Simpanan;

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

    function getAnggota(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_agg');
            $db_col_name = array('a.no_agg');
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $sql="select a.no_agg, a.kode_lokasi, a.nama, a.tempat, a.tgl_lahir, a.alamat, a.no_tel, a.bank, a.cabang, a.no_rek,
            a.nama_rek, a.flag_aktif, a.id_lain
            FROM  kop_agg a
            $where order by a.no_agg ";
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getSimpanan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_agg','no_simp');
            $db_col_name = array('a.no_agg','a.no_simp');
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $sql="select a.no_agg,a.kode_lokasi,b.nama,a.jenis,a.no_simp,a.kode_param,c.nama as nama_param,
            a.status_bayar,a.nilai,a.p_bunga,convert(varchar,a.tgl_tagih,103) as tgl_tagih
            from kop_simp_m a
            inner join kop_agg b on a.no_agg=b.no_agg and a.kode_lokasi=b.kode_lokasi
            inner join kop_simp_param c on a.kode_param=c.kode_param and a.kode_lokasi=b.kode_lokasi
            $where
            order by a.no_agg ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.no_simp,a.no_bill,b.tanggal,a.nilai,c.no_angs,
                    isnull(c.pokok,0) as pokok,isnull(c.bunga,0) as bunga,isnull(c.pokok,0)+isnull(c.bunga,0) as bayar,
                    a.kode_lokasi,
                    convert(varchar,b.tanggal,103) as tgl,convert(varchar,d.tanggal,103) as tgl_bayar,d.modul
                    from kop_simp_d a
                    inner join kop_simpbill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi
                    left join (select a.no_angs,a.kode_lokasi,a.no_bill, a.no_simp,
                                    sum(case when substring(a.akun_piutang,1,1)='1' then a.nilai else 0 end) as pokok,
                                    sum(case when substring(a.akun_piutang,1,1)<>'1' then a.nilai else 0 end) as bunga
                                from kop_simpangs_d a
                                where a.kode_lokasi='$kode_lokasi'
                                group by a.no_angs,a.kode_lokasi,a.no_bill, a.no_simp
                            )c on a.no_simp=c.no_simp and a.no_bill=c.no_bill 
                    inner join trans_m d on c.no_angs=d.no_bukti and c.kode_lokasi=d.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.no_simp='".$res[$i]['no_simp']."' 
                    order by b.tanggal";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getSaldoSimpanan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_agg');
            $db_col_name = array('a.no_agg');
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $periode = (isset($request->periode[1]) ? $request->periode[1] : '-');

            $sql="select a.no_agg,a.nama,a.kode_lokasi,
            ISNULL(c.nilai_sp,0)-ISNULL(d.nilai_sp,0) as tgk_sp,
            ISNULL(c.nilai_sw,0)-ISNULL(d.nilai_sw,0) as tgk_sw,
            ISNULL(c.nilai_ss,0)-ISNULL(d.nilai_ss,0) as tgk_ss,
            ISNULL(e.nilai_sp,0) as byr_sp,
            ISNULL(e.nilai_sw,0) as byr_sw,
            ISNULL(e.nilai_ss,0) as byr_ss,
            ISNULL(f.nilai_bunga,0) as nilai_bunga,
            ISNULL(c.nilai_sp,0)+ISNULL(c.nilai_sw,0)+ISNULL(c.nilai_ss,0) - (ISNULL(d.nilai_sp,0)+ISNULL(d.nilai_sw,0)+ISNULL(d.nilai_ss,0)) as tgk_total,
            ISNULL(e.nilai_sp,0)+ISNULL(e.nilai_sw,0)+ISNULL(e.nilai_ss,0)+ISNULL(f.nilai_bunga,0) as byr_total
            from kop_agg a
            inner join (select a.no_agg,a.kode_lokasi
                        from kop_simp_m a
                        where kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )b on a.no_agg=b.no_agg and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when b.jenis='SP' then a.nilai else 0 end) as nilai_sp,
                            sum(case when b.jenis='SW' then a.nilai else 0 end) as nilai_sw,
                            sum(case when b.jenis='SS' then a.nilai else 0 end) as nilai_ss
                        from kop_simp_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where  a.periode<'$periode' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )c on a.no_agg=c.no_agg and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when b.jenis='SP' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_sp,
                            sum(case when b.jenis='SW' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_sw,
                            sum(case when b.jenis='SS' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_ss
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi and a.jenis<>'BSIMP'
                        where a.periode<'$periode' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )d on a.no_agg=d.no_agg and a.kode_lokasi=d.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when b.jenis='SP' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_sp,
                            sum(case when b.jenis='SW' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_sw,
                            sum(case when b.jenis='SS' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_ss
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where a.periode='$periode' and a.kode_lokasi='$kode_lokasi' and a.jenis<>'BSIMP'
                        group by a.no_agg,a.kode_lokasi
                        )e on a.no_agg=e.no_agg and a.kode_lokasi=e.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when b.jenis='SS' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as nilai_bunga
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where a.periode<='$periode' and a.kode_lokasi='$kode_lokasi' and a.jenis='BSIMP'
                        group by a.no_agg,a.kode_lokasi
                        )f on a.no_agg=f.no_agg and a.kode_lokasi=f.kode_lokasi
            $where
            order by a.no_agg ";
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getAkruSimpanan(Request $request){
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $sql="select a.no_bukti,a.kode_lokasi,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_lokasi,
            a.nik_user,b.nama as nama_buat,a.nik1,c.nama as nama_setuju, d.kota,a.keterangan,a.no_dokumen 
                 from trans_m a
                 inner join lokasi d on d.kode_lokasi = a.kode_lokasi 
                 left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
                 left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi 
                 $where and a.form='GENBILL'
                 order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                    from trans_j a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_bukti']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.nu";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBayarSimpanan(Request $request){
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $sql="select a.no_bukti,a.kode_lokasi,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_lokasi,
            a.nik_user,b.nama as nama_buat,a.nik1,c.nama as nama_setuju, d.kota,a.keterangan,a.no_dokumen 
                 from trans_m a
                 inner join lokasi d on d.kode_lokasi = a.kode_lokasi 
                 left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
                 left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi 
                 $where and a.form in ('KBSIMP','LOAD')
                 order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                    from trans_j a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_bukti']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.nu";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBatalSimpanan(Request $request){
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $sql="select a.no_bukti,a.kode_lokasi,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_lokasi,
            a.nik_user,b.nama as nama_buat,a.nik1,c.nama as nama_setuju, d.kota,a.keterangan,a.no_dokumen 
                 from trans_m a
                 inner join lokasi d on d.kode_lokasi = a.kode_lokasi 
                 left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
                 left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi 
                 $where and a.form in ('BTLBILL')
                 order by a.no_bukti ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0; $i < count($res); $i++){
                    $sql2 = "select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                    from trans_j a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti='".$res[$i]['no_bukti']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.nu";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                }
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
                $success["auth_status"] = 2;   
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getRekapSimpanan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_agg');
            $db_col_name = array('a.no_agg');
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
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }
                }
            }

            $periode = (isset($request->periode[1]) ? $request->periode[1] : '-');

            $sql="select a.no_agg,a.nama,a.kode_lokasi,
            ISNULL(c.nilai,0) as nilai_sp,ISNULL(d.nilai,0) as nilai_ss,ISNULL(e.nilai,0) as nilai_sw,ISNULL(f.nilai,0) as bunga,
            ISNULL(c.nilai,0)+ISNULL(d.nilai,0)+ISNULL(e.nilai,0) as jumlah
            from kop_agg a
            inner join (select a.no_agg,a.kode_lokasi
                        from kop_simp_m a
                        group by a.no_agg,a.kode_lokasi
                        )b on a.no_agg=b.no_agg and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when a.dc='D' then a.nilai else -a.nilai end) as nilai
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='SP' and a.periode<='$periode' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )c on a.no_agg=c.no_agg and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when a.dc='D' then a.nilai else -a.nilai end) as nilai
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='SS' and a.periode<='$periode' and a.jenis='SIMP' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )d on a.no_agg=d.no_agg and a.kode_lokasi=d.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when a.dc='D' then a.nilai else -a.nilai end) as nilai
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='SW' and a.periode<='$periode' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )e on a.no_agg=e.no_agg and a.kode_lokasi=e.kode_lokasi
            left join (select a.no_agg,a.kode_lokasi,
                            sum(case when a.dc='D' then a.nilai else -a.nilai end) as nilai
                        from kop_simpangs_d a
                        inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='SS' and a.periode<='$periode' and a.jenis='BSIMP' and a.kode_lokasi='$kode_lokasi'
                        group by a.no_agg,a.kode_lokasi
                        )f on a.no_agg=f.no_agg and a.kode_lokasi=f.kode_lokasi
            $where
            order by a.no_agg ";
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
