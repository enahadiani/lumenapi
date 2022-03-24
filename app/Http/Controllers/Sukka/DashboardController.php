<?php

namespace App\Http\Controllers\Sukka;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Queue;

class DashboardController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function index(Request $r) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $q = "select a.no_bukti, convert(varchar,a.tanggal,103) as tgl, a.jenis, a.kegiatan as keterangan, a.nilai, case when a.progress = '0' then 'Pengajuan Juskeb' 
            when a.progress='B' then 'Return Approval' 
            when a.progress='R' then 'Return Approval'
            when a.progress in ('J') then 'Finish Juskeb' 
            when a.progress='J' and isnull(c.progress,'-') ='0' then 'Pengajuan RRA' 
            when a.progress in ('J') and isnull(c.progress,'-') ='R' then 'Return Approval RRA' 
            when a.progress in ('J') and isnull(c.progress,'-') ='B' then 'Return Approval RRA' 
            when a.progress='J' and isnull(c.progress,'-') ='P' then 'Finish RRA' 
            when a.progress='J' and isnull(c.progress,'-') not in ('R','B','J','0') then 'App Juspeng: '+isnull(y.nama_jab,'-')
            else 'App Juskeb: '+isnull(x.nama_jab,'-') end as posisi
            from apv_juskeb_m a
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join karyawan c on a.nik=c.nik
                    left join apv_jab b on c.kode_jab=b.kode_jab
                    where a.status='1'
                    )x on a.no_bukti=x.no_bukti 
            left join apv_pdrk_m c on a.no_bukti=convert(varchar,c.justifikasi)
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join karyawan c on a.nik=c.nik
                    left join apv_jab b on c.kode_jab=b.kode_jab
                    where a.status='1'
                    )y on c.no_pdrk=y.no_bukti 
            where a.kode_lokasi = '".$kode_lokasi."' and a.nik_buat='$nik' ";

            $res = DB::connection($this->db)->select($q);

            $success['status'] = true;
            $success['data'] = $res;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBox(Request $r) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $q = "select a.nik,a.nama,isnull(b.ret,0) as ret,isnull(d.selesai,0) as selesai,isnull(f.perlu,0) as perlu,isnull(h.sedang,0) as sedang
            from karyawan a
            left join (select a.nik_buat,a.kode_lokasi,count(*) as ret
                                    from apv_juskeb_m a
                                    where (a.progress in ('R')) 
                                    group by a.nik_buat,a.kode_lokasi
                                ) b on a.nik=b.nik_buat and a.kode_lokasi=b.kode_lokasi
            left join (select a.nik_buat,a.kode_lokasi,count(*) as selesai				
                                    from apv_juskeb_m a
                                    where a.progress in ('J') 
                                    group by a.nik_buat,a.kode_lokasi
                                ) d on a.nik=d.nik_buat and a.kode_lokasi=d.kode_lokasi
            left join ( select b.nik,a.kode_lokasi,count(*) as perlu				
                                    from apv_juskeb_m a
                                    inner join apv_flow b on a.no_bukti=b.no_bukti and b.status in ('1')
                                    group by b.nik,a.kode_lokasi
                                ) f on a.nik=f.nik and a.kode_lokasi=f.kode_lokasi
            left join (select a.nik_buat,a.kode_lokasi,count(*) as sedang
                                    from apv_juskeb_m a
                                    where a.progress not in ('R','J')
                                    group by a.nik_buat,a.kode_lokasi
                                ) h on a.nik=h.nik_buat and a.kode_lokasi=h.kode_lokasi
            where a.nik='$nik' and a.kode_lokasi='$kode_lokasi' ";

            $res = DB::connection($this->db)->select($q);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $jk['return'] = $res[0]->ret;
                $jk['sedang'] = $res[0]->sedang;
                $jk['perlu'] = $res[0]->perlu;
                $jk['selesai'] = $res[0]->selesai;
            }else{
                $jk['return'] = 0;
                $jk['sedang'] = 0;
                $jk['perlu'] = 0;
                $jk['selesai'] = 0;
            }

            $q = "select a.nik,a.nama,isnull(b.ret,0) as ret,isnull(d.selesai,0) as selesai,isnull(f.perlu,0) as perlu,isnull(h.sedang,0) as sedang
            from karyawan a
            left join (select a.nik_user,a.kode_lokasi,count(*) as ret
                                    from apv_pdrk_m a
                                    where (a.progress ='R') 
                                    group by a.nik_user,a.kode_lokasi
                                ) b on a.nik=b.nik_user and a.kode_lokasi=b.kode_lokasi
            left join (select a.nik_user,a.kode_lokasi,count(*) as selesai				
                                    from apv_pdrk_m a
                                    where a.progress in ('P') 
                                    group by a.nik_user,a.kode_lokasi
                                ) d on a.nik=d.nik_user and a.kode_lokasi=d.kode_lokasi
            left join ( select b.nik,a.kode_lokasi,count(*) as perlu				
                                    from apv_pdrk_m a
                                    inner join apv_flow b on a.no_pdrk=b.no_bukti and b.status in ('1')
                                    group by b.nik,a.kode_lokasi
                                ) f on a.nik=f.nik and a.kode_lokasi=f.kode_lokasi
            left join (select a.nik_user,a.kode_lokasi,count(*) as sedang
                                    from apv_pdrk_m a
                                    where a.progress not in ('P','R')
                                    group by a.nik_user,a.kode_lokasi
                                ) h on a.nik=h.nik_user and a.kode_lokasi=h.kode_lokasi
            where a.nik='$nik' and a.kode_lokasi='$kode_lokasi' ";

            $res2 = DB::connection($this->db)->select($q);

            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $rra['return'] = $res2[0]->ret;
                $rra['sedang'] = $res2[0]->sedang;
                $rra['perlu'] = $res2[0]->perlu;
                $rra['selesai'] = $res2[0]->selesai;
            }else{
                $rra['return'] = 0;
                $rra['sedang'] = 0;
                $rra['perlu'] = 0;
                $rra['selesai'] = 0;
            }
            $success['data'] = [
                'jk' => $jk,
                'rra' => $rra
            ];
            $success['status'] = true;
            $success['message'] = "Success!";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $jk['return'] = 0;
            $jk['sedang'] = 0;
            $jk['perlu'] = 0;
            $jk['selesai'] = 0;
            $rra['return'] = 0;
            $rra['sedang'] = 0;
            $rra['perlu'] = 0;
            $rra['selesai'] = 0;
            $success['data'] = [
                'jk' => $jk,
                'rra' => $rra
            ];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataReturn(Request $r) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $q = "  select b.* from apv_juskeb_m a
            inner join apv_pesan b on a.no_bukti=b.no_bukti 
            where a.nik_buat='$nik' and a.progress='R' and b.no_urut = 0 and b.status = 3 and a.kode_lokasi='$kode_lokasi'
            order by b.tanggal desc";

            $res = DB::connection($this->db)->select($q);

            $success['status'] = true;
            $success['data'] = $res;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
?>