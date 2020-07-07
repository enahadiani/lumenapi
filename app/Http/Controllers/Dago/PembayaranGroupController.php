<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PembayaranGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_peserta from dgw_paket where id_peserta ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function getNoBukti(Request $request)
    {
        $this->validate($request,[
            'tanggal' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m","no_bukti",$kode_lokasi."-BM".substr($periode,2,4).".","0001");

            $success['status'] = "SUCCESS";
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_bukti, convert(varchar,a.tanggal,103) as tanggal, b.no_agen+' - '+b.nama_agen as agen, a.nilai1 
            from trans_m a inner join dgw_agent b on a.nik1=b.no_agen and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and a.posted='F'  and a.form='KBGROUP' order by a.no_bukti");
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

    public function getDetailBiaya(Request $request)
    {
        $this->validate($request,[
            'no_reg' => 'required',
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr,a.nilai-isnull(c.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <>'".$request->no_bukti."'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.nilai <> 0 and a.no_reg='$request->no_reg' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr,a.harga_room-isnull(c.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$request->no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga_room <> 0 and a.no_reg='$request->no_reg' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'PAKET' as kode_biaya, a.harga-isnull(a.diskon,0) as tarif, a.harga-isnull(a.diskon,0) as nilai,isnull(c.byr,0) as byr,a.harga-isnull(a.diskon,0)-isnull(c.byr,0) as saldo, 1 as jml, 'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$request->no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga <> 0 and a.no_reg='$request->no_reg' and a.kode_lokasi='$kode_lokasi' 
           
            order by curr desc");
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

    public function getRegistrasi(Request $request)
    {
        $this->validate($request,[
            'tanggal' => 'required',
            'no_agen' => 'required',
            'no_peserta' =>'required',
            'no_paket' =>'required',
            'no_jadwal' =>'required',
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->no_peserta != "") {
                $jamaahRef = " and b.no_peserta_ref='".$request->no_peserta."' ";
            }
			else {
                $jamaahRef = " ";
            }
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $request->no_bukti;

            $strSQL = "select a.nama, b.no_reg, b.no_peserta,round((b.harga+b.harga_room) - isnull(g.bayar_p,0),4) as saldo_p, 
                        isnull(h.nilai_t,0) - isnull(g.bayar_t,0) - b.diskon as saldo_t, 
                        isnull(i.nilai_m,0) - isnull(g.bayar_m,0) as saldo_m, 
                        convert(varchar,c.tgl_berangkat,103) as tgl_berangkat, e.nama as paket, b.no_paket,
                        case when c.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun, c.no_closing, c.kurs_closing 
                        from dgw_peserta a 
                        inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi and b.no_paket='".$request->no_paket."' and b.no_jadwal='".$request->no_jadwal."' ".$jamaahRef." 
						inner join dgw_jadwal c on b.no_paket = c.no_paket and b.no_jadwal = c.no_jadwal and b.kode_lokasi=c.kode_lokasi 
						inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 	
                        inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi 
                        left join (
                            select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai_t 
                            from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='TAMBAHAN' 
                            where a.kode_lokasi='".$kode_lokasi."' group by a.no_reg,a.kode_lokasi  
                        ) h on b.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi 
                        left join (
                            select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai_m 
                            from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and b.jenis='DOKUMEN' 
                            where a.kode_lokasi='".$kode_lokasi."' group by a.no_reg,a.kode_lokasi  
                            ) i on b.no_reg=i.no_reg and a.kode_lokasi=i.kode_lokasi 
                        left join (
                            select no_reg,kode_lokasi,sum(nilai_p) as bayar_p, sum(nilai_t) as bayar_t, sum(nilai_m) as bayar_m 
                            from dgw_pembayaran 
                            where kode_lokasi='".$kode_lokasi."' and no_kwitansi <> '".$no_bukti."' group by no_reg,kode_lokasi  
                        ) g on b.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi 
                        where ( ((b.harga+b.harga_room) - isnull(g.bayar_p,0) > 0)  or  (isnull(h.nilai_t,0) - b.diskon - isnull(g.bayar_t,0) > 0)  or  (isnull(i.nilai_m,0) - isnull(g.bayar_m,0) > 0) ) and b.no_agen='".$request->no_agen."' and b.kode_lokasi='".$kode_lokasi."' ";

            $res = DB::connection($this->sql)->select($strSQL);
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr,a.nilai-isnull(c.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_reg d on a.no_reg=d.no_reg and a.kode_lokasi=d.kode_lokasi
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.nilai <> 0  and a.kode_lokasi='$kode_lokasi' and d.no_peserta='$request->no_peserta' and d.no_paket='".$request->no_paket."' and d.no_jadwal='".$request->no_jadwal."' and d.no_agen='".$request->no_agen."'
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr,a.harga_room-isnull(c.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga_room <> 0 and a.kode_lokasi='$kode_lokasi' and a.no_peserta='$request->no_peserta' and a.no_paket='".$request->no_paket."' and a.no_jadwal='".$request->no_jadwal."' and a.no_agen='".$request->no_agen."'
            union all 
            select 'PAKET' as kode_biaya, a.harga-isnull(a.diskon,0) as tarif, a.harga-isnull(a.diskon,0) as nilai,isnull(c.byr,0) as byr,a.harga-isnull(a.diskon,0)-isnull(c.byr,0) as saldo, 1 as jml, 'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga <> 0 and a.kode_lokasi='$kode_lokasi' and a.no_peserta='$request->no_peserta' and a.no_paket='".$request->no_paket."' and a.no_jadwal='".$request->no_jadwal."' and a.no_agen='".$request->no_agen."'
           
            order by curr desc");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'no_agen' => 'required',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'nik_user' => 'required',
            'no_bukti' => 'required',
            'no_reg' => 'required|array',
            'nama' => 'required|array',
            'paket' => 'required|array',
            'akun_titip' => 'required|array',
            'tgl_berangkat' => 'required|array',
            'nilai_paket' => 'required|array',
            'nilai_tambahan' => 'required|array',
            'nilai_dok' => 'required|array',
            'kurs_closing' => 'required|array',
            'no_closing' => 'required|array',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $d = DB::connection($this->sql)->select("select kode_spro,flag from spro where kode_spro in ('LKURS','RKURS','AKUNT','AKUND','AKUNOI','AKUNOE') and kode_lokasi = '".$kode_lokasi."'");
            $d = json_decode(json_encode($d),true);	
            if (count($d) > 0){
				for ($i=0;$i<count($d);$i++){
					$line = $d[$i];	
					if ($line['kode_spro'] == "AKUNOI") $akunOI = $line['flag'];
                    if ($line['kode_spro'] == "AKUNOE") $akunOE = $line['flag'];
                    if ($line['kode_spro'] == "LKURS") $lKurs = $line['flag'];
                    if ($line['kode_spro'] == "RKURS") $rKurs = $line['flag'];
				}
            }	

            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-BM'.substr($periode,2,4).".", "0001");
            
            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBGROUP','F','-','-',$request->kode_pp,$request->tanggal,'-',$request->deskripsi,$request->kode_curr,$request->kurs,$request->total_bayar,0,0,$request->no_agen,'-','-','-','-','-',$request->kode_akun,$request->status_bayar,'BM'));

            $ins2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,999,$request->kode_akun,'D',$request->total_bayar,$request->total_bayar,$request->deskripsi,'KB','KB','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));

            //PENAMBAHAN KURS
            $totalOri = (floatval($request->kurs) *  floatval($request->bayar_paket)) + floatval($request->bayar_tambahan) + floatval($request->bayar_dok);							
            $sls = floatval($request->total_bayar) - $totalOri;		
            if ($sls != 0) {								
				if ($sls < 0) {							
					$sls = $sls * -1;
                    $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOE."','D',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                }
				else {							
					$insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOI."','C',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");											
				}
			}	
            //

            for($i=0;$i<count($request->no_reg);$i++){


                $insp = DB::connection($this->sql)->update("insert into dgw_pembayaran (no_kwitansi,no_reg,jadwal,tgl_bayar,paket,sistem_bayar,kode_lokasi,periode,nilai_t,nilai_p,kode_curr,kurs,nilai_m) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_reg[$i],$request->tgl_berangkat[$i],$request->tanggal,$request->paket[$i],$request->status_bayar,$kode_lokasi,$periode,$request->nilai_tambahan[$i],$request->nilai_paket[$i],$request->kode_curr,$request->kurs,$request->nilai_dok[$i]));

                if(floatval($request->bayar_paket) > 0){
                    
                    $bayarPaketIDR = floatval($request->nilai_paket[$i])*floatval($request->kurs);
    
                    $ins3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,1,$request->akun_titip[$i],'C',$bayarPaketIDR,$request->nilai_paket[$i],$request->deskripsi,'KB','KB',$request->kode_curr,$request->kurs,$request->kode_pp,'-','-','-','-','-','-','-','-'));				
                }

                //hitung selisih kurs pembyaran dan closing jadwal (untuk yg di piutang-kan - saat berangkat blm lunas)
                //jika pembayran dilakukan setelah berangkat
                if (floatval($request->no_closing[$i]) !="-"){
                    if( floatval($request->kurs_closing[$i]) != 0 && floatval($request->kurs_closing[$i]) != floatval($request->kurs))  {

                        $sls = (floatval($request->kurs) - floatval($request->kurs_closing[$i])) * floatval($request->nilai_paket[$i]);
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
                            
                            $insk2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',777,'".$akunKurs."','".$dc."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SKURS','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                            
                            $insk3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',778,'".$request->akun_titip[$i]."','".$dcPiutang."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SLSPIU','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");		
                        }
                    }
                
                }

                $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai) select no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai from dgw_pembayaran_d_tmp where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."'");

                if (intval($request->nilai_tambahan[$i]) != 0 ) {

                    $biaya_t = DB::connection($this->sql)->select("select no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis,sum(nilai) as nilai 
                    from dgw_pembayaran_d_tmp 
                    where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."' and jenis in ('TAMBAHAN')
                    group by no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis ");
                    $biaya_t = json_decode(json_encode($biaya_t),true);
	

                    $nu = 1000+$i;
                    for($x=0; $x<count($biaya_t);$x++){
                            
                        $ins4[$x] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,$nu,$biaya_t[$x]['kode_akun'],'C',$biaya_t[$x]['nilai'],$biaya_t[$x]['nilai'],'Pembayaran a.n Reg '.$request->no_reg[$i],'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                       
                        $nu++;
                            
                    }
                }

                if (intval($request->nilai_dok[$i]) != 0 ){

                    $biaya_d = DB::connection($this->sql)->select("select no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis,sum(nilai) as nilai 
                    from dgw_pembayaran_d_tmp 
                    where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."' and jenis in ('DOKUMEN')
                    group by no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis ");
                    $biaya_d = json_decode(json_encode($biaya_d),true);
    
                    $nu = 3000+$i;
                    for($x=0; $x<count($biaya_d);$x++){
                        $ins5[$x] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,$nu,$biaya_d[$x]['kode_akun'],'C',$biaya_d[$x]['nilai'],$biaya_d[$x]['nilai'],'Pembayaran a.n Reg '.$request->no_reg[$i],'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        
                        $nu++; 
                    }
                }	
            }

            $del5 = DB::connection($this->sql)->table('dgw_pembayaran_d_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->where('nik_user', $request->nik_user)
                ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_kwitansi'] = $no_bukti;
            $success['message'] = "Data Pembayaran berhasil disimpan. No Bukti:".$no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format:Y-m-d',
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'no_agen' => 'required',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'nik_user' => 'required',
            'no_bukti' => 'required',
            'no_reg' => 'required|array',
            'nama' => 'required|array',
            'paket' => 'required|array',
            'akun_titip' => 'required|array',
            'tgl_berangkat' => 'required|array',
            'nilai_paket' => 'required|array',
            'nilai_tambahan' => 'required|array',
            'nilai_dok' => 'required|array',
            'kurs_closing' => 'required|array',
            'no_closing' => 'required|array',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $d = DB::connection($this->sql)->select("select kode_spro,flag from spro where kode_spro in ('LKURS','RKURS','AKUNT','AKUND','AKUNOI','AKUNOE') and kode_lokasi = '".$kode_lokasi."'");
            $d = json_decode(json_encode($d),true);	
            if (count($d) > 0){
				for ($i=0;$i<count($d);$i++){
					$line = $d[$i];	
					if ($line['kode_spro'] == "AKUNOI") $akunOI = $line['flag'];
                    if ($line['kode_spro'] == "AKUNOE") $akunOE = $line['flag'];
                    if ($line['kode_spro'] == "LKURS") $lKurs = $line['flag'];
                    if ($line['kode_spro'] == "RKURS") $rKurs = $line['flag'];
				}
            }	

            $no_bukti = $request->no_bukti;

            $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_pembayaran')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_pembayaran_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	
            
            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBGROUP','F','-','-',$request->kode_pp,$request->tanggal,'-',$request->deskripsi,$request->kode_curr,$request->kurs,$request->total_bayar,0,0,$request->no_agen,'-','-','-','-','-',$request->kode_akun,$request->status_bayar,'BM'));

            $ins2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,999,$request->kode_akun,'D',$request->total_bayar,$request->total_bayar,$request->deskripsi,'KB','KB','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));

            //PENAMBAHAN KURS
            $totalOri = (floatval($request->kurs) *  floatval($request->bayar_paket)) + floatval($request->bayar_tambahan) + floatval($request->bayar_dok);							
            $sls = floatval($request->total_bayar) - $totalOri;		
            if ($sls != 0) {								
				if ($sls < 0) {							
					$sls = $sls * -1;
                    $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOE."','D',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                }
				else {							
					$insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOI."','C',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");											
				}
			}	
            //

            for($i=0;$i<count($request->no_reg);$i++){


                $insp = DB::connection($this->sql)->update("insert into dgw_pembayaran (no_kwitansi,no_reg,jadwal,tgl_bayar,paket,sistem_bayar,kode_lokasi,periode,nilai_t,nilai_p,kode_curr,kurs,nilai_m) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_reg[$i],$request->tgl_berangkat[$i],$request->tanggal,$request->paket[$i],$request->status_bayar,$kode_lokasi,$periode,$request->nilai_tambahan[$i],$request->nilai_paket[$i],$request->kode_curr,$request->kurs,$request->nilai_dok[$i]));

                if(floatval($request->bayar_paket) > 0){
                    
                    $bayarPaketIDR = floatval($request->nilai_paket[$i])*floatval($request->kurs);
    
                    $ins3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,1,$request->akun_titip[$i],'C',$bayarPaketIDR,$request->nilai_paket[$i],$request->deskripsi,'KB','KB',$request->kode_curr,$request->kurs,$request->kode_pp,'-','-','-','-','-','-','-','-'));				
                }

                //hitung selisih kurs pembyaran dan closing jadwal (untuk yg di piutang-kan - saat berangkat blm lunas)
                //jika pembayran dilakukan setelah berangkat
                if (floatval($request->no_closing[$i]) !="-"){
                    if( floatval($request->kurs_closing[$i]) != 0 && floatval($request->kurs_closing[$i]) != floatval($request->kurs))  {

                        $sls = (floatval($request->kurs) - floatval($request->kurs_closing[$i])) * floatval($request->nilai_paket[$i]);
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
                            
                            $insk2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',777,'".$akunKurs."','".$dc."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SKURS','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                            
                            $insk3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg[$i]."','".$request->tanggal."',778,'".$request->akun_titip[$i]."','".$dcPiutang."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SLSPIU','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");		
                        }
                    }
                
                }

                $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai) select no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai from dgw_pembayaran_d_tmp where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."'");

                if (intval($request->nilai_tambahan[$i]) != 0 ) {

                    $biaya_t = DB::connection($this->sql)->select("select no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis,sum(nilai) as nilai 
                    from dgw_pembayaran_d_tmp 
                    where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."' and jenis in ('TAMBAHAN')
                    group by no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis ");
                    $biaya_t = json_decode(json_encode($biaya_t),true);
	

                    $nu = 1000+$i;
                    for($x=0; $x<count($biaya_t);$x++){
                            
                        $ins4[$x] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,$nu,$biaya_t[$x]['kode_akun'],'C',$biaya_t[$x]['nilai'],$biaya_t[$x]['nilai'],'Pembayaran a.n Reg '.$request->no_reg[$i],'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                       
                        $nu++;
                            
                    }
                }

                if (intval($request->nilai_dok[$i]) != 0 ){

                    $biaya_d = DB::connection($this->sql)->select("select no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis,sum(nilai) as nilai 
                    from dgw_pembayaran_d_tmp 
                    where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' and no_kwitansi='$request->no_bukti' and no_reg='".$request->no_reg[$i]."' and jenis in ('DOKUMEN')
                    group by no_kwitansi,kode_lokasi,no_reg,kode_akun,jenis ");
                    $biaya_d = json_decode(json_encode($biaya_d),true);
    
                    $nu = 3000+$i;
                    for($x=0; $x<count($biaya_d);$x++){
                        $ins5[$x] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg[$i],$request->tanggal,$nu,$biaya_d[$x]['kode_akun'],'C',$biaya_d[$x]['nilai'],$biaya_d[$x]['nilai'],'Pembayaran a.n Reg '.$request->no_reg[$i],'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                        
                        $nu++; 
                    }
                }	
            }

            $del5 = DB::connection($this->sql)->table('dgw_pembayaran_d_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->where('nik_user', $request->nik_user)
                ->delete();	

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_kwitansi'] = $no_bukti;
            $success['message'] = "Data Pembayaran berhasil diubah. No Bukti:".$no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal diubah ".$e;
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
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id = $request->no_bukti;

            $sql = "select b.nik1,a.sistem_bayar,a.kurs,sum(a.nilai_p) as nilai_p,sum(a.nilai_t) as nilai_t,sum(a.nilai_m) as nilai_m,b.param1,b.keterangan,b.nilai1,convert(varchar,b.tanggal,105) as tanggal
            from dgw_pembayaran a inner join trans_m b on a.no_kwitansi = b.no_bukti and a.kode_lokasi=b.kode_lokasi 
            where a.no_kwitansi='".$id."' and a.kode_lokasi='".$kode_lokasi."' 
            group by a.sistem_bayar,a.kurs,b.param1,b.nik1,b.keterangan,b.nilai1,convert(varchar,b.tanggal,105)";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select a.nama, b.no_reg, b.no_peserta, i.nilai_p,i.nilai_t,i.nilai_m, round((b.harga+b.harga_room) - isnull(g.bayar_p,0),4) as saldo_p, isnull(h.nilai_t,0) - isnull(g.bayar_t,0) - b.diskon as saldo_t, isnull(h.nilai_m,0) - isnull(g.bayar_m,0) as saldo_m, convert(varchar,c.tgl_berangkat,103) as tgl_berangkat, e.nama as paket, case when c.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun, c.no_closing, c.kurs_closing 
            from dgw_peserta a 
            inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_jadwal c on b.no_paket = c.no_paket and b.no_jadwal = c.no_jadwal  
            inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 
            inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi 
            inner join dgw_pembayaran i on b.no_reg=i.no_reg and b.kode_lokasi=i.kode_lokasi 
            left join (
                select a.no_reg,a.kode_lokasi, sum(case when b.jenis='TAMBAHAN' then a.nilai else 0 end) as nilai_t, sum(case when b.jenis='DOKUMEN' then a.nilai else 0 end) as nilai_m from dgw_reg_biaya a inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' group by a.no_reg,a.kode_lokasi  
            ) h on b.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi 
            left join (
                select no_reg,kode_lokasi,sum(nilai_p) as bayar_p, sum(nilai_t) as bayar_t , sum(nilai_m) as bayar_m 
                from dgw_pembayaran 
                where kode_lokasi='".$kode_lokasi."' and no_kwitansi <> '".$id."' group by no_reg,kode_lokasi  
            ) g on b.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi 
            where i.no_kwitansi='".$id."' and b.kode_lokasi='".$kode_lokasi."' ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select  a.kode_biaya, a.tarif, a.nilai,a.jml, a.nama, a.curr, a.jenis,a.akun_pdpt,isnull(sum(a.byr_e),0) as byr_e,isnull(sum(a.byr),0) as byr,sum(a.saldo) as saldo
            from (select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.nilai-isnull(d.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
             from dgw_reg_biaya a 
             inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
             inner join dgw_pembayaran i on a.no_reg=i.no_reg and a.kode_lokasi=i.kode_lokasi
             left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.no_kwitansi = '$id'
                         group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                         and a.no_reg=c.no_reg 
              left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.no_kwitansi <> '$id'
                         group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                         and a.no_reg=d.no_reg 
             where a.nilai <> 0 and i.no_kwitansi='$id' and a.kode_lokasi='$kode_lokasi' 
             union all 
             select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga_room-isnull(c.byr,0)-isnull(d.byr,0) as saldo, 
                     1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
             from dgw_reg a 
             inner join dgw_pembayaran i on a.no_reg=i.no_reg and a.kode_lokasi=i.kode_lokasi
             left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.kode_biaya ='ROOM' and a.no_kwitansi = '$id'
                         group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                         and a.no_reg=c.no_reg 
             left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.kode_biaya ='ROOM' and a.no_kwitansi <> '$id'
                         group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                         and a.no_reg=d.no_reg 
             where a.harga_room <> 0 and i.no_kwitansi='$id'  and a.kode_lokasi='$kode_lokasi' 
             union all 
             select 'PAKET' as kode_biaya, a.harga-a.diskon as tarif, a.harga-a.diskon as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga-isnull(c.byr,0)-isnull(d.byr,0)-a.diskon as saldo, 1 as jml, 
                     'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
             from dgw_reg a 
             inner join dgw_pembayaran i on a.no_reg=i.no_reg and a.kode_lokasi=i.kode_lokasi
             left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.kode_biaya = 'PAKET' and a.no_kwitansi = '$id'
                         group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                         and a.no_reg=c.no_reg 
             left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                         from dgw_pembayaran_d a 
                         where a.kode_biaya = 'PAKET' and a.no_kwitansi <> '$id'
                         group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                         and a.no_reg=d.no_reg 
             where a.harga <> 0 and i.no_kwitansi='$id' and a.kode_lokasi='$kode_lokasi' 
             ) a
             group by a.kode_biaya, a.tarif, a.nilai,a.jml, a.nama, a.curr, a.jenis,a.akun_pdpt");
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['detail_reg'] = $res2;
                $success['detail_biaya'] = $res3;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_reg'] = [];
                $success['detail_biaya'] = [];
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
                $no_bukti = $request->no_bukti;
                $sql = "select no_bukti,keterangan,param1 as kode_akun 
                from trans_m 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and no_ref1='".$id."'";
                $res4 = DB::connection($this->sql)->select( $sql);
                $res4 = json_decode(json_encode($res4),true);
                
            }else{
                $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-BM'.date('ym'), "0001");
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

            $sql2= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
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

            $sql4 = " select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t,nilai_m, (a.nilai_p * a.kurs) + a.nilai_t+a.nilai_m as total_idr 
            from dgw_pembayaran a 
            inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and a.no_reg='$id' and b.posted='F' and b.form='KBREG' ";
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

            $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
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

    public function destroyDetTmp(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'nik_user' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('dgw_pembayaran_d_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->where('nik_user', $request->nik_user)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data Detail Pembayaran tmp berhasil dihapus ";
            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Detail Pembayaran tmp gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPreview(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_kwitansi, a.kurs,a.paket,b.no_type,c.nama as room,b.harga+b.harga_room as harga_paket,a.jadwal,h.nama_marketing,e.nama_agen,isnull(b.referal,'-') as referal,a.no_reg,i.biaya_tambah,j.paket+j.tambahan+j.dokumen as bayar_lain,n.cicil_ke as cicil_ke, (a.kurs*(b.harga+b.harga_room))+i.biaya_tambah+j.dokumen as biaya_paket,(b.harga+b.harga_room)-(j.paket)+a.nilai_p as saldo, a.nilai_p as bayar,(b.harga+b.harga_room)-(j.paket) as sisa,CONVERT(varchar, a.tgl_bayar, 105) as tgl_bayar,k.nama as peserta,l.kode_curr,m.nik_user
            from dgw_pembayaran a
            inner join dgw_reg b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            inner join dgw_typeroom c on b.no_type=c.no_type and b.kode_lokasi=c.kode_lokasi
            inner join dgw_agent e on b.no_agen=e.no_agen and b.kode_lokasi=e.kode_lokasi 
            inner join dgw_marketing h on b.no_marketing=h.no_marketing and b.kode_lokasi=h.kode_lokasi
            inner join dgw_peserta k on b.no_peserta=k.no_peserta and b.kode_lokasi=k.kode_lokasi 
            inner join dgw_paket l on b.no_paket=l.no_paket and b.kode_lokasi=l.kode_lokasi 
            inner join trans_m m on a.no_kwitansi=m.no_bukti and a.kode_lokasi=m.kode_lokasi
            left join ( select no_reg,kode_lokasi,sum(jml*nilai) as biaya_tambah 
                        from dgw_reg_biaya 
                        group by no_reg,kode_lokasi ) i on b.no_reg=i.no_reg and b.kode_lokasi=i.kode_lokasi
            left join (select no_reg,kode_lokasi,isnull(sum(nilai_p),0) as paket, 
                        isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) j on b.no_reg=j.no_reg and b.kode_lokasi=j.kode_lokasi
			left join (select no_reg,kode_lokasi,count(no_kwitansi) as cicil_ke
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) n on b.no_reg=n.no_reg and b.kode_lokasi=n.kode_lokasi
			where a.no_kwitansi='$request->no_bukti' and a.kode_lokasi='$kode_lokasi'
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['data']= $res;
                $success['status'] = "SUCCESS";
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


    public function simpanDetTmp(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'no_reg' => 'required',
            'kode_biaya' => 'required|array',
            'kode_akunbiaya' => 'required|array',
            'jenis_biaya' => 'required|array',
            'nilai' => 'required|array',
            'nik_user' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('dgw_pembayaran_d_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->where('nik_user', $request->nik_user)
                ->where('no_reg', $request->no_reg)
                ->delete();

            $total_d = 0;$total_t=0;$total_p=0;
            for($i=0; $i<count($request->kode_biaya);$i++){
                if($request->jenis_biaya[$i] == "TAMBAHAN"){
                    $total_t+=floatval($request->nilai[$i]);
                }else if($request->jenis_biaya[$i] == "DOKUMEN"){
                    $total_d+=floatval($request->nilai[$i]);
                }else{
                    $total_p+=floatval($request->nilai[$i]);
                }
                $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d_tmp (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai,nik_user,kode_akun) values(?, ?, ?, ?, ?, ?, ?, ?) ", array($request->no_bukti,$kode_lokasi,$request->no_reg,$request->kode_biaya[$i],$request->jenis_biaya[$i],$request->nilai[$i],$request->nik_user,$request->kode_akunbiaya[$i]));
            }	

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_kwitansi'] = $request->no_bukti;
            $success['bayar_paket'] = $total_p;
            $success['bayar_tambahan'] = $total_t;
            $success['bayar_dokumen'] = $total_d;
            $success['message'] = "Data Detail Pembayaran berhasil disimpan. No Bukti:".$request->no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Detail Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    public function simpanDetTmp2(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'no_reg' => 'required|array',
            'saldo_paket' => 'required|array',
            'saldo_tambahan' => 'required|array',
            'saldo_dokumen' => 'required|array',
            'kode_biaya' => 'required',
            'kode_akunbiaya' => 'required',
            'jenis_biaya' => 'required',
            'nilai' => 'required',
            'nik_user' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            $del = DB::connection($this->sql)->table('dgw_pembayaran_d_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_kwitansi', $request->no_bukti)
            ->where('nik_user', $request->nik_user)
            ->where('kode_biaya', $request->kode_biaya)
            ->delete();

            $jmlRow = count($request->no_reg);
            if ($request->nilai != "" && $request->nilai != "0" && $jmlRow != 0) {
                $total = floatval($request->nilai) / $jmlRow;
                $nilaiDis = round($total*100)/100;
                $nTemp = 0;
                $bayar = array();
                for ($i=0;$i < $jmlRow ;$i++){
                    if($request->jenis_biaya == "TAMBAHAN"){
                        $saldo = $request->saldo_tambahan[$i];
                    }else if($request->jenis_biaya == "DOKUMEN"){
                        $saldo = $request->saldo_dokumen[$i];
                    }else if($request->jenis_biaya == "-"){
                        $saldo = $request->saldo_paket[$i];
                    }
                    if (floatval($saldo)  > 0) {
                        if (floatval($saldo) > $nilaiDis) {
                            $bayar[$i] = $nilaiDis;
                            $nTemp += $nilaiDis;
                        }
                        else {
                            $bayar[$i] = floatval($saldo);
                            $nTemp += floatval($saldo);
                        }
                        $j=$i;
                        $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d_tmp (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai,nik_user,kode_akun) values(?, ?, ?, ?, ?, ?, ?, ?) ", array($request->no_bukti,$kode_lokasi,$request->no_reg[$i],$request->kode_biaya,$request->jenis_biaya,$bayar[$i],$request->nik_user,$request->kode_akunbiaya));
                    }
                }	

                $selisih = (floatval($request->nilai) * 100) - ($nTemp * 100);
                $recAkhir = round($selisih + (floatval($bayar[$j]) * 100));
                $recAkhir = $recAkhir/100;

                $upd = DB::connection($this->sql)->update("update dgw_pembayaran_d_tmp set nilai=$recAkhir where no_kwitansi='$request->no_kwitansi' and kode_lokasi='$kode_lokasi' and no_reg='".$request->no_reg[$j]."' and nik_user='$request->nik_user' ");		
            }

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_kwitansi'] = $request->no_bukti;
            $res = DB::connection($this->sql)->select("select isnull(sum(case when jenis = 'TAMBAHAN' then nilai else 0 end),0) as bayar_tambahan,isnull(sum(case when jenis = 'DOKUMEN' then nilai else 0 end),0) as bayar_dokumen,isnull(sum(case when jenis = '-' then nilai else 0 end),0) as bayar_paket
            from dgw_pembayaran_d_tmp
            where no_kwitansi='$request->no_bukti' and kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user' ");

            $success['bayar_paket'] = $res[0]->bayar_paket;
            $success['bayar_tambahan'] = $res[0]->bayar_tambahan;
            $success['bayar_dokumen'] = $res[0]->bayar_dokumen;
            $success['message'] = "Data Detail Pembayaran berhasil disimpan. No Bukti:".$request->no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Detail Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }
}
