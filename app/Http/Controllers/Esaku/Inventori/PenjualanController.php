<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PenjualanController extends Controller
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

    public function getNoOpen(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "select no_open from kasir_open where nik='$nik' and kode_lokasi='$kode_lokasi' and no_close='-' ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['no_open'] = $res[0]['no_open'];
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['no_open'] = '-';
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
            'no_open' => 'required',
            'kode_pp' => 'required',
            'total_trans' => 'required',
            // 'total_ppn' => 'required',
            'diskon' => 'required',
            'total_bayar' => 'required',
            'kode_barang' => 'required|array',
            'qty_barang' => 'required|array',
            'harga_barang' => 'required|array',
            'diskon_barang' => 'required|array',
            'sub_barang' => 'required|array',
            'ppn_barang' => 'required|array',
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

            $str_format="00000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-PNJ".$per.".";
            $sql="select right(isnull(max(no_jual),'00000'),".strlen($str_format).")+1 as id from brg_jualpiu_dloc where no_jual like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $sql="select kode_spro,flag from spro where kode_spro in ('JUALDIS','HUTPPN','JUALKAS','CUSTINV') and kode_lokasi = '".$kode_lokasi."'";
            $get2 = DB::connection($this->sql)->select($sql);
            $get2 = json_decode(json_encode($get2),true);
            if(count($get2) > 0){
                foreach ($get2 as $row){
                    if ($row['kode_spro'] == "HUTPPN") $akunPPN=$row['flag'];
                    if ($row['kode_spro'] == "JUALDIS") $akunDiskon=$row['flag'];
                    if ($row['kode_spro'] == "JUALKAS") $akunKas=$row['flag'];
                    if ($row['kode_spro'] == "CUSTINV") $akunPiutang=$row['flag'];
                }
            }

            $sqlp="select distinct b.akun_pdpt as kode_akun from brg_barang a inner join brg_barangklp b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='$kode_lokasi' ";

            $get3 = DB::connection($this->sql)->select($sqlp);
            $get3 = json_decode(json_encode($get3),true);
            if(count($get3) > 0){

                $akunPDPT=$get3[0]['kode_akun'];
            }else{
                $akunPDPT= "-";
            }

            $sqlg="select top 1 a.kode_gudang from brg_gudang a where a.kode_lokasi='$kode_lokasi' ";

            $get4 = DB::connection($this->sql)->select($sqlg);
            $get4 = json_decode(json_encode($get4),true);
            if(count($get4) > 0){
                $kodeGudang=$get4[0]['kode_gudang'];
            }else{
                $kodeGudang="-";
            }

            $ins =DB::connection($this->sql)->insert("insert into brg_jualpiu_dloc(no_jual,kode_lokasi,tanggal,keterangan,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp,diskon,kode_gudang,no_ba,tobyr,no_open,no_close) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($id,$kode_lokasi,date('Y-m-d H:i:s'),"Penjualan No: $id",'CASH','IDR',1,$request->kode_pp,$request->total_trans,$periode,$nik,date('Y-m-d H:i:s'),$akunPiutang,0,0,'-',$request->diskon,$kodeGudang,'-',$request->total_bayar,$request->no_open,'-'));		

            if(isset($request->kode_barang) && count($request->kode_barang) > 0){

                for($a=0; $a<count($request->kode_barang);$a++){
                    $ppn = ($request->ppn_barang[$a] * $request->sub_barang[$a])/100;
                    $ins2[$a] = DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total,ppn) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,$periode,'BRGJUAL','BRGJUAL',$a,$kodeGudang,$request->kode_barang[$a],'-',date('Y-m-d H:i:s'),'-','C',0,$request->qty_barang[$a],0,$request->harga_barang[$a],0,0,$request->diskon_barang[$a],0,$request->sub_barang[$a],$ppn));
                }	
            }

            $ins3 = DB::connection($this->sql)->insert("
            update a set a.hpp=b.hpp, a.no_belicurr=b.no_belicurr 
            from brg_trans_d a 
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti=? and a.kode_lokasi=? ",array($id,$kode_lokasi));

            // $exec2 = DB::connection($this->sql)->update("exec sp_brg_saldo_harian ?,? ", array($id,$kode_lokasi));
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_jual'] = $id;
            $success['message'] = "Data Penjualan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penjualan gagal disimpan ".$e;
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
        //
        
    }

    public function getNota(Request $request)
    {
        $this->validate($request, [
            'no_jual' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
  
            $success["nik"]=$nik;
            $success["no_jual"] = $request->no_jual;

            $sql="select * from brg_jualpiu_dloc where no_jual='$request->no_jual' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $total_trans=$get[0]['nilai'];
                $total_disk=$get[0]['diskon'];
                $total_stlh=$get[0]['nilai']-$get[0]['diskon'];
                // $total_ppn=$get[0]['nilai_ppn'];
                // $total_stlh_ppn=floatval($total_ppn)+floatval($total_stlh);
                $total_byr=$get[0]['tobyr'];
                $kembalian=$get[0]['tobyr']-($total_stlh);
                $success["tgl"] = $get[0]['tanggal'];
            }else{
                $total_trans=0;
                $total_disk=0;
                $total_stlh=0;
                // $total_ppn=0;
                // $total_stlh_ppn=0;
                $total_byr=0;
                $kembalian=0;
                $success["tgl"] = null;
            }

            $success["total_trans"]=$total_trans;
            $success["total_disk"]=$total_disk;
            $success["total_stlh"]=$total_stlh;
            // $success["total_ppn"]=$total_ppn;
            // $success["total_stlh_ppn"]=$total_stlh_ppn;
            $success["total_byr"]=$total_byr;
            $success["kembalian"]=$kembalian;

            $sql="select a.kode_barang,a.harga,a.jumlah,a.diskon*-1 as diskon,b.nama,b.sat_kecil,a.total from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi where a.no_bukti='$request->no_jual' and a.kode_lokasi='$kode_lokasi' ";
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

    public function cekBonus(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format:Y-m-d',
            'kode_barang' => 'required',
            'jumlah' => 'required',
            'harga' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql=" select ref_qty as beli,bonus_qty as bonus from brg_bonus where kode_barang='$request->kode_barang' and kode_lokasi='$kode_lokasi' and '$request->tanggal' between tgl_mulai and tgl_selesai ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $jumlah_brg=$request->jumlah;
            $harga=$request->harga;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $bonus=0;
                $diskon=0;
                $jml_bonus=0;
                if(count($res)>0){
                    for($i=0;$i<count($res);$i++){
                        $bonus += (int) floor(abs($jumlah_brg/$res[$i]["beli"]));
                        $jumlah_brg+=($bonus*$res[$i]["bonus"]);
                        $diskon+= $bonus*$harga;
                    }
                }
                $jml_bonus = $jumlah_brg - $request->jumlah;
        
                $success["bonus"] = $jml_bonus;
                $success["jumlah"] = $jumlah_brg;
                $success["diskon"] = $jml_bonus*$harga;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success["bonus"] = 0;
                $success["jumlah"] = $jumlah_brg;
                $success["diskon"] = 0;
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

}
