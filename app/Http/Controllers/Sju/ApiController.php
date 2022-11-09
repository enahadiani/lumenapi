<?php

namespace App\Http\Controllers\Sju;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public $successStatus = 200;
    public $db = "sqlsrvsju";
    public $guard = "sju";

    public function getDataPolis(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'POLIS','dbnewsju'));
            // END SAVE

            $filter_periode = "";
            if(isset($r->periode) && $r->periode != ""){
                $filter_periode = " and d.periode='$r->periode' ";
            }

            $filter_no_polis = "";
            if(isset($r->no_polis) && $r->no_polis != ""){
                $filter_no_polis = " and d.no_dok='$r->no_polis' ";
            }

            $filter_kode_cob = "";
            if(isset($r->kode_cob) && $r->kode_cob != ""){
                $filter_kode_cob = " and d.kode_tipe='$r->kode_cob' ";
            }

            $filter_kode_tertanggung = "";
            if(isset($r->kode_tertanggung) && $r->kode_tertanggung != ""){
                $filter_kode_tertanggung = " and d.kode_cust='$r->kode_tertanggung' ";
            }

            $res = DB::connection($this->db)->select("
            select d.no_dok as no_polis,a.kode_vendor as kode_penanggung, e.nama as nama_penanggung,d.kode_cust as kode_tertanggung,c.nama as nama_tertanggung,d.kode_tipe as kode_cob,h.nama as nama_cob,convert(varchar,d.tgl_mulai,103)+' - '+convert(varchar,d.tgl_selesai,103) as periode_polis,d.objek as objek_pertanggungan,
            case when a.no_bill='-' then d.total else a.kurs*d.total end as nilai_petanggungan,
            case when a.no_bill='-' then a.premi else (a.premi*a.kurs) end as nilai_premi,
            convert(varchar,a.due_date,103) as tgl_jatuh_tempo,
            convert(varchar,l.tanggal,103) as tgl_bayar_premi,
            case when a.no_bill='-' then 'Polis'
                when a.no_bill<>'-' and isnull(g.no_bukti,'-')='-' then 'OutStanding'
                    when a.no_bill<>'-' and isnull(g.no_bukti,'-')<>'-' and isnull(g.no_kashut,'-')='-' then 'Lunas Premi'
                when a.no_bill<>'-' and isnull(g.no_bukti,'-')<>'-' and isnull(g.no_kashut,'-')<>'-' then 'Lunas'
                    else '-'
                end as status_periode_polis
            from sju_polis_termin a
            inner join sju_polis_m d on a.no_polis=d.no_polis and a.kode_lokasi=d.kode_lokasi
            inner join sju_cust c on d.kode_cust=c.kode_cust and d.kode_lokasi=c.kode_lokasi
            inner join sju_vendor e on a.kode_vendor=e.kode_vendor and a.kode_lokasi=e.kode_lokasi
            inner join sju_tipe h on d.kode_tipe=h.kode_tipe and d.kode_lokasi=h.kode_lokasi
            left join sju_polisbayar_d g on a.no_polis=g.no_polis and a.no_bill=g.no_bill and a.kode_lokasi=g.kode_lokasi and a.ke=g.ke and a.kode_vendor=g.kode_vendor
            left join trans_m l on g.no_bukti=l.no_bukti and g.kode_lokasi=l.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter_periode $filter_no_polis $filter_kode_cob $filter_kode_tertanggung
            order by d.kode_tipe,a.no_polis
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $i=0;
                foreach($res as $row){
                    $res[$i]['dokumen'] = DB::connection($this->db)->select("select b.no_dok as no_polis,'https://newsju.simkug.com/server/media/'+a.no_gambar as path_file,a.kode_jenis,a.nu as no_urut 
                    from sju_polis_dok a
                    inner join sju_polis_m b on a.no_polis=b.no_polis and a.kode_lokasi=b.kode_lokasi
                    where b.no_dok = '".$row['no_polis']."' ");
                    $i++;
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataCOB(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'COB','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_tipe 
            where kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataTertanggung(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'Tertanggung/Customer','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_cust 
            where kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataPenanggung(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'Penanggung/Vendor','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_vendor 
            where kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
   
}
