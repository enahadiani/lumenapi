<?php

namespace App\Http\Controllers\Esaku\Aktap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AktapController extends Controller
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'tgl_perolehan' => 'required|date_format:Y-m-d',
            'jumlah' => 'required',
            'deskripsi' => 'required',
            'no_seri' => 'required',
            'merk' => 'required',
            'tipe' => 'required',
            'nilai' => 'required',
            'residu' => 'required',
            'total' => 'required',
            'tgl_susut' => 'required',
            'kode_pp1' => 'required',
            'kode_pp2' => 'required',
            'kode_klpfa' => 'required',
            'kode_klpakun' => 'required',
            'kode_akun' => 'required',
            'umur' => 'required',
            'persen' => 'required',
            'no_bukti' => 'required'
        ]);

        
        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $per = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
            ");
            if(count($per) > 0){
                $peraktif = $per[0]->periode;
            }else{
                $peraktif = "0";
            }
            $pernext = 1;
            $periode = substr($request->tgl_perolehan,0,4).substr($request->tgl_perolehan,5,2);
            $msg = "";
            if (intval($peraktif) < intval($periode)) {
                // if ($pernext == "1"){
                //     $msg .= "Periode transaksi melebihi periode aktif sistem.[".$peraktif."]". "Data akan disimpan?";
                //     $sts = true;
                // }else {
                    $msg .= "Periode transaksi tidak valid.Periode transaksi tidak boleh melebihi periode aktif sistem.[".$peraktif."]";
                    $sts = false;
                // }
            }else{
                $sts = true;
            }

            if($sts){
                $periodeSusut = substr($request->tgl_susut,0,4).substr($request->tgl_susut,5,2);
                $nbfa = $nbfa2 = $this->generateKode("fa_asset", "no_fa", $kode_lokasi."-FA".substr($periode,2, 4).".", "001");
                $nbfa = substr($nbfa,0, 10);
                $idx = floatval(substr($nbfa2,10, 3));
                $nu = $idx2 = "";
                $jml = floatval($request->jumlah);
                $nsusut = round(floatval($request->nilai) / floatval($request->umur));
                for ($i = 0; $i < $jml; $i++) {
                    $idx2 = $idx;
                    if (strlen($idx2) == 1) $nu = "00".$idx2;
                    if (strlen($idx2) == 2) $nu = "0".$idx2;
                    if (strlen($idx2) == 3) $nu = $idx2;
                    
                    $nbfa2 = $nbfa.$nu;
                    $ins[$i] = DB::connection($this->db)->insert("insert into fa_asset(no_fa,kode_lokasi,kode_klpfa,kode_klpakun,kode_akun,umur,persen,nama,merk,tipe,no_seri,nilai,nilai_residu,kode_pp,kode_pp_susut,tgl_perolehan,tgl_susut,periode,periode_susut,progress,nik_user,tgl_input,catatan,kode_lokfa,nik_pnj,nilai_susut,jenis,akum_nilai) values ('".$nbfa2."','".$kode_lokasi."','".$request->kode_klpfa."','".$request->kode_klpakun."','".$request->kode_akun."',".floatval($request->umur).",".floatval($request->persen).",'".$request->deskripsi."','".$request->merk."','".$request->tipe."','".$request->no_seri."',".floatval($request->nilai).",".floatval($request->residu).",'".$request->kode_pp1."','".$request->kode_pp2."','".$request->tgl_perolehan."','".$request->tgl_susut."','".$periode."','".$periodeSusut."','2','".$nik."',getdate(),'".$request->no_bukti."','-','-',".$nsusut.",'A',0)");
    
                    $ins2[$i] = DB::connection($this->db)->insert("insert into fa_nilai(no_fa,kode_lokasi,no_bukti,dc,nilai,periode) values ('".$nbfa2."','".$kode_lokasi."','".$request->no_bukti."','D',".floatval($request->nilai).",'".$periode."')");
                    $idx = $idx + 1;
                }
                $sts = true;
                $msg .= "Data Aktiva Tetap berhasil disimpan. ";  
                $success['no_fa'] = $nbfa2;
                DB::connection($this->db)->commit();
            }

            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function getKlpAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_klpfa) && $request->kode_klpfa != ""){
                $filter .= " and a.kode_klpfa = '$request->kode_klpfa'  ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_klpakun) && $request->kode_klpakun != ""){
                $filter .= " and a.kode_klpakun = '$request->kode_klpakun'  ";
            }else{
                $filter .= "";
            }
            $res = DB::connection($this->db)->select("select a.kode_klpakun,b.nama,b.kode_akun,c.nama as nama_akun,b.umur,b.persen 
            from fa_klp a 
            	 inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi 
            	 inner join masakun c on b.kode_akun=c.kode_akun and c.kode_lokasi = '$kode_lokasi'
            where a.kode_lokasi='$kode_lokasi' $filter ");	
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp = '$request->kode_pp'  ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_pp, a.nama 
            from pp a
            where a.flag_aktif ='1'
            and a.kode_lokasi='$kode_lokasi' $filter ");	
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAktap(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_fa, nama from fa_asset where jenis='A' and kode_lokasi='".$kode_lokasi."' ");
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_fa' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.periode,a.periode_susut,convert(varchar,a.tgl_perolehan,103) as tgl_perolehan,a.nama,a.no_seri,a.merk,a.tipe,a.nilai_residu,a.kode_pp,a.kode_pp_susut 
            ,c.kode_klpfa+'-'+c.nama as klpfa,d.kode_klpakun+'-'+d.nama as klpakun,d.kode_akun+'-'+e.nama as akun, a.umur,a.persen,a.catatan 
            ,zz.nilai as holeh, isnull(b.susut,0) as susut 
            from fa_asset a 
                    inner join (select no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                                from fa_nilai where kode_lokasi='".$kode_lokasi."' 
                            group by kode_lokasi,no_fa) zz on a.no_fa=zz.no_fa  
                    inner join fa_klp c on a.kode_klpfa=c.kode_klpfa and a.kode_lokasi=c.kode_lokasi 
                    inner join fa_klpakun d on a.kode_klpakun=d.kode_klpakun and a.kode_lokasi=d.kode_lokasi 
                    inner join masakun e on d.kode_akun=e.kode_akun and d.kode_lokasi=e.kode_lokasi 
                    left join (select no_fa,sum(case dc when 'D' then nilai else -nilai end) as susut 
                            from fasusut_d where kode_lokasi ='".$kode_lokasi."' 
                            group by no_fa) b on a.no_fa=b.no_fa
            where a.no_fa='".$request->no_fa."' and a.kode_lokasi='".$kode_lokasi."' ");
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_fa' => 'required',
            'nama' => 'required',
            'no_seri' => 'required',
            'merk' => 'required',
            'tipe' => 'required',
            'nilai_residu' => 'required',
            'kode_pp' => 'required',
            'kode_pp_susut' => 'required',
        ]);

        
        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $ins2[$i] = DB::connection($this->db)->update("update fa_asset set nama='".$request->nama."',no_seri='".$request->no_seri."',merk='".$request->merk."',tipe='".$request->tipe."',nilai_residu=".floatval($request->nilai_residu).",kode_pp='".$request->kode_pp."',kode_pp_susut='".$request->kode_pp_susut."' where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
            
            $sts = true;
            $msg = "Data Aktiva Tetap berhasil diubah. ";  
            $success['no_fa'] = $nbfa2;
            DB::connection($this->db)->commit();
            
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_fa' => 'required'
        ]);

        
        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $get = DB::connection($this->db)->select("select isnull(count(*),0)  as jml from fasusut_d where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
            if (count($get) > 0){
                $msg = "Transaksi tidak valid. Sudah pernah disusutkan, data tidak bisa dihapus.";
                $sts = false;
            }else{
                $del1 = DB::connection($this->db)->update("delete from fa_asset where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
                $del2 = DB::connection($this->db)->update("delete from fa_nilai where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
                
                $sts = true;
                $msg = "Data Aktiva Tetap berhasil dihapus. ";  
                DB::connection($this->db)->commit();
            }	

            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal dihapus ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }


}

