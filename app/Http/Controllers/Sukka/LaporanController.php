<?php

namespace App\Http\Controllers\Sukka;

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
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function filterRpt($request,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($request->input($col_array[$i])[0])){
                if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                    $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
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
                }else if($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public function getAjuForm(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti[1];

            $sql="select *,b.nama as nama_pp,c.nama as nama_jenis 
            from apv_juskeb_m a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            left join apv_jenis c on a.kode_jenis=c.kode_jenis 
            where a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select * from (select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut,a.tanggal as tgl
                from apv_juskeb_m a
                left join apv_karyawan c on a.nik_buat=c.nik 
                left join apv_jab d on c.kode_jab=d.kode_jab 
                where a.no_bukti='$no_bukti'
                union all
                select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
                from apv_flow a
                inner join apv_juskeb_m b on a.no_bukti=b.no_bukti 
                inner join apv_karyawan c on a.nik=c.nik
                left join apv_jab d on a.kode_jab=d.kode_jab 
                inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
                where a.no_bukti='$no_bukti'
			) a
			order by a.no_app,a.tgl
            ";
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $i=0;
                foreach($res as $row){
                    $sql2 = "select a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.dc,a.nilai,
                    b.nama as nama_akun,c.nama as nama_pp,isnull(d.nama,'-') as nama_drk, 
                    case when a.dc='D' then a.nilai else 0 end debet,case when a.dc='C' then a.nilai else 0 end kredit
                    from apv_juskeb_d a
                    left join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring('".$row['periode']."',1,4)
                    where a.no_bukti='".$row['no_bukti']."' 
                    order by a.dc,a.kode_akun";
                    $res3 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res3),true);
                    $i++;
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRRAForm(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti[1];

            $sql = "select a.periode,convert(varchar,a.tanggal,103) as tanggal,a.no_pdrk,a.kode_lokasi,a.keterangan,a.nik_buat,b.nama as nama_buat
            from apv_pdrk_m a
            inner join apv_karyawan b on a.nik_buat=b.nik
            where a.no_pdrk='$no_bukti'
            order by a.no_pdrk";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select * from (select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut,a.tanggal as tgl
                from apv_pdrk_m a
                left join apv_karyawan c on a.nik_buat=c.nik 
                left join apv_jab d on c.kode_jab=d.kode_jab 
                where a.no_pdrk='$no_bukti'
                union all
                select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
                from apv_flow a
                inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk 
                inner join apv_karyawan c on a.nik=c.nik
                left join apv_jab d on a.kode_jab=d.kode_jab 
                inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
                where a.no_bukti='$no_bukti'
			) a
			order by a.no_app,a.tgl
            ";
            $res3 = DB::connection($this->db)->select($sql);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $i=0;
                foreach($res as $row){
                    $sql2 = "select a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.dc,a.nilai,
                    b.nama as nama_akun,c.nama as nama_pp,d.nama as nama_drk, 
                    case when a.dc='D' then a.nilai else 0 end debet,case when a.dc='C' then a.nilai else 0 end kredit
                    from apv_pdrk_d a
                    left join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring('".$row['periode']."',1,4)
                    where a.no_pdrk='".$row['no_pdrk']."' 
                    order by a.dc,a.kode_akun";
                    $res2 = DB::connection($this->db)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                    $i++;
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_app'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_app'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_app'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getPosisiJuskeb(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_lokasi','no_bukti','periode','kode_pp');
            $db_col_name = array('a.kode_lokasi','a.no_bukti','a.periode','a.kode_pp');
            $where = "";
            $where = $this->filterRpt($request,$col_array,$db_col_name,$where,"");
            $where = ($where == "" ? "" : "where ".substr($where,4));            

            $sql="select a.no_bukti,a.no_dokumen,a.kode_pp,a.kegiatan,a.latar,a.aspek,a.spesifikasi,a.rencana,a.periode,convert(varchar,a.tanggal,103) as tanggal,p.nama as nama_pp,
            a.nilai,
            case when a.progress = 'R' then 'Return Approval Juskeb' 
            when a.progress not in ('R','J') then isnull(x.nama_jab,'-')
            when a.progress = 'J' and isnull(c.progress,'-') ='-' then 'Finish Juskeb' 
            when a.progress = 'J' and c.progress ='P' then 'Finish RRA' 
            when a.progress = 'J' and c.progress ='R' then 'Return Approval RRA' 
            when a.progress = 'J' and c.progress not in ('R','P') then isnull(y.nama_jab,'-')
            end as posisi,
			isnull(e.nilai,0) as nilai_rra
            from apv_juskeb_m a
            left join apv_flow d on a.no_bukti=d.no_bukti and d.no_urut=0
            left join pp p on a.kode_pp=p.kode_pp
            left join apv_pdrk_m c on a.no_bukti=convert(varchar,c.justifikasi) 
            left join (select no_pdrk, sum(nilai) as nilai
					from apv_pdrk_d 
					where dc='D'
					group by no_pdrk
			) e on c.no_pdrk=e.no_pdrk
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab
                                where a.status='1'
                                )x on a.no_bukti=x.no_bukti
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab 
                                where a.status='1'
                                )y on c.no_pdrk=y.no_bukti
            $where 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $kode_lokasi = $request->input('kode_lokasi')[1];
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getHistoryAppJuskeb(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_lokasi = $request->input('kode_lokasi')[1];
            $no_bukti = $request->input('no_bukti')[1];

            $sql="select * from (
            select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,a.no_bukti,'Pengajuan' as status,-4 as nu, '-' as urut,a.tanggal as tgl,a.kegiatan as keterangan
			from apv_juskeb_m a
            left join apv_karyawan c on a.nik_buat=c.nik 
			left join apv_jab d on c.kode_jab=d.kode_jab
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl,e.keterangan
            from apv_flow a
			inner join apv_juskeb_m b on a.no_bukti=b.no_bukti
            inner join apv_karyawan c on a.nik=c.nik 
			left join apv_jab d on a.kode_jab=d.kode_jab
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl,e.keterangan
            from apv_flow a
			inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk
            inner join apv_karyawan c on a.nik=c.nik 
			left join apv_jab d on a.kode_jab=d.kode_jab
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and convert(varchar,b.justifikasi)='$no_bukti'
			) a
			order by a.urut,a.tgl
            ";
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPosisiRRA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_lokasi','no_bukti','periode','kode_pp');
            $db_col_name = array('a.kode_lokasi','a.no_pdrk','a.periode','a.kode_pp');
            $where = "";
            $where = $this->filterRpt($request,$col_array,$db_col_name,$where,"");
            $where = ($where == "" ? "" : "where ".substr($where,4));            

            $sql="select a.no_pdrk as no_bukti,a.no_dokumen,a.kode_pp,a.keterangan,a.periode,convert(varchar,a.tanggal,103) as tanggal,p.nama as nama_pp,
            case when a.progress = 'R' then 'Return Approval RRA' 
            when a.progress = 'P' then 'Finish RRA' 
            when a.progress not in ('R','P') then isnull(x.nama_jab,'-')
            end as posisi,
			isnull(e.nilai,0) as nilai_rra
            from apv_pdrk_m a
            left join apv_flow d on a.no_pdrk=d.no_bukti and d.no_urut=0
            left join pp p on a.kode_pp=p.kode_pp
            left join (select no_pdrk, sum(nilai) as nilai
					from apv_pdrk_d 
					where dc='D'
					group by no_pdrk
			) e on a.no_pdrk=e.no_pdrk
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab
                                where a.status='1'
                                )x on a.no_pdrk=x.no_bukti
                            
            $where 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $kode_lokasi = $request->input('kode_lokasi')[1];
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['lokasi'] = [];
            $success['data'] = "Error ".$e;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getHistoryAppRRA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_lokasi = $request->input('kode_lokasi')[1];
            $no_bukti = $request->input('no_bukti')[1];

            $sql="select * from (
            select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,a.no_pdrk as no_bukti,'Pengajuan' as status,-4 as nu, '-' as urut,a.tanggal as tgl,a.kegiatan as keterangan
			from apv_pdrk_m a
            left join apv_karyawan c on a.nik_buat=c.nik 
			left join apv_jab d on c.kode_jab=d.kode_jab
            where a.kode_lokasi='$kode_lokasi' and a.no_pdrk='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl,e.keterangan
            from apv_flow a
			inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk
            inner join apv_karyawan c on a.nik=c.nik 
			left join apv_jab d on a.kode_jab=d.kode_jab
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			) a
			order by a.urut,a.tgl
            ";
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
