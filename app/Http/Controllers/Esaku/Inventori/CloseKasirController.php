<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CloseKasirController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_klp form brg_barangklp where kode_klp ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select no_close,nik,tgl_input,saldo_awal,total_pnj from kasir_close where kode_lokasi='".$kode_lokasi."' ";

            $res = DB::connection($this->sql)->select($sql);
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

    public function getOpenKasir(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select no_open,nik,tgl_input,saldo_awal from kasir_open where kode_lokasi='".$kode_lokasi."' and no_close='-' 
            ";

            $res = DB::connection($this->sql)->select($sql);
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
        $this->validate($request, [
            'no_open' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "select a.no_open,a.nik,a.tgl_input,a.saldo_awal,isnull(b.total,0) as total_pnj,isnull(b.diskon,0) as total_disk,convert(varchar(10),a.tgl_input,121) as tgl 
            from kasir_open a 
            left join 
            ( select a.no_open,sum(a.nilai) as total,sum(a.diskon) as diskon
              from brg_jualpiu_dloc a
              where isnull(a.no_close,'-')='-' 
              group by a.no_open
              ) b on a.no_open=b.no_open 
            where a.kode_lokasi='".$kode_lokasi."' and a.no_open='".$request->no_open."' and a.nik='".$nik."' 
            ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select no_jual,tanggal,keterangan,periode,nilai,diskon from brg_jualpiu_dloc
            where kode_lokasi = '".$kode_lokasi."' and no_open='$request->no_open' and no_jual not like '%RJ%'" ;
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 = "select no_beli,tanggal,keterangan,periode,nilai,diskon from brg_belihut_d
            where kode_lokasi = '".$kode_lokasi."' and isnull(no_close,'-') = '-' " ;
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "select no_jual as no_retur,tanggal,keterangan,periode,nilai,diskon from brg_jualpiu_dloc
            where kode_lokasi = '".$kode_lokasi."' and isnull(no_close,'-') = '-' and no_jual like '%RJ%' " ;
            $res4 = DB::connection($this->sql)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_beli'] = $res3;
                $success['data_retur_jual'] = $res4;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_beli'] = [];
                $success['data_retur_jual'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_detail'] = [];
            $success['data_beli'] = [];
            $success['data_retur_jual'] = [];
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
            'no_open' => 'required',
            'no_jual' => 'required|array',
            'kode_pp' => 'required',
            'tanggal' => 'required',
            'total_pnj' => 'required',
            // 'total_ppn' => 'required',
            'total_diskon' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            date_default_timezone_set('Asia/Jakarta');
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sqlcek = "select no_open from kasir_open where no_open='".$request->no_open."' and isnull(no_close,'-') = '-' and kode_lokasi='$kode_lokasi' ";

            $cek = DB::connection($this->sql)->select($sqlcek);
            $cek = json_decode(json_encode($cek),true);
            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-CLS".$per.".";
            $sql="select right(isnull(max(no_close),'0000'),".strlen($str_format).")+1 as id from kasir_close where no_close like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            if(count($cek) > 0){

    
                $spro = DB::connection($this->sql)->select("select a.akun_pdpt, sum (case when c.dc='C' then c.total else -c.total end) as nilai_jual from brg_barangklp a
                inner join brg_barang b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi
                inner join brg_trans_d c on b.kode_barang=c.kode_barang and c.kode_lokasi=b.kode_lokasi
                inner join brg_jualpiu_dloc d on c.no_bukti=d.no_jual and c.kode_lokasi=d.kode_lokasi and d.no_jual not like '%RJ%'
                where  a.kode_lokasi='$kode_lokasi' and d.no_close='-' and d.no_open='".$request->no_open."' and d.nik_user='$nik' group by a.akun_pdpt
                ");
                $spro = json_decode(json_encode($spro),true);
                if(count($spro)>0){
                    $akunpdpt=$spro[0]["akun_pdpt"];
                }else{
                    $akunpdpt = "-";
                }
    
                $spro2 =DB::connection($this->sql)->select("select kode_spro,flag  from spro where kode_lokasi='$kode_lokasi' and kode_spro='JUALDIS'");
                $spro2 = json_decode(json_encode($spro2),true);
                if(count($spro2)>0){
                    $akunDiskon=$spro2[0]["flag"];
                }else{
                    $akunDiskon = "-";
                }
    
                $spro3 = DB::connection($this->sql)->select(" select kode_spro,flag  from spro where kode_lokasi='$kode_lokasi' and kode_spro='CUSTINV'");
                $spro3 = json_decode(json_encode($spro3),true);
                if(count($spro3)>0){
                    $akunpiu=$spro3[0]["flag"];
                }else{
                    $akunpiu = "-";
                }

                $sql="select kode_spro,flag from spro where kode_spro in ('HUTPPN') and kode_lokasi = '".$kode_lokasi."'";
                $spro4 = DB::connection($this->sql)->select($sql);
                $spro4 = json_decode(json_encode($spro4),true);
                $akunPPN = '-';
                if(count($spro4)>0){
                    $akunPPN=$spro4[0]["flag"];
                }else{
                    $akunPPN = "-";
                }
    
                $exec = array();
                $total_pnj=floatval($request->total_pnj)-floatval($request->total_diskon);
                $ins= DB::connection($this->sql)->insert("insert into kasir_close (no_close,kode_lokasi,tgl_input,nik_user,nik,saldo_awal,total_pnj) values (?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$nik,$request->saldo_awal,$total_pnj));

                $upd3 = DB::connection($this->sql)->table('kasir_open')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_open', $request->no_open)
                ->update(['no_close'=>$id]);
    
    
                if(count($request->no_jual) > 0){

                    $sql = "select no_jual from brg_jualpiu_dloc where no_open='".$request->no_open."' and kode_lokasi='$kode_lokasi' and no_jual not like '%RJ%'";
                    $return = DB::connection($this->sql)->select($sql);
                    $return = json_decode(json_encode($return),true);
    
                    for($i=0;$i<count($return);$i++){
    
                        $upd1[$i] = DB::connection($this->sql)->table('brg_jualpiu_dloc')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_jual', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);

                        $upd2[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);   
                        
                    } 

                    // $get2=DB::connection($this->sql)->select("select c.akun_pdpt,'akun_ppn', round ((sum(a.total)*100/110)*10/100,0) as nilai_ppn from brg_trans_d a 
                    // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    // inner join brg_barangklp c on b.kode_klp=c.kode_klp and b.kode_lokasi=c.kode_lokasi
                    // where  a.no_close='$id' and a.kode_lokasi='$kode_lokasi' group by c.akun_pdpt");
                    // if(count($get2) > 0){
                    //     $nilai_ppn = $get2[0]->nilai_ppn;
                    // }else{
                    //     $nilai_ppn = 0;
                    // }

                    // $get3=DB::connection($this->sql)->select("select c.akun_hpp,c.akun_pers, sum(a.jumlah*a.hpp) as nilai_hpp from brg_trans_d a 
                    // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    // inner join brg_barangklp c on b.kode_klp=c.kode_klp and b.kode_lokasi=c.kode_lokasi
                    // where  a.no_close='$id' and a.kode_lokasi='$kode_lokasi' group by c.akun_hpp,c.akun_pers");
                    // if(count($get3) > 0){
                    //     $nilai_hpp = $get3[0]->nilai_hpp;
                    //     $akunHPP = $get3[0]->akun_hpp;
                    // }else{
                    //     $nilai_hpp = 0;
                    //     $akunHPP = "-";
                    // }

                    // $total_pnj += floatval($nilai_ppn) - floatval($nilai_hpp);

                    // $sqlm = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'IV','CLOSING','F','-','-',$request->kode_pp,$request->tanggal,'-','Penjualan Persediaan '.$request->no_open,'IDR',1,$total_pnj,0,floatval($request->total_diskon),floatval($nilai_ppn),'-','-','-','-','-',floatval($nilai_hpp),'-','-'));
    
                    
                    // $sqlJ2=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,0,$akunpiu,'D',$total_pnj,$total_pnj,'Piutang','BRGJUAL','PIUTANG','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    
                    // if ($request->total_diskon > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,1,$akunDiskon,'D',floatval($request->total_diskon),floatval($request->total_diskon),'Diskon Penjualan','BRGJUAL','JUALDISC','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }

                    // // JURNAL HPP
                    // if (floatval($nilai_hpp) > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,$nik,$periode,'-',$request->tanggal,1,$akunHPP,'D',floatval($nilai_hpp),floatval($nilai_hpp),'HPP Penjualan','BRGJUAL','JUALHPP','IDR',2,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }

                    // // JURNAL PPN
                    // if (floatval($nilai_ppn) > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,$nik,$periode,'-',$request->tanggal,1,$akunPPN,'C',floatval($nilai_ppn),floatval($nilai_ppn),'PPN Penjualan','BRGJUAL','JUALPPN','IDR',3,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }
    
                    // $sqlJ= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    // array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,4,$akunpdpt,'C',floatval($request->total_pnj),floatval($request->total_pnj),'Penjualan','BRGJUAL','PDPT','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
    
                    // ///------------------------------------END JURNAL--------------------------------//                    
    
                } 
                
                if(isset($request->no_retur) && count($request->no_retur) > 0){

                    $sql = "select no_jual from brg_jualpiu_dloc where isnull(no_close,'-')='-' and kode_lokasi='$kode_lokasi' and no_jual like '%RJ%' ";
                    $return = DB::connection($this->sql)->select($sql);
                    $return = json_decode(json_encode($return),true);
    
                    for($i=0;$i<count($return);$i++){
    
                        $upd5[$i] = DB::connection($this->sql)->table('brg_jualpiu_dloc')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_jual', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);

                        $upd6[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);   
                        
                    } 
                    
                    // $exec = DB::connection($this->sql)->update('exec sp_brg_closing ?, ?, ?, ?, ?, ?, ?, ?, ?',array($id,$kode_lokasi,$nik,$periode,'Penjualan Persediaan '.$request->no_open,$request->kode_pp,'-',$akunpiu,$akunPPN));                 
    
                } 
                
                if(isset($request->no_beli) && count($request->no_beli) > 0){

                    $sql = "select no_beli,id_sync,akun_hutang from brg_belihut_d where isnull(no_close,'-')='-' and kode_lokasi='$kode_lokasi' ";
                    $return2 = DB::connection($this->sql)->select($sql);
                    $return2 = json_decode(json_encode($return2),true);
    
                    for($i=0;$i<count($return2);$i++){
    
                        $upd1[$i] = DB::connection($this->sql)->table('brg_belihut_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_beli', $return2[$i]['no_beli'])
                        ->update(['no_close'=>$id]);

                        $upd2[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return2[$i]['no_beli'])
                        ->update(['no_close'=>$id]);   

                        $get = DB::connection($this->sql)->select("select sum(case dc when 'D' then nilai else -nilai end) as sls  
                        from trans_j where no_bukti ='".$return2[$i]['no_beli']."' and kode_lokasi='$kode_lokasi'
                        ");

                        if(count($get) > 0){
                            $sls = $get[0]->sls;
                        }else{
                            $sls = 0;
                        }

                        if($sls < 0){
                            $dc = "D";
                        }else{
                            $dc = "C";
                        }

                        $insls = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3,id_sync) values (?, ?, getdate(), ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($return2[$i]['no_beli'],$kode_lokasi,$nik,$periode,'-',999,$akunpiu,$dc,abs($sls),abs($sls),'Selisih Koma','BRGBELI','SLS','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-',$return2[$i]['id_sync']));
                    } 
                }

                set_time_limit(300);
                $exec = DB::connection($this->sql)->update('exec sp_brg_closing ?, ?, ?, ?, ?, ?, ?, ?, ?',array($id,$kode_lokasi,$nik,$periode,'Penjualan Persediaan '.$request->no_open,$request->kode_pp,'-',$akunpiu,$akunPPN));

                $exec2 = DB::connection($this->sql)->update("exec sp_brg_saldo_harian ?,? ", array($id,$kode_lokasi));
    
                $tmp="Data Close Kasir berhasil disimpan";
                $sts=true;
                DB::connection($this->sql)->commit();
            }else{
                $tmp="Error! No open ".$request->no_open." sudah diclose.";
                $sts=false;
            }

            $success['status'] = $sts;
            $success['no_close'] = $id;
            $success['message'] = $tmp;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Close Kasir gagal disimpan ".$e;
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
            'no_open' => 'required',
            'no_close' => 'required',
            'no_jual' => 'required|array',
            'kode_pp' => 'required',
            'tanggal' => 'required',
            'total_pnj' => 'required',
            'total_diskon' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            date_default_timezone_set('Asia/Jakarta');
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sqlcek = "select no_open from kasir_open where no_open='".$request->no_open."' and isnull(no_close,'-') = '-' and kode_lokasi='$kode_lokasi' ";

            $cek = DB::connection($this->sql)->select($sqlcek);
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){

                $str_format="0000";
                $periode=date('Y').date('m');
                $per=date('y').date('m');
                $prefix=$kode_lokasi."-CLS".$per.".";
                $sql="select right(isnull(max(no_close),'0000'),".strlen($str_format).")+1 as id from kasir_close where no_close like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
                
                $get = DB::connection($this->sql)->select($sql);
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
                }else{
                    $id = "-";
                }
    
                $spro = DB::connection($this->sql)->select("select a.akun_pdpt, sum (case when c.dc='C' then c.total else -c.total end) as nilai_jual from brg_barangklp a
                inner join brg_barang b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi
                inner join brg_trans_d c on b.kode_barang=c.kode_barang and c.kode_lokasi=b.kode_lokasi
                inner join brg_jualpiu_dloc d on c.no_bukti=d.no_jual and c.kode_lokasi=d.kode_lokasi and d.no_jual not like '%RJ%'
                where  a.kode_lokasi='$kode_lokasi' and d.no_close='-' and d.no_open='".$request->no_open."' and d.nik_user='$nik' group by a.akun_pdpt
                ");
                $spro = json_decode(json_encode($spro),true);
                if(count($spro)>0){
                    $akunpdpt=$spro[0]["akun_pdpt"];
                }else{
                    $akunpdpt = "-";
                }
    
                $spro2 =DB::connection($this->sql)->select("select kode_spro,flag  from spro where kode_lokasi='$kode_lokasi' and kode_spro='JUALDIS'");
                $spro2 = json_decode(json_encode($spro2),true);
                if(count($spro2)>0){
                    $akunDiskon=$spro2[0]["flag"];
                }else{
                    $akunDiskon = "-";
                }
    
                $spro3 = DB::connection($this->sql)->select(" select kode_spro,flag  from spro where kode_lokasi='$kode_lokasi' and kode_spro='CUSTINV'");
                $spro3 = json_decode(json_encode($spro3),true);
                if(count($spro3)>0){
                    $akunpiu=$spro3[0]["flag"];
                }else{
                    $akunpiu = "-";
                }

                $sql="select kode_spro,flag from spro where kode_spro in ('HUTPPN') and kode_lokasi = '".$kode_lokasi."'";
                $spro4 = DB::connection($this->sql)->select($sql);
                $spro4 = json_decode(json_encode($spro4),true);
                $akunPPN = '-';
                if(count($spro4)>0){
                    $akunPPN=$spro4[0]["flag"];
                }else{
                    $akunPPN = "-";
                }
    
                $exec = array();
    
                $del = DB::connection($this->sql)->table('kasir_close')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_close', $request->no_close)
                ->delete();
                
                $del2 = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_close)
                ->delete();
                
                $del3 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_close)
                ->delete();
                
                $del4 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_close)
                ->delete();
                
                $upd0 = DB::connection($this->sql)->table('kasir_open')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_open', $request->no_open)
                ->update(['no_close'=>'-']);
                
                if(count($request->no_jual) > 0){
                    
                    for($i=0;$i<count($request->no_jual);$i++){
                        $upd[$i] = DB::connection($this->sql)->table('brg_jualpiu_dloc')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_jual', $request->no_jual[$i])
                        ->update(['no_close'=>'-']);
                    }   
                }

                
                $total_pnj=floatval($request->total_pnj)-floatval($request->total_diskon);
                $ins= DB::connection($this->sql)->insert("insert into kasir_close (no_close,kode_lokasi,tgl_input,nik_user,nik,saldo_awal,total_pnj) values (?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$nik,$request->saldo_awal,$total_pnj));

                $upd3 = DB::connection($this->sql)->table('kasir_open')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_open', $request->no_open)
                ->update(['no_close'=>$id]);
    
    
                if(count($request->no_jual) > 0){
                    
                    $sql = "select no_jual from brg_jualpiu_dloc where no_open='".$request->no_open."' and kode_lokasi='$kode_lokasi' and no_jual not like '%RJ%' ";
                    $return = DB::connection($this->sql)->select($sql);
                    $return = json_decode(json_encode($return),true);
    
                    for($i=0;$i<count($return);$i++){
    
                        $upd1[$i] = DB::connection($this->sql)->table('brg_jualpiu_dloc')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_jual', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);

                        $upd2[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);   
                        
                    } 
                    
                    $exec = DB::connection($this->sql)->update('exec sp_brg_closing ?, ?, ?, ?, ?, ?, ?, ?, ?',array($id,$kode_lokasi,$nik,$periode,'Penjualan Persediaan '.$request->no_open,$request->kode_pp,'-',$akunpiu,$akunPPN));
                    
                    // $get2=DB::connection($this->sql)->select("select c.akun_pdpt,'akun_ppn', round ((sum(a.total)*100/110)*10/100,0) as nilai_ppn from brg_trans_d a 
                    // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    // inner join brg_barangklp c on b.kode_klp=c.kode_klp and b.kode_lokasi=c.kode_lokasi
                    // where  a.no_close='$id' and a.kode_lokasi='$kode_lokasi' group by c.akun_pdpt");
                    // if(count($get2) > 0){
                    //     $nilai_ppn = $get2[0]->nilai_ppn;
                    // }else{
                    //     $nilai_ppn = 0;
                    // }

                    // $get3=DB::connection($this->sql)->select("select c.akun_hpp,c.akun_pers, sum(a.jumlah*a.hpp) as nilai_hpp from brg_trans_d a 
                    // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    // inner join brg_barangklp c on b.kode_klp=c.kode_klp and b.kode_lokasi=c.kode_lokasi
                    // where  a.no_close='$id' and a.kode_lokasi='$kode_lokasi' group by c.akun_hpp,c.akun_pers");
                    // if(count($get3) > 0){
                    //     $nilai_hpp = $get3[0]->nilai_hpp;
                    //     $akunHPP = $get3[0]->akun_hpp;
                    // }else{
                    //     $nilai_hpp = 0;
                    //     $akunHPP = "-";
                    // }

                    // $total_pnj += floatval($nilai_ppn) - floatval($nilai_hpp);

                    // $sqlm = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'IV','CLOSING','F','-','-',$request->kode_pp,$request->tanggal,'-','Penjualan Persediaan '.$request->no_open,'IDR',1,$total_pnj,0,floatval($request->total_diskon),floatval($nilai_ppn),'-','-','-','-','-',floatval($nilai_hpp),'-','-'));
    
                    
                    // $sqlJ2=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,0,$akunpiu,'D',$total_pnj,$total_pnj,'Piutang','BRGJUAL','PIUTANG','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    
                    // if ($request->total_diskon > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,1,$akunDiskon,'D',floatval($request->total_diskon),floatval($request->total_diskon),'Diskon Penjualan','BRGJUAL','JUALDISC','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }

                    // // JURNAL HPP
                    // if (floatval($nilai_hpp) > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,$nik,$periode,'-',$request->tanggal,1,$akunHPP,'D',floatval($nilai_hpp),floatval($nilai_hpp),'HPP Penjualan','BRGJUAL','JUALHPP','IDR',2,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }

                    // // JURNAL PPN
                    // if (floatval($nilai_ppn) > 0) {
    
                    //     $sqld= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    //     array($id,$kode_lokasi,$nik,$periode,'-',$request->tanggal,1,$akunPPN,'C',floatval($nilai_ppn),floatval($nilai_ppn),'PPN Penjualan','BRGJUAL','JUALPPN','IDR',3,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    // }
    
                    // $sqlJ= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",
                    // array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',$request->tanggal,4,$akunpdpt,'C',floatval($request->total_pnj),floatval($request->total_pnj),'Penjualan','BRGJUAL','PDPT','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
    
                    // ///------------------------------------END JURNAL--------------------------------//                    
    
                }

                if(count($request->no_retur) > 0){

                    $sql = "select no_jual from brg_jualpiu_dloc where isnull(no_close,'-')='-' and kode_lokasi='$kode_lokasi' and no_jual like '%RJ%' ";
                    $return = DB::connection($this->sql)->select($sql);
                    $return = json_decode(json_encode($return),true);
    
                    for($i=0;$i<count($return);$i++){
    
                        $upd5[$i] = DB::connection($this->sql)->table('brg_jualpiu_dloc')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_jual', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);

                        $upd6[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return[$i]['no_jual'])
                        ->update(['no_close'=>$id]);   
                        
                    } 
                    
                    // $exec = DB::connection($this->sql)->update('exec sp_brg_closing ?, ?, ?, ?, ?, ?, ?, ?, ?',array($id,$kode_lokasi,$nik,$periode,'Penjualan Persediaan '.$request->no_open,$request->kode_pp,'-',$akunpiu,$akunPPN));                 
    
                }

                if(isset($request->no_beli) && count($request->no_beli) > 0){

                    $sql = "select no_beli from brg_belihut_d where isnull(no_close,'-')='-' and kode_lokasi='$kode_lokasi' ";
                    $return2 = DB::connection($this->sql)->select($sql);
                    $return2 = json_decode(json_encode($return2),true);
    
                    for($i=0;$i<count($return2);$i++){
    
                        $upd1[$i] = DB::connection($this->sql)->table('brg_belihut_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_beli', $return2[$i]['no_beli'])
                        ->update(['no_close'=>$id]);

                        $upd2[$i] = DB::connection($this->sql)->table('brg_trans_d')
                        ->where('kode_lokasi', $kode_lokasi)
                        ->where('no_bukti', $return2[$i]['beli'])
                        ->update(['no_close'=>$id]);   
                        
                    } 
                }
    
                $tmp="Data Close Kasir berhasil disimpan";
                $sts=true;
                DB::connection($this->sql)->commit();
            }else{
                $tmp="Error! No open ".$request->no_open." sudah diclose.";
                $sts=false;
            }

            $success['status'] = $sts;
            $success['message'] = $tmp;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Close Kasir gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }			
        
    }
}
