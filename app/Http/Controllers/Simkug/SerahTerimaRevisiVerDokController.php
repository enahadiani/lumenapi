<?php

namespace App\Http\Controllers\Simkug;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helper\SaiHelpers;

class SerahTerimaRevisiVerDokController extends Controller
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

            $sql="select a.no_aju,a.form,a.nilai,a.kode_pp,a.keterangan,a.tanggal,a.kode_pp+' - '+b.nama as pp,a.kode_akun+' - '+c.nama as akun,a.kode_drk+' - '+isnull(d.nama,'-') as drk 
            from it_aju_m a 
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi 
            where a.no_app like '__-APP%' and a.no_aju = ? and a.kode_lokasi=? and a.progress in ('D')   ";
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

    public function cekFormAkses(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $openAwal = "0"; 
			$openAkhir = "0"; 
			$data = DB::connection($this->db)->select("select kode_spro,value1,value2 from spro where kode_spro in ('OPEN_JAM2') and kode_lokasi = ? ",[$kode_lokasi]);			
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
            from spro where kode_spro in ('OPEN_JAM2') and kode_lokasi = ?",[$kode_lokasi]);
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
            'no_aju' => 'required|regex:/^[^()]+$/'
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
            from spro where kode_spro in ('OPEN_JAM2') and kode_lokasi = ? ";	
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
                
                $no_bukti = SaiHelpers::generateKode($this->db,"it_aju_fisik", "no_bukti", $kode_lokasi."-STR".substr($periode,2,2).".", "00001");
  
                DB::connection($this->db)->insert("insert into it_aju_fisik(no_bukti,no_aju,kode_lokasi,tgl_input,nik_user) values (?, ?, ?, getdate(), ?)", 
                    [
                        $no_bukti,$r->input('no_aju'),$kode_lokasi,$nik_user
                    ]
                );
  
                DB::connection($this->db)->update("update it_aju_m set progress='R' where no_aju=? and kode_lokasi=?",[$r->input('no_aju'),$kode_lokasi]);

                DB::connection($this->db)->commit();

                $sts = true;
                $msg = 'sukses diproses';

                $tahun = substr($periode,0,4);
                $sql="select a.no_aju,a.kode_lokasi,convert(varchar(20),f.tgl_input,105) as tgl,a.keterangan,a.kode_pp,b.nama as nama_pp,a.nilai,a.nik_user,f.tgl_input as tanggal,a.kode_akun,c.nama as nama_akun,
                a.kode_drk,e.nama as nama_drk,f.no_bukti as no_app,convert(varchar(20),f.tgl_input,108) as tgl2
                from it_aju_m a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                left join drk e on a.kode_drk=e.kode_drk and a.kode_lokasi=e.kode_lokasi and e.tahun='$tahun'
                inner join it_aju_fisik f on a.no_aju=f.no_aju and a.kode_lokasi=f.kode_lokasi
                left join karyawan g on f.nik_user=g.nik and a.kode_lokasi=g.kode_lokasi
                where a.no_aju=? and a.kode_lokasi=? 
                order by a.no_aju";
                $rs2 = DB::connection($this->db)->select($sql,[$tahun, $r->input('no_aju'),$kode_lokasi]);

                if(count($rs2) > 0){
                    $row = $rs2[0];
                    $html = "<div align='center'><table width='800' border='0' cellspacing='2' cellpadding='1'>
                    <tr align='center'>
                      <td colspan='2' ><b>TANDA TERIMA PENYERAHAN DOKUMEN ONLINE</b></td>
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
                      <td>: $row->tgl  $row->tgl2  </td>
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
                      <td colspan='2'>Bandung, ".substr($row->tanggal,8,2)." ".SaiHelpers::getNamaBulan(substr(str_replace("-","",$row->tanggal),0,6))." </td>
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
                          <td width='400'></td>
                        </tr>
                        <tr>
                          <td height='60'>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                        <tr>
                          <td>$row->nama_terima</td>
                          <td></td>
                        </tr>
                      </table></td>
                    </tr>
                  </table><br>
                    </div>";
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
