<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanBPJSController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yakes';
    public $sql = 'dbsapkug';

    // BPJS
    function getPremiKapitasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);

            $sql = "select a.kode_bulan,a.nama,isnull(b.pensiun,0) as pensiun,isnull(b.pegawai,0) as pegawai, isnull(c.n1,0) as n1,isnull(c.n2,0) as n2, isnull(d.ni_akun,0) - isnull(c.n1,0) - isnull(c.n2,0) as n3,  isnull(d.ni_akun,0) as n4
                    from yk_bulan a 
                    
                    left join 
                    ( 
                    select substring(a.periode,5,2) as kode_bulan, 
                    sum(case when a.jenis='PENSIUN' then a.nilai else 0 end) as pensiun, 
                    sum(case when a.jenis='PEGAWAI' then a.nilai else 0 end) as pegawai 
                    from yk_bpjs_iuran a 
                    where substring(a.periode,1,4) = '".$tahun."' 
                    group by substring(a.periode,5,2) 
                    ) b on a.kode_bulan=b.kode_bulan 
                    
                    left join ( 
                    select substring(a.periode,5,2) as kode_bulan, 
                    sum(case when a.jenis='PENSIUN' and a.jenis_tpkk='TPKK' then a.nilai else 0 end) as n1, 
                    sum(case when a.jenis='PEGAWAI' and a.jenis_tpkk='TPKK' then a.nilai else 0 end) as n2
                    from yk_bpjs_kapitasi a 
                    where substring(a.periode,1,4) = '".$tahun."' 
                    group by substring(a.periode,5,2) 
                    )c on a.kode_bulan=c.kode_bulan 
                    
                    left join (
                    select substring(a.periode,5,2) as kode_bulan,sum(a.nilai) as ni_akun
                    from gldt a
                    where substring(a.periode,1,4) ='".$tahun."' and a.kode_akun='21060103' and a.dc='C' 
                    group by substring(a.periode,5,2) 
                    ) d on a.kode_bulan=d.kode_bulan 
                    where a.kode_bulan <= '".$bulan."'
                    order by a.kode_bulan ";

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

    function getClaimBPJS(Request $request){
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
            $bulan = substr($periode,4,2);

            $sql = "select 'TAGIHAN AWAL' as jenis,b.kode_biaya,b.nama as nama_biaya, 
            sum(case when a.kode_lokasi='01' then a.total else 0 end) as n1, 
            sum(case when a.kode_lokasi='02' then a.total else 0 end) as n2,
            sum(case when a.kode_lokasi='03' then a.total else 0 end) as n3,
            sum(case when a.kode_lokasi='04' then a.total else 0 end) as n4,
            sum(case when a.kode_lokasi='05' then a.total else 0 end) as n5,
            sum(case when a.kode_lokasi='06' then a.total else 0 end) as n6,
            sum(case when a.kode_lokasi='07' then a.total else 0 end) as n7,
            isnull(sum(a.total),0) as total 
            from yk_bpjs_biaya b 
            left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$tahun."01'  
            and '$periode' $filter_jenis
            group by b.kode_biaya,b.nama 
            union all 
            select 'CLAIM' as jenis,b.kode_biaya,b.nama as nama_biaya, 
            sum(case when a.kode_lokasi='01' then a.claim else 0 end) as n1, 
            sum(case when a.kode_lokasi='02' then a.claim else 0 end) as n2, 
            sum(case when a.kode_lokasi='03' then a.claim else 0 end) as n3, 
            sum(case when a.kode_lokasi='04' then a.claim else 0 end) as n4, 
            sum(case when a.kode_lokasi='05' then a.claim else 0 end) as n5, 
            sum(case when a.kode_lokasi='06' then a.claim else 0 end) as n6, 
            sum(case when a.kode_lokasi='07' then a.claim else 0 end) as n7,
            isnull(sum(a.total),0) as total 
            from yk_bpjs_biaya b 
            left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$tahun."01' 
            and '$periode' $filter_jenis
            group by b.kode_biaya,b.nama  
            union all 
            select 'DIBAYAR' as jenis,b.kode_biaya,b.nama as nama_biaya, 
            sum(case when a.kode_lokasi='01' then a.selisih else 0 end) as n1, 
            sum(case when a.kode_lokasi='02' then a.selisih else 0 end) as n2, 
            sum(case when a.kode_lokasi='03' then a.selisih else 0 end) as n3, 
            sum(case when a.kode_lokasi='04' then a.selisih else 0 end) as n4, 
            sum(case when a.kode_lokasi='05' then a.selisih else 0 end) as n5, 
            sum(case when a.kode_lokasi='06' then a.selisih else 0 end) as n6, 
            sum(case when a.kode_lokasi='07' then a.selisih else 0 end) as n7,
            isnull(sum(a.total),0) as total 
            from yk_bpjs_biaya b 
            left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$tahun."01' 
            and '$periode' $filter_jenis
            group by b.kode_biaya,b.nama ";

            $res = DB::connection($this->sql)->select($sql);
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
    

}
