<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AkruSimpController extends Controller
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

    public function generateNo(Request $request) {
        $this->validate($request, [    
            'kode_param' => 'required',
            'no_agg' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $no_bukti = $this->generateKode("kop_simp_m", "no_simp", $kode_lokasi."-".$request->kode_param.''.$request->no_agg.".", "01");

            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai1,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
            from trans_m a 
            where a.kode_lokasi='$kode_lokasi' and a.posted='F' and a.form='GENBILL'";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'keterangan' => 'required|max:100',
            'akun_piutang' => 'required|array',
            'akun_simpanan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-BSM".substr($periode.$request->no_agg,2,4).".", "0001");

            $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($getPP) > 0){
                $kode_pp = $getPP[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $getSPRO = DB::connection($this->db)->select("select a.kode_spro,a.flag,b.nama from spro a inner join masakun b on a.flag=b.kode_akun and a.kode_lokasi=b.kode_lokasi where a.kode_spro in ('BSIMP') and a.kode_lokasi = '".$kode_lokasi."'");
            if(count($getSPRO) > 0){
                $line = $getSPRO[0];
                if ($line->kode_spro == "BSIMP") $akunBunga = $line->flag;
				if ($line->kode_spro == "BSIMP") $namaBunga = $line->nama;
            }else{
                $akunBunga = "-";
                $namaBunga = "-";
            }
            
            $j = 0;
            $total = 0;
            for ($i=0; $i<count($request->akun_piutang); $i++){
                $j = $i+1000;			
                $ins2[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$i.",'".$request->akun_piutang[$i]."','D',".floatval($request->nilai[$i]).",".floatval($request->nilai[$i]).",'".$request->keterangan."','GENBILL','PIUTANG','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");
                
                $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$j.",'".$request->akun_simpanan[$i]."','C',".floatval($request->nilai[$i]).",".floatval($request->nilai[$i]).",'".$request->keterangan."','GENBILL','SIMP','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");

                $total+= floatval($request->nilai[$i]);
            }
            
            $ins1 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','KP','GENBILL','F','0','0','".$kode_pp."','".$request->tanggal."','-','".$request->keterangan."','IDR',1,".floatval($total).",0,0,'-','-','-','-','-','-','-','-','-')");
            
            //hitung dan generate bunga simp sukarela (sebagai setoran angsuran)
            $ins4 = DB::connection($this->db)->insert("insert into kop_simpangs_d (no_angs,no_simp,no_bill,akun_piutang,nilai,kode_lokasi,dc,periode,modul,no_agg,jenis)
            select '".$no_bukti."',a.no_simp,'".$no_bukti."','".$akunBunga."', round(sum( case a.dc when 'D' then a.nilai else -a.nilai end * b.p_bunga/100/12),0) as bunga,a.kode_lokasi,'D','".$periode."','BSIMP',b.no_agg,'BSIMP' 
            from 
            kop_simpangs_d a 
            inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
            inner join kop_simp_param c on c.kode_param=b.kode_param and b.kode_lokasi=c.kode_lokasi 
            inner join masakun d on c.akun_titip=d.kode_akun and c.kode_lokasi=d.kode_lokasi 
            inner join kop_agg y on b.no_agg=y.no_agg and b.kode_lokasi = y.kode_lokasi 
            where b.jenis = 'SS' and b.p_bunga <>0 and a.periode<='".$periode."' and a.kode_lokasi='".$kode_lokasi."' and b.periode_bunga <= '".$periode."' and b.flag_aktif='1' and y.flag_aktif='1' 
            group by a.no_simp,a.kode_lokasi,b.no_agg ");
            
            //generate billing untuk bunga simp sukarela (sebagai billingnya)
            $ins5 = DB::connection($this->db)->insert("insert into kop_simp_d (no_simp,no_bill,kode_lokasi,periode,nilai,akun_piutang,akun_titip,dc,modul,no_agg) 
            select a.no_simp,'".$no_bukti."',a.kode_lokasi,'".$periode."', 
            round(sum( case a.dc when 'D' then a.nilai else -a.nilai end * b.p_bunga/100/12),0) as bunga,'".$akunBunga."',c.akun_titip,'D','BSIMP',b.no_agg 
            from 
            kop_simpangs_d a 
            inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi 
            inner join kop_simp_param c on c.kode_param=b.kode_param and b.kode_lokasi=c.kode_lokasi 
            inner join masakun d on c.akun_titip=d.kode_akun and c.kode_lokasi=d.kode_lokasi 
            inner join kop_agg y on b.no_agg=y.no_agg and b.kode_lokasi = y.kode_lokasi 
            where b.jenis = 'SS' and b.p_bunga <>0 and a.periode<='".$periode."' and a.kode_lokasi='".$kode_lokasi."' 
            	  and b.periode_bunga <= '".$periode."' 
            	  and b.flag_aktif='1' and y.flag_aktif='1' 
            group by a.no_simp,a.kode_lokasi,c.akun_titip,b.no_agg ");
            
            //generate billing untuk seluruh jenis simpanan (kecuali ss yg tunai angsurannya / bukan potong gaji)
            $ins6 = DB::connection($this->db)->insert("insert into kop_simp_d (no_simp,no_bill,kode_lokasi,periode,nilai,akun_piutang,akun_titip,dc,modul,no_agg) 
            select x.no_simp,'".$no_bukti."',x.kode_lokasi,'".$periode."',x.nilai,a.akun_piutang,a.akun_titip,'D','GENBILL',x.no_agg 
            from kop_simp_m x 
                 inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi=y.kode_lokasi 
                 inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi = a.kode_lokasi 
            where a.kode_lokasi = '".$kode_lokasi."' and x.flag_aktif='1' and y.flag_aktif='1' and 
                  ((x.jenis in ('SP','SW')) or (x.jenis='SS' and x.status_bayar='PGAJI')) and x.nilai>0  and x.periode_gen<='".$periode."'");
            
            $pNext = nextNPeriode($periode,1);		
            $ins7 = DB::connection($this->db)->update("update a set a.periode_gen ='".$pNext."',a.periode_bunga ='".$pNext."' from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."' and a.jenis<>'SP' ");

            $ins8 = DB::connection($this->db)->update("update a set a.periode_gen ='999999' from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."' and a.jenis='SP' ");
            
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $no_bukti;
            $success['message'] = "Data Akru Simpanan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akru Simpanan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
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
            'no_bukti' => 'required|max:20',
            'tanggal' => 'required|date_format:Y-m-d',
            'keterangan' => 'required|max:100',
            'akun_piutang' => 'required|array',
            'akun_simpanan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            $del2 = DB::connection($this->db)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $upd = DB::connection($this->db)->update("update a set a.periode_gen = b.periode, a.periode_bunga = b.periode from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."'");
            
            $del3 = DB::connection($this->db)->table('kop_simp_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bill', $no_bukti)
            ->delete();
            $del4 = DB::connection($this->db)->table('kop_simpangs_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_angs', $no_bukti)
            ->delete();

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($getPP) > 0){
                $kode_pp = $getPP[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $getSPRO = DB::connection($this->db)->select("select a.kode_spro,a.flag,b.nama from spro a inner join masakun b on a.flag=b.kode_akun and a.kode_lokasi=b.kode_lokasi where a.kode_spro in ('BSIMP') and a.kode_lokasi = '".$kode_lokasi."'");
            if(count($getSPRO) > 0){
                $line = $getSPRO[0];
                if ($line->kode_spro == "BSIMP") $akunBunga = $line->flag;
				if ($line->kode_spro == "BSIMP") $namaBunga = $line->nama;
            }else{
                $akunBunga = "-";
                $namaBunga = "-";
            }
            
            $j = 0;
            $total = 0;
            for ($i=0; $i<count($request->akun_piutang); $i++){
                $j = $i+1000;			
                $ins2[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$i.",'".$request->akun_piutang[$i]."','D',".floatval($request->nilai[$i]).",".floatval($request->nilai[$i]).",'".$request->keterangan."','GENBILL','PIUTANG','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");
                
                $ins3[$i] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$j.",'".$request->akun_simpanan[$i]."','C',".floatval($request->nilai[$i]).",".floatval($request->nilai[$i]).",'".$request->keterangan."','GENBILL','SIMP','IDR',1,'".$kode_pp."','-','-','-','-','-','-','-','-')");

                $total+= floatval($request->nilai[$i]);
            }
            
            $ins1 = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','KP','GENBILL','F','0','0','".$kode_pp."','".$request->tanggal."','-','".$request->keterangan."','IDR',1,".floatval($total).",0,0,'-','-','-','-','-','-','-','-','-')");
            
            //hitung dan generate bunga simp sukarela (sebagai setoran angsuran)
            $ins4 = DB::connection($this->db)->insert("insert into kop_simpangs_d (no_angs,no_simp,no_bill,akun_piutang,nilai,kode_lokasi,dc,periode,modul,no_agg,jenis)
            select '".$no_bukti."',a.no_simp,'".$no_bukti."','".$akunBunga."', round(sum( case a.dc when 'D' then a.nilai else -a.nilai end * b.p_bunga/100/12),0) as bunga,a.kode_lokasi,'D','".$periode."','BSIMP',b.no_agg,'BSIMP' 
            from 
            kop_simpangs_d a 
            inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi
            inner join kop_simp_param c on c.kode_param=b.kode_param and b.kode_lokasi=c.kode_lokasi 
            inner join masakun d on c.akun_titip=d.kode_akun and c.kode_lokasi=d.kode_lokasi 
            inner join kop_agg y on b.no_agg=y.no_agg and b.kode_lokasi = y.kode_lokasi 
            where b.jenis = 'SS' and b.p_bunga <>0 and a.periode<='".$periode."' and a.kode_lokasi='".$kode_lokasi."' and b.periode_bunga <= '".$periode."' and b.flag_aktif='1' and y.flag_aktif='1' 
            group by a.no_simp,a.kode_lokasi,b.no_agg ");
            
            //generate billing untuk bunga simp sukarela (sebagai billingnya)
            $ins5 = DB::connection($this->db)->insert("insert into kop_simp_d (no_simp,no_bill,kode_lokasi,periode,nilai,akun_piutang,akun_titip,dc,modul,no_agg) 
            select a.no_simp,'".$no_bukti."',a.kode_lokasi,'".$periode."', 
            round(sum( case a.dc when 'D' then a.nilai else -a.nilai end * b.p_bunga/100/12),0) as bunga,'".$akunBunga."',c.akun_titip,'D','BSIMP',b.no_agg 
            from 
            kop_simpangs_d a 
            inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi 
            inner join kop_simp_param c on c.kode_param=b.kode_param and b.kode_lokasi=c.kode_lokasi 
            inner join masakun d on c.akun_titip=d.kode_akun and c.kode_lokasi=d.kode_lokasi 
            inner join kop_agg y on b.no_agg=y.no_agg and b.kode_lokasi = y.kode_lokasi 
            where b.jenis = 'SS' and b.p_bunga <>0 and a.periode<='".$periode."' and a.kode_lokasi='".$kode_lokasi."' 
            	  and b.periode_bunga <= '".$periode."' 
            	  and b.flag_aktif='1' and y.flag_aktif='1' 
            group by a.no_simp,a.kode_lokasi,c.akun_titip,b.no_agg ");
            
            //generate billing untuk seluruh jenis simpanan (kecuali ss yg tunai angsurannya / bukan potong gaji)
            $ins6 = DB::connection($this->db)->insert("insert into kop_simp_d (no_simp,no_bill,kode_lokasi,periode,nilai,akun_piutang,akun_titip,dc,modul,no_agg) 
            select x.no_simp,'".$no_bukti."',x.kode_lokasi,'".$periode."',x.nilai,a.akun_piutang,a.akun_titip,'D','GENBILL',x.no_agg 
            from kop_simp_m x 
                 inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi=y.kode_lokasi 
                 inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi = a.kode_lokasi 
            where a.kode_lokasi = '".$kode_lokasi."' and x.flag_aktif='1' and y.flag_aktif='1' and 
                  ((x.jenis in ('SP','SW')) or (x.jenis='SS' and x.status_bayar='PGAJI')) and x.nilai>0  and x.periode_gen<='".$periode."'");
            
            $pNext = nextNPeriode($periode,1);		
            $ins7 = DB::connection($this->db)->update("update a set a.periode_gen ='".$pNext."',a.periode_bunga ='".$pNext."' from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."' and a.jenis<>'SP' ");

            $ins8 = DB::connection($this->db)->update("update a set a.periode_gen ='999999' from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."' and a.jenis='SP' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $request->no_simp;
            $success['message'] = "Data Akru Simpanan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Akru Simpanan gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            $del2 = DB::connection($this->db)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $upd = DB::connection($this->db)->update("update a set a.periode_gen = b.periode, a.periode_bunga = b.periode from kop_simp_m a inner join kop_simp_d b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi where b.no_bill='".$no_bukti."' and b.kode_lokasi = '".$kode_lokasi."'");
            
            $del3 = DB::connection($this->db)->table('kop_simp_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bill', $no_bukti)
            ->delete();
            $del4 = DB::connection($this->db)->table('kop_simpangs_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_angs', $no_bukti)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akru Simpanan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akru Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getDaftarJurnal(Request $request)
    {
        $this->validate($request,[
            'tanggal' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $getSPRO = DB::connection($this->db)->select("select a.kode_spro,a.flag,b.nama from spro a inner join masakun b on a.flag=b.kode_akun and a.kode_lokasi=b.kode_lokasi where a.kode_spro in ('BSIMP') and a.kode_lokasi = '".$kode_lokasi."'");
            if(count($getSPRO) > 0){
                $line = $getSPRO[0];
                if ($line->kode_spro == "BSIMP") $akunBunga = $line->flag;
				if ($line->kode_spro == "BSIMP") $namaBunga = $line->nama;
            }else{
                $akunBunga = "-";
                $namaBunga = "-";
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $sql="select x.jenis,a.kode_param,a.nama as nama_simp,a.akun_piutang,b.nama as nama_ar,a.akun_titip,c.nama as nama_asimp,sum(x.nilai) as total 
            from kop_simp_m x 
                 inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi = y.kode_lokasi 
                 inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi = a.kode_lokasi 
                 inner join masakun b on a.akun_piutang = b.kode_akun and a.kode_lokasi=b.kode_lokasi     
                 inner join masakun c on a.akun_titip = c.kode_akun and a.kode_lokasi=c.kode_lokasi 
            where x.kode_lokasi = '".$kode_lokasi."' and x.flag_aktif='1' and y.flag_aktif='1' and 
                 ((x.jenis in ('SP','SW')) or (x.jenis='SS' and x.status_bayar='PGAJI')) and 
                 x.periode_gen<='".$periode."' and x.nilai>0 
                 group by x.jenis,a.kode_param,a.nama,a.akun_piutang,b.nama,a.akun_titip,c.nama 
            union all 
            select 'BS' as jenis,c.kode_param,c.nama,'".$akunBunga+"','".$namaBunga."',c.akun_titip,d.nama as nama_titip, 
            round(sum( case a.dc when 'D' then a.nilai else -a.nilai end * b.p_bunga/100/12),0) as bunga 
            	from 
            	kop_simpangs_d a 
            	inner join kop_simp_m b on a.no_simp=b.no_simp and a.kode_lokasi=b.kode_lokasi 
            	inner join kop_simp_param c on c.kode_param=b.kode_param and b.kode_lokasi=c.kode_lokasi 
            	inner join masakun d on c.akun_titip=d.kode_akun and c.kode_lokasi=d.kode_lokasi 
               inner join kop_agg y on b.no_agg=y.no_agg and b.kode_lokasi = y.kode_lokasi 
               where b.jenis = 'SS' and b.p_bunga <>0 and a.periode<='".$periode."' and a.kode_lokasi='".$kode_lokasi."' 
                and b.periode_bunga <= '".$periode."' 
            		  and b.flag_aktif='1' and y.flag_aktif='1' 
                      group by  b.jenis,c.kode_param,c.nama,c.akun_titip,d.nama 
            order by x.jenis,a.kode_param";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDaftarKartu(Request $request)
    {
        $this->validate($request,[
            'kode_param' => 'required',
            'jenis' => 'required',
            'status_simpan' => 'required|in:0,1',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            if ($request->status_simpan == 1) {
				$strSQL = "select x.no_simp,convert(varchar,x.tgl_tagih,103) as tgl_tagih,y.no_agg,y.nama as nama_agg,x.jenis,a.kode_param+' - '+a.nama as nama_simp,a.akun_piutang as akun_ar,a.akun_titip as akun_simp,x.nilai 
				 from kop_simp_m x 
				      inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi=y.kode_lokasi 
				      inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi=a.kode_lokasi 
				 where x.jenis='".$request->jenis."' and a.kode_param='".$request->kode_param."' and a.kode_lokasi = '".$kode_lokasi."' and x.flag_aktif='1' and y.flag_aktif='1' and 
				      ((x.jenis in ('SP','SW')) or (x.jenis='SS' and x.status_bayar='PGAJI')) and 
				      x.periode_gen<='".$periode."' order by x.jenis,x.no_simp";
			}
			else {
				$strSQL = "select x.no_simp,convert(varchar,x.tgl_tagih,103) as tgl_tagih,y.no_agg,y.nama as nama_agg,x.jenis,a.kode_param+' - '+a.nama as nama_simp,a.akun_piutang as akun_ar,a.akun_titip as akun_simp,x.nilai 
					from kop_simp_m x 
					     inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi=y.kode_lokasi 
					     inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi=a.kode_lokasi 
					     inner join kop_simp_d b on x.no_simp=b.no_simp and x.kode_lokasi=b.kode_lokasi 
					where x.jenis='".$request->jenis."' and a.kode_param='".$request->kode_param."' and a.kode_lokasi = '".$kode_lokasi."' and x.flag_aktif='1' and y.flag_aktif='1' 
					and b.no_bill='".$request->no_bukti."' order by x.jenis,x.no_simp";
			}

            $res = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select * from trans_m 
            where no_bukti = '".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            $strSQL2 = "select case when d.modul='BSIMP' then 'BS' else x.jenis end as jenis,a.kode_param,a.nama as nama_simp,d.akun_piutang,b.nama as nama_ar,d.akun_titip,c.nama as nama_asimp,sum(d.nilai) as total 
            from kop_simp_m x 
                 inner join kop_simp_d d on x.no_simp=d.no_simp and x.kode_lokasi=d.kode_lokasi 
                 inner join kop_agg y on x.no_agg=y.no_agg and x.kode_lokasi = y.kode_lokasi 
                 inner join kop_simp_param a on x.kode_param=a.kode_param and x.kode_lokasi = a.kode_lokasi 
                 inner join masakun b on d.akun_piutang = b.kode_akun and a.kode_lokasi=b.kode_lokasi     
                 inner join masakun c on d.akun_titip = c.kode_akun and a.kode_lokasi=c.kode_lokasi 						   
            where d.kode_lokasi = '".$kode_lokasi."' and d.no_bill='".$request->no_bukti."' 
            group by case when d.modul='BSIMP' then 'BS' else x.jenis end,a.kode_param,a.nama,d.akun_piutang,b.nama,d.akun_titip,c.nama 
            order by a.kode_param";
            $rs2 = DB::connection($this->db)->select($strSQL2);
            $res2 = json_decode(json_encode($rs2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
