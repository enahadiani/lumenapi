<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PenyusutanController extends Controller
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

    
    function nextNPeriode($periode, $n) 
    {
        $bln = floatval(substr($periode,4,2));
        $thn = floatval(substr($periode,0,4));
        for ($i = 1; $i <= $n;$i++){
            if ($bln < 12) $bln++;
            else {
                $bln = 1;
                $thn++;
            }
        }
        if ($bln < 10) $bln = "0".$bln;
        return $thn."".$bln;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'kode_pp' => 'required',
            'kode_drk' => 'required',
            'nik_app' => 'required',
            'akun_bp' => 'required|array',
            'akun_deprs' => 'required|array',
            'kode_ppsusut' => 'required|array',
            'nilai_susut' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("fasusut_m", "no_fasusut", $kode_lokasi."-RSU".substr($periode,2, 4).".", "0001");

            $ins = DB::connection($this->db)->select("insert into fasusut_m(no_fasusut,no_dokumen,tanggal,keterangan,kode_curr,kurs,nilai,kode_pp,kode_drk,posted,modul,nik_buat,nik_setuju,kode_lokasi,periode,no_del,no_link,nik_user,tgl_input) values ('".$no_bukti."','-','".$request->tanggal."','".$request->keterangan."','IDR',1,".floatval($request->nilai).",'".$request->kode_pp."','".$request->kode_drk."','F','FA_SSTREG','".$nik."','".$request->nik_app."','".$kode_lokasi."','".$periode."','-','-','".$nik."',getdate())");

            for ($i=0;$i < count($request->akun_bp);$i++){
                $ins2[$i] = DB::connection($this->db)->select("insert into fasusut_j(no_fasusut,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_drk,kode_pp,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input) values ('".$no_bukti."','-','".$request->tanggal."',0,'".$request->akun_bp[$i]."','".$request->keterangan."','D',".$request->nilai_susut[$i].",'".$request->kode_drk."','".$request->kode_ppsusut[$i]."','".$kode_lokasi."','FA_SSTREG','BP','".$periode."','IDR',1,'".$nik."',getdate())");

                $ins3[$i] = DB::connection($this->db)->select("insert into fasusut_j(no_fasusut,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_drk,kode_pp,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input) values ('".$no_bukti."','-','".$request->tanggal."',1,'".$request->akun_deprs[$i]."','".$request->keterangan."','C',".$request->nilai_susut[$i].",'".$request->kode_drk."','".$request->kode_ppsusut[$i]."','".$kode_lokasi."','FA_SSTREG','AP','".$periode."','IDR',1,'".$nik."',getdate())");
            }
            $ins4 = DB::connection($this->db)->select("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) 
            select no_fasusut,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode,periode,dc,0,nilai from fasusut_j where dc= 'D' and no_fasusut = '".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");
            
            //hanya yg flag_susutnya == 1 yg bisa susut (tanah flag_susutny == 0 / gak boleh susut)		
            $ins5 = DB::connection($this->db)->select("insert into fasusut_d(no_fasusut,no_fa,periode,nilai,kode_lokasi,akun_bp,akun_ap,kode_akun,kode_pp,kode_drk,dc,no_del,nilai_aset,umur) 
            select '".$no_bukti."',a.no_fa,'".$periode."', 
            case when (zz.nilai-a.nilai_residu-a.akum_nilai-isnull(b.tot_susut,0)) > ceiling((zz.nilai-a.akum_nilai)/a.umur) 
                then ceiling((zz.nilai-a.akum_nilai)/a.umur) 
                else (zz.nilai-a.nilai_residu-a.akum_nilai-isnull(b.tot_susut,0)) end 
                as nilai_susut, 
                a.kode_lokasi,c.akun_bp,c.akun_deprs,c.kode_akun,a.kode_pp_susut,'".$request->kode_drk."','D','-',(zz.nilai - a.akum_nilai),a.umur 
            from fa_asset a 
                inner join fa_klpakun c on a.kode_klpakun=c.kode_klpakun and a.kode_lokasi=c.kode_lokasi and c.flag_susut='1' 
                inner join (select kode_lokasi,no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                    from fa_nilai where periode<='".$periode."' and kode_lokasi='".$kode_lokasi."' 
                    group by kode_lokasi,no_fa) zz on a.no_fa=zz.no_fa and a.kode_lokasi=zz.kode_lokasi 
                left join  (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut 
                from fasusut_d where kode_lokasi = '".$kode_lokasi."' and no_del='-' 
                group by no_fa,kode_lokasi) b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
            where progress = '2' and (zz.nilai-a.nilai_residu-a.akum_nilai) > isnull(b.tot_susut,0) and a.kode_lokasi='".$kode_lokasi."' and a.periode_susut<='".$periode."' "); 
            
            $perNext = $this->nextNPeriode($periode,1);
            $ins6 = DB::connection($this->db)->update("update a set periode_susut='".$perNext."' 
            from fa_asset a inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi where b.no_fasusut='".$no_bukti."' and b.kode_lokasi='".$kode_lokasi."'");

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

    public function getDRK(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = substr($request->periode,0,4);
            $res = DB::connection($this->db)->select("select kode_drk, nama from drk where tahun = '".$periode."' and kode_lokasi='".$kode_lokasi."'");	
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

    public function hitungAnggaran(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = substr($request->periode,0,4);
            $res = DB::connection($this->db)->select("select kode_drk, nama from drk where tahun = '".$periode."' and kode_lokasi='".$kode_lokasi."'");	
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

}

