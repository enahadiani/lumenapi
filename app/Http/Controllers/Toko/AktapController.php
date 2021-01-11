<?php

namespace App\Http\Controllers\Toko;

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
            'tgl_perolehan' => 'date_format:Y-m-d',
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
                if ($pernext == "1"){
                    $msg .= "Periode transaksi melebihi periode aktif sistem.[".$peraktif."]". "Data akan disimpan?";
                    $sts = true;
                }else {
                    $msg .= "Periode transaksi tidak valid.Periode transaksi tidak boleh melebihi periode aktif sistem.[".$peraktif."]";
                    $sts = false;
                }
            }else{
                $sts = true;
            }

            if($sts){
                $periodeSusut = substr($request->tgl_susut,0,4).substr($request->tgl_susut,5,2);
                $nbfa = $nbfa2 = $this->generateKode("fa_asset", "no_fa", $kode_lokasi."-FA".substr($periode,2, 4).".", "000");
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
                    $ins[$i] = DB::connection($this->db)->insert("insert into fa_asset(no_fa,kode_lokasi,kode_klpfa,kode_klpakun,kode_akun,umur,persen,nama,merk,tipe,no_seri,nilai,nilai_residu,kode_pp,kode_pp_susut,tgl_perolehan,tgl_susut,periode,periode_susut,progress,nik_user,tgl_input,catatan,kode_lokfa,nik_pnj,nilai_susut,jenis,akum_nilai) values ('".$nbfa2."','".$kode_lokasi."','".$request->kode_klfa."','".$request->kode_klpakun."','".$request->kode_akun."',".floatval($request->umur).",".floatval($request->persen).",'".$request->deskripsi."','".$request->merk."','".$request->tipe."','".$request->seri."',".floatval($request->nilai).",".floatval($request->residu).",'".$request->kode_pp1."','".$request->kode_pp2."','".$request->tgl_perolehan."','".$request->tgl_susut."','".$periode."','".$periodeSusut."','2','".$nik."',getdate(),'".$request->no_bukti."','-','-',".$nsusut.",'A',0)");
    
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
        $this->validate($request,[
            'kode_klpfa' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_klpakun,b.nama,b.kode_akun,c.nama as nama_akun,b.umur,b.persen 
            from fa_klp a 
            	 inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi 
            	 inner join masakun c on b.kode_akun=c.kode_akun and c.kode_lokasi = '$kode_lokasi'
            where a.kode_klpfa = '$request->kode_klpfa' ");	
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

