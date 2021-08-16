<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

function joinNum($num){
    // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
    $num = str_replace(".", "", $num);
    $num = str_replace(",", ".", $num);
    return $num;
}

class SyncController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvginas';
    public $guard = 'ginas';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function syncMaster(Request $request)
    {
        $this->validate($request, [
            'VENDOR' => 'required|array',
            'BARANG' => 'required|array',
            'GUDANG' => 'required|array',
            'BARANGKLP' => 'required|array',
            'SATUAN' => 'required|array',
            'BONUS' => 'required|array',

        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            //VENDOR
            $vendor = $request->VENDOR;
            if(count($vendor) > 0){
                $del = DB::connection($this->sql)->table('vendor')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

                for($i=0;$i<count($vendor);$i++){
                    $ins2= DB::connection($this->sql)->insert("insert into vendor(kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang) values ('".$vendor[$i]['kode_vendor']."','".$kode_lokasi."','".$vendor[$i]['nama']."','".$vendor[$i]['alamat']."','".$vendor[$i]['no_telp']."','".$vendor[$i]['email']."','".$vendor[$i]['npwp']."','".$vendor[$i]['pic']."','".$vendor[$i]['alamat_npwp']."','".$vendor[$i]['bank']."','".$vendor[$i]['cabang']."','".$vendor[$i]['no_rek']."','".$vendor[$i]['nama_rek']."','".$vendor[$i]['no_fax']."','".$vendor[$i]['no_tel2']."','-','-','-','-','".$vendor[$i]['akun_hutang']."') ");
                }
            }

            //BARANG
            $barang = $request->BARANG;
            if(count($barang) > 0){
                $del3 = DB::connection($this->sql)->table('brg_barang')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
                for($i=0;$i<count($barang);$i++){
                    $hna=(joinNum($barang[$i]['harga_jual']) != "" ? joinNum($barang[$i]['harga_jual']) : 0);
                    $ss=(joinNum($barang[$i]['ss']) != "" ? joinNum($barang[$i]['ss']) : 0);
                    $sm1=(joinNum($barang[$i]['sm1']) != "" ? joinNum($barang[$i]['sm1']) : 0);
                    $sm2=(joinNum($barang[$i]['sm2']) != "" ? joinNum($barang[$i]['sm2']) : 0);
                    $mm1=(joinNum($barang[$i]['mm1']) != "" ? joinNum($barang[$i]['mm1']) : 0);
                    $mm2=(joinNum($barang[$i]['mm2']) != "" ? joinNum($barang[$i]['mm2']) : 0);
                    $fm1=(joinNum($barang[$i]['fm1']) != "" ? joinNum($barang[$i]['fm1']) : 0);
                    $fm2=(joinNum($barang[$i]['fm2']) != "" ? joinNum($barang[$i]['fm2']) : 0);
                    
                    $hrg_satuan=(joinNum($barang[$i]['hrg_satuan']) != "" ? joinNum($barang[$i]['hrg_satuan']) : 0);
                    $ppn=(joinNum($barang[$i]['ppn']) != "" ? joinNum($barang[$i]['ppn']) : 0);
                    $profit=(joinNum($barang[$i]['profit']) != "" ? joinNum($barang[$i]['profit']) : 0);
                    $nilai_beli=(joinNum($barang[$i]['nilai_beli']) != "" ? joinNum($barang[$i]['nilai_beli']) : 0);
                    
                    $ins3=DB::connection($this->sql)->insert("insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli) values ('".$barang[$i]['kode_barang']."','".$barang[$i]['nama']."','".$kode_lokasi."','".$barang[$i]['kode_satuan']."','-',1,".$hna.",'".$barang[$i]['keterangan']."','-','1',".$ss.",".$sm1.",".$sm2.",".$mm1.",".$mm2.",".$fm1.",".$fm2.",'".$barang[$i]['kode_klp']."','".$filepath."','".$barang[$i]['barcode']."',$hrg_satuan,$ppn,$profit,$nilai_beli) ");
                }
            }

            //GUDANG
            $gudang = $request->GUDANG;
            if(count($gudang) > 0){
                $del4 = DB::connection($this->sql)->table('brg_gudang')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
                for($i=0;$i<count($gudang);$i++){
        
                    $ins4= DB::connection($this->sql)->insert("insert into brg_gudang(kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp) values ('".$gudang[$i]['kode_gudang']."','".$kode_lokasi."','".$gudang[$i]['nama']."','".$gudang[$i]['pic']."','".$gudang[$i]['telp']."','".$gudang[$i]['alamat']."','".$gudang[$i]['kode_pp']."') ");
                }
            }

            //BARANG KLP
            $klp = $request->BARANGKLP;
            if(count($klp) > 0){
                $del5 = DB::connection($this->sql)->table('brg_barangklp')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

                for($i=0;$i<count($klp);$i++){
                    $ins5 = DB::connection($this->sql)->insert("insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp) values ('".$klp[$i]['kode_klp']."','".$kode_lokasi."','".$klp[$i]['nama']."','".$klp[$i]['akun_pers']."','".$klp[$i]['akun_pdpt']."','".$klp[$i]['akun_hpp']."') ");
                }
            }

            //SATUAN
            $satuan = $request->SATUAN;
            if(count($satuan) > 0){
                
                $del6 = DB::connection($this->sql)->table('brg_satuan')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
                for($i=0;$i<count($satuan);$i++){
        
                    $ins6= DB::connection($this->sql)->insert("insert into brg_satuan(kode_satuan,kode_lokasi,nama) values ('".$satuan[$i]['kode_satuan']."','".$kode_lokasi."','".$satuan[$i]['nama']."') ");
                }
            }
            //BONUS
            $bonus = $request->BONUS;
            if(count($bonus) > 0){

                $del7 = DB::connection($this->sql)->table('brg_bonus')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

                for($i=0;$i<count($bonus);$i++){

                    $ref_qty=(joinNum($barang[$i]['ref_qty']) != "" ? joinNum($barang[$i]['ref_qty']) : 0);
                    $bonus_qty=(joinNum($barang[$i]['bonus_qty']) != "" ? joinNum($barang[$i]['bonus_qty']) : 0);
        
                    $ins7= DB::connection($this->sql)->insert("insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values ('".$bonus[$i]['kode_barang']."','".$bonus[$i]['keterangan']."','".$kode_lokasi."',".$ref_qty.",".$bonus_qty.",'".$bonus[$i]['tgl_mulai']."','".$bonus[$i]['tgl_selesai']."') ");
                }
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function syncPnj(Request $request)
    {
        $this->validate($request, [
            'TRANSM' => 'required|array',
            'TRANSJ' => 'required|array',
            'BRGJUAL' => 'required|array',
            'BRGTRANS' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            //TRANSM
            $transm = $request->TRANSM;
            if(count($transm) > 0){
                for($i=0;$i<count($transm);$i++){

                    $kurs=($transm[$i]['kurs'] != "" ? $transm[$i]['kurs'] : 0);
                    $nilai1=($transm[$i]['nilai1'] != "" ? $transm[$i]['nilai1'] : 0);
                    $nilai2=($transm[$i]['nilai2'] != "" ? $transm[$i]['nilai2'] : 0);
                    $nilai3=($transm[$i]['nilai3'] != "" ? $transm[$i]['nilai3'] : 0);

                    $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                    ('".$transm[$i]["no_bukti"]."','".$transm[$i]["kode_lokasi"]."','".$transm[$i]["tgl_input"]."','".$transm[$i]["nik_user"]."','".$transm[$i]["periode"]."','".$transm[$i]["modul"]."','".$transm[$i]["form"]."','".$transm[$i]["posted"]."','".$transm[$i]["prog_seb"]."','".$transm[$i]["progress"]."','".$transm[$i]["kode_pp"]."','".$transm[$i]["tanggal"]."','".$transm[$i]["no_dokumen"]."','".$transm[$i]["keterangan"]."','".$transm[$i]["kode_curr"]."',".$kurs.",".$nilai1.",".$nilai2.",".$nilai3.",'".$transm[$i]["nik1"]."','".$transm[$i]["nik2"]."','".$transm[$i]["nik3"]."','".$transm[$i]["no_ref1"]."','".$transm[$i]["no_ref2"]."','".$transm[$i]["no_ref3"]."','".$transm[$i]["param1"]."','".$transm[$i]["param2"]."','".$transm[$i]["param3"]."')");
                }
            }

            //TRANSJ
            $transj = $request->TRANSJ;
            if(count($transj) > 0){
                
                for($i=0;$i<count($transj);$i++){
                    
                    $kurs=($transj[$i]['kurs'] != "" ? $transj[$i]['kurs'] : 0);
                    $nu=($transm[$i]['nu'] != "" ? $transm[$i]['nu'] : 0);
                    $nilai=($transj[$i]['nilai'] != "" ? $transj[$i]['nilai'] : 0);
                    $nilai_curr=($transj[$i]['nilai_curr'] != "" ? $transj[$i]['nilai_curr'] : 0);

                    $ins2=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                    ('".$transj[$i]["no_bukti"]."','".$transj[$i]["kode_lokasi"]."','".$transj[$i]["tgl_input"]."','".$transj[$i]["nik_user"]."','".$transj[$i]["periode"]."','".$transj[$i]["no_dokumen"]."','".$transj[$i]["tanggal"]."',".$nu.",'".$transj[$i]["kode_akun"]."','".$transj[$i]["dc"]."',".$nilai.",".$nilai_curr.",'".$transj[$i]["keterangan"]."','".$transj[$i]["keterangan"]."','".$transj[$i]["modul"]."','".$transj[$i]["jenis"]."',".$kurs.",'".$transj[$i]["kode_pp"]."','".$transj[$i]["kode_drk"]."','".$transj[$i]["kode_cust"]."','".$transj[$i]["kode_vendor"]."','".$transj[$i]["no_fa"]."','".$transj[$i]["no_selesai"]."','".$transj[$i]["no_ref1"]."','".$transj[$i]["no_ref2"]."','".$transj[$i]["no_ref3"]."')");
                }
            }

            //BRGJUAL
            $brgJual = $request->BRGJUAL;
            if(count($brgJual) > 0){
                for($i=0;$i<count($brgJual);$i++){
                    $kurs=($brgJual[$i]['kurs'] != "" ? $brgJual[$i]['kurs'] : 0);
                    $nilai=($brgJual[$i]['nilai'] != "" ? $brgJual[$i]['nilai'] : 0);
                    $nilai_ppn=($brgJual[$i]['nilai_ppn'] != "" ? $brgJual[$i]['nilai_ppn'] : 0);
                    $nilai_pph=($brgJual[$i]['nilai_pph'] != "" ? $brgJual[$i]['nilai_pph'] : 0);
                    $diskon=($brgJual[$i]['diskon'] != "" ? $brgJual[$i]['diskon'] : 0);
                    $tobyr=($brgJual[$i]['tobyr'] != "" ? $brgJual[$i]['tobyr'] : 0);

                    $ins3 = DB::connection($this->sql)->insert("insert into brg_jualpiu_d (no_jual,kode_lokasi,tanggal,keterangan,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp,diskon,kode_gudang,no_ba,tobyr,no_open,no_close) values ('".$brgJual[$i]["no_jual"]."','".$brgJual[$i]["kode_lokasi"]."','".$brgJual[$i]["tanggal"]."','".$brgJual[$i]["keterangan"]."','".$brgJual[$i]["kode_cust"]."','".$brgJual[$i]["kode_curr"]."',$kurs,'".$brgJual[$i]["kode_pp"]."',".$nilai.",'".$brgJual[$i]["periode"]."','".$brgJual[$i]["nik_user"]."','".$brgJual[$i]["tgl_input"]."','".$brgJual[$i]["akun_piutang"]."',".$nilai_ppn.",".$nilai_pph.",'".$brgJual[$i]["no_fp"]."',".$diskon.",'".$brgJual[$i]["kode_gudang"]."','".$brgJual[$i]["no_ba"]."',".$tobyr.",'".$brgJual[$i]["no_open"]."','".$brgJual[$i]["no_close"]."') ");
                }
            }

            //BRGTRANS
            $brgTrans = $request->BRGTRANS;
            if(count($brgTrans) > 0){
                for($i=0;$i<count($brgTrans);$i++){
                    $stok=($brgTrans[$i]['stok'] != "" ? $brgTrans[$i]['stok'] : 0);  
                    $jumlah=($brgTrans[$i]['jumlah'] != "" ? $brgTrans[$i]['jumlah'] : 0);
                    $bonus=($brgTrans[$i]['bonus'] != "" ? $brgTrans[$i]['bonus'] : 0);
                    $harga=($brgTrans[$i]['harga'] != "" ? $brgTrans[$i]['harga'] : 0);
                    $hpp_p=($brgTrans[$i]['hpp_p'] != "" ? $brgTrans[$i]['hpp_p'] : 0);  
                    $p_disk=($brgTrans[$i]['p_disk'] != "" ? $brgTrans[$i]['p_disk'] : 0);
                    $diskon=($brgTrans[$i]['diskon'] != "" ? $brgTrans[$i]['diskon'] : 0);
                    $tot_diskon=($brgTrans[$i]['tot_diskon'] != "" ? $brgTrans[$i]['tot_diskon'] : 0);
                    $total=($brgTrans[$i]['total'] != "" ? $brgTrans[$i]['total'] : 0);
                    $ins4= DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                        ('".$brgTrans[$i]["no_bukti"]."','".$brgTrans[$i]["kode_lokasi"]."','".$brgTrans[$i]["periode"]."','".$brgTrans[$i]["modul"]."','".$brgTrans[$i]["form"]."',".$brgTrans[$i]["nu"].",'".$brgTrans[$i]["kode_gudang"]."','".$brgTrans[$i]["kode_barang"]."','".$brgTrans[$i]["no_batch"]."','".$brgTrans[$i]["tgl_ed"]."','".$brgTrans[$i]["satuan"]."','".$brgTrans[$i]["dc"]."',".$stok.",".$jumlah.",".$bonus.",".$harga.",".$hpp_p.",".$p_disk.",".$diskon.",".$tot_diskon.",".$total.") ");
                }
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function syncPmb(Request $request)
    {
        $this->validate($request, [
            'TRANSM' => 'required|array',
            'TRANSJ' => 'required|array',
            'BRGHUT' => 'required|array',
            'BRGTRANS' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            //TRANSM
            $transm = $request->TRANSM;
            if(count($transm) > 0){
                for($i=0;$i<count($transm);$i++){

                    $kurs=($transm[$i]['kurs'] != "" ? $transm[$i]['kurs'] : 0);
                    $nilai1=($transm[$i]['nilai1'] != "" ? $transm[$i]['nilai1'] : 0);
                    $nilai2=($transm[$i]['nilai2'] != "" ? $transm[$i]['nilai2'] : 0);
                    $nilai3=($transm[$i]['nilai3'] != "" ? $transm[$i]['nilai3'] : 0);
                    $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                    ('".$transm[$i]["no_bukti"]."','".$transm[$i]["kode_lokasi"]."','".$transm[$i]["tgl_input"]."','".$transm[$i]["nik_user"]."','".$transm[$i]["periode"]."','".$transm[$i]["modul"]."','".$transm[$i]["form"]."','".$transm[$i]["posted"]."','".$transm[$i]["prog_seb"]."','".$transm[$i]["progress"]."','".$transm[$i]["kode_pp"]."','".$transm[$i]["tanggal"]."','".$transm[$i]["no_dokumen"]."','".$transm[$i]["keterangan"]."','".$transm[$i]["kode_curr"]."',".$kurs.",".$nilai1.",".$nilai2.",".$nilai3.",'".$transm[$i]["nik1"]."','".$transm[$i]["nik2"]."','".$transm[$i]["nik3"]."','".$transm[$i]["no_ref1"]."','".$transm[$i]["no_ref2"]."','".$transm[$i]["no_ref3"]."','".$transm[$i]["param1"]."','".$transm[$i]["param2"]."','".$transm[$i]["param3"]."')");
                }
            }

            //TRANSJ
            $transj = $request->TRANSJ;
            if(count($transj) > 0){
                
                for($i=0;$i<count($transj);$i++){
                    
                    $kurs=($transj[$i]['kurs'] != "" ? $transj[$i]['kurs'] : 0);
                    $nu=($transm[$i]['nu'] != "" ? $transm[$i]['nu'] : 0);
                    $nilai=($transj[$i]['nilai'] != "" ? $transj[$i]['nilai'] : 0);
                    $nilai_curr=($transj[$i]['nilai_curr'] != "" ? $transj[$i]['nilai_curr'] : 0);
                    $ins2=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                    ('".$transj[$i]["no_bukti"]."','".$transj[$i]["kode_lokasi"]."','".$transj[$i]["tgl_input"]."','".$transj[$i]["nik_user"]."','".$transj[$i]["periode"]."','".$transj[$i]["no_dokumen"]."','".$transj[$i]["tanggal"]."',".$nu.",'".$transj[$i]["kode_akun"]."','".$transj[$i]["dc"]."',".$nilai.",".$nilai_curr.",'".$transj[$i]["keterangan"]."','".$transj[$i]["keterangan"]."','".$transj[$i]["modul"]."','".$transj[$i]["jenis"]."',".$kurs.",'".$transj[$i]["kode_pp"]."','".$transj[$i]["kode_drk"]."','".$transj[$i]["kode_cust"]."','".$transj[$i]["kode_vendor"]."','".$transj[$i]["no_fa"]."','".$transj[$i]["no_selesai"]."','".$transj[$i]["no_ref1"]."','".$transj[$i]["no_ref2"]."','".$transj[$i]["no_ref3"]."')");
                }
            }

            //BRGBELI HUT
            $brgBeli = $request->BRGHUT;
            if(count($brgBeli) > 0){
                for($i=0;$i<count($brgBeli);$i++){
                    $kurs=($brgBeli[$i]['kurs'] != "" ? $brgBeli[$i]['kurs'] : 0);
                    $nilai=($brgBeli[$i]['nilai'] != "" ? $brgBeli[$i]['nilai'] : 0);
                    $nilai_ppn=($brgBeli[$i]['nilai_ppn'] != "" ? $brgBeli[$i]['nilai_ppn'] : 0);
                    $nilai_pph=($brgBeli[$i]['nilai_pph'] != "" ? $brgBeli[$i]['nilai_pph'] : 0);
                    $diskon=($brgBeli[$i]['diskon'] != "" ? $brgBeli[$i]['diskon'] : 0);

                    $ins3 = DB::connection($this->sql)->insert("insert into brg_belihut_d (no_beli, kode_lokasi, tanggal, keterangan, kode_vendor, kode_curr, kurs, kode_pp, nilai, periode, nik_user, tgl_input, akun_hutang, nilai_ppn, no_fp, due_date, nilai_pph, diskon, modul, kode_gudang
                    ) values ('".$brgBeli[$i]["no_beli"]."','".$brgBeli[$i]["kode_lokasi"]."','".$brgBeli[$i]["tanggal"]."','".$brgBeli[$i]["keterangan"]."','".$brgBeli[$i]["kode_vendor"]."','".$brgBeli[$i]["kode_curr"]."',$kurs,'".$brgBeli[$i]["kode_pp"]."',".$nilai.",'".$brgBeli[$i]["periode"]."','".$brgBeli[$i]["nik_user"]."','".$brgBeli[$i]["tgl_input"]."','".$brgBeli[$i]["akun_hutang"]."',".$nilai_ppn.",'".$brgBeli[$i]["no_fp"]."','".$brgBeli[$i]["due_date"]."',".$nilai_pph.",$diskon,'".$brgBeli[$i]["modul"]."','".$brgBeli[$i]["kode_gudang"]."') ");
                }
            }

            //BRGTRANS
            $brgTrans = $request->BRGTRANS;
            if(count($brgTrans) > 0){
                for($i=0;$i<count($brgTrans);$i++){
                    $stok=($brgTrans[$i]['stok'] != "" ? $brgTrans[$i]['stok'] : 0);  
                    $jumlah=($brgTrans[$i]['jumlah'] != "" ? $brgTrans[$i]['jumlah'] : 0);
                    $bonus=($brgTrans[$i]['bonus'] != "" ? $brgTrans[$i]['bonus'] : 0);
                    $harga=($brgTrans[$i]['harga'] != "" ? $brgTrans[$i]['harga'] : 0);
                    $hpp_p=($brgTrans[$i]['hpp_p'] != "" ? $brgTrans[$i]['hpp_p'] : 0);  
                    $p_disk=($brgTrans[$i]['p_disk'] != "" ? $brgTrans[$i]['p_disk'] : 0);
                    $diskon=($brgTrans[$i]['diskon'] != "" ? $brgTrans[$i]['diskon'] : 0);
                    $tot_diskon=($brgTrans[$i]['tot_diskon'] != "" ? $brgTrans[$i]['tot_diskon'] : 0);
                    $total=($brgTrans[$i]['total'] != "" ? $brgTrans[$i]['total'] : 0);
                    $ins4= DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                        ('".$brgTrans[$i]["no_bukti"]."','".$brgTrans[$i]["kode_lokasi"]."','".$brgTrans[$i]["periode"]."','".$brgTrans[$i]["modul"]."','".$brgTrans[$i]["form"]."',".$brgTrans[$i]["nu"].",'".$brgTrans[$i]["kode_gudang"]."','".$brgTrans[$i]["kode_barang"]."','".$brgTrans[$i]["no_batch"]."','".$brgTrans[$i]["tgl_ed"]."','".$brgTrans[$i]["satuan"]."','".$brgTrans[$i]["dc"]."',".$stok.",".$jumlah.",".$bonus.",".$harga.",".$hpp_p.",".$p_disk.",".$diskon.",".$tot_diskon.",".$total.") ");
                }
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function syncReturBeli(Request $request)
    {
        $this->validate($request, [
            'TRANSM' => 'required|array',
            'TRANSJ' => 'required|array',
            'BRGBAYAR' => 'required|array',
            'BRGTRANS' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

             //TRANSM
             $transm = $request->TRANSM;
             if(count($transm) > 0){
                 for($i=0;$i<count($transm);$i++){
 
                     $kurs=($transm[$i]['kurs'] != "" ? $transm[$i]['kurs'] : 0);
                     $nilai1=($transm[$i]['nilai1'] != "" ? $transm[$i]['nilai1'] : 0);
                     $nilai2=($transm[$i]['nilai2'] != "" ? $transm[$i]['nilai2'] : 0);
                     $nilai3=($transm[$i]['nilai3'] != "" ? $transm[$i]['nilai3'] : 0);
                     $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                     ('".$transm[$i]["no_bukti"]."','".$transm[$i]["kode_lokasi"]."','".$transm[$i]["tgl_input"]."','".$transm[$i]["nik_user"]."','".$transm[$i]["periode"]."','".$transm[$i]["modul"]."','".$transm[$i]["form"]."','".$transm[$i]["posted"]."','".$transm[$i]["prog_seb"]."','".$transm[$i]["progress"]."','".$transm[$i]["kode_pp"]."','".$transm[$i]["tanggal"]."','".$transm[$i]["no_dokumen"]."','".$transm[$i]["keterangan"]."','".$transm[$i]["kode_curr"]."',".$kurs.",".$nilai1.",".$nilai2.",".$nilai3.",'".$transm[$i]["nik1"]."','".$transm[$i]["nik2"]."','".$transm[$i]["nik3"]."','".$transm[$i]["no_ref1"]."','".$transm[$i]["no_ref2"]."','".$transm[$i]["no_ref3"]."','".$transm[$i]["param1"]."','".$transm[$i]["param2"]."','".$transm[$i]["param3"]."')");
                 }
             }
 
             //TRANSJ
             $transj = $request->TRANSJ;
             if(count($transj) > 0){
                 
                 for($i=0;$i<count($transj);$i++){
                     
                     $kurs=($transj[$i]['kurs'] != "" ? $transj[$i]['kurs'] : 0);
                     $nu=($transm[$i]['nu'] != "" ? $transm[$i]['nu'] : 0);
                     $nilai=($transj[$i]['nilai'] != "" ? $transj[$i]['nilai'] : 0);
                     $nilai_curr=($transj[$i]['nilai_curr'] != "" ? $transj[$i]['nilai_curr'] : 0);
                     $ins2=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                     ('".$transj[$i]["no_bukti"]."','".$transj[$i]["kode_lokasi"]."','".$transj[$i]["tgl_input"]."','".$transj[$i]["nik_user"]."','".$transj[$i]["periode"]."','".$transj[$i]["no_dokumen"]."','".$transj[$i]["tanggal"]."',".$nu.",'".$transj[$i]["kode_akun"]."','".$transj[$i]["dc"]."',".$nilai.",".$nilai_curr.",'".$transj[$i]["keterangan"]."','".$transj[$i]["keterangan"]."','".$transj[$i]["modul"]."','".$transj[$i]["jenis"]."',".$kurs.",'".$transj[$i]["kode_pp"]."','".$transj[$i]["kode_drk"]."','".$transj[$i]["kode_cust"]."','".$transj[$i]["kode_vendor"]."','".$transj[$i]["no_fa"]."','".$transj[$i]["no_selesai"]."','".$transj[$i]["no_ref1"]."','".$transj[$i]["no_ref2"]."','".$transj[$i]["no_ref3"]."')");
                 }
             }
 
             //BRGBELI BAYAR
             $brgBeli = $request->BRGBAYAR;
             if(count($brgBeli) > 0){
                 for($i=0;$i<count($brgBeli);$i++){
                     $nilai=($brgBeli[$i]['nilai'] != "" ? $brgBeli[$i]['nilai'] : 0);
 
                     $ins3 = DB::connection($this->sql)->insert("insert into brg_belibayar_d(no_bukti,kode_lokasi,no_beli,kode_vendor,periode,dc,modul,nilai,nik_user,tgl_input) 
                     values ('".$brgBeli[$i]["no_bukti"]."','".$brgBeli[$i]["kode_lokasi"]."','".$brgBeli[$i]['no_beli']."','".$brgBeli[$i]['kode_vendor']."', '".$brgBeli[$i]['periode']."','".$brgBeli[$i]["dc"]."','".$brgBeli[$i]["modul"]."',".$nilai.",'".$brgBeli[$i]['nik_user']."','".$brgBeli[$i]['tgl_input']."')");
                 }
             }
 
             //BRGTRANS
             $brgTrans = $request->BRGTRANS;
             if(count($brgTrans) > 0){
                 for($i=0;$i<count($brgTrans);$i++){
                     $stok=($brgTrans[$i]['stok'] != "" ? $brgTrans[$i]['stok'] : 0);  
                     $jumlah=($brgTrans[$i]['jumlah'] != "" ? $brgTrans[$i]['jumlah'] : 0);
                     $bonus=($brgTrans[$i]['bonus'] != "" ? $brgTrans[$i]['bonus'] : 0);
                     $harga=($brgTrans[$i]['harga'] != "" ? $brgTrans[$i]['harga'] : 0);
                     $hpp_p=($brgTrans[$i]['hpp_p'] != "" ? $brgTrans[$i]['hpp_p'] : 0);  
                     $p_disk=($brgTrans[$i]['p_disk'] != "" ? $brgTrans[$i]['p_disk'] : 0);
                     $diskon=($brgTrans[$i]['diskon'] != "" ? $brgTrans[$i]['diskon'] : 0);
                     $tot_diskon=($brgTrans[$i]['tot_diskon'] != "" ? $brgTrans[$i]['tot_diskon'] : 0);
                     $total=($brgTrans[$i]['total'] != "" ? $brgTrans[$i]['total'] : 0);
                     $ins4= DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                         ('".$brgTrans[$i]["no_bukti"]."','".$brgTrans[$i]["kode_lokasi"]."','".$brgTrans[$i]["periode"]."','".$brgTrans[$i]["modul"]."','".$brgTrans[$i]["form"]."',".$brgTrans[$i]["nu"].",'".$brgTrans[$i]["kode_gudang"]."','".$brgTrans[$i]["kode_barang"]."','".$brgTrans[$i]["no_batch"]."','".$brgTrans[$i]["tgl_ed"]."','".$brgTrans[$i]["satuan"]."','".$brgTrans[$i]["dc"]."',".$stok.",".$jumlah.",".$bonus.",".$harga.",".$hpp_p.",".$p_disk.",".$diskon.",".$tot_diskon.",".$total.") ");
                 }
             }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }				   
        
    }

    public function executeSQL(Request $request)
    {
        $this->validate($request, [
            'sql' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $query = DB::connection($this->db)->update($request->sql);
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Berhasil.";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
