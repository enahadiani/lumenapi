<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class ClosingJadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
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

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->sql)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
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

            $auth = DB::connection($this->sql)->select($strSQL);
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
                $get = DB::connection($this->sql)->select($strSQL2);
                if(count($get) > 0){
                    $per_awal = $this->namaPeriode($get[0]->per_awal);
                    $per_akhir = $this->namaPeriode($get[0]->per_akhir);
                    $msg = "Transaksi tidak dapat disimpan karena tanggal di periode $periode ditutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
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

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_closing,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai 
            from dgw_closing_d a inner join trans_m b on a.no_closing=b.no_bukti and a.kode_lokasi=b.kode_lokasi 					 					 
            where a.kode_lokasi='".$kode_lokasi."' and b.posted ='F' and b.form='CLOSING' 
            order by a.tanggal ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKurs(Request $request)
    {
        $this->validate($request,[
            'kode_curr' => 'required',
            'tgl_bayar' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kurs from dgw_kurs where kd_curr = '".$request->kode_curr."' and tgl = '".$request->tgl_bayar."' and kode_lokasi='".$kode_lokasi."' order by id DESC ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['kurs'] = $res[0]['kurs'];
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['kurs'] = 1;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRegistrasi(Request $request)
    {
        $this->validate($request,[
            'no_paket' => 'required',
            'no_jadwal' => 'required',
            'kurs' => 'required',
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select 
            a.no_reg,b.no_peserta+' - '+b.nama as nama, f.kode_akun as akun_titip,f.akun_piutang,f.akun_pdpt, (a.harga+a.harga_room) as harga_paket, isnull(d.biaya_tambah,0)-a.diskon as biaya_tambah, isnull(h.biaya_dok,0) as biaya_dok, 						
            isnull(e.bayar_p,0) as bayar_p, isnull(e.bayar_t,0) as bayar_t, isnull(e.bayar_m,0) as bayar_m, 
            (a.harga+a.harga_room) - isnull(e.bayar_p,0) as saldo_p,  
            isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0) as saldo_t, 
            isnull(h.biaya_dok,0) - isnull(e.bayar_m,0) as saldo_m, 
            isnull(e.bayar_p_idr,0) as bayar_p_idr, 
            isnull(e.bayar_p_idr,0) + isnull(e.bayar_t,0) + isnull(e.bayar_m,0) as  tot_bayaridr, 
            (case when ((a.harga+a.harga_room) - isnull(e.bayar_p,0) <> 0) then (  ((a.harga+a.harga_room) - isnull(e.bayar_p,0)) * ".floaval($request->kurs)."  ) else 0 end) 
            + (isnull(d.biaya_tambah,0) - a.diskon - isnull(e.bayar_t,0)) 
            + (isnull(h.biaya_dok,0) - isnull(e.bayar_m,0)) 
            as tot_saldo_idr, 
            (a.harga+a.harga_room) * ".floaval($request->kurs)." as pdpt_paket_idr 
            from dgw_reg a  
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi and c.no_closing='-' 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi =b.kode_lokasi 
            inner join dgw_paket g on a.no_paket=g.no_paket and a.kode_lokasi =g.kode_lokasi 
            inner join dgw_jenis_produk f on g.kode_produk=f.kode_produk and g.kode_lokasi =f.kode_lokasi 
            left join ( 
              select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_tambah 
              from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='TAMBAHAN' 
              where a.kode_lokasi ='".$kode_lokasi."' 
              group by a.kode_lokasi,a.no_reg 
            ) d on a.no_reg=d.no_reg and a.kode_lokasi=d.kode_lokasi 
            left join ( 
              select a.kode_lokasi,a.no_reg,sum(a.nilai) as biaya_dok 
              from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='DOKUMEN' 
              where a.kode_lokasi ='".$kode_lokasi."' 
              group by a.kode_lokasi,a.no_reg 
            ) h on a.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi 
            left join (						 
              select  no_reg,kode_lokasi,sum(nilai_p) as bayar_p,sum(nilai_t) as bayar_t,sum(nilai_m) as bayar_m,sum(nilai_p * kurs) as bayar_p_idr 
              from dgw_pembayaran where kode_lokasi ='".$kode_lokasi."' 
              group by kode_lokasi,no_reg 						 
            ) e on a.no_reg=e.no_reg and a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi ='".$kode_lokasi."' and a.no_paket='".$request->no_paket."' and a.no_jadwal='".$request->no_jadwal."'");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
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
            'tanggal' => 'required|date_format:Y-m-d',
            'no_reg' => 'required',
            'nama' => 'required',
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'akun_titip' => 'required',
            'kurs_closing' => 'required',
            'akun_tambah' => 'required',
            'akun_dokumen' => 'required', 
            'paket' => 'required',
            'jenis' => 'required',
            'tgl_berangkat' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'biaya' => 'required|array',
            'biaya.*.kode_biaya' => 'required',
            'biaya.*.jenis_biaya' => 'required',
            'biaya.*.kode_akun' => 'required',
            'biaya.*.bayar' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2("KB",$status_admin,$periode);
            if($cek['status']){

                
                $no_bukti = $this->generateKode("dgw_pembayaran", "no_kwitansi", $kode_lokasi.'-TT'.substr($periode,2,4).".", "0001");
                sql.add("insert into dgw_closing_d (no_closing,kode_lokasi,no_paket,no_jadwal,tanggal,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,tgl_input,nik_user) values  "+
							"('"+this.e_nb.getText()+"','"+this.app._lokasi+"','"+this.cb_paket.getText()+"','"+this.cb_jadwal.rightLabelCaption+"','"+this.dp_d1.getDateString()+"','"+this.e_ket.getText()+"','"+this.app._kodePP+"','CLOSING','"+this.e_periode.getText()+"','"+this.c_curr.getText()+"',"+nilaiToFloat(this.e_kurs.getText())+","+parseNilai(this.e_pdpt.getText())+",getdate(),'"+this.app._userLog+"')");
												
					sql.add("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values "+
							"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','MI','CLOSING','F','-','-','"+this.app._kodePP+"','"+this.dp_d1.getDateString()+"','-','-','"+this.c_curr.getText()+"',"+nilaiToFloat(this.e_kurs.getText())+","+
							parseNilai(this.e_pdpt.getText())+",0,0,'-','-','-','-','-','-','-','-','-')");
		
					for (var i=0;i < this.dataJU.rs.rows.length;i++){
						var line = this.dataJU.rs.rows[i];
						
						sql.add("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values "+
								"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','"+line.no_reg+"','"+this.dp_d1.getDateString()+"',"+i+",'"+line.akun_titip+"','D',"+parseFloat(line.bayar_p_idr)+","+
								parseFloat(line.bayar_p_idr)+",'"+this.e_ket.getText()+"','MI','TITIP','IDR',1,'"+this.app._kodePP+"','-','-','-','-','-','-','-','-')");	

						if (parseFloat(line.tot_saldo_idr) != 0) {		
							sql.add("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values "+
									"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','"+line.no_reg+"','"+this.dp_d1.getDateString()+"',"+i+",'"+line.akun_piutang+"','D',"+parseFloat(line.tot_saldo_idr)+","+
									parseFloat(line.tot_saldo_idr)+",'"+this.e_ket.getText()+"','MI','PIUTANG','IDR',1,'"+this.app._kodePP+"','-','-','-','-','-','-','-','-')");																																			
						}
						
						sql.add("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values "+
								"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','"+line.no_reg+"','"+this.dp_d1.getDateString()+"',"+i+",'"+line.akun_pdpt+"','C',"+parseFloat(line.pdpt_paket_idr)+","+
								parseFloat(line.pdpt_paket_idr)+",'"+this.e_ket.getText()+"','MI','PDPT','IDR',1,'"+this.app._kodePP+"','-','-','-','-','-','-','-','-')");																																								

						sql.add("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values "+
								"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','"+line.no_reg+"','"+this.dp_d1.getDateString()+"',"+i+",'"+this.akunTambah+"','C',"+parseFloat(line.saldo_t)+","+
								parseFloat(line.saldo_t)+",'"+this.e_ket.getText()+"','MI','PDPTTAMBAH','IDR',1,'"+this.app._kodePP+"','-','-','-','-','-','-','-','-')");																																								

						sql.add("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values "+
								"('"+this.e_nb.getText()+"','"+this.app._lokasi+"',getdate(),'"+this.app._userLog+"','"+this.e_periode.getText()+"','"+line.no_reg+"','"+this.dp_d1.getDateString()+"',"+i+",'"+this.akunDokumen+"','C',"+parseFloat(line.saldo_m)+","+
								parseFloat(line.saldo_m)+",'"+this.e_ket.getText()+"','MI','PDPTDOK','IDR',1,'"+this.app._kodePP+"','-','-','-','-','-','-','-','-')");																																								
					}						
					
					sql.add("update dgw_jadwal set no_closing='"+this.e_nb.getText()+"',kurs_closing="+nilaiToFloat(this.e_kurs.getText())+" where no_paket='"+this.cb_paket.getText()+"' and no_jadwal='"+this.cb_jadwal.rightLabelCaption+"' and kode_lokasi='"+this.app._lokasi+"'");
                        
                DB::connection($this->sql)->commit();
                $success['status'] = "SUCCESS";
                $success['no_kwitansi'] = $no_bukti;
                $success['message'] = "Data Pembayaran berhasil disimpan. No Bukti:".$no_bukti;
            }else{
                
                $success['status'] = "FAILED";
                $success['no_kwitansi'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id = $request->no_reg;
            if(isset($request->no_bukti)){
                $no_bukti = $request->no_bukti;
                $sql = "select a.no_bukti,a.keterangan,a.param1 as kode_akun 
                from trans_m a
                inner join dgw_pembayaran b on a.no_bukti=b.no_kb and a.kode_lokasi=b.kode_lokasi
                where b.no_kwitansi='$no_bukti' and a.kode_lokasi='$kode_lokasi' and a.no_ref1='".$id."'";
                $res4 = DB::connection($this->sql)->select($sql);
                $res4 = json_decode(json_encode($res4),true);
                
            }else{
                $no_bukti = $this->generateKode("dgw_pembayaran", "no_kwitansi", $kode_lokasi.'-TT'.date('ym'), "0001");
                $res4 = array();
            }

            $sql = "select b.no_reg,a.nama,d.tgl_berangkat,e.nama as paket,e.kode_curr,b.harga + b.harga_room as harga_tot,  case when d.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun,d.kurs_closing, d.no_closing, f.akun_piutang, b.diskon,isnull(g.jenis,'-') as jenis,isnull(g.sistem_bayar,'-') as status_bayar,g.tgl_bayar  
            from dgw_peserta a 
                inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
                left join dgw_jadwal d on b.no_paket=d.no_paket and b.no_jadwal=d.no_jadwal and b.kode_lokasi=d.kode_lokasi 
                inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 
                inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi
                left join dgw_pembayaran g on b.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi
            where b.no_reg='".$id."' and b.kode_lokasi='".$kode_lokasi."' and g.no_kwitansi='$no_bukti' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen, isnull(sum(total_bayar),0) as total_bayar
            from dgw_pembayaran 
            where no_reg='".$id."' and kode_lokasi='".$kode_lokasi."' and no_kwitansi ='".$no_bukti."'";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.nilai-isnull(d.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			 left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga_room-isnull(d.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.harga_room <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'PAKET' as kode_biaya, a.harga-a.diskon as tarif, a.harga-a.diskon as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga-isnull(d.byr,0)-a.diskon as saldo, 1 as jml, 
                    'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.harga <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            order by curr desc");
            $res3 = json_decode(json_encode($res3),true);

            $sql5 = " select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t,nilai_m, a.total_bayar as total_idr 
            from dgw_pembayaran a 
            inner join trans_m b on a.no_kb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and a.no_reg='$id' and b.form in ('KBREG','KBGROUP') and a.no_kwitansi <> '$no_bukti' ";
            $res5 = DB::connection($this->sql)->select( $sql5);
            $res5 = json_decode(json_encode($res5),true);

            $sql6= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen, isnull(sum(total_bayar),0) as total_bayar
            from dgw_pembayaran 
            where no_reg='".$id."' and kode_lokasi='".$kode_lokasi."' and no_kwitansi <>'".$no_bukti."'";
            $res6 = DB::connection($this->sql)->select($sql6);
            $res6 = json_decode(json_encode($res6),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $totTambah = $totDok = 0;
                if (count($res3) > 0){
                    for($i=0;$i<count($res3);$i++){
                        if ($res3[$i]['jenis'] == "TAMBAHAN") $totTambah += floatval($res3[$i]['nilai']);
                        if ($res3[$i]['jenis'] == "DOKUMEN") $totDok += floatval($res3[$i]['nilai']);	
                    }
                } 
                
                $success['status'] = "SUCCESS";
                $success['data_jamaah'] = $res;
                $success['detail_bayar'] = $res2;
                $success['detail_biaya'] = $res3;
                $success['data_bayar'] = $res4;
                $success['histori_bayar'] = $res5;
                $success['detail_bayar_lain'] = $res6;
                $success['totTambah']=$totTambah;
                $success['totDok']=$totDok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data_jamaah'] = [];
                $success['detail_bayar'] = [];
                $success['detail_biaya'] = [];
                $success['data_bayar'] = [];
                $success['histori_bayar'] = [];
                $success['detail_bayar_lain'] = [];
                $success['totTambah']=0;
                $success['totDok']=0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

     /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id = $request->no_reg;
            if(isset($request->no_bukti)){
                // $no_bukti = $request->no_bukti;
                // $sql = "select no_bukti,keterangan,param1 as kode_akun 
                // from trans_m 
                // where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and no_ref1='".$id."'";
                // $res4 = DB::connection($this->sql)->select( $sql);
                // $res4 = json_decode(json_encode($res4),true);
                $no_bukti = $request->no_bukti;
                $sql = "select a.no_bukti,a.keterangan,a.param1 as kode_akun 
                from trans_m a
                inner join dgw_pembayaran b on a.no_bukti=b.no_kb and a.kode_lokasi=b.kode_lokasi
                where b.no_kwitansi='$no_bukti' and a.kode_lokasi='$kode_lokasi' and a.no_ref1='".$id."'";
                $res4 = DB::connection($this->sql)->select($sql);
                $res4 = json_decode(json_encode($res4),true);
                
            }else{
                $no_bukti = $this->generateKode("dgw_pembayaran", "no_kwitansi", $kode_lokasi.'-TT'.date('ym'), "0001");
                $res4 = array();
            }

            $sql = "select b.no_reg,a.nama,d.tgl_berangkat,e.nama as paket,e.kode_curr,b.harga + b.harga_room as harga_tot,  case when d.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun,d.kurs_closing, d.no_closing, f.akun_piutang, b.diskon 
            from dgw_peserta a 
                inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
                left join dgw_jadwal d on b.no_paket=d.no_paket and b.no_jadwal=d.no_jadwal and b.kode_lokasi=d.kode_lokasi 
                inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 
                inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi
            where b.no_reg='".$id."' and b.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen, isnull(sum(total_bayar),0) as total_bayar
            from dgw_pembayaran 
            where no_reg='".$id."' and kode_lokasi='".$kode_lokasi."' and no_kwitansi <> '".$no_bukti."'";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr,a.nilai-isnull(c.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.nilai <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr,a.harga_room-isnull(c.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga_room <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'PAKET' as kode_biaya, a.harga-isnull(a.diskon,0) as tarif, a.harga-isnull(a.diskon,0) as nilai,isnull(c.byr,0) as byr,a.harga-isnull(a.diskon,0)-isnull(c.byr,0) as saldo, 1 as jml, 'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
           
            order by curr desc");
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = " select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t,a.nilai_m, a.total_bayar as total_idr 
            from dgw_pembayaran a 
            inner join trans_m b on a.no_kb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and a.no_reg='$id' and b.form in ('KBREG','KBGROUP')  ";
            $res4 = DB::connection($this->sql)->select( $sql4);
            $res4 = json_decode(json_encode($res4),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $totTambah = $totDok = 0;
                if (count($res3) > 0){
                    for($i=0;$i<count($res3);$i++){
                        if ($res3[$i]['jenis'] == "TAMBAHAN") $totTambah += floatval($res3[$i]['nilai']);
                        if ($res3[$i]['jenis'] == "DOKUMEN") $totDok += floatval($res3[$i]['nilai']);	
                    }
                } 
                
                $success['status'] = "SUCCESS";
                $success['data_jamaah'] = $res;
                $success['detail_bayar'] = $res2;
                $success['detail_biaya'] = $res3;
                $success['histori_bayar'] = $res4;
                $success['totTambah']=$totTambah;
                $success['totDok']=$totDok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['biaya_tambahan'] = [];
                $success['biaya_dokumen'] = [];
                $success['histori_bayar'] = [];
                $success['totTambah']=0;
                $success['totDok']=0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
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
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_reg' => 'required',
            'nama' => 'required',
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'akun_titip' => 'required',
            'kurs_closing' => 'required',
            'akun_tambah' => 'required',
            'akun_dokumen' => 'required', 
            'paket' => 'required',
            'jenis' => 'required',
            'tgl_berangkat' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'biaya' => 'required|array',
            'biaya.*.kode_biaya' => 'required',
            'biaya.*.jenis_biaya' => 'required',
            'biaya.*.kode_akun' => 'required',
            'biaya.*.bayar' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            if($request->jenis == "NONCASH"){
                $modul = "MI";
                $format = "JU";
            }else{
                $modul = "KB";
                $format = "BM";
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2($modul,$status_admin,$periode);
            if($cek['status']){
                $d = DB::connection($this->sql)->select("select kode_spro,flag from spro where kode_spro in ('LKURS','RKURS','AKUNT','AKUND','AKUNOI','AKUNOE') and kode_lokasi = '".$kode_lokasi."'");
                $d = json_decode(json_encode($d),true);	
                if (count($d) > 0){
                    for ($i=0;$i<count($d);$i++){
                        $line = $d[$i];	
                        if ($line['kode_spro'] == "AKUNOI") $akunOI = $line['flag'];
                        if ($line['kode_spro'] == "AKUNOE") $akunOE = $line['flag'];
                    }
                }	

                $no_bukti = $request->no_bukti;

                $nk = DB::connection($this->sql)->select("select no_kb from dgw_pembayaran where no_kwitansi='$no_bukti' and kode_lokasi='$kode_lokasi' ");	
                if (count($nk) > 0){
                    $no_kb = $nk[0]->no_kb;
                }else{
                    $no_kb = "-";
                }

                
                $del = DB::connection($this->sql)->table('trans_m')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_bukti', $no_kb)
                    ->delete();

                $del2 = DB::connection($this->sql)->table('trans_j')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_bukti', $no_kb)
                    ->delete();
                
                $del3 = DB::connection($this->sql)->table('dgw_pembayaran')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_kwitansi', $request->no_bukti)
                    ->delete();	
                
                $del4 = DB::connection($this->sql)->table('dgw_pembayaran_d')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_kwitansi', $request->no_bukti)
                    ->delete();

                $bayarPaketIDR = floatval($request->bayar_paket)*floatval($request->kurs);    
                $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$modul,'KBREG','F','-','-',$request->kode_pp,$request->tanggal,$request->no_reg,$request->deskripsi,$request->kode_curr,$request->kurs,$request->total_bayar,0,0,'-','-','-',$request->no_reg,'-','-',$request->kode_akun,'-',$format));

                $ins2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,0,$request->kode_akun,'D',$request->total_bayar,$request->total_bayar,$request->deskripsi,$modul,$modul,'IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                
                //PENAMBAHAN KURS
                $totalOri = (floatval($request->kurs) *  floatval($request->bayar_paket)) + floatval($request->bayar_tambahan) + floatval($request->bayar_dok);							
                $sls = floatval($request->total_bayar) - $totalOri;		
                if ($sls != 0) {								
                    if ($sls < 0) {							
                        $sls = $sls * -1;
                        $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_kb."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOE."','D',".$sls.",".$sls.",'".$request->deskripsi."','$modul','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                    }
                    else {							
                        $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_kb."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOI."','C',".$sls.",".$sls.",'".$request->deskripsi."','$modul','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");											
                    }
                }	
                //
                    
                $ins3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,1,$request->akun_titip,'C',$bayarPaketIDR,$request->bayar_paket,$request->deskripsi,$modul,'TTPPAKET',$request->kode_curr,$request->kurs,$request->kode_pp,'-','-','-','-','-','-','-','-'));										
                    
                if (intval($request->bayar_tambahan) != 0 || intval($request->bayar_dok) != 0 || intval($request->bayar_paket) != 0) 
                { 
                    $nilai_t=0;$nilai_d=0;$total_t=0;$total_d=0;$ser_t=array();$ser2_t=array();$ser_d=array();$ser2_d=array();$tes=array();
                    $biaya = $request->biaya;
                    for($i=0; $i<count($biaya);$i++){
                        if(intval($biaya[$i]['bayar']) != 0){
                            if($biaya[$i]['jenis_biaya'] == 'TAMBAHAN'){
                                $nilai_t = intval($biaya[$i]['bayar']);
                                array_push($tes,$nilai_t);
                                $isAda_t = false;
                                $idx_t = 0;
                                
                                $akun_t = $biaya[$i]['kode_akun'];						
                                for ($c=0;$c <= $i;$c++)
                                {
                                    if(isset($biaya[$c-1]['kode_akun']))
                                    {
                                        
                                        if ($akun_t == $biaya[$c-1]['kode_akun']) 
                                        {
                                            $isAda_t = true;
                                            $idx_t = $c;
                                            break;
                                        }
                                    }
                                }

                                if (!$isAda_t) {							
                                    array_push($ser_t,$biaya[$i]['kode_akun']);
                                    
                                    $ser2_t[$biaya[$i]['kode_akun']]=$nilai_t;
                                } 
                                else { 
                                    $total_t = $ser2_t[$biaya[$i]['kode_akun']];
                                    $total_t = $total_t + $nilai_t;
                                    $ser2_t[$biaya[$i]['kode_akun']]=$total_t;
                                }		
                            }else if($biaya[$i]['jenis_biaya'] == 'DOKUMEN'){
                                $nilai_d = intval($biaya[$i]['bayar']);
                                $isAda_d = false;
                                $idx_d = 0;
                                
                                $akun_d = $biaya[$i]['kode_akun'];						
                                for ($c=0;$c <= $i;$c++){
                                    if(isset($biaya[$c-1]['kode_akun'])){
                                        if ($akun_d == $biaya[$c-1]['kode_akun']) {
                                            $isAda_d = true;
                                            $idx_d = $c;
                                        break;
                                        }
                                    }
                                }
                                if (!$isAda_d) {							
                                    array_push($ser_d,$biaya[$i]['kode_akun']);
                                    
                                    $ser2_d[$biaya[$i]['kode_akun']]=$nilai_d;
                                } 
                                else { 
                                    $total_d = $ser2_d[$biaya[$i]['kode_akun']];
                                    $total_d = $total_d + $nilai_d;
                                    $ser2_d[$biaya[$i]['kode_akun']]=$total_d;
                                }
                            }
                        
                            $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai) values(?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,$request->no_reg,$biaya[$i]['kode_biaya'],$biaya[$i]['jenis_biaya'],$biaya[$i]['bayar']));
                        } 
                    }	
                    $nu =2;
                    for($x=0; $x<count($ser_t);$x++){
                        
                        if($request->akun_tambah == "" || $request->akun_tambah == "-"){
                            
                            $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_t[$x],'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,$modul,'PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        }else{
                            $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_tambah,'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,$modul,'PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        }
                        $nu++;
                        
                    }
                    
                    $nu =3;
                    for($x=0; $x<count($ser_d);$x++){
                        if($request->akun_dokumen == "" || $request->akun_dokumen == "-"){   
                            $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_d[$x],'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,$modul,'PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        }else{
                            $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_kb,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_dokumen,'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,$modul,'PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        }
                        $nu++;
                        
                    }
                }		
            
                $insp = DB::connection($this->sql)->update("insert into dgw_pembayaran (no_kwitansi,no_reg,jadwal,tgl_bayar,paket,sistem_bayar,kode_lokasi,periode,nilai_t,nilai_p,kode_curr,kurs,nilai_m,flag_ver,no_kb,jenis,total_bayar) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_reg,$request->tgl_berangkat,$request->tanggal,$request->paket,$request->status_bayar,$kode_lokasi,$periode,$request->bayar_tambahan,$request->bayar_paket,$request->kode_curr,$request->kurs,$request->bayar_dok,'0',$no_kb,$request->jenis,$request->total_bayar));
                
                //hitung selisih kurs pembyaran dan closing jadwal (untuk yg di piutang-kan - saat berangkat blm lunas)
                //jika pembayran dilakukan setelah berangkat
                if (floatval($request->kurs_closing) != 0 && floatval($request->kurs_closing) != floatval($request->kurs))  {
                    $sls = (floatval($request->kurs) - floatval($request->kurs_closing)) * floatval($request->bayar_paket);
                    if ($sls !=0 ) {
                        if ($sls > 0){ 
                            $akunKurs = $lKurs;
                            $dc = "C";
                            $dcPiutang = "D";
                        }
                        else {
                            $akunKurs = $rKurs;
                            $dc = "D";
                            $dcPiutang = "C";
                        }
                        $sls = abs($sls);
                        
                        $insk2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_kb."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',777,'".$akunKurs."','".$dc."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','$modul','SKURS','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                        
                        $insk3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_kb."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',778,'".$request->akun_titip."','".$dcPiutang."',".$sls.",".$sls.",'Selisih Kurs a.n ".$request->nama."','$modul','SLSPIU','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");		
                    }
                }

                DB::connection($this->sql)->commit();
                $success['status'] = "SUCCESS";
                $success['no_terima'] = $no_bukti;
                $success['message'] = "Data Pembayaran berhasil diubah";
            }else{
                
                $success['status'] = "FAILED";
                $success['no_terima'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }		
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nk = DB::connection($this->sql)->select("select no_kb from dgw_pembayaran where no_kwitansi='$request->no_bukti' and kode_lokasi='$kode_lokasi' ");
            if (count($nk) > 0){
                $no_kb = $nk[0]->no_kb;
            }else{
                $no_kb = "-";
            }
            $success['no_kb'] = $no_kb;

            $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_kb)
                ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_kb)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_pembayaran')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_pembayaran_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	

            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil dihapus ";
            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
