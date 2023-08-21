<?php

namespace App\Http\Controllers\Simkug;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helper\SaiHelpers;

class SerahTerimaDokumenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsimkug';
    public $guard = 'simkug';

    public function loadData(Request $r)
    {
        $this->validate($r,[
            'no_aju' => 'required|regex:/^[^()]+$/'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_aju = $r->input('no_aju');
            $success['rekening'] = array();
            $success['jurnal'] = array();

            $sql="select a.no_aju,a.nilai,a.kode_pp,a.keterangan,a.tanggal,a.kode_pp+' - '+b.nama as pp,a.kode_akun+' - '+c.nama as akun,a.kode_drk+' - '+isnull(d.nama,'-') as drk 
            from it_aju_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi 
            where a.no_aju = ? and a.kode_lokasi=? and a.progress='A'  ";
            $res = DB::connection($this->db)->select($sql,[$no_aju,$kode_lokasi]);

            if(count($res)>0){

                $success['daftar'] = $res;
                $success['message'] = "Dokumen sudah diproses";
                     
            }else{
                $success['daftar'] = array();
                $success['message'] = "No Agenda tidak ditemukan";
            }
            $success['status'] = true;
            return response()->json($success, $this->successStatus);  
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNIKTerima(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->default) && $r->input('default') == 1) {
                $sql="select a.kode_spro,a.flag,b.nama 
                from spro a
                left join karyawan b on a.flag=b.nik and a.kode_lokasi=b.kode_lokasi
                where a.kode_spro = 'ITNIKVER' and a.kode_lokasi = ?";
                $res = DB::connection($this->db)->select($sql,[$kode_lokasi]);			
                if (count($res) > 0){
                    $nik_terima = $res[0]->flag;
                    $nama_terima = $res[0]->nama;							
                }else{
                    $nik_terima = "";
                    $nama_terima = "";
                }
                $success["nik_terima"] = $nik_terima;
                $success["nama_terima"] = $nama_terima;

                $sql="select nik,dbo.fn_removekutip(nama) as nama from hakakses where nik = ? and kode_lokasi = ? ";
                $res2 = DB::connection($this->db)->select($sql,[$nik_user,$kode_lokasi]);			
                if (count($res2) > 0){
                    $user_input = $res2[0]->nama;							
                }else{
                    $user_input = "";
                }
                $success["user_input"] = $user_input;
            }

            $filter_arr = [$kode_lokasi];
            $filter = " and kode_lokasi=? ";
            if(isset($r->nik) && $r->input('nik') !=""){
                $filter.=" and nik=? ";
                array_push($filter_arr,$r->input('nik'));
            }

            $res = DB::connection($this->db)->select("select nik, dbo.fn_removekutip(nama) as nama from karyawan where flag_aktif='1' $filter
            ",$filter_arr);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['daftar'] = [];
            $success['message'] = "Error ".$e->getMessage();
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function cekFormAkses(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $openAwal = "0"; 
			$openAkhir = "0"; 
			$data = DB::connection($this->db)->select("select kode_spro,value1,value2 from spro where kode_spro in ('OPEN_JAM') and kode_lokasi = ? ",[$kode_lokasi]);			
			if (count($data) > 0){
				$line = $data[0];																	
				$openAwal = intval($line->value1);								
				$openAkhir = intval($line->value2);								
			}

            $data = DB::connection($this->db)->select("SELECT substring(convert(varchar,getdate(),103),7,4) as tahun,substring(convert(varchar,getdate(),103),4,2) as bulan,substring(convert(varchar,getdate(),103),1,2) as tgl");					
			if (count($data) > 0) {
				$line = $data[0];				
				$tahun = $line->tahun;
				$bulan = $line->bulan;
				$tgl = $line->tgl;
			}

            $formLock = 0;
			$data = DB::connection($this->db)->select("select substring(flag,1,2) as jamawal,substring(flag,4,2) as minawal, 
            substring(keterangan,1,2) as jamakhir,substring(keterangan,4,2) as minakhir, substring(CONVERT(VARCHAR(8),GETDATE(),108) ,1,2) as jamnow, substring(CONVERT(VARCHAR(8),GETDATE(),108) ,4,2) as minnow 
            from spro where kode_spro in ('OPEN_JAM') and kode_lokasi = ?",[$kode_lokasi]);
			if (count($data) > 0) {
				$line = $data[0];																	
				$openAwal = date($tahun.'-'.$bulan.'-'.$tgl.' '.$line->jamawal.':'.$line->minawal.':0');				
				$openAkhir = date($tahun.'-'.$bulan.'-'.$tgl.' '.$line->jamakhir.':'.$line->minakhir.':0');				
				$jamNow = date($tahun.'-'.$bulan.'-'.$tgl.' '.$line->jamnow.':'.$line->minnow.':0');	

				if ($jamNow < $openAwal || $jamNow > $openAkhir) {
					$formLock = 1;								
				}				
			}

			if ($formLock == 1) {
				$msg = "Form tidak bisa digunakan. Akses Form ini Berbatas Waktu.";					
				$sts = false;
			}else{
                $msg = "Form berhasil diakses";
                $sts = true;
            }
            
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e->getMessage();
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function store(Request $r)
    {
        $this->validate($r, [
            'no_aju' => 'required|regex:/^[^()]+$/',
            'nik_terima' => 'required|regex:/^[^()]+$/',
            'user_input' => 'regex:/^[^()]+$/',
            'kode_pp' => 'regex:/^[^()]+$/'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            date_default_timezone_set('Asia/Jakarta');
            $jam_now = date("Y-m-d H:i:s");
            $formLock = 0;
            $msg = ""; $sts = true; $no_bukti = "";
            $html = "";

            $ceka= "select substring(flag,1,2) as jamawal,substring(flag,4,2) as minawal,  substring(keterangan,1,2) as jamakhir,substring(keterangan,4,2) as minakhir, substring(CONVERT(VARCHAR(8),GETDATE(),108) ,1,2) as jamnow, substring(CONVERT(VARCHAR(8),GETDATE(),108) ,4,2) as minnow 
            from spro where kode_spro in ('OPEN_JAM') and kode_lokasi = ? ";	
            $rsceka = DB::connection($this->db)->select($ceka,[$kode_lokasi]);		
            if(count($rsceka) > 0){										
                $openAwal = date("Y-m-d ".$rsceka[0]->jamawal.":".$rsceka[0]->minawal.":00");								
                $openAkhir = date("Y-m-d ".$rsceka[0]->jamakhir.":".$rsceka[0]->minakhir.":00");							
            }

            $success['open'] = $jam_now < $openAwal || $jam_now > $openAkhir;
            $success['openAwal'] = $openAwal;
            $success['openAkhir'] = $openAkhir;
            if(isset($jam_now)){
                if ($jam_now < $openAwal || $jam_now > $openAkhir){
                    $formLock = 1;					
                }
            }

            $cek2 = "SELECT FORMAT(getdate(), 'dddd') AS hari";	
            $rscek2 = DB::connection($this->db)->select($cek2);
            if(count($rscek2) > 0){
                if ($rscek2[0]->hari == "Sunday" || $rscek2[0]->hari == "Saturday") {
                    $formLock = 1;	
                }
            }

            if($formLock == 0){

                $periode = date('Ym');
                $kode_pp = explode("-",$r->input('kode_pp'));
                $strSQL = "select count(*) as jml 
                from it_aju_m a 					 
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 					 					 
                inner join it_ajuapp_m f on a.kode_lokasi=f.kode_lokasi and f.jenis='ONLINE' and a.no_app=f.no_app 					 					 
                left join it_aju_fisik e on a.no_aju=e.no_aju and a.kode_lokasi=e.kode_lokasi 							
                where a.kode_pp=? and a.progress='3' and e.no_aju is null and a.periode<=? and a.kode_lokasi=? ";
                $ck = DB::connection($this->db)->select($strSQL,[$kode_pp[0], $periode, $kode_lokasi]);	

                if(count($ck) > 0){
                    if (intval($ck[0]->jml) > 2) {
                        $msg = "Form tidak bisa digunakan. Ditemukan lebih Dari 2 Agenda Online YG Dokumen Fisiknya Belum Diserahkan Ke Perbendaharaan,Silahkan cek Menu dilaporan Rekap Penyerahan Dokumen Fisik Online";	
                        $sts = false; 
                    }else{
                        $no_bukti = SaiHelpers::generateKode($this->db,"it_ajuapp_m", "no_app", $kode_lokasi."-APP".substr($periode,2,2).".", "00001");
          
                        DB::connection($this->db)->insert("insert into it_ajuapp_m(no_app,no_aju,kode_lokasi,periode,tgl_input,user_input,tgl_aju,nik_terima,jenis) values (?, ?, ?, ?, getdate(), ?, getdate(), ?, ?)", 
                            [
                                $no_bukti,$r->input('no_aju'),$kode_lokasi,$periode,$r->input('user_input'),$r->input('nik_terima'),'OFFLINE'
                            ]
                        );
          
                        DB::connection($this->db)->update("update it_aju_m set progress='0', no_app=? where no_aju=? and kode_lokasi=?",[$no_bukti, $r->input('no_aju'),$kode_lokasi]);

                        DB::connection($this->db)->commit();

                        $sts = true;
                        $msg = 'sukses diproses';
        
                        $tahun = substr($periode,0,4);
                        $sql="select a.no_aju,a.kode_lokasi,convert(varchar(20),f.tgl_input,105) as tgl,a.keterangan,a.kode_pp,b.nama as nama_pp,a.nilai,a.nik_user,a.tanggal,a.kode_akun,c.nama as nama_akun,
                        a.kode_drk,e.nama as nama_drk,a.no_app,convert(varchar(20),f.tgl_input,108) as jam,
                        f.user_input,f.nik_terima,g.nama as nama_terima,f.tgl_input
                        from it_aju_m a
                        inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        left join drk e on a.kode_drk=e.kode_drk and a.kode_lokasi=e.kode_lokasi and e.tahun=?
                        inner join it_ajuapp_m f on a.no_app=f.no_app and a.kode_lokasi=f.kode_lokasi
                        left join karyawan g on f.nik_terima=g.nik and a.kode_lokasi=g.kode_lokasi
                        where a.no_aju=? and a.kode_lokasi=? 
                        order by a.no_aju";
                        $rs2 = DB::connection($this->db)->select($sql,[$tahun, $r->input('no_aju'),$kode_lokasi]);

                        if(count($rs2) > 0){
                            $row = $rs2[0];
                            $html = "<div align='center'><table width='800' border='0' cellspacing='2' cellpadding='1'>
                            <tr align='center'>
                                <td colspan='2' ><b>TANDA TERIMA DOKUMEN</b></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td width='200'>No Bukti</td>
                                <td width='600'>: $row->no_app </td>
                            </tr>
                            <tr>
                                <td width='200'>No Agenda</td>
                                <td width='600'>: $row->no_aju </td>
                            </tr>
                            <tr>
                                <td>Tanggal</td>
                                <td>: $row->tgl  $row->jam  </td>
                            </tr>
                            <tr>
                                <td>PP</td>
                                <td>: $row->kode_pp - $row->nama_pp </td>
                            </tr>
                            <tr>
                                <td>MTA</td>
                                <td>: $row->kode_akun - $row->nama_akun </td>
                            </tr>
                            <tr>
                                <td>DRK</td>
                                <td>: $row->kode_drk - $row->nama_drk </td>
                            </tr>
                            <tr>
                                <td>Keterangan</td>
                                <td>: $row->keterangan </td>
                            </tr>
                            <tr>
                                <td>Nilai</td>
                                <td>: ".number_format($row->nilai,0,",",".")."</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            
                            <tr>
                                <td colspan='2'>Bandung, ".substr($row->tgl_input,8,2)." ".SaiHelpers::getNamaBulan(substr(str_replace("-","",$row->tgl_input),0,6))."</td>
                            </tr>
                            
                            <tr>
                                <td>Dibuat Oleh : </td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan='2'><table width='800' border='0' cellspacing='2' cellpadding='1'>
                                <tr>
                                    <td width='400'>Yang Menerima </td>
                                    <td width='400'>Yang Menyerahkan </td>
                                </tr>
                                <tr>
                                    <td height='60'>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>$row->nama_terima</td>
                                    <td>$row->user_input</td>
                                </tr>
                                </table></td>
                            </tr>
                            </table><br>
                            </div>";
                        }
                    }								
                }else{
                    $msg = "Form tidak bisa digunakan. Internal Server Error";	
                    $sts=false; 
                }
            }else{
                $msg = " gagal diproses. Form tidak bisa digunakan. Akses Form ini Berbatas Waktu. ";
                $sts=false; 
            }

            $success['no_aju'] = $r->input('no_aju');
            $success['no_bukti'] = $no_bukti;
            $success['html']=$html;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Dokumen gagal diproses ".$e->getMessage();
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

}

?>
