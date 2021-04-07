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
    public $sql = 'tokoaws';

    function getAnggaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_akun','kode_pp','no_fa','kode_pp','catatan');
            $db_col_name = array('substring(x.periode,1,4)','x.kode_akun','x.kode_pp','x.no_fa','x.kode_pp','x.catatan');
            $where = "where x.kode_lokasi='$kode_lokasi'";
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
           
            $res = DB::connection($this->sql)->select($sql);
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
