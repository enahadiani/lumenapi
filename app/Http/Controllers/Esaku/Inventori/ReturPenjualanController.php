<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReturPenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getPenjualan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            $sql = "select a.no_jual, a.keterangan from brg_jualpiu_dloc a where kode_lokasi='$kode_lokasi' and no_jual not like '%RJ%' ";
            
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

            $sql = "select a.no_jual,a.tanggal,a.nilai-isnull(c.retur,0) as saldo,a.akun_piutang
            from brg_jualpiu_dloc a
            left join ( select no_ref1,kode_lokasi,sum(nilai) as retur 
                        from brg_jualpiu_dloc 
						where no_jual like '%RJ%' and kode_lokasi='$kode_lokasi'
                        group by no_ref1,kode_lokasi 
						) c on a.no_jual=c.no_ref1 and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_jual='$no_bukti' ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select a.kode_barang,b.nama,a.jumlah,a.harga,isnull(c.jum_ret,0) as jum_ret, a.jumlah- isnull(c.jum_ret,0) as saldo,d.akun_pers
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            inner join brg_barangklp d on b.kode_klp=d.kode_klp and b.kode_lokasi=d.kode_lokasi
            left join (select b.no_ref1,a.kode_barang, a.kode_lokasi, sum(a.jumlah) as jum_ret 
                       from brg_trans_d a
                       inner join brg_jualpiu_dloc b on a.no_bukti=b.no_jual and a.kode_lokasi=b.kode_lokasi
                       where a.form='BRGRETJ' and a.kode_lokasi='$kode_lokasi'
                       group by b.no_ref1,a.kode_barang,a.kode_lokasi ) c on a.kode_barang=c.kode_barang and a.no_bukti=c.no_ref1 and a.kode_lokasi=c.kode_lokasi 
            where a.no_bukti='$request->no_bukti' and a.kode_lokasi='$kode_lokasi' and a.form='BRGJUAL' and a.jumlah- isnull(c.jum_ret,0) > 0";
            
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
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
            $success['data'] = [];
            $success['detail'] = [];
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
            'akun_piutang' => 'required',
            'total_return' => 'required',
            'saldo' => 'required',
            'kode_barang' => 'required|array',
            'kode_akun' => 'required|array',
            'qty_jual' => 'required|array',
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
                $prefix=$kode_lokasi."-RJ".$per.".";
                $sql = "select right(max(no_jual), ".strlen($str_format).")+1 as id from brg_jualpiu_dloc where no_jual like '$prefix%'";
               
                $get = DB::connection($this->sql)->select($sql);
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
                }else{
                    $id = "-";
                }

                $sql = "select no_open from brg_jualpiu_dloc where no_jual ='$request->no_bukti' ";
               
                $get2 = DB::connection($this->sql)->select($sql);
                if(count($get2) > 0){
                    $no_open = $request->no_open;
                }else{
                    $no_open = "-";
                }

                $ins =DB::connection($this->sql)->insert("insert into brg_jualpiu_dloc(no_jual,kode_lokasi,tanggal,keterangan,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp,diskon,kode_gudang,no_ba,tobyr,no_open,no_close,no_ref1) values (?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($id,$kode_lokasi,"Return Penjualan No: $id",'CASH','IDR',1,$request->kode_pp,$request->total_return,$periode,$nik,$request->akun_piutang,0,0,'-',0,$kodeGudang,'-',$request->total_return,$no_open,'-',$request->no_bukti));		

                $nu=1;
                for ($i=0;$i < count($request->kode_barang);$i++){						
                    if(floatval($request->qty_return[$i]) > 0){
                        $insert5 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) 
                        values (?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $sql3 = DB::connection($this->sql)->insert($insert5, [
                            $id,$kode_lokasi,$periode,'BRGRETJ','BRGRETJ',$nu,$kodeGudang,$request->kode_barang[$i],'-',$request->satuan[$i],'D',$request->qty_jual[$i],$request->qty_return[$i],0,$request->harga[$i],0,0,0,0,$request->subtotal[$i]
                        ]);
                        $nu++;	
                    }
                    
                }

                $ins3 = DB::connection($this->sql)->insert("
                update a set a.hpp=b.hpp, a.no_belicurr=b.no_belicurr 
                from brg_trans_d a 
                inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
                inner join brg_trans_d b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and c.no_ref=b.no_bukti
                where a.no_bukti=? and a.kode_lokasi=?  and c.no_ref1= ?",array($id,$kode_lokasi,$request->no_bukti));
                	
                $exec = DB::connection($this->sql)->update("exec sp_brg_hpp ?,?,?,? ", array($id,$periode,$kode_lokasi,$nik));
                
                $success["no_bukti"] =$id;
                $success["message"] = "Data Retur Penjualan berhasil disimpan";
                $success["status"] = true;
                
                DB::connection($this->sql)->commit();
            }else{
                $success["message"] = "error. Total Retur tidak boleh melebihi saldo Penjualan ";
                $success["status"] = false;
                DB::connection($this->sql)->rollback();
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Retur Penjualan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

}
