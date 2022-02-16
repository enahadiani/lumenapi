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
                left join apv_jab d on c.kode_jab=d.kode_jab 
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
            inner join karyawan b on a.nik_buat=b.nik
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
                left join apv_jab d on c.kode_jab=d.kode_jab 
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

    
    

}
