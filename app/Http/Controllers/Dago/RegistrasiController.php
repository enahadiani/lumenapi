<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class RegistrasiController extends Controller
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
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_reg,a.no_peserta,b.nama,a.tgl_input,e.nama as nama_paket,c.tgl_berangkat,a.flag_group
            from dgw_reg a
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi
            inner join dgw_paket e on a.no_paket=e.no_paket and a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."'");
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
            'periode' => 'required',
            'paket' => 'required',
            'jadwal' => 'required|integer',
            'no_peserta' => 'required',
            'agen' => 'required',
            'type_room' => 'required',
            'harga_room' => 'required',
            'sumber' => 'required',
            'quota' => 'required',
            'harga_paket' => 'required|numeric',
            'ukuran_pakaian' => 'required|in:S,XS,L,2L,3L,4L,5L,6L,7L,8L,9L,10L',
            'marketing' => 'required',
            'jenis_promo' => 'required',
            'jenis_paket' => 'required',
            'kode_pp' => 'required',
            'diskon' => 'required',
            'flag_group' => 'required',
            'berangkat_dengan' => 'required',
            'hubungan' => 'required',
            'referal' => 'required',
            'ket_diskon' => 'required',
            'dokumen'=>'required|array',
            'dokumen.*.no_dokumen' => 'required',
            'dokumen.*.deskripsi' => 'required',
            'biaya_tambahan'=>'required|array',
            'biaya_tambahan.*.kode_biaya' => 'required',
            'biaya_tambahan.*.nilai' => 'required',
            'biaya_tambahan.*.jumlah' => 'required',
            'biaya_tambahan.*.total' => 'required',
            'biaya_dokumen'=>'required|array',
            'biaya_dokumen.*.kode_biaya' => 'required',
            'biaya_dokumen.*.nilai' => 'required',
            'biaya_dokumen.*.jumlah' => 'required',
            'biaya_dokumen.*.total' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = date('y');
            $no_reg = $this->generateKode("dgw_reg", "no_reg", "REG/".substr($request->periode,2,4)."/", "0001");
            
            $ins = DB::connection($this->sql)->insert("insert into dgw_history_jadwal(no_reg,no_paket,no_jadwal,no_paket_lama,no_jadwal_lama,kode_lokasi) values (?, ?, ?, ?, ?, ?) ", array($no_reg,$request->paket,$request->jadwal,'-','-',$kode_lokasi));

            //NON-agen tidak ada fee
            if ($request->no_agen == "NON") {
                $noFee = "X";
            }else{
                $noFee = "-";
            }

            $ins2 = DB::connection($this->sql)->insert("insert into dgw_reg(no_reg,tgl_input,no_peserta,no_paket,no_jadwal,no_agen,no_type,harga_room,info,kode_lokasi,no_quota,harga,uk_pakaian, no_marketing,kode_harga,periode, jenis,no_fee, no_peserta_ref, kode_pp, diskon,flag_group,brkt_dgn,hubungan,referal,ket_diskon) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_reg,date('Y-m-d H:i:s'),$request->no_peserta,$request->paket,$request->jadwal,$request->agen,$request->type_room,$request->harga_room,$request->sumber,$kode_lokasi,$request->quota,$request->harga_paket,$request->ukuran_pakaian,$request->marketing,$request->jenis_promo,$request->periode,$request->jenis_paket,$noFee,$request->no_peserta,$request->kode_pp,$request->diskon,$request->flag_group,$request->berangkat_dengan,$request->hubungan,$request->referal,$request->ket_diskon));

            $dok = $request->dokumen;
            if (count($dok) > 0){
                for ($i=0;$i <count($dok);$i++){
                   
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into dgw_reg_dok(no_reg,no_dok,ket,kode_lokasi,tgl_terima) values (?, ?, ?, ?, ?) ",array($no_reg,$dok[$i]['no_dokumen'],$dok[$i]['deskripsi'],$kode_lokasi,NULL));
                    
                }						
            }
            $btambah = $request->biaya_tambahan;
            if (count($btambah) > 0){
                for ($i=0;$i <count($btambah);$i++){
                  
                    $ins4[$i] = DB::connection($this->sql)->insert("insert into dgw_reg_biaya(no_reg,kode_biaya,tarif,jml,nilai,kode_lokasi) values (?, ?, ?, ?, ?, ? ) ",array($no_reg,$btambah[$i]['kode_biaya'],$btambah[$i]['nilai'],$btambah[$i]['jumlah'],$btambah[$i]['total'],$kode_lokasi));
                
                }						
            }	
            
            $bdok = $request->biaya_dokumen;
            if (count($bdok) > 0){
                for ($i=0;$i <count($bdok);$i++){
                    
                    $ins5[$i] =  DB::connection($this->sql)->insert("insert into dgw_reg_biaya(no_reg,kode_biaya,tarif,jml,nilai,kode_lokasi) values (?, ?, ?, ?, ?, ?) ", array($no_reg,$bdok[$i]['kode_biaya'],$bdok[$i]['nilai'],$bdok[$i]['jumlah'],$bdok[$i]['total'],$kode_lokasi));
                    
                }						
            }

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Registrasi berhasil disimpan. No Reg:".$no_reg;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Registrasi gagal disimpan ".$e;
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

            $res = DB::connection($this->sql)->select( "select a.no_reg,a.kode_harga,a.harga, a.harga_room,a.no_paket, a.no_jadwal, a.tgl_input, a.no_type, b.tgl_berangkat, b.lama_hari, a.uk_pakaian, a.no_peserta, a.no_agen,a.no_jadwal,a.no_paket, a.no_marketing, a.info, a.jenis, a.no_peserta_ref, a.kode_pp, a.diskon,c.kode_curr,a.no_quota,a.flag_group,a.brkt_dgn,a.hubungan,a.referal,a.ket_diskon
            from dgw_reg a 
            left join dgw_jadwal b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi and a.no_jadwal=b.no_jadwal 
            left join dgw_typeroom c on a.no_type=c.no_type and a.kode_lokasi=c.kode_lokasi 
            where a.no_reg='$request->no_reg'  and a.kode_lokasi='$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select( "select b.kode_biaya,isnull(a.tarif,0) as tarif,isnull(a.jml,0) as jml,isnull(a.nilai,0) as nilai,b.nama 
            from  dgw_biaya b left join dgw_reg_biaya a on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi and a.no_reg = '$request->no_reg' 
            where b.jenis='TAMBAHAN' and b.kode_lokasi='$kode_lokasi' order by b.kode_biaya ");
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select( "select a.kode_biaya,a.tarif,a.jml,a.nilai,b.nama 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya 
            where b.jenis='DOKUMEN' and a.no_reg = '$request->no_reg' and a.kode_lokasi='$kode_lokasi' order by a.kode_biaya ");
            $res3 = json_decode(json_encode($res3),true);

            $res4 = DB::connection($this->sql)->select( "select a.no_dok,b.deskripsi as ket,b.jenis 
            from dgw_reg_dok a 
            inner join dgw_dok b on a.no_dok=b.no_dokumen 
            where a.no_reg = '$request->no_reg' and a.kode_lokasi='$kode_lokasi' order by a.no_dok");
            $res4 = json_decode(json_encode($res4),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['biaya_tambahan'] = $res2;
                $success['biaya_dokumen'] = $res3;
                $success['dokumen'] = $res4;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['biaya_tambahan'] = [];
                $success['biaya_dokumen'] = [];
                $success['dokumen'] = [];
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
            'no_reg' => 'required',
            'periode' => 'required',
            'paket' => 'required',
            'jadwal' => 'required|integer',
            'no_peserta' => 'required',
            'agen' => 'required',
            'type_room' => 'required',
            'harga_room' => 'required',
            'sumber' => 'required',
            'quota' => 'required',
            'harga_paket' => 'required|numeric',
            'ukuran_pakaian' => 'required|in:S,XS,L,2L,3L,4L,5L,6L,7L,8L,9L,10L',
            'marketing' => 'required',
            'jenis_promo' => 'required',
            'jenis_paket' => 'required',
            'kode_pp' => 'required',
            'diskon' => 'required',
            'flag_group' => 'required',
            'berangkat_dengan' => 'required',
            'hubungan' => 'required',
            'referal' => 'required',
            'ket_diskon' => 'required',
            'dokumen'=>'required|array',
            'dokumen.*.no_dokumen' => 'required',
            'dokumen.*.deskripsi' => 'required',
            'biaya_tambahan'=>'required|array',
            'biaya_tambahan.*.kode_biaya' => 'required',
            'biaya_tambahan.*.nilai' => 'required',
            'biaya_tambahan.*.jumlah' => 'required',
            'biaya_tambahan.*.total' => 'required',
            'biaya_dokumen'=>'required|array',
            'biaya_dokumen.*.kode_biaya' => 'required',
            'biaya_dokumen.*.nilai' => 'required',
            'biaya_dokumen.*.jumlah' => 'required',
            'biaya_dokumen.*.total' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = date('y');
            $no_reg = $request->no_reg;

            $del = DB::connection($this->sql)->table('dgw_reg')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();

            $del2 = DB::connection($this->sql)->table('dgw_reg_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_reg_biaya')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_history_jadwal')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();		
            
            if($request->paket_lama != $request->paket && $request->jadwal_lama != $request->jadwal){						
                $ins = DB::connection($this->sql)->insert("insert into dgw_history_jadwal(no_reg,no_paket,no_jadwal,no_paket_lama,no_jadwal_lama,kode_lokasi) values (?, ?, ?, ?, ?, ?) ", array($no_reg,$request->paket,$request->jadwal,$request->paket_lama,$request->jadwal_lama,$kode_lokasi));
            }	

            //NON-agen tidak ada fee
            if ($request->no_agen == "NON") {
                $noFee = "X";
            }else{
                $noFee = "-";
            }

            $ins2 = DB::connection($this->sql)->insert("insert into dgw_reg(no_reg,tgl_input,no_peserta,no_paket,no_jadwal,no_agen,no_type,harga_room,info,kode_lokasi,no_quota,harga,uk_pakaian, no_marketing,kode_harga,periode, jenis,no_fee, no_peserta_ref, kode_pp, diskon,flag_group,brkt_dgn,hubungan,referal,ket_diskon) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_reg,date('Y-m-d H:i:s'),$request->no_peserta,$request->paket,$request->jadwal,$request->agen,$request->type_room,$request->harga_room,$request->sumber,$kode_lokasi,$request->quota,$request->harga_paket,$request->ukuran_pakaian,$request->marketing,$request->jenis_promo,$request->periode,$request->jenis_paket,$noFee,$request->no_peserta,$request->kode_pp,$request->diskon,$request->flag_group,$request->berangkat_dengan,$request->hubungan,$request->referal,$request->ket_diskon));

            $dok = $request->dokumen;
            if (count($dok) > 0){
                for ($i=0;$i <count($dok);$i++){
                   
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into dgw_reg_dok(no_reg,no_dok,ket,kode_lokasi,tgl_terima) values (?, ?, ?, ?, ?) ",array($no_reg,$dok[$i]['no_dokumen'],$dok[$i]['deskripsi'],$kode_lokasi,NULL));
                    
                }						
            }
            $btambah = $request->biaya_tambahan;
            if (count($btambah) > 0){
                for ($i=0;$i <count($btambah);$i++){
                  
                    $ins4[$i] = DB::connection($this->sql)->insert("insert into dgw_reg_biaya(no_reg,kode_biaya,tarif,jml,nilai,kode_lokasi) values (?, ?, ?, ?, ?, ? ) ",array($no_reg,$btambah[$i]['kode_biaya'],$btambah[$i]['nilai'],$btambah[$i]['jumlah'],$btambah[$i]['total'],$kode_lokasi));
                
                }						
            }	
            
            $bdok = $request->biaya_dokumen;
            if (count($bdok) > 0){
                for ($i=0;$i <count($bdok);$i++){
                    
                    $ins5[$i] =  DB::connection($this->sql)->insert("insert into dgw_reg_biaya(no_reg,kode_biaya,tarif,jml,nilai,kode_lokasi) values (?, ?, ?, ?, ?, ?) ", array($no_reg,$bdok[$i]['kode_biaya'],$bdok[$i]['nilai'],$bdok[$i]['jumlah'],$bdok[$i]['total'],$kode_lokasi));
                    
                }						
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Registrasi berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Registrasi gagal diubah ".$e;
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
            'no_reg' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('dgw_reg')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();

            $del2 = DB::connection($this->sql)->table('dgw_reg_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_reg_biaya')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_history_jadwal')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_reg', $request->no_reg)
                ->delete();	

            $success['status'] = true;
            $success['message'] = "Data Registrasi berhasil dihapus ";
            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Registrasi gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getBiayaTambahan()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_biaya, nama, nilai from dgw_biaya where jenis = 'TAMBAHAN' and kode_lokasi='$kode_lokasi'");
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

    public function getBiayaDokumen()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_biaya, nama, nilai from dgw_biaya where jenis = 'DOKUMEN' and kode_lokasi='$kode_lokasi'");
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

    public function getPP()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select kode_pp, nama from pp where flag_aktif='1' and kode_lokasi = '$kode_lokasi'";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select kode_pp from karyawan_pp where nik='".$nik."' and kode_lokasi = '$kode_lokasi'";
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            $success["kodePP"]= $res2[0]['kode_pp'];

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

    public function getHarga(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required',
            'jenis_paket' => 'required',
            'jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select harga,harga_se,harga_e from dgw_harga where no_paket ='".$request->no_paket."' and kode_harga ='".$request->jenis_paket."' and kode_lokasi='$kode_lokasi'";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if ($request->jenis == "STANDAR") $harga = $res[0]['harga'];
                if ($request->jenis == "SEMI") $harga = $res[0]['harga_se'];
                if ($request->jenis == "EKSEKUTIF") $harga = $res[0]['harga_e'];	

                $success['harga']= $harga;
                $success['status'] = "SUCCESS";
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['harga'] = 0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }   
    }

    public function getQuota(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required',
            'jadwal' => 'required',
            'jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.tgl_berangkat,a.lama_hari,b.kode_curr 
            from dgw_jadwal a 
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi 
            where a.no_paket='".$request->no_paket."' and a.no_jadwal='".$request->jadwal."' and a.kode_lokasi='$kode_lokasi'";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
               
                $tgl_berangkat = $res[0]['tgl_berangkat'];
                $kode_curr = $res[0]['kode_curr'];
                $lama_hari = $res[0]['lama_hari'];
                
                if ($request->jenis == "STANDAR") $strSQL="select quota as quota1 from dgw_jadwal where no_paket= '".$request->no_paket."' and no_jadwal = '".$request->jadwal."' and kode_lokasi='".$kode_lokasi."'";				
                if ($request->jenis == "SEMI") $strSQL="select quota_se as quota1 from dgw_jadwal where no_paket= '".$request->no_paket."' and no_jadwal = '".$request->jadwal."' and kode_lokasi='".$kode_lokasi."'";				
                if ($request->jenis == "EKSEKUTIF") $strSQL="select quota_e as quota1 from dgw_jadwal where no_paket= '".$request->no_paket."' and no_jadwal = '".$request->jadwal."' and kode_lokasi='".$kode_lokasi."'";	
                if($request->jenis == "") {
                    $strSQL = "select quota+quota_se+quota_e as quota1 from dgw_jadwal where no_paket= '".$request->no_paket."' and no_jadwal = '".$request->jadwal."' and kode_lokasi='".$kode_lokasi."'";
                    $filter_jenis = "";
                }else{
                    $filter_jenis = " and jenis='".$request->jenis."' ";
                }
                
                $res2 = DB::connection($this->sql)->select($strSQL);
                $res2 = json_decode(json_encode($res2),true);
                if(count($res2) > 0){
                    $quota1 = intval($res2[0]['quota1']);
                }else{
                    $quota1 = 0;
                }
    
                $strSQL2="select COUNT(*) as jumlah from dgw_reg where no_paket= '".$request->no_paket."' and no_jadwal= '".$request->jadwal."' and kode_lokasi='".$kode_lokasi."' $filter_jenis  ";				
                $res3 = DB::connection($this->sql)->select($strSQL2);
                $res3 = json_decode(json_encode($res3),true);
                if(count($res3) > 0){
                    $jumlah = intval($res3[0]['jumlah']);
                }else{
                    $jumlah = 0;
                }

                $quota = $quota1-$jumlah;
                
                $success['tgl_berangkat']= $tgl_berangkat;
                $success['kode_curr']= $kode_curr;
                $success['lama_hari']= $lama_hari;
                $success['quota']= $quota;
                $success['status'] = "SUCCESS";
                $success['message'] = "Success!";  
                $success['strSQL'] = $strSQL;  
                $success['strSQL2'] = $strSQL2;   
            }
            else{
                $success['tgl_berangkat']= NULL;
                $success['kode_curr']= '';
                $success['lama_hari']= 0;
                $success['quota']= 0;
                $success['message'] = "Data Kosong!";
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getHargaRoom(Request $request)
    {
        $this->validate($request, [
            'kode_curr' => 'required',
            'type_room' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL="select harga  
            from dgw_typeroom 
            where kode_curr ='".$request->kode_curr."' and no_type='".$request->type_room."' and kode_lokasi='".$kode_lokasi."'";	
            
            $res = DB::connection($this->sql)->select($strSQL);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['harga_room']= $res[0]['harga'];
                $success['status'] = "SUCCESS";
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['harga_room'] = 0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }   
    }

    public function getNoMarketing(Request $request)
    {
        $this->validate($request, [
            'no_agen' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select kode_marketing from dgw_agent where no_agen= '".$request->no_agen."' and kode_lokasi = '$kode_lokasi'";	
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['marketing']= $res[0]['kode_marketing'];
                $success['status'] = "SUCCESS";
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['marketing'] = '';
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }   
    }

    public function getPreview(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_reg,b.alamat, a.no_quota, a.uk_pakaian, b.hp, a.no_peserta, b.nopass, b.norek, b.nama as peserta, b.status, a.no_paket, c.nama as namapaket, a.no_jadwal, d.tgl_berangkat, a.no_agen, e.nama_agen, a.no_type, f.nama as type, a.harga, h.nama_marketing, a.kode_lokasi,b.id_peserta,b.jk,b.tgl_lahir,b.tempat,b.th_umroh,b.th_haji,b.pekerjaan,b.kantor_mig,b.hp,b.telp,b.email,b.ec_telp,a.info,a.uk_pakaian,a.diskon,a.no_peserta_ref,isnull(a.brkt_dgn,'-') as brkt_dgn,isnull(a.hubungan,'-') as hubungan,isnull(a.referal,'-') as referal,g.nama as nama_pekerjaan,c.jenis as jenis_paket,a.harga_room
            from dgw_reg a
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi
            left join dgw_agent e on a.no_agen=e.no_agen and a.kode_lokasi=e.kode_lokasi 
            inner join dgw_typeroom f on a.no_type=f.no_type and a.kode_lokasi=f.kode_lokasi 
            left join dgw_marketing h on a.no_marketing=h.no_marketing and a.kode_lokasi=h.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_jadwal d on  a.no_paket=d.no_paket and a.no_jadwal=d.no_jadwal and a.kode_lokasi=d.kode_lokasi
            inner join dgw_pekerjaan g on b.pekerjaan=g.id_pekerjaan and b.kode_lokasi=g.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_reg='$request->no_reg' ";	
            
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
}
