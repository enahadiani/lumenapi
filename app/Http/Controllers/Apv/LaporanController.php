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
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';
    
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
