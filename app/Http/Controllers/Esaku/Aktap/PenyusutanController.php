<?php

namespace App\Http\Controllers\Esaku\Aktap;

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


    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
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

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            if ($status == "A") {

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
            }else{

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
            }

            $auth = DB::connection($this->db)->select($strSQL);
            $auth = json_decode(json_encode($auth),true);
            if(count($auth) > 0){
                $perValid = true;
                $msg = "ok";
            }else{
                if ($status == "A") {

                    $strSQL2 = "select per_awal2 as per_awal,per_akhir2 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' ";
                }else{
    
                    $strSQL2 = "select per_awal1 as per_awal,per_akhir1 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."'";
                }
                $get = DB::connection($this->db)->select($strSQL2);
                if(count($get) > 0){
                    $per_awal = $this->namaPeriode($get[0]->per_awal);
                    $per_akhir = $this->namaPeriode($get[0]->per_akhir);
                    $msg = "Transaksi tidak dapat disimpan karena tanggal periode sudah di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif belum disetting ";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'kode_pp' => 'required',
            'akun_bp' => 'required|array',
            'akun_deprs' => 'required|array',
            'kode_ppsusut' => 'required|array',
            'nilai_susut' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2('AT',$status_admin,$periode);

            if($cek['status']){

                $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-RSU".substr($periode,2, 4).".", "0001");

                $ins = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','$kode_lokasi',getdate(),'".$nik."','$periode','AT','SUSUTREG','F','-','-','$request->kode_pp','$request->tanggal','$request->no_dokumen','$request->keterangan','IDR',1,".floatval($request->nilai).",0,0,'$nik','-','-','-','-','-','-','-','-')");

                for ($i=0;$i < count($request->akun_bp);$i++){
                    $ins2[$i] = DB::connection($this->db)->insert("insert into trans_j(no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','$kode_lokasi',getdate(),'$nik','".$request->periode."','$request->no_dokumen','".$request->tanggal."',0,".$request->akun_bp[$i].",'D',".floatval($request->nilai_susut[$i]).",".floatval($request->nilai_susut[$i]).",'".$request->keterangan."','AT','BP','IDR',1,'".$request->kode_ppsusut[$i]."','-','-','-','-','-','-','-','-')");

                    $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j(no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','$kode_lokasi',getdate(),'$nik','".$request->periode."','$request->no_dokumen','".$request->tanggal."',1,".$request->akun_deprs[$i].",'C',".floatval($request->nilai_susut[$i]).",".floatval($request->nilai_susut[$i]).",'".$request->keterangan."','AT','AP','IDR',1,'".$request->kode_ppsusut[$i]."','-','-','-','-','-','-','-','-')");

                }

                //hanya yg flag_susutnya == 1 yg bisa susut (tanah flag_susutny == 0 / gak boleh susut)		
                $sqlins5 = "insert into fasusut_d(no_fasusut,no_fa,periode,nilai,kode_lokasi,akun_bp,akun_ap,kode_akun,kode_pp,kode_drk,dc,no_del,nilai_aset,umur) 
                    select '".$no_bukti."',a.no_fa,'".$periode."', 
                    case when (zz.nilai-a.nilai_residu-a.akum_nilai-isnull(b.tot_susut,0)) > ceiling((zz.nilai-a.akum_nilai)/a.umur) 
                    then ceiling((zz.nilai-a.akum_nilai)/a.umur) 
                    else (zz.nilai-a.nilai_residu-a.akum_nilai-isnull(b.tot_susut,0)) end 
                    as nilai_susut, 
                a.kode_lokasi,c.akun_bp,c.akun_deprs,c.kode_akun,a.kode_pp_susut,c.kode_drk,'D','-',(zz.nilai - a.akum_nilai),a.umur 
                from fa_asset a 						
                inner join fa_klpakun c on a.kode_klpakun=c.kode_klpakun and a.kode_lokasi=c.kode_lokasi and c.flag_susut='1' 							
                inner join (select kode_lokasi,no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                            from fa_nilai where periode<='".$periode."' and kode_lokasi='".$kode_lokasi."' 
                            group by kode_lokasi,no_fa) zz on a.no_fa=zz.no_fa and a.kode_lokasi=zz.kode_lokasi 
                left join 
                            (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut
                            from fasusut_d where kode_lokasi = '".$kode_lokasi."' and no_del='-'
                            group by no_fa,kode_lokasi) b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                where a.jenis = 'A' and a.umur <> 0 and progress = '2' and (zz.nilai-a.nilai_residu-a.akum_nilai) > isnull(b.tot_susut,0) and a.kode_lokasi='".$kode_lokasi."' and a.periode_susut<='".$periode."'";
                $ins5 = DB::connection($this->db)->insert($sqlins5); 

                
                DB::connection($this->db)->commit();
                $perNext = $this->nextNPeriode($periode,1);
                $ins6 = DB::connection($this->db)->update("update a set periode_susut='".$perNext."' 
                from fa_asset a inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi where b.no_fasusut='".$no_bukti."' and b.kode_lokasi='".$kode_lokasi."'");

                
                DB::connection($this->db)->commit();

                $msg = "Data Penyusutan berhasil disimpan.";
                $sts = true;
                $success['no_bukti'] = $no_bukti;
            }else{
                
                DB::connection($this->db)->rollback();
                $msg = $cek['message'];
                $sts = false;
                $success['no_bukti'] = '-';
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

    public function hitungPenyusutan(Request $request)
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
            $sql = "select b.akun_bp,x.nama as nama_bp,b.akun_deprs,y.nama as nama_deprs,a.kode_pp_susut,c.nama as nama_pp, 

            sum(case when (zz.nilai-a.nilai_residu-a.akum_nilai-isnull(d.tot_susut,0)) > ceiling((zz.nilai-isnull(a.akum_nilai,0))/a.umur) 
            		   then ceiling((zz.nilai-isnull(a.akum_nilai,0))/a.umur)  
            		   else ceiling(zz.nilai-a.nilai_residu-isnull(a.akum_nilai,0)-isnull(d.tot_susut,0)) end) 						 
            as nilai_susut 
            from fa_asset a 
            inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi and b.flag_susut='1' 

            inner join (select kode_lokasi,no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                        from fa_nilai where periode<='".$periode."' and kode_lokasi='".$kode_lokasi."' 
                        group by kode_lokasi,no_fa 
            		      ) zz on a.no_fa=zz.no_fa and a.kode_lokasi=zz.kode_lokasi 						
            
            inner join pp c on a.kode_pp_susut=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
            inner join masakun x on b.akun_bp=x.kode_akun and x.kode_lokasi=a.kode_lokasi 
            inner join masakun y on b.akun_deprs=y.kode_akun and y.kode_lokasi=a.kode_lokasi 
            left join 
               (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut 
            	  from fasusut_d where kode_lokasi = '".$kode_lokasi."' and no_del='-' 
            	  group by no_fa,kode_lokasi) d on a.no_fa=d.no_fa and a.kode_lokasi=d.kode_lokasi 					 

            where a.jenis = 'A' and a.umur <> 0 and a.progress = '2' and a.kode_lokasi='".$kode_lokasi."' and (zz.nilai-isnull(a.akum_nilai,0)-a.nilai_residu) > isnull(d.tot_susut,0) and a.periode_susut <= '".$periode."' 
            group by b.akun_bp,b.akun_deprs,a.kode_pp_susut,c.nama,x.nama,y.nama 
            order by b.akun_bp";		
            $res = DB::connection($this->db)->select($sql);	
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

