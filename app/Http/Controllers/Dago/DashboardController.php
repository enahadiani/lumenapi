<?php

namespace App\Http\Controllers\Dago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public $successStatus = 200;
    public $db = "sqlsrvdago";
    public $guard = "dago";

    function dbResultArray($sql){
    
        $res = DB::connection($this->db)->select($sql);
        $res = json_decode(json_encode($res),true);
        return $res;
    }

    function execute($sql){
    
        $res = DB::connection($this->db)->select($sql);
        return $res;
    }

    public function getDataBox(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->execute("select count(a.no_peserta) as jumlah from dgw_peserta a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['jamaah'] = (count($res) ? $res[0]->jumlah : 0);

            $res = $this->execute("select count(a.no_reg) as jumlah from dgw_reg a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['reg'] = (count($res) ? $res[0]->jumlah : 0);

            $res = $this->execute("select count(a.no_kwitansi) as jumlah from dgw_pembayaran a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['pbyr'] = (count($res) ? $res[0]->jumlah : 0);

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTopAgen(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->dbResultArray("select top 7 a.no_agen,b.nama_agen,count(*) as jum
            from dgw_reg a
            inner join dgw_agent b on a.no_agen=b.no_agen and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            group by a.no_agen,b.nama_agen
            order by count(*) desc            
            ");
            $success['daftar'] = $res;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRegHarian(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->execute("select * from (select top 12 a.tgl_input,count(*) as jumlah
            from dgw_reg a
            where a.kode_lokasi='$kode_lokasi'
            group by a.tgl_input ) a
            order by a.tgl_input asc
            ");
            $result = array();
            if(count($res) > 0){
                foreach ($res as $row){
                    $result['reg'][] = floatval($row->jumlah);
                    $ctg[] = $row->tgl_input;
                }
            }
            
            $series[] = array("name"=>"Total Registrasi","data"=>$result['reg']);
            $success['series']=$series;
            $success['ctg']=$ctg;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKuotaPaket(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->dbResultArray(" select a.no_paket,a.no_jadwal,a.tgl_berangkat,b.nama, a.quota+a.quota_se+a.quota_e as quota,isnull(c.jum,0) as jum,  (a.quota+a.quota_se+a.quota_e) - isnull(c.jum,0) as sisa 
            from dgw_jadwal a
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi
            left join (
                    select a.no_paket,a.no_jadwal,a.kode_lokasi,count(*) as jum
                    from dgw_reg a
                    group by a.no_paket,a.no_jadwal,a.kode_lokasi
            ) c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            order by a.tgl_berangkat desc,a.no_paket
            ");
            $success['daftar']=$res;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKartu(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->nik)){

                if($request->nik != ""){
                    $filter = " and a.no_peserta='$nik' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql="select top 1 a.no_reg,a.no_peserta,a.no_paket,a.tgl_input,d.nama,e.nama_agen,convert(varchar(20),a.tgl_input,103) as tgl,
            a.harga+harga_room as paket,isnull(b.nilai,0) as tambahan,f.nama as nama_paket,a.no_agen
            from dgw_reg a
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=d.kode_lokasi
            inner join dgw_agent e on a.no_agen=e.no_agen and a.kode_lokasi=e.kode_lokasi
            inner join dgw_paket f on a.no_paket=f.no_paket and a.kode_lokasi=f.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai
            from dgw_reg_biaya a
            where a.kode_lokasi='$kode_lokasi'
            group by a.no_reg,a.kode_lokasi
            )b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter
            order by a.no_reg desc ";

            $success['daftar'] = $this->dbResultArray($sql);

            $sql="select a.no_reg,a.no_kwitansi,a.kode_lokasi,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,a.nilai_p,a.nilai_t
            from dgw_pembayaran a
			inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi
            inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and c.no_peserta='$nik'
            order by b.tanggal ";
            
            $success['daftar2'] = $this->dbResultArray($sql);

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDokumen(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->nik)){

                if($request->nik != ""){
                    $filter = " and a.no_peserta='$nik' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql="select top 1 a.no_reg,a.no_peserta,a.no_paket,a.tgl_input,d.nama,e.nama_agen,convert(varchar(20),a.tgl_input,103) as tgl,
            a.harga+harga_room as paket,isnull(b.nilai,0) as tambahan,f.nama as nama_paket,a.no_agen
            from dgw_reg a
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=d.kode_lokasi
            inner join dgw_agent e on a.no_agen=e.no_agen and a.kode_lokasi=e.kode_lokasi
            inner join dgw_paket f on a.no_paket=f.no_paket and a.kode_lokasi=f.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai
            from dgw_reg_biaya a
            where a.kode_lokasi='$kode_lokasi'
            group by a.no_reg,a.kode_lokasi
            )b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter
            order by a.no_reg desc ";

            $rs = $this->execute($sql);
            if(count($rs)>0){
                $no_reg = $rs[0]->no_reg;
                $success['daftar'] = $this->dbResultArray("select a.no_dokumen,a.deskripsi,a.jenis,isnull(convert(varchar,b.tgl_terima,111),'-') as tgl_terima,isnull(c.no_gambar,'-') as fileaddres 
                from dgw_dok a 
                left join dgw_reg_dok b on a.no_dokumen=b.no_dok and b.no_reg='$no_reg'
                left join dgw_scan c on a.no_dokumen=c.modul and c.no_bukti ='$no_reg' 
                where a.kode_lokasi='$kode_lokasi' order by a.no_dokumen");
            }else{
                $success['daftar'] = array();
            }      

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    

    
}
