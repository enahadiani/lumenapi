<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReturPembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql="SELECT no_bukti,tanggal,no_dokumen,keterangan,nilai1 FROM trans_m where form='BRGRETBELI' and kode_lokasi='".$kode_lokasi."'";
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
    
    public function getNew(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql="SELECT a.no_bukti,a.param2+' - '+b.nama as vendor,a.tanggal,a.nilai1
            FROM trans_m a
            left join vendor b on a.param2=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
            where a.form='BRGBELI' and a.kode_lokasi='$kode_lokasi'";
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

    public function getBarang(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            $no_bukti = $request->no_bukti;

            $sql="select a.kode_barang,b.nama,a.jumlah,a.harga,isnull(c.jum_ret,0) as jum_ret, a.jumlah- isnull(c.jum_ret,0) as saldo,d.akun_pers
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            inner join brg_barangklp d on b.kode_klp=d.kode_klp and b.kode_lokasi=d.kode_lokasi
            left join (select b.no_ref1,a.kode_barang, a.kode_lokasi, sum(a.jumlah) as jum_ret 
                       from brg_trans_d a
                       inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                       where a.form='BRGRETBELI' and a.kode_lokasi='$kode_lokasi'
                       group by b.no_ref1,a.kode_barang,a.kode_lokasi ) c on a.kode_barang=c.kode_barang and a.no_bukti=c.no_ref1 and a.kode_lokasi=c.kode_lokasi 
            where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi' and a.form='BRGBELI' and a.jumlah- isnull(c.jum_ret,0) > 0";
            
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
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            $no_bukti = $request->no_bukti;

            $sql = "select a.no_bukti,a.param2+'-'+b.nama as vendor,a.tanggal,a.nilai1-isnull(c.retur,0) as saldo,a.param3 as akun_hutang
            from trans_m a
            left join vendor b on a.param2=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
            left join ( select no_ref1,kode_lokasi,sum(nilai1) as retur 
                        from trans_m where form='BRGRETBELI' and kode_lokasi='$kode_lokasi'
                        group by no_ref1,kode_lokasi ) c on a.no_bukti=c.no_ref1 and a.kode_lokasi=c.kode_lokasi
            where a.form='BRGBELI' and a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' ";

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
   
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'periode' => 'required',
            'no_bukti' => 'required',
            'kode_pp' => 'required',
            'kode_vendor' => 'required',
            'akun_hutang' => 'required',
            'total_return' => 'required',
            'saldo' => 'required',
            'kode_barang' => 'required|array',
            'kode_akun' => 'required|array',
            'qty_beli' => 'required|array',
            'qty_return' => 'required|array',
            'harga' => 'required|array',
            'satuan' => 'required|array',
            'subtotal' => 'required|array',

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

            if($request->total_return <= $request->saldo){

                $sqlg="select top 1 a.kode_gudang from brg_gudang a where a.kode_lokasi='$kode_lokasi' ";
                $rsg=DB::connection($this->sql)->select($sqlg);
                if(count($rsg) > 0){
                    $kodeGudang=$rsg[0]->kode_gudang;
                }else{
                    $kodeGudang="-";
                }
    
                $str_format="0001";
                $periode = $request->periode;
                $kode_pp = $request->kode_pp;
                $per = substr($periode,2,2).substr($periode,4,2);
                $prefix=$kode_lokasi."-RTR".$per.".";
                $sql = "select right(max(no_bukti), ".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%'";
               
                $get = DB::connection($this->sql)->select($sql);
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
                }else{
                    $id = "-";
                }

                // $temp = explode("-",$request->kode_vendor);
                // $vendor = $temp[0];
                $sql = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                        ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$request->periode."','IV','BRGRETBELI','F','-','-','".$kode_pp."','".$request->tanggal."','-','Retur Pembelian No: ".$id."','IDR',1,".$request->total_return.",0,0,'-','-','-','".$request->no_bukti."','-','-','-','".$request->kode_vendor."','-')");

                $sql2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values
                        ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$request->periode."','-','".$request->tanggal."',0,'".$request->akun_hutang."','D',$request->total_return,$request->total_return,'Retur Pembelian No:".$request->no_bukti."','BRGBELI','BRGRETBELI','IDR',1,'$kode_pp','-','-','".$request->kode_vendor."','-','-','-','-','-')");    
                $nu=1;
                for ($i=0;$i < count($request->kode_barang);$i++){						
                    
                    $sql1 = DB::connection($this->sql)->insert("insert into brg_belibayar_d(no_bukti,kode_lokasi,no_beli,kode_vendor,periode,dc,modul,nilai,nik_user,tgl_input) 
                    values ('".$id."','".$kode_lokasi."','".$request->no_bukti."','-', '".$periode."','D','KBBELICCL',$request->total_return,'".$nik."',getdate())");
                
                    $sql2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',".$nu.",'".$request->kode_akun[$i]."','C',".$request->subtotal[$i].",".$request->subtotal[$i].",'Retur Pembelian No:".$request->no_bukti."','BRGBELI','BRGRETBELI','IDR',1,'$kode_pp','-','-','".$request->kode_vendor."','-','-','-','-','-')");
                   
                    $sql3 = DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values ('".$id."','".$kode_lokasi."','".$periode."','BRGRETBELI','BRGRETBELI',".$nu.",'$kodeGudang','".$request->kode_barang[$i]."','-',getdate(),'".$request->satuan[$i]."','C',".$request->qty_beli[$i].",".$request->qty_return[$i].",0,".$request->harga[$i].",0,0,0,0,".$request->subtotal[$i].")");
                    $nu++;	
                    
                }
                	
                $tmp="Data Retur Pembelian berhasil disimpan";
                $sts=true;
                $success["no_bukti"] =$id;
                $success["message"] =$tmp;
                $success["status"] = $sts;
                
                DB::connection($this->sql)->commit();
            }else{
                $success["message"] = "error. Total Retur tidak boleh melebihi saldo pembelian";
                $success["status"] = false;
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Retur Pembelian gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function destroy(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;

            DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->where('nik_user', $nik)
                ->delete();

            DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->where('nik_user', $nik)
                ->delete();

            DB::connection($this->sql)->table('brg_belibayar_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->where('nik_user', $nik)
                ->delete();

            DB::connection($this->sql)->table('brg_trans_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

            DB::connection($this->sql)->commit();

            $success['status'] = true;
            $success['message'] = "Retur pembelian $no_bukti berhasil";    

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $th) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Retur Pembelian gagal dihapus ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
