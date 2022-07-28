<?php

namespace App\Http\Controllers\Esaku\Inventori;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 

class HppController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';
    
    public function joinNum($num){
        // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
        $num = str_replace(".", "", $num);
        $num = str_replace(",", ".", $num);
        return $num;
    }
    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
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

    function namaPeriode($periode){
        $bulan = substr($periode,4,2);
        $tahun = substr($periode,0,4);
        switch ($bulan){
            case 1 : case '1' : case '01': $bulan = "Januari"; break;
            case 2 : case '2' : case '02': $bulan = "Februari"; break;
            case 3 : case '3' : case '03': $bulan = "Maret"; break;
            case 4 : case '4' : case '04': $bulan = "April"; break;
            case 5 : case '5' : case '05': $bulan = "Mei"; break;
            case 6 : case '6' : case '06': $bulan = "Juni"; break;
            case 7 : case '7' : case '07': $bulan = "Juli"; break;
            case 8 : case '8' : case '08': $bulan = "Agustus"; break;
            case 9 : case '9' : case '09': $bulan = "September"; break;
            case 10 : case '10' : case '10': $bulan = "Oktober"; break;
            case 11 : case '11' : case '11': $bulan = "November"; break;
            case 12 : case '12' : case '12': $bulan = "Desember"; break;
            default: $bulan = null;
        }
    
        return $bulan.' '.$tahun;
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

    function isUnik($isi,$no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select no_bukti from trans_m where no_dokumen = '".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '".$no_bukti."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['no_bukti'] = $auth[0]['no_bukti'];
        }else{
            $res['status'] = true;
        }
        return $res;
    }
    
    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            $res = DB::connection($this->db)->select("select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.no_dokumen,case a.posted when 'T' then 'Close' else 'Open' end as posted,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input
            from trans_m a 
            inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik' 			 
            where a.modul='IV' and a.form='BRGHPP' and a.kode_lokasi='$kode_lokasi' and a.posted ='F' ");
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'kode_form' => 'required',
            'deskripsi' => 'required',
            'total' => 'required'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
                $pabrik= $data->pabrik;
            }

            $tanggal = $this->reverseDate($request->tanggal,"/","-");

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            DB::connection($this->db)->beginTransaction();
            
            
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-HP".substr($periode,2,4).".", "0001");
            
            $cek = $this->doCekPeriode2('IV',$status_admin,$periode);
            
            if($cek['status']){
                $res = $this->isUnik($request->no_dokumen,$no_bukti);
                if($res['status']){
                    
                    //reverse
					if (substr($periode,4,2) !="01") {
						$upd = DB::connection($this->db)->table("trans_m")
                        ->where('periode','LIKE', '%'.substr($periode,0,4).'%')
                        ->where('form','BRGHPP')
                        ->where('no_ref1','-') 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update(['no_ref1'=>$no_bukti]);

						$ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                            select '".$no_bukti."',a.kode_lokasi,getdate(),'".$nik."','".$periode."',a.no_dokumen,'".$tanggal."',a.nu,a.kode_akun,case a.dc when 'D' then 'C' else 'D' end,a.nilai,a.nilai_curr,'Reverse HPP '+a.no_bukti,a.modul,a.jenis,a.kode_curr,a.kurs,a.kode_pp,a.kode_drk,a.kode_cust,a.kode_vendor,a.no_fa,a.no_selesai,a.no_ref1,a.no_ref2,a.no_bukti 
                            from trans_j a inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
                            where b.no_ref1='".$no_bukti."' and b.kode_lokasi='".$kode_lokasi."' and a.no_ref3='-'");
					}

					$insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
							[$no_bukti,$kode_lokasi,$nik,$periode,'IV','BRGHPP','F','-','-',$kode_pp,$tanggal,$request->no_dokumen,'Jurnal HPP No: '.$no_bukti,'IDR',1,$this->joinNum($request->total),0,0,'-','-','-','-','-','-','-','-','-']);

                    $sqldet =  "select *,0 as no from ( 
                                select a.kode_barang,a.nama,a.pabrik,a.kode_gudang,a.kode_pp,a.sat_kecil,f.akun_pers,f.akun_hpp ,isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0) as jumlah, 
                                isnull(round(e.h_avg,2),0) as h_avg,
                                round((isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0)) * isnull(e.h_avg,0),0) as hpp from (select a.kode_barang,a.sat_kecil,'-' as pabrik,a.nama,b.kode_gudang,b.kode_pp,a.kode_lokasi,a.kode_klp 
                                    from brg_barang a 
                                    inner join brg_gudang b on a.pabrik=b.kode_gudang and a.kode_lokasi=b.kode_lokasi
                                    where a.kode_lokasi='$kode_lokasi' and b.kode_lokasi='$kode_lokasi' and a.pabrik='$pabrik'
                                ) a 
                                inner join brg_barangklp f on a.kode_klp=f.kode_klp and a.kode_lokasi=f.kode_lokasi 
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah) as sawal 
                                        from brg_sawal 
                                        where periode='".substr($periode,0,4)."01' and kode_lokasi='$kode_lokasi' 
                                        group by kode_lokasi,kode_barang,kode_gudang 
                                ) b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.kode_gudang 
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah+bonus) as beli 
                                        from brg_trans_d 
                                        where modul='BRGBELI' and periode like '".substr($periode,0,4)."%' and periode <= '".$periode."' and kode_lokasi='".$kode_lokasi."' 
                                        group by kode_lokasi,kode_barang,kode_gudang 
                                ) c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.kode_gudang   
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(stok) as sakhir 
                                         from brg_stok where kode_lokasi='".$kode_lokasi."' and nik_user ='".$nik."' 
                                         group by kode_lokasi,kode_barang,kode_gudang 
                                         ) d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.kode_gudang=d.kode_gudang 
                                left join brg_hpp e on a.kode_barang=e.kode_barang and a.kode_lokasi=e.kode_lokasi and e.nik_user ='".$nik."' 
                              ) x where  x.hpp<>0 order by x.kode_barang ";
                    $detail = DB::connection($this->db)->select($sqldet);				
                    $detail= json_decode(json_encode($detail),true);
					for ($i=0;$i<count($detail);$i++){
						$line = $detail[$i];	
						if(floatval($line['hpp']) != 0){											
							$insd = DB::connection($this->db)->insert("insert into brg_hpp_d (no_hpp,kode_lokasi,kode_barang,satuan,jumlah,h_avg,nilai_hpp,kode_gudang,kode_pp,akun_pers,akun_hpp) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",[
								$no_bukti,$kode_lokasi,$line['kode_barang'],$line['sat_kecil'],floatval($line['jumlah']),floatval($line['h_avg']),floatval($line['hpp']),$line['kode_gudang'],$line['kode_pp'],$line['akun_pers'],$line['akun_hpp']]);																					
						}
					}

					$insj = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                        select no_hpp,kode_lokasi,getdate(),?,?,?,?,0,akun_hpp,'D',sum(nilai_hpp),sum(nilai_hpp),?,'IV','HPP','IDR',1,kode_pp,'-','-','-','-','-','-','-','-' 
						from brg_hpp_d 
						where no_hpp='$no_bukti' and kode_lokasi='$kode_lokasi' 
						group by no_hpp,kode_lokasi,akun_hpp,kode_pp", [$nik,$periode,$request->no_dokumen,$tanggal,$request->keterangan]);

                    $insj2 = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                        select no_hpp,kode_lokasi,getdate(),?,?,?,?,1,akun_pers,'C',sum(nilai_hpp),sum(nilai_hpp),?,'IV','BRG','IDR',1,kode_pp,'-','-','-','-','-','-','-','-' 
                        from brg_hpp_d 
                        where no_hpp='".$no_bukti."' and kode_lokasi='".$kode_lokasi."' 
                        group by no_hpp,kode_lokasi,akun_pers,kode_pp", [$nik,$periode,$request->no_dokumen,$tanggal,$request->keterangan]);

                    $tmp="sukses";
                    $sts=true;
                    
                }else{
                    $tmp = "Transaksi tidak valid. No Dokumen '".$request->no_dokumen."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                    $sts = false;
                }
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }         

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data HPP berhasil disimpan ";
                return response()->json($success, $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data HPP gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'kode_form' => 'required',
            'deskripsi' => 'required',
            'total' => 'required'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
                $pabrik= $data->pabrik;
            }
            
            $tanggal = $this->reverseDate($request->tanggal,"/","-");

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            DB::connection($this->db)->beginTransaction();
            
            $periode=substr($tanggal,0,4).substr($tanggal,5,2);
            $no_bukti = $request->no_bukti;
            
            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection($this->db)->table('brg_hpp_d')->where('kode_lokasi', $kode_lokasi)->where('no_hpp', $no_bukti)->delete();
            $upd = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ref1', $no_bukti)
            ->where('form','BRGHPP')
            ->update(['no_ref1'=>'-']);
            
            $cek = $this->doCekPeriode2('IV',$status_admin,$periode);
            
            if($cek['status']){
                $res = $this->isUnik($request->no_dokumen,$no_bukti);
                if($res['status']){
                    
                     //reverse
					if (substr($periode,4,2) !="01") {
						$upd = DB::connection($this->db)->table("trans_m")
                        ->where('periode','LIKE', '%'.substr($periode,0,4).'%')
                        ->where('form','BRGHPP')
                        ->where('no_ref1','-') 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update(['no_ref1'=>$no_bukti]);

						$ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                            select '".$no_bukti."',a.kode_lokasi,getdate(),'".$nik."','".$periode."',a.no_dokumen,'".$tanggal."',a.nu,a.kode_akun,case a.dc when 'D' then 'C' else 'D' end,a.nilai,a.nilai_curr,'Reverse HPP '+a.no_bukti,a.modul,a.jenis,a.kode_curr,a.kurs,a.kode_pp,a.kode_drk,a.kode_cust,a.kode_vendor,a.no_fa,a.no_selesai,a.no_ref1,a.no_ref2,a.no_bukti 
                            from trans_j a inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
                            where b.no_ref1='".$no_bukti."' and b.kode_lokasi='".$kode_lokasi."' and a.no_ref3='-'");
					}

					$insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
							[$no_bukti,$kode_lokasi,$nik,$periode,'IV','BRGHPP','F','-','-',$kode_pp,$tanggal,$request->no_dokumen,'Jurnal HPP No: '.$no_bukti,'IDR',1,$this->joinNum($total),0,0,'-','-','-','-','-','-','-','-','-']);

                    $sqldet =  "select *,0 as no from ( 
                                select a.kode_barang,a.nama,a.pabrik,a.kode_gudang,a.kode_pp,a.sat_kecil,f.akun_pers,f.akun_hpp ,isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0) as jumlah, 
                                isnull(round(e.h_avg,2),0) as h_avg,
                                round((isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0)) * isnull(e.h_avg,0),0) as hpp from (select a.kode_barang,a.sat_kecil,'-' as pabrik,a.nama,b.kode_gudang,b.kode_pp,a.kode_lokasi,a.kode_klp 
                                    from brg_barang a inner join brg_gudang b on a.pabrik=b.kode_gudang and a.kode_lokasi=b.kode_lokasi
                                    where a.kode_lokasi='$kode_lokasi' and b.kode_lokasi='$kode_lokasi' and a.pabrik='$pabrik'
                                ) a 
                                inner join brg_barangklp f on a.kode_klp=f.kode_klp and a.kode_lokasi=f.kode_lokasi 
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah) as sawal 
                                        from brg_sawal 
                                        where periode='".substr($periode,0,4)."01' and kode_lokasi='$kode_lokasi' 
                                        group by kode_lokasi,kode_barang,kode_gudang 
                                ) b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.kode_gudang 
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah+bonus) as beli 
                                        from brg_trans_d 
                                        where modul='BRGBELI' and periode like '".substr($periode,0,4)."%' and periode <= '".$periode."' and kode_lokasi='".$kode_lokasi."' 
                                        group by kode_lokasi,kode_barang,kode_gudang 
                                ) c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.kode_gudang   
                                left join (select kode_barang,kode_gudang,kode_lokasi,sum(stok) as sakhir 
                                         from brg_stok where kode_lokasi='".$kode_lokasi."' and nik_user ='".$nik."' 
                                         group by kode_lokasi,kode_barang,kode_gudang 
                                         ) d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.kode_gudang=d.kode_gudang 
                                left join brg_hpp e on a.kode_barang=e.kode_barang and a.kode_lokasi=e.kode_lokasi and e.nik_user ='".$nik."' 
                              ) x where  x.hpp<>0 order by x.kode_barang ";
                    $detail = DB::connection($this->db)->select($sqldet);				
                    $detail= json_decode(json_encode($detail),true);
					for ($i=0;$i<count($detail);$i++){
						$line = $detail[$i];	
						if(floatval($line['hpp']) != 0){											
							$insd = DB::connection($this->db)->insert("insert into brg_hpp_d (no_hpp,kode_lokasi,kode_barang,satuan,jumlah,h_avg,nilai_hpp,kode_gudang,kode_pp,akun_pers,akun_hpp) values values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",[
								$no_bukti,$kode_lokasi,$line['kode_barang'],$line['sat_kecil'],floatval($line['jumlah']),floatval($line['h_avg']),floatval($line['hpp']),$line['kode_gudang'],$line['kode_pp'],$line['akun_pers'],$line['akun_hpp']]);																					
						}
					}

					$insj = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                        select no_hpp,kode_lokasi,getdate(),?,?,?,?,0,akun_hpp,'D',sum(nilai_hpp),sum(nilai_hpp),?,'IV','HPP','IDR',1,kode_pp,'-','-','-','-','-','-','-','-' 
						from brg_hpp_d 
						where no_hpp='$no_bukti' and kode_lokasi='$kode_lokasi' 
						group by no_hpp,kode_lokasi,akun_hpp,kode_pp", [$nik,$periode,$request->no_dokumen,$tanggal,$request->keterangan]);

                    $insj2 = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                        select no_hpp,kode_lokasi,getdate(),?,?,?,?,1,akun_pers,'C',sum(nilai_hpp),sum(nilai_hpp),?,'IV','BRG','IDR',1,kode_pp,'-','-','-','-','-','-','-','-' 
                        from brg_hpp_d 
                        where no_hpp='".$no_bukti."' and kode_lokasi='".$kode_lokasi."' 
                        group by no_hpp,kode_lokasi,akun_pers,kode_pp", [$nik,$periode,$request->no_dokumen,$tanggal,$request->keterangan]);

                    $tmp="sukses";
                    $sts=true;
                    
                }else{
                    $tmp = "Transaksi tidak valid. No Dokumen '".$request->no_dokumen."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                    $sts = false;
                }
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }         

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data HPP berhasil diubah ";
                return response()->json($success, $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data HPP gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            
            $ins = DB::connection($this->db)->insert("insert into trans_h 
            select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync,'$nik',getdate()
            from trans_m 
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'  
            ");

            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del3 = DB::connection($this->db)->table('brg_hpp_d')->where('kode_lokasi', $kode_lokasi)->where('no_hpp', $no_bukti)->delete();
            $upd = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ref1', $no_bukti)
            ->where('form','BRGHPP')
            ->update(['no_ref1'=>'-']);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data HPP berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data HPP gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            $res = DB::connection($this->db)->select("select a.tanggal,a.no_bukti,a.periode,keterangan as deskripsi,a.nilai1,a.no_dokumen,a.modul as jenis,a.nik2 as nik_periksa,b.nama as nama_periksa 
            from trans_m a
            left join karyawan b on a.nik2=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select 0 as no,a.kode_barang,a.nama,a.pabrik,a.sat_kecil,b.jumlah,b.h_avg,b.nilai_hpp as hpp,c.kode_pp, c.kode_gudang,f.akun_pers,f.akun_hpp 
            from brg_barang a 					 
            inner join brg_barangklp f on a.kode_klp=f.kode_klp and a.kode_lokasi=f.kode_lokasi 
            inner join brg_hpp_d b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.pabrik=b.kode_gudang
            inner join brg_gudang c on b.kode_gudang=c.kode_gudang and b.kode_lokasi=c.kode_lokasi 
            where b.no_hpp='$no_bukti' and b.kode_lokasi='$kode_lokasi' and a.pabrik='$pabrik' ");
            $res2= json_decode(json_encode($res2),true);

            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            
            $success['data'] = [];
            $success['detail'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadData(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }
            $periode = $request->periode;
            $exec1 = DB::connection($this->db)->update("exec sp_brg_hpp ?, ?, ?",[$periode,$kode_lokasi,$nik]);
            $exec2 = DB::connection($this->db)->update("exec sp_brg_stok_hpp ?, ?, ?",[$periode,$kode_lokasi,$nik]);
            $sql =  "select *,0 as no from ( 
                        select a.kode_barang,a.nama,a.pabrik,a.kode_gudang,a.kode_pp,a.sat_kecil,f.akun_pers,f.akun_hpp ,isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0) as jumlah, 
                        isnull(round(e.h_avg,2),0) as h_avg,
                        round((isnull(b.sawal,0)+isnull(c.beli,0)-isnull(d.sakhir,0)) * isnull(e.h_avg,0),0) as hpp from (select a.kode_barang,a.sat_kecil,'-' as pabrik,a.nama,b.kode_gudang,b.kode_pp,a.kode_lokasi,a.kode_klp 
                            from brg_barang a inner join brg_gudang b on a.pabrik=b.kode_gudang and a.kode_lokasi=b.kode_lokasi
                            where a.kode_lokasi='$kode_lokasi' and b.kode_lokasi='$kode_lokasi' and a.pabrik='$pabrik'
                        ) a 
                        inner join brg_barangklp f on a.kode_klp=f.kode_klp and a.kode_lokasi=f.kode_lokasi 
                        left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah) as sawal 
                                from brg_sawal 
                                where periode='".substr($periode,0,4)."01' and kode_lokasi='$kode_lokasi' 
                                group by kode_lokasi,kode_barang,kode_gudang 
                        ) b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.kode_gudang 
                        left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah+bonus) as beli 
                                from brg_trans_d 
                                where modul='BRGBELI' and periode like '".substr($periode,0,4)."%' and periode <= '".$periode."' and kode_lokasi='".$kode_lokasi."' 
							    group by kode_lokasi,kode_barang,kode_gudang 
						) c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.kode_gudang   
                        left join (select kode_barang,kode_gudang,kode_lokasi,sum(stok) as sakhir 
					             from brg_stok where kode_lokasi='".$kode_lokasi."' and nik_user ='".$nik."' 
							     group by kode_lokasi,kode_barang,kode_gudang 
								 ) d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.kode_gudang=d.kode_gudang 
                        left join brg_hpp e on a.kode_barang=e.kode_barang and a.kode_lokasi=e.kode_lokasi and e.nik_user ='".$nik."' 
					  ) x where  x.hpp<>0 order by x.kode_barang ";
            $res = DB::connection($this->db)->select($sql);						
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $tot=0;
                for ($i=0;$i< count($res); $i++){
                    $line =  $res[$i];												
                    $tot = $tot + floatval($line['hpp']);
                }
		        $success['total'] = $tot;
                $success['data'] = $res;
                
                //--------- kode_barang tidak terdaftar -----
                $brgIlegal = 0;

		        $strSQL =  "select distinct 0 as no,a.kode_barang,a.tgl_ed
					from brg_trans_d a left join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and b.pabrik='$pabrik'
					where b.kode_barang is null and a.modul='BRGBELI' and a.periode >= '".substr($periode,0,4)."' and a.periode <= '".$periode."' and a.kode_lokasi='".$kode_lokasi."' ";
                $res2 = DB::connection($this->db)->select($strSQL);						
                $res2= json_decode(json_encode($res2),true);
                $success['detail'] = $res2;
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            $success['data'] = [];
            $success['detail'] = [];
            return response()->json($success, $this->successStatus);
        }
        
    }

}

