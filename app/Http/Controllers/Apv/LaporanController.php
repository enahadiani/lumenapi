<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'silo';
    public $db = 'dbsilo';
    
    function getPosisi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti','no_dokumen','kode_pp','kode_kota');
            $db_col_name = array('a.no_bukti','a.no_dokumen','a.kode_pp','a.kode_kota');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_bukti,a.no_dokumen,a.kode_pp,a.kode_kota,a.kegiatan,a.waktu,a.dasar,
            a.nilai as nilai_kebutuhan,isnull(c.nilai,0) as nilai_pengadaan,
            case when a.progress = 'A' then 'Verifikasi' 
            when a.progress='F' then 'Return Verifikasi' 
            when a.progress='R' then 'Return Approval' 
            when a.progress not in ('R','S','F') then isnull(x.nama_jab,'-')
            when a.progress = 'S' and isnull(c.progress,'-') ='-' then 'Finish Kebutuhan' 
            when a.progress = 'S' and c.progress ='S' then 'Finish Pengadaan' 
            when a.progress = 'S' and c.progress ='R' then 'Return Approval' 
            when a.progress = 'S' and c.progress not in ('R','S') then isnull(y.nama_jab,'-')
            end as posisi,
            isnull(b.MaxVer,'-') as no_ver,isnull(convert(varchar,b.tanggal,103),'-') as tgl_ver, 
            isnull(d.nik,'-') as nik_apprm,isnull(convert(varchar,d.tgl_app,103),'-') as tgl_apprm,
            isnull(c.no_bukti,'-') as no_juspo,isnull(convert(varchar,c.tanggal,103),'-') as tgl_pengadaan,
            isnull(e.nik,'-') as nik_app1,isnull(convert(varchar,e.tgl_app,103),'-') as tgl_app1,
            isnull(f.nik,'-') as nik_app2,isnull(convert(varchar,f.tgl_app,103),'-') as tgl_app2,
            isnull(g.nik,'-') as nik_app3,isnull(convert(varchar,g.tgl_app,103),'-') as tgl_app3,
            isnull(h.nik,'-') as nik_app4,isnull(convert(varchar,h.tgl_app,103),'-') as tgl_app4
            from apv_juskeb_m a
            left join (SELECT no_juskeb,kode_lokasi,tanggal,MAX(no_bukti) as MaxVer
                        FROM apv_ver_m
                        GROUP BY no_juskeb,kode_lokasi,tanggal
                        ) b on a.no_bukti=b.no_juskeb and a.kode_lokasi=b.kode_lokasi
            left join apv_flow d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and d.no_urut=0
            left join apv_juspo_m c on a.no_bukti=c.no_juskeb and a.kode_lokasi=c.kode_lokasi
            left join apv_flow e on c.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and e.no_urut=0
            left join apv_flow f on c.no_bukti=f.no_bukti and a.kode_lokasi=f.kode_lokasi and f.no_urut=1
            left join apv_flow g on c.no_bukti=g.no_bukti and a.kode_lokasi=g.kode_lokasi and g.no_urut=2
            left join apv_flow h on c.no_bukti=h.no_bukti and a.kode_lokasi=h.kode_lokasi and h.no_urut=3
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )x on a.no_bukti=x.no_bukti
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )y on c.no_bukti=y.no_bukti
                                       
            $filter ";
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getCattApp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti','no_dokumen','kode_pp','kode_kota');
            $db_col_name = array('a.no_bukti','a.no_dokumen','a.kode_pp','a.kode_kota');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select no_bukti from apv_juskeb_m a
            $filter ";
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            
            $nb = "";
            $i=0;
            foreach($rs as $row){
                if($i == 0){
                    $nb .= "'".$row->no_bukti."'";
                }else{

                    $nb .= ",'".$row->no_bukti."'";
                }
                $i++;
            }

            $sql2="
            select e.no_bukti as id,a.no_bukti,case e.status when 'V' then 'APPROVE' when 'F' then 'REVISI' else '-' end as status,e.keterangan,e.nik_user as nik,f.nama,-3 as no_urut,-4 as id2,convert(varchar,e.tanggal,103) as tanggal  
            from apv_juskeb_m a
            inner join apv_ver_m e on a.no_bukti=e.no_juskeb and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on e.nik_user=f.nik and e.kode_lokasi=f.kode_lokasi
            where a.no_bukti in ($nb) and a.kode_lokasi='$kode_lokasi' 

            union all
			select convert(varchar,e.id) as id,a.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tanggal 
            from apv_juskeb_m a
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where a.no_bukti in ($nb)  and a.kode_lokasi='$kode_lokasi' 

            union all
            select convert(varchar,e.id) as id,b.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,c.nik,f.nama,c.no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tanggal 
            from apv_juspo_m a
			inner join apv_juskeb_m b on a.no_juskeb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_flow c on e.no_bukti=c.no_bukti and e.kode_lokasi=c.kode_lokasi and e.no_urut=c.no_urut
            inner join apv_karyawan f on c.nik=f.nik and c.kode_lokasi=f.kode_lokasi
            where b.no_bukti in ($nb)  and a.kode_lokasi='$kode_lokasi'
            
            union all
            select convert(varchar,e.id) as id,b.no_bukti,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,e.keterangan,a.nik_buat as nik,f.nama,-1 as no_urut,e.id as id2,convert(varchar,e.tanggal,103) as tanggal
            from apv_juspo_m a
			inner join apv_juskeb_m b on a.no_juskeb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi
            inner join apv_karyawan f on a.nik_buat=f.nik and a.kode_lokasi=f.kode_lokasi
            where b.no_bukti in ($nb)  and a.kode_lokasi='$kode_lokasi' and e.modul='PO'
			order by id2 ";
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
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getRekapAju(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','kode_kota');
            $db_col_name = array('a.kode_pp','a.kode_kota');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.kode_pp,b.nama as nama_pp,a.kode_kota,c.nama as nama_kota,count(a.no_bukti) as jum 
            from apv_juskeb_m a
            inner join apv_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join apv_kota c on a.kode_kota=c.kode_kota and a.kode_lokasi=c.kode_lokasi
            $filter 
            group by a.kode_pp,b.nama,a.kode_kota,c.nama
            order by a.kode_pp,a.kode_kota
            ";
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getRekapAjuDetail(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_pp','kode_kota');
            $db_col_name = array('a.kode_pp','a.kode_kota');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_bukti as no_juskeb,a.no_dokumen,a.kegiatan as juskeb,a.dasar as latar_belakang,a.kode_divisi,a.nilai,isnull(a.pemakai,'-') as pic,isnull(b.nama,'-') as nama_divisi,isnull(c.no_bukti,'-') as no_juspo,isnull(c.nilai,0) as nilai_juspo,
            case when a.progress = 'A' then 'Verifikasi' 
                       when a.progress='F' then 'Return Verifikasi' 
                       when a.progress='R' then 'Return Approval' 
                       when a.progress not in ('R','S','F') then isnull(x.nama_jab,'-')
                       when a.progress = 'S' and isnull(c.progress,'-') ='-' then 'Finish Kebutuhan' 
                       when a.progress = 'S' and c.progress ='S' then 'Finish Pengadaan' 
                       when a.progress = 'S' and c.progress ='R' then 'Return Approval' 
                       when a.progress = 'S' and c.progress not in ('R','S') then isnull(y.nama_jab,'-')
                       end as posisi
            from apv_juskeb_m a
            left join apv_divisi b on a.kode_divisi=b.kode_divisi and a.kode_lokasi=b.kode_lokasi
            left join apv_juspo_m c on a.no_bukti=c.no_juskeb and a.kode_lokasi=c.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                                           from apv_flow a
                                           inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                           where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                           )x on a.no_bukti=x.no_bukti
                       left join (select a.no_bukti,b.nama as nama_jab
                                           from apv_flow a
                                           inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                                           where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                           )y on c.no_bukti=y.no_bukti
            $filter
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['sql'] = $sql;
            
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
