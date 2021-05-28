<?php

namespace App\Http\Controllers\Bangtel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
	public $successStatus = 200;
    public $guard = 'bangtel';
    public $db = 'dbbangtelindo';
    public $dark_color = array('#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7');

    public function getPeriode(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct a.periode,dbo.fnNamaBulan(a.periode) as nama
            from periode a
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,5,2) not in ('13','14','15','16','17')
            order by a.periode desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['periode_max'] = $res[0]['periode'];
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['periode_max'] = date('Ym');
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPP(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select  a.kode_pp,a.nama
            from pp a
            where a.kode_lokasi='$kode_lokasi'
            order by a.kode_pp desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['kode_pp_max'] = $res[0]['kode_pp'];
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['periode_max'] = date('Ym');
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBoxProject(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }else{
                $filter .="";
            }
            
			$sql="select COUNT(a.kode_proyek) as total,COUNT(CASE WHEN substring(i.status,1,1) = '6' THEN 1 ELSE NULL END) as selesai,COUNT(CASE WHEN substring(i.status,1,1) <> '6' THEN 1 ELSE NULL END) as berjalan
            from spm_proyek a
            left join (	select kode_proyek,
                        progress,
                        status,
                        kode_lokasi
                        from (select kode_proyek,
                            progress,
                            status,
                            kode_lokasi,
                            row_number() over(partition by kode_proyek order by no_bukti desc) as rn
                        from spm_proyek_prog) as T
                        where rn = 1  
                        ) i on a.kode_proyek=i.kode_proyek and a.kode_lokasi=i.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBoxPendapatan(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }else{
                $filter .="";
            }
            
			$sql="select sum(isnull(g.pdpt,0)) as pdpt, 0 as diterima, 0 as piutang
            from spm_proyek a
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as pdpt 
                       from spm_piutang_d 
                       where kode_lokasi='$kode_lokasi' 
                       group by kode_proyek,kode_lokasi
                      )g on a.kode_proyek=g.kode_proyek and a.kode_lokasi=g.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter";
            $success['sql'] = $sql;
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBoxProfit(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }else{
                $filter .="";
            }
            
			$sql="select sum(isnull(g.pdpt,0)) as pdpt,sum(isnull(f.beban,0)) as beban,sum(isnull(g.pdpt,0))-sum(isnull(f.beban,0)) as profit, 0 profit_lalu
            from spm_proyek a
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
                       from spm_proyek_reklas_d 
                       where kode_lokasi='$kode_lokasi' 
                       group by kode_proyek,kode_lokasi
                      )f on a.kode_proyek=f.kode_proyek and a.kode_lokasi=f.kode_lokasi
            left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as pdpt 
                       from spm_piutang_d 
                       where kode_lokasi='$kode_lokasi' 
                       group by kode_proyek,kode_lokasi
                      )g on a.kode_proyek=g.kode_proyek and a.kode_lokasi=g.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBoxBeban(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }
            
			$sql="select sum(isnull(f.beban,0)) as beban, 0 as terbayar, 0 as belum_bayar
            from spm_proyek a
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
                       from spm_proyek_reklas_d 
                       where kode_lokasi='$kode_lokasi' 
                       group by kode_proyek,kode_lokasi
                      )f on a.kode_proyek=f.kode_proyek and a.kode_lokasi=f.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getProjectAktif(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }
            
			$sql="select top 5 a.kode_proyek,a.kode_cust,b.nama as nama_cust,a.nilai,a.nilai_or,a.nama,a.kode_jenis,d.nama as nama_jenis,a.no_pks,
            a.kode_pp,c.nama as nama_pp ,convert(varchar(20),a.tgl_mulai,103) as tgl_mulai,convert(varchar(20),a.tgl_selesai,103) as tgl_selesai,
            isnull(e.bdd,0) as bdd,a.nilai_or-isnull(e.bdd,0) as saldo,sum(case when f.dc='D' then f.nilai else -f.nilai end) as npiu,isnull(h.reklas,0) as reklas,i.progress,i.status
            from spm_proyek a
            inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            inner join spm_proyek_jenis d on a.kode_jenis=d.kode_jenis and a.kode_lokasi=d.kode_lokasi
            left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bdd 
                      from spm_proyek_bdd 
                      where kode_lokasi='$kode_lokasi' 
                      group by kode_proyek,kode_lokasi
                      ) e on a.kode_proyek=e.kode_proyek and a.kode_lokasi=e.kode_lokasi
            left join spm_piutang_d f on a.kode_proyek=f.kode_proyek and a.kode_lokasi=f.kode_lokasi 
            left join spm_piutang_m g on f.no_piutang=g.no_piutang and f.kode_lokasi=g.kode_lokasi
               left join (select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as reklas 
            from spm_proyek_reklas_d
             where kode_lokasi='$kode_lokasi' 
            group by kode_proyek,kode_lokasi ) h on a.kode_proyek=h.kode_proyek and a.kode_lokasi=h.kode_lokasi 
            left join (	select kode_proyek,
                        progress,
                        status,
                        kode_lokasi
                        from (select kode_proyek,
                            progress,
                            status,
                            kode_lokasi,
                            row_number() over(partition by kode_proyek order by no_bukti desc) as rn
                        from spm_proyek_prog) as T
                        where rn = 1  
                        ) i on a.kode_proyek=i.kode_proyek and a.kode_lokasi=i.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter and i.status in ('1.ONGOING','1.ONGING') 
            group by h.reklas,a.kode_proyek,a.kode_cust,b.nama,a.nilai,a.nilai_or,a.nama,a.kode_jenis,d.nama,a.no_pks,a.kode_pp,c.nama,a.tgl_mulai,a.tgl_selesai,e.bdd,i.progress,i.status
            order by a.kode_proyek";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
        
}
