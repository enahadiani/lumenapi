<?php

namespace App\Http\Controllers\Ts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class BayarMandiriController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "ts";
    public $db = "sqlsrvyptkug";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }  

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select *
            from sis_mandiri_bill a
            where a.kode_lokasi='$kode_lokasi' and a.nik_user='$nik' $filter");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function cekBill(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select *
            from sis_mandiri_bill a
            where a.kode_lokasi='$kode_lokasi' and a.bill_cust_id='$request->bill_cust_id' and a.status = 'WAITING' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = false;
                $success['data'] = $res;
                $success['no_bukti'] = $res[0]['no_bukti'];
                $success['message'] = "Success!";     
            }
            else{
                $per = date('ymd');
                $no_bukti = $this->generateKode("sis_mandiri_bill", "no_bukti", $kode_lokasi."-MB".$per.".", "0001"); 
                $ins = DB::connection($this->db)->insert("insert into sis_mandiri_bill (no_bukti,status,nik_user,tgl_input,kode_lokasi,bill_cust_id) values ('".$no_bukti."','PROCESS','$nik',getdate(),'$kode_lokasi','$request->bill_cust_id') ");
            
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
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
            'nilai' => 'required',
            'nis' => 'required',
            'nama' => 'required',
            'nama_jurusan' => 'required',
            'kode_pp' => 'required',
            'no_bill' => 'required',
            'periode_bill' => 'required',
            'kode_param' => 'required',
            'id_bank' => 'required',
            'log' => 'required',
            'bill_cust_id' => 'required',
            'bill_short_name' => 'required',
            'status' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik = $request->nik;
                $kode_lokasi = $request->kode_lokasi;
            }

            $ins = DB::connection($this->db)->update("update sis_mandiri_bill set nilai=".$request->nilai.",kode_pp='".$request->kode_pp."',response_log='".$request->log."',status='".$request->status."'
            where no_bukti='".$request->bill_short_name."' and bill_cust_id='".$request->bill_cust_id."' and kode_lokasi='".$kode_lokasi."' and status='PROCESS' ");
            
            $ins2 = DB::connection($this->db)->insert("insert into sis_mandiri_bill_d (no_bukti,no_bill,nis,nilai,kode_param,kode_lokasi,kode_pp,nu,periode_bill) values ('".$request->bill_short_name."','".$request->no_bill."','".$request->nis."',".$request->nilai.",'$request->kode_param','$kode_lokasi','$request->kode_pp',1,'$request->periode_bill') ");     

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bill Mandiri berhasil disimpan";

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bill Mandiri gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    public function update(Request $request)
    {
        $this->validate($request, [
            'bill_cust_id' => 'required',
            'bill_short_name' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik = $request->nik;
                $kode_lokasi = $request->kode_lokasi;
            }
            
            
            $ins = DB::connection($this->db)->update("update sis_mandiri_bill set status ='CANCEL' where no_bukti='$request->bill_short_name' and bill_cust_id='$request->bill_cust_id' ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bill Mandiri berhasil dicancel";

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bill Mandiri gagal dicancel ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function updateStatus(Request $request)
    {
        $this->validate($request, [
            'bill_cust_id' => 'required',
            'bill_short_name' => 'required',
            'bill_status' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            
            $ins = DB::connection($this->db)->update("update sis_mandiri_bill set status ='$request->bill_status' where no_bukti='$request->bill_short_name' and bill_cust_id='$request->bill_cust_id' and status='WAITING' ");
            $kode_lokasi = substr($request->bill_short_name,0,2);
            
            $per = date('ymd');
            $no_bukti = $this->generateKode("sis_mandiri_bayar", "no_bukti", $kode_lokasi."-BYM".$per.".", "0001");
            
            $ins2 = DB::connection($this->db)->insert("
            insert into sis_mandiri_bayar (no_bukti,nilai,kode_lokasi,kode_pp,bill_short_name,bill_cust_id,tgl_input) select '$no_bukti',nilai,kode_lokasi,kode_pp,no_bukti,bill_cust_id,getdate() 
            from sis_mandiri_bill 
            where no_bukti='$request->bill_short_name' and bill_cust_id='$request->bill_cust_id' and status='SUCCESS'
            ");

            $ins3 = DB::connection($this->db)->insert("
            insert into sis_mandiri_bayar_d (no_bukti,no_bill,nis,kode_param,kode_pp,nu,periode_bill) 
            select '$no_bukti',a.no_bill,a.nis,a.kode_param,a.kode_pp,a.nu,a.periode_bill
            from sis_mandiri_bill_d a
            inner join sis_mandiri_bill b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.no_bukti='$request->bill_short_name' and b.bill_cust_id='$request->bill_cust_id' and b.status='SUCCESS'
            ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bill Mandiri berhasil diubah";

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bill Mandiri gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

 

}
