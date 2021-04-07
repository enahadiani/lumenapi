<?php

namespace App\Http\Controllers\Esaku\Anggaran;

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

    function getAnggaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_akun','kode_pp');
            $db_col_name = array('substring(x.periode,1,4)','x.kode_akun','x.kode_pp');
            $where = "where x.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            $jenis="";
			if (isset($request->jenis) && $request->jenis[0] != "All")
			{
				if ($request->jenis[1]=="Investasi")
				{
					$jenis=" and y.jenis='Neraca' ";
				}
				else
				{
					$jenis=" and y.jenis='".$request->jenis[1]."' ";
				}
			}
			$modul="";
			if (isset($request->status) && $request->status[1]!="Berjalan")
			{
				$modul=" and x.modul='ORGI' ";
			}

            if (isset($request->periodik) && $request->periodik[1]=="Triwulan")
			{			
				$sql = " select a.kode_akun,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp,
                isnull(e.agg_tw1,0) as agg_tw1,isnull(e.agg_tw2,0) as agg_tw2,isnull(e.agg_tw3,0) as agg_tw3,isnull(e.agg_tw4,0) as agg_tw4,isnull(e.total,0) as total
         from (select x.kode_lokasi,x.kode_akun,x.kode_pp
               from anggaran_d x
               inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi 
               $where
               group by x.kode_lokasi,x.kode_akun,x.kode_pp) a
         inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
         inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
         left join (select x.kode_lokasi,x.kode_akun,x.kode_pp
                               , sum(case when substring(x.periode,5,2) between '01' and '03' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_tw1
                           , sum(case when substring(x.periode,5,2) between '04' and '06' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_tw2
                             , sum(case when substring(x.periode,5,2) between '07' and '09' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_tw3
                             , sum(case when substring(x.periode,5,2) between '10' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_tw4
                             , sum(case when substring(x.periode,5,2) between '01' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as total
                      from anggaran_d x
                      inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi $where
                    group by x.kode_lokasi,x.kode_akun,x.kode_pp) e on a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp  and a.kode_lokasi=e.kode_lokasi
       order by a.kode_akun,a.kode_pp ";
			}
			if (isset($request->periodik) && $request->periodik[1]=="Semester")
			{
				$sql="select a.kode_akun,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp,
                isnull(e.n1,0) as n1,isnull(e.n2,0) as n2,isnull(e.total,0) as total 
         from (select x.kode_lokasi,x.kode_akun,x.kode_pp
               from anggaran_d x
               inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi $where
               group by x.kode_lokasi,x.kode_akun,x.kode_pp) a
         inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
         inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
         left join (select x.kode_lokasi,x.kode_akun,x.kode_pp
                               , sum(case when substring(x.periode,5,2) between '01' and '06' then case when dc='D' then nilai else -nilai end else 0 end ) as n1
                           , sum(case when substring(x.periode,5,2) between '07' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as n2
                             , sum(case when substring(x.periode,5,2) between '01' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as total
                      from anggaran_d x
                      inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi $where
                    group by x.kode_lokasi,x.kode_akun,x.kode_pp) e on a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp  and a.kode_lokasi=e.kode_lokasi
       order by a.kode_akun,a.kode_pp";
			}
			if (isset($request->periodik) && $request->periodik[1]=="Bulanan")
			{
				$sql="select a.kode_akun,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp,
                isnull(e.agg_01,0) as n1,isnull(e.agg_02,0) as n2,isnull(e.agg_03,0) as n3,isnull(e.agg_04,0) as n4,
                isnull(e.agg_05,0) as n5,isnull(e.agg_06,0) as n6,isnull(e.agg_07,0) as n7,isnull(e.agg_08,0) as n8,
                isnull(e.agg_09,0) as n9,isnull(e.agg_10,0) as n10,isnull(e.agg_11,0) as n11,isnull(e.agg_12,0) as n12,isnull(e.total,0) as total
         from (select x.kode_lokasi,x.kode_akun,x.kode_pp
               from anggaran_d x
               inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi $where
               group by x.kode_lokasi,x.kode_akun,x.kode_pp) a
         inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
         inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
         left join (select x.kode_lokasi,x.kode_akun,x.kode_pp
                               , sum(case when substring(x.periode,5,2) between '01' and '01' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_01
                           , sum(case when substring(x.periode,5,2) between '02' and '02' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_02
                           , sum(case when substring(x.periode,5,2) between '03' and '03' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_03
                           , sum(case when substring(x.periode,5,2) between '04' and '04' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_04
                           , sum(case when substring(x.periode,5,2) between '05' and '05' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_05
                           , sum(case when substring(x.periode,5,2) between '06' and '06' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_06
                           , sum(case when substring(x.periode,5,2) between '07' and '07' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_07
                           , sum(case when substring(x.periode,5,2) between '08' and '08' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_08
                           , sum(case when substring(x.periode,5,2) between '09' and '09' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_09
                           , sum(case when substring(x.periode,5,2) between '10' and '10' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_10
                           , sum(case when substring(x.periode,5,2) between '11' and '11' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_11
                           , sum(case when substring(x.periode,5,2) between '12' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_12
                           , sum(case when substring(x.periode,5,2) between '01' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as total
                    from anggaran_d x
                      inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi $where
                    group by x.kode_lokasi,x.kode_akun,x.kode_pp) e on a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp and a.kode_lokasi=e.kode_lokasi
                    order by a.kode_akun,a.kode_pp";
			}
           
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

    function getRealAnggaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_akun','kode_pp');
            $db_col_name = array('substring(periode,1,4)','kode_akun','kode_pp');
            $where = "where kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            if (isset($request->periodik) && $request->periodik[1]=="Triwulan")
            {
                if (isset($request->realisasi) && $request->realisasi[1]=="Anggaran")
                {
                    $sql = "exec sp_agg_real_bulan_pp '".$kode_lokasi."','".$request->jenis[1]."','".$request->tahun[1]."','".$request->nik_user."'";
                }
                else
                {
                    $sql = "exec sp_agg_real_bulan_pp_gl '".$kode_lokasi."','".$request->jenis[1]."','".$request->tahun[1]."','".$request->nik_user."'";
                }
                
            }
            $exec = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2 = "select *,substring(periode,1,4) as tahun from glma_drk_tmp $where and nik_user='$request->nik_user' order by kode_akun";
			
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

    function getCapaiAnggaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            
            if (isset($request->realisasi) && $request->realisasi[1]=="Anggaran")
            {
                $sql = "exec sp_agg_pakai_pp '".$kode_lokasi."','".$request->jenis[1]."','".$request->periode[1]."','".$request->nik_user."'";
            }
            else
            {
                $sql = "exec sp_agg_pakai_pp_gl '".$kode_lokasi."','".$request->jenis[1]."','".$request->periode[1]."','".$request->nik_user."'";
            }

            $exec = DB::connection($this->db)->getPdo()->exec($sql);

            $sql2 = "select  a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk, a.nama_akun, a.nama_pp, a.nama_drk, a.periode, substring(a.periode,1,4) as tahun,
            n1,n2,n3,
            case when b.jenis='Pendapatan' then -n4 else n4 end as n4,
            case when b.jenis='Pendapatan' then -n5 else n5 end as n5
            from glma_drk_tmp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where and a.nik_user='$request->nik_user' order by a.kode_akun";
			
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

    function getKartuAnggaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_akun','kode_pp');
            $db_col_name = array('substring(a.periode,1,4)','a.kode_akun','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $periode = "";
            $this_in = "";

            $periode = $this->filterLap($request,$col_array,$db_col_name,$periode,$this_in);

            $tahun = $request->tahun[1];
            if (isset($request->jenis))
            {
                if($request->jenis[1]=="Investasi"){

                    $jenis= "Neraca";
                }else{
                    $jenis= $request->jenis[1];
                }
            }else{
                $jenis= "-";
            }

            $sql = "select a.kode_akun,a.kode_lokasi,a.kode_pp,b.nama as nama_akun,d.nama as nama_pp
				, sum(case when substring(a.periode,5,2)='01' then case when dc='D' then nilai else -nilai end else 0 end ) as n1
                , sum(case when substring(a.periode,5,2)='02' then case when dc='D' then nilai else -nilai end else 0 end ) as n2
	            , sum(case when substring(a.periode,5,2)='03' then case when dc='D' then nilai else -nilai end else 0 end ) as n3
	            , sum(case when substring(a.periode,5,2)='04' then case when dc='D' then nilai else -nilai end else 0 end ) as n4
				, sum(case when substring(a.periode,5,2)='05' then case when dc='D' then nilai else -nilai end else 0 end ) as n5
				, sum(case when substring(a.periode,5,2)='06' then case when dc='D' then nilai else -nilai end else 0 end ) as n6
                , sum(case when substring(a.periode,5,2)='07' then case when dc='D' then nilai else -nilai end else 0 end ) as n7
	            , sum(case when substring(a.periode,5,2)='08' then case when dc='D' then nilai else -nilai end else 0 end ) as n8
	            , sum(case when substring(a.periode,5,2)='09' then case when dc='D' then nilai else -nilai end else 0 end ) as n9
				, sum(case when substring(a.periode,5,2)='10' then case when dc='D' then nilai else -nilai end else 0 end ) as n10
				, sum(case when substring(a.periode,5,2)='11' then case when dc='D' then nilai else -nilai end else 0 end ) as n11
	            , sum(case when substring(a.periode,5,2)='12' then case when dc='D' then nilai else -nilai end else 0 end ) as n12
				, sum(case when substring(a.periode,5,2) between '01' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as total
            from anggaran_d a
            inner join masakun b on a.kode_akun=b.kode_akun and b.jenis='$jenis' and a.kode_lokasi=b.kode_lokasi 
            inner join lokasi c on a.kode_lokasi=c.kode_lokasi
            inner join pp d on a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi 
            $where 
            group by a.kode_akun,a.kode_lokasi,a.kode_pp,b.nama,d.nama
            order by a.kode_akun";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){

                    if (isset($request->realisasi) && $request->realisasi[1]=="Anggaran")
                    {
                        $sql2 = "select a.no_bukti,a.no_dokumen,convert(varchar(20),a.tanggal,103) as tgl,a.kode_akun,a.keterangan,a.kode_lokasi,a.periode,
                        case when a.dc='D' then nilai else 0 end as debet,case when a.dc='C' then nilai else 0 end as kredit
                        from (
                            select no_bukti,no_dokumen,kode_lokasi,tanggal,periode,kode_akun,kode_pp,dc,nilai,keterangan
                            from trans_j
                            where kode_lokasi='".$res[$i]['kode_lokasi']."' and substring(periode,1,4)='$tahun' and kode_akun='".$res[$i]['kode_akun']."' and kode_pp='".$res[$i]['kode_pp']."' 
                            --union all
                            --select no_kas as no_bukti,no_dokumen,kode_lokasi,tanggal,periode,kode_akun,kode_pp,dc,nilai,keterangan
                            --from kas_j
                            --where kode_lokasi='".$res[$i]['kode_lokasi']."' and substring(periode,1,4)='$tahun' and kode_akun='".$res[$i]['kode_akun']."' and kode_pp='".$res[$i]['kode_pp']."' 
                            )a
                        where a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                        order by a.tanggal";
                    }
                    else
                    {
                        $tabel ="(select a.* from gldt_h a
                        where a.kode_lokasi='".$res[$i]['kode_lokasi']."' and substring(a.periode,1,4)='$tahun' and a.kode_akun='".$res[$i]['kode_akun']."' and a.kode_pp='".$res[$i]['kode_pp']."' ".$periode."
                        union all 
                        select a.* from gldt a
                        where a.kode_lokasi='".$res[$i]['kode_lokasi']."' and substring(a.periode,1,4)='$tahun' and a.kode_akun='".$res[$i]['kode_akun']."' and a.kode_pp='".$res[$i]['kode_pp']."' ".$periode." ) ";
    
                        $sql2="select a.no_bukti,a.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.kode_akun,a.kode_pp,a.kode_drk,a.keterangan,a.kode_lokasi,a.periode,
                        case when a.dc='D' then nilai else 0 end as debet,case when a.dc='C' then nilai else 0 end as kredit 
                        from $tabel a order by a.tanggal ";
                    }
        
                    $res[$i]['detail'] = DB::connection($this->db)->select($sql2);
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

    function getLabaRugiAgg(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('periode');
            $where = "where kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $lev=$request->input('level')[1];
            
            $sql2="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                    case jenis_akun when 'Pendapatan' then -n1 else n1 end as n1, 
                    case jenis_akun when 'Pendapatan' then -n2 else n2 end as n2, 
                    case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
                    case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4,
                    case jenis_akun when  'Pendapatan' then -n5 else n5 end as n5,
                    case jenis_akun when  'Pendapatan' then -n6 else n6 end as n6,
                    case jenis_akun when  'Pendapatan' then -n7 else n7 end as n7
                    case jenis_akun when  'Pendapatan' then -n8 else n8 end as n8,
                    case jenis_akun when  'Pendapatan' then -n9 else n9 end as n9,
					case jenis_akun when  'Pendapatan' then -n10 else n10 end as n10,
					case jenis_akun when  'Pendapatan' then -n11 else n11 end as n11,
					case jenis_akun when  'Pendapatan' then -n12 else n12 end as n12,
					case jenis_akun when  'Pendapatan' then -n13 else n13 end as n13,
					case jenis_akun when  'Pendapatan' then -n14 else n14 end as n14
            from exs_neraca
			$where and modul='L' and kode_fs='$kode_fs' and periode='$periode'  and level_lap<='$lev'
			order by rowindex";
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

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $id = isset($request->id) ? $request->id : '-';
            $periode = $request->periode[1];
            $kode_fs = $request->kode_fs[1];

            $sql = "select a.kode_akun,c.nama,
            case c.jenis when 'Pendapatan' then -a.n1 else a.n1 end as n1, 
            case c.jenis when 'Pendapatan' then -a.n2 else a.n2 end as n2, 
            case c.jenis when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case c.jenis when 'Pendapatan' then -a.n4 else a.n4 end as n4, 
            case c.jenis when 'Pendapatan' then -a.n5 else a.n5 end as n5, 
            case c.jenis when  'Pendapatan' then -a.n6 else a.n6 end as n6,
            case c.jenis when 'Pendapatan' then -a.n7 else a.n7 end as n7, 
            case c.jenis when 'Pendapatan' then -a.n8 else a.n8 end as n8, 
            case c.jenis when  'Pendapatan' then -a.n9 else a.n9 end as n9,
            case c.jenis when  'Pendapatan' then -a.n10 else a.n10 end as n10,
            case c.jenis when  'Pendapatan' then -a.n11 else a.n11 end as n11,
            case c.jenis when  'Pendapatan' then -a.n12 else a.n12 end as n12,
            case c.jenis when  'Pendapatan' then -a.n13 else a.n13 end as n13,
            case c.jenis when  'Pendapatan' then -a.n14 else a.n14 end as n14
            from exs_glma_gar a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            where b.kode_fs='$kode_fs' and b.kode_lokasi='$kode_lokasi' and b.kode_neraca='$id' and a.periode='$periode' 
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

    function getLabaRugiAggUnit(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs','kode_pp');
            $db_col_name = array('a.periode','a.kode_fs','a.kode_pp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $lev=$request->input('level')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql2="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.kode_pp,
            case a.jenis_akun when 'Pendapatan' then -a.n1 else a.n1 end as n1, 
            case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end as n2, 
            case a.jenis_akun when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case a.jenis_akun when 'Pendapatan' then -a.n4 else a.n4 end as n4, 
            case a.jenis_akun when 'Pendapatan' then -a.n5 else a.n5 end as n5, 
            case a.jenis_akun when  'Pendapatan' then -a.n6 else a.n6 end as n6,
            case a.jenis_akun when 'Pendapatan' then -a.n7 else a.n7 end as n7, 
            case a.jenis_akun when 'Pendapatan' then -a.n8 else a.n8 end as n8, 
            case a.jenis_akun when  'Pendapatan' then -a.n9 else a.n9 end as n9,
            case a.jenis_akun when  'Pendapatan' then -a.n10 else a.n10 end as n10,
            case a.jenis_akun when  'Pendapatan' then -a.n11 else a.n11 end as n11,
            case a.jenis_akun when  'Pendapatan' then -a.n12 else a.n12 end as n12,
            case a.jenis_akun when  'Pendapatan' then -a.n13 else a.n13 end as n13,
            case a.jenis_akun when  'Pendapatan' then -a.n14 else a.n14 end as n14
            from exs_neraca_pp a
            $where and a.modul='L' and a.level_lap<=$lev
            order by a.rowindex";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
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

    function getLabaRugiAggUnitDetail(Request $request){
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

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $id = isset($request->id) ? $request->id : '-';
            $periode = $request->periode[1];
            $kode_fs = $request->kode_fs[1];
            $kode = $request->kode;

            $sql = "select a.kode_akun,c.nama,b.kode_fs,
            case c.jenis when  'Pendapatan' then -a.n1 else a.n1 end as n1,
            case c.jenis when  'Pendapatan' then -a.n2 else a.n2 end as n2,
            case c.jenis when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case c.jenis when  'Pendapatan' then -a.n4 else a.n4 end as n4,
            case c.jenis when  'Pendapatan' then -a.n5 else a.n5 end as n5,
            case c.jenis when  'Pendapatan' then -a.n6 else a.n6 end as n6,
            case c.jenis when  'Pendapatan' then -a.n7 else a.n7 end as n7, 
            case c.jenis when 'Pendapatan' then -a.n8 else a.n8 end as n8, 
            case c.jenis when  'Pendapatan' then -a.n9 else a.n9 end as n9,
            case c.jenis when  'Pendapatan' then -a.n10 else a.n10 end as n10,
            case c.jenis when  'Pendapatan' then -a.n11 else a.n11 end as n11,
            case c.jenis when  'Pendapatan' then -a.n12 else a.n12 end as n12,
            case c.jenis when  'Pendapatan' then -a.n13 else a.n13 end as n13,
            case c.jenis when  'Pendapatan' then -a.n14 else a.n14 end as n14, 3 as level_spasi
            from exs_glma_gar_pp a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            $where and b.kode_neraca='$id' and a.kode_pp='$kode'
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

    function getPengajuanRRA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','sts_pdrk','no_pdrk');
            $db_col_name = array('a.periode','a.sts_pdrk','a.no_pdrk');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $sql = "select a.periode,convert(varchar,a.tanggal,103) as tgl,a.no_pdrk,a.kode_lokasi,a.keterangan,a.nik_buat,b.nama as nama_buat,
            a.nik_app1,c.nama as nama_setuju,substring(a.periode,1,4) as tahun,d.kota,a.tanggal,b.email
            from rra_pdrk_m a
            inner join karyawan b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
            inner join karyawan c on a.nik_app1=c.nik and a.kode_lokasi=c.kode_lokasi
            inner join lokasi d on a.kode_lokasi=d.kode_lokasi
            $where order by a.no_pdrk";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){

                    $sql2 = "select a.kode_akun,a.periode,a.dc,a.nilai,
                    b.nama as nama_akun,
                    case when a.dc='D' then a.nilai else 0 end debet,case when a.dc='C' then a.nilai else 0 end kredit
                    from rra_pdrk_d a
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    where a.no_pdrk='".$res[$i]['no_pdrk']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.dc desc";
        
                    $res[$i]['detail'] = DB::connection($this->db)->select($sql2);
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

    function getApprovalRRA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul','no_app');
            $db_col_name = array('a.periode','a.modul','a.no_app');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $sql = "select a.no_app,convert(varchar,a.tanggal,103) as tanggal,a.modul,a.nik_app,b.nama,a.keterangan,a.periode,a.kode_lokasi
            from rra_app_m a
            inner join karyawan b on a.nik_app=b.nik and a.kode_lokasi=b.kode_lokasi $where order by a.no_app";
			
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){

                    $sql2 = "select a.modul,a.no_bukti,a.catatan,convert(varchar,b.tanggal,103) as tanggal,b.keterangan,
                    b.nik_buat,a.sts_pdrk
                    from rra_app_d a
                    inner join rra_pdrk_m b on a.no_bukti=b.no_pdrk and b.kode_lokasi=a.kode_lokbukti
                    inner join karyawan d on b.nik_buat=d.nik and b.kode_lokasi=d.kode_lokasi 
                    where a.no_app='".$res[$i]['no_app']."' and a.kode_lokasi='".$res[$i]['kode_lokasi']."'
                    order by a.no_bukti";
        
                    $res[$i]['detail'] = DB::connection($this->db)->select($sql2);
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

    function getPosisiRRA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','sts_pdrk','no_pdrk');
            $db_col_name = array('a.periode','a.sts_pdrk','a.no_pdrk');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            $where = $this->filterLap($request,$col_array,$db_col_name,$where,$this_in);

            $sql = "select a.no_pdrk,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.kode_pp,f.nama as nama_pp,a.kode_lokasi, 
            d.no_app,convert(varchar,d.tanggal,103) as tgl_app,isnull(g.nilai,0) as nilai,
                case when a.progress='1' then 'Approve' when a.progress='X' then 'Not Approve' else '-' end as status
            from rra_pdrk_m a  
            inner join karyawan b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi  
            inner join pp f on a.kode_pp=f.kode_pp and b.kode_lokasi=f.kode_lokasi  
            left join rra_app_d c on a.no_pdrk=c.no_bukti 
            left join rra_app_m d on c.no_app=d.no_app 
            left join (select no_pdrk,sum(nilai) as nilai	
                    from rra_pdrk_d 
                    where dc='D'
                    group by no_pdrk
                    )g on a.no_pdrk=g.no_pdrk 
            $where
            order by a.no_pdrk";
			
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
}
