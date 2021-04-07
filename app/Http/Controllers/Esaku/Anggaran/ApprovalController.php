<?php

namespace App\Http\Controllers\Esaku\Anggaran;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }
    
    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
    }

    public function getAju(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select a.kode_pp+' - '+c.nama as pp,a.no_pdrk as no_bukti,b.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status  
				from rra_pdrk_m a 
				inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi 
				inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
				where a.modul = 'MULTI' and a.progress='0' and a.kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'status' => 'required|in:APPROVE,NONAPP,RETURN',
            'catatan' => 'required',
            'no_pdrk' => 'required',
            'keterangan' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = date('Ym');
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            
            $no_bukti = $this->generateKode("rra_app_m", "no_app", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");
            
            if ($request->status == "APPROVE") $vProg = "1"; 
            if ($request->status == "NONAPP") $vProg = "X"; 
            if ($request->status == "RETURN") $vProg = "R"; 

			$upd = DB::connection($this->db)->update("update rra_pdrk_m set progress='".$vProg."' where no_pdrk='".$request->no_pdrk."' and kode_lokasi='".$kode_lokasi."'");	

            if ($request->status == "APPROVE") {
                $ins = DB::connection($this->db)->insert("insert into anggaran_d (no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai_sat,nilai,dc,satuan,nik_user,tgl_input,modul)  		
                select a.no_pdrk,a.kode_lokasi,a.no_urut,a.kode_pp,a.kode_akun,a.kode_drk,1,a.periode,a.nilai,a.nilai,a.dc,'-',b.nik_user,getdate(),'RRA' 
                from rra_pdrk_d a inner join rra_pdrk_m b on a.no_pdrk=b.no_pdrk  
                where a.no_pdrk = '".$request->no_pdrk."' and a.dc ='D'"); 					
            }
            if ($request->status == "NONAPP") {
                $del = DB::connection($this->db)->update("delete from anggaran_d where no_agg='".$request->no_pdrk."'");
            }
							
			$ins = DB::connection($this->db)->insert("update a set a.no_del='".$no_bukti."' 
			from rra_app_m a inner join rra_app_d b on a.no_app=b.no_app and a.kode_lokasi=b.kode_lokasi 
			where b.no_bukti='".$request->no_pdrk."' and a.no_del='-' and a.kode_lokasi='".$kode_lokasi."'");					

			$ins = DB::connection($this->db)->insert("insert into rra_app_m(no_app, kode_lokasi,tanggal,keterangan,modul,periode,no_del,nik_buat,nik_app,nik_user,tgl_input,jenis_form) values 
                ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$request->keterangan."','PUSAT','".$periode."','-','".$nik."','".$nik."','".$nik."',getdate(),'APPPST')");

			$ins = DB::connection($this->db)->insert("insert into rra_app_d(no_app,modul,kode_lokasi,no_bukti,kode_lokbukti,sts_pdrk,catatan,status) values 
            ('".$no_bukti."','PUSAT','".$kode_lokasi."','".$request->no_pdrk."','".$kode_lokasi."','RRR','".$request->catatan."','".$request->status."')");
								
            DB::connection($this->db)->commit();
            $sts = true;
            $msg = "Data Approval RRA berhasil disimpan ";
                    
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Approval RRA gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function doLoadCtt($no_pdrk,$no_applama,$kode_lokasi)
    {
        try {
            

            $sql = "select distinct convert(varchar,a.tanggal,103) as tgl,tanggal 
            from rra_app_m a inner join rra_app_d b on a.no_app=b.no_app and a.kode_lokasi=b.kode_lokasi 
            where b.no_bukti='".$no_pdrk."' and b.kode_lokasi='".$kode_lokasi."' and a.no_app<>'".$no_applama."' 
            order by convert(varchar,a.tanggal,103) desc where b.no_bukti ='".$request->no_pdrk."' and b.kode_lokasi='".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);						
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){
                    $sql2 = "select b.catatan,a.no_app, convert(varchar,a.tanggal,103) as tgl,tanggal, convert(varchar,a.tgl_input,108) as jam,a.nik_user 
                    from rra_app_m a inner join rra_app_d b on a.no_app=b.no_app and a.kode_lokasi=b.kode_lokasi 
                    where b.no_bukti='".$no_pdrk."' and a.tanggal='".$res[$i]['tanggal']."' and a.kode_lokasi='".$kode_lokasi."' and a.no_app<>'".$no_applama."' 
                    order by a.tanggal desc,convert(varchar,a.tgl_input,108) desc
                    ";					
                    $res2 = DB::connection($this->db)->select($sql2);						
                    $res[$i]['detail']= json_decode(json_encode($res2),true);
                }
                $success['message'] = "Data Kosong!"; 
                $success['data'] = $res;
                $success['status'] = false;
                return $success;     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return $success;
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return $success;
        }
        
    }

    public function getAjuDet(Request $request)
    {
        $this->validate($request,[
            'no_pdrk' => 'required'
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select b.catatan,b.no_app from rra_app_m a inner join rra_app_d b on a.no_app=b.no_app and a.kode_lokasi=b.kode_lokasi and a.no_del='-' where b.no_bukti ='".$request->no_pdrk."' and b.kode_lokasi='".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);						
            $res= json_decode(json_encode($res),true);

			
			$sql2 = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_akun,c.nama as nama_akun,
				a.nilai,case a.dc when 'D' then 'PENERIMA' else 'PEMBERI' end as dc 
				from rra_pdrk_d a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
						inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
				where a.no_pdrk = '".$request->no_pdrk."'  order by a.dc";					
            $res2 = DB::connection($this->db)->select($sql2);						
            $res2= json_decode(json_encode($res2),true);
                
            $success['data_rra'] = $res2;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $res3 = $this->doLoadCtt($request->no_pdrk,$res[0]['no_app'],$kode_lokasi);
                $success['data_app'] = $res;
                $success['catatan'] = $res3['data'];
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data_app'] = [];
                $success['catatan'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    
}

