<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanRealAggController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yakes';
    public $sql = 'dbsapkug';

      
    function getRealBeban(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $jenis=$request->input('jenis')[1];
            if($jenis != "TOTAL"){
                $filter_jenis = " and a.jenis='".$jenis."' ";
            }else{
                $filter_jenis = "";
            }
            $tahun = substr($periode,0,4);
            $tahunseb = intval($tahun)-1;
            $bulan = substr($periode,4,2);
            $periodeseb = $tahunseb.$bulan;

            $sql = "select a.kode_klpakun,a.nama
            from dash_klp_akun a
            where a.jenis='Beban'
            order by a.kode_klpakun ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select a.kode_akun,b.kode_klpakun,a.nama,isnull(b.rea_now,0) as rea_now,isnull(b.rea_bef,0) as rea_bef,isnull(b.rea_now,0)-isnull(b.rea_bef,0) as selisih
            from masakun a
            inner join (select a.kode_akun,b.kode_klpakun,a.kode_lokasi, 
                           sum(case when periode between '".$tahun."01' and '$periode' then (case a.dc when 'D' then a.nilai else -a.nilai end) else 0 end) as rea_now,
                           sum(case when periode between '".$tahunseb."01' and '$periodeseb' then (case a.dc when 'D' then a.nilai else -a.nilai end) else 0 end) as rea_bef
                    from gldt a 
                    inner join dash_klp_akun_d b on a.kode_akun=b.kode_akun
                    inner join dash_klp_akun c on b.kode_klpakun=c.kode_klpakun
                    where a.kode_lokasi='$kode_lokasi' and c.jenis in ('Beban','CC') 
                    group by a.kode_akun,b.kode_klpakun,a.kode_lokasi
                    )b on a.kode_lokasi=b.kode_lokasi and a.kode_akun=b.kode_akun
            where a.kode_lokasi='$kode_lokasi'";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['status'] = true;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
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

    function getClaimCost(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $tahun = substr($periode,0,4);
            $tahunseb = intval($tahun)-1;
            $bulan = substr($periode,4,2);
            $periodeseb = $tahunseb.$bulan;

            $sql = "select substring(a.kode_pp,3,2) as kode_pp, b.rka_tahun, b.rka_now, isnull(c.rea_now,0) as rea_now, isnull(c.rea_before,0) as rea_before, (isnull(c.rea_now,0)/b.rka_tahun)*100 as persen_rka,  (isnull(c.rea_now,0)/b.rka_now)*100 as persen_now, ((isnull(c.rea_now,0)- isnull(c.rea_before,0)) / isnull(c.rea_before,0))*100 as yoy
            from pp a
            inner join (select case substring(a.kode_pp,1,2) when '99' then '00' else substring(a.kode_pp,1,2) end as kode_pp,
                sum(case when periode between '".$tahun."01' and '".$tahun."12' then a.nilai/1000000 else 0 end) as rka_tahun,
                sum(case when periode between '".$tahun."01' and '$periode' then a.nilai/1000000 else 0 end) as rka_now
                from dash_gar_lap a
                inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun 
                where b.jenis='CC'
                group by substring(a.kode_pp,1,2)
            ) b on substring(a.kode_pp,3,2)=b.kode_pp
            left join (select substring(b.kode_pp,3,2) as kode_pp,
                sum(case when periode between '".$tahun."01' and '$periode' then b.nilai/1000000 else 0 end) as rea_now,
                sum(case when periode between '".$tahunseb."01' and '$periodeseb' then b.nilai/1000000 else 0 end) as rea_before
                from dash_klp_akun a 
                inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun 
                where a.jenis='CC'
                group by substring(b.kode_pp,3,2)
            ) c on substring(a.kode_pp,3,2)=c.kode_pp
            where a.kode_lokasi='$kode_lokasi'
            ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select substring(a.kode_pp,3,2) as kode_pp, b.rka_tahun, b.rka_now, isnull(c.rea_now,0) as rea_now, isnull(c.rea_before,0) as rea_before, (isnull(c.rea_now,0)/b.rka_tahun)*100 as persen_rka,  (isnull(c.rea_now,0)/b.rka_now)*100 as persen_now, case isnull(rea_before,0) when 0 then 0 else ((isnull(c.rea_now,0)- isnull(c.rea_before,0)) / isnull(c.rea_before,0))*100  end as yoy
            from pp a
            inner join (select case substring(a.kode_pp,1,2) when '99' then '00' else substring(a.kode_pp,1,2) end as kode_pp,
                sum(case when periode between '".$tahun."01' and '".$tahun."12' then a.nilai/1000000 else 0 end) as rka_tahun,
                sum(case when periode between '".$tahun."01' and '$periode' then a.nilai/1000000 else 0 end) as rka_now
                from dash_gar_lap a
                inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun 
                where b.jenis='BP'
                group by substring(a.kode_pp,1,2)
            ) b on substring(a.kode_pp,3,2)=b.kode_pp
            left join (select substring(b.kode_pp,3,2) as kode_pp,
                sum(case when periode between '".$tahun."01' and '$periode' then b.nilai/1000000 else 0 end) as rea_now,
                sum(case when periode between '".$tahunseb."01' and '$periodeseb' then b.nilai/1000000 else 0 end) as rea_before
                from dash_klp_akun a 
                inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun 
                where a.jenis='BP'
                group by substring(b.kode_pp,3,2)
            ) c on substring(a.kode_pp,3,2)=c.kode_pp
            where a.kode_lokasi='$kode_lokasi'";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $res;
                $success['data_bp'] = $res2;
                $success['status'] = true;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_bp'] = [];
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
