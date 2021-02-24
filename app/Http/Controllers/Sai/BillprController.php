<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class BillprController extends Controller
{
    
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function isUnik($isi,$kode_lokasi){        
        $auth = DB::connection($this->sql)->select("select no_dokumen from sai_bill_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select = "select a.no_bill, a.no_dokumen, a.tanggal, a.keterangan, a.nilai, a.nilai_ppn, 
            case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status
            from sai_bill_m a where a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select("select a.no_bill,a.no_dokumen,a.tanggal,a.keterangan,a.nilai,a.nilai_ppn from sai_bill_m a where a.kode_lokasi='".$kode_lokasi."'");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
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
            'tgl_bast' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'total_nilai' => 'required',
            'total_ppn' => 'required',
            'kode_cust' => 'required',
            'no_kontrak' => 'required',                                    
            'tanggal' => 'required',
            'item'=> 'required|array',
            'harga'=> 'required|array',
            'jumlah'=> 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->no_dokumen,$kode_lokasi)){
               
                $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                $per = substr($periode,2,4);

                $no_bukti = $this->generateKode("sai_bill_m", "no_bill", $kode_lokasi."-BPR".$per.".", "0001");
                $ins = DB::connection($this->sql)->insert("insert into sai_bill_m (no_bill,kode_lokasi,no_dokumen,tanggal,tgl_bast,keterangan,nilai,nilai_ppn,periode,nik_user,tgl_input,progress,modul) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->tgl_bast','$request->keterangan',$request->total_nilai,$request->total_ppn,'$periode','$nik_user',getdate(),'1','BILPROYEK') ");
    
                $item = $request->input('item');
                $harga = $request->input('harga');
                $jumlah = $request->input('jumlah');
                $nilai = $request->input('nilai');
                
                if(count($item) > 0){
                    $nu=1;
                    for($i=0; $i<count($item);$i++){
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bill_d (no_bill,kode_lokasi,nu,item,harga,jumlah,nilai,periode,no_kontrak,kode_cust) values ('$no_bukti','$kode_lokasi',$nu,'".$item[$i]."',".$harga[$i].",".$jumlah[$i].",".$nilai[$i].",'$periode','$request->no_kontrak','$request->kode_cust') ");
                        $nu++;
                    }
                }
    
                DB::connection($this->sql)->commit();
               
                $success['status'] = true;
                $success['message'] = "Data Tagihan berhasil disimpan. No Bukti:".$no_bukti;
                $success['no_bukti'] = $no_bukti;
              
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                $success['no_bukti'] = '-';
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal disimpan ".$e;
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
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql="select distinct a.no_bill,a.no_dokumen,a.tanggal,a.tgl_bast,a.keterangan,a.nilai,a.nilai_ppn,b.no_kontrak,b.kode_cust
            from sai_bill_m a 
            inner join sai_bill_d b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and a.no_bill='$no_bukti' ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select nu,item,harga,jumlah,nilai from sai_bill_d where kode_lokasi='".$kode_lokasi."' and no_bill='$no_bukti'  order by nu ";					
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;                
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];                
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'tgl_bast' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'total_nilai' => 'required',
            'total_ppn' => 'required',
            'kode_cust' => 'required',
            'no_kontrak' => 'required',
            'item'=> 'required|array',
            'harga'=> 'required|array',
            'jumlah'=> 'required|array',
            'nilai' => 'required|array'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                $no_bukti = $request->no_bukti;

                $del = DB::connection($this->sql)->table('sai_bill_m')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
                $del2 = DB::connection($this->sql)->table('sai_bill_d')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
                
                $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                $per = substr($periode,2,4);

                $ins = DB::connection($this->sql)->insert("insert into sai_bill_m (no_bill,kode_lokasi,no_dokumen,tanggal,tgl_bast,keterangan,nilai,nilai_ppn,periode,nik_user,tgl_input,progress,modul) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->tgl_bast','$request->keterangan',$request->total_nilai,$request->total_ppn,'$periode','$nik_user',getdate(),'1','BILPROYEK') ");
              
                $item = $request->input('item');
                $harga = $request->input('harga');
                $jumlah = $request->input('jumlah');
                $nilai = $request->input('nilai');
                
                if(count($item) > 0){
                    $nu=1;
                    for($i=0; $i<count($item);$i++){                        
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bill_d (no_bill,kode_lokasi,nu,item,harga,jumlah,nilai,periode,no_kontrak,kode_cust) values ('$no_bukti','$kode_lokasi',$nu,'".$item[$i]."',".$harga[$i].",".$jumlah[$i].",".$nilai[$i].",'$periode','$request->no_kontrak','$request->kode_cust') ");
                        $nu++;
                    }
                }

                DB::connection($this->sql)->commit();
                
                $success['status'] = true;
                $success['message'] = "Data Tagihan berhasil diubah. No Bukti:".$no_bukti;
                $success['no_bukti'] = $no_bukti;
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $del = DB::connection($this->sql)->table('sai_bill_m')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('sai_bill_d')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Tagihan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
