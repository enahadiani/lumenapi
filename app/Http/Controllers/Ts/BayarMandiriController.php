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
            'bill_code' => 'required',
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
            
            $per = date('ymd');
            $cek = DB::connection($this->db)->select("select no_bukti from sis_mandiri_bill_d where kode_lokasi='$kode_lokasi' and no_bill='$request->no_bill' and kode_param='$request->kode_param' and nis='$request->nis' ");
            if(count($cek) > 0){
                $no_bukti = $cek[0]->no_bukti;
                $del = DB::connection($this->db)->delete("delete from sis_mandiri_bill where no_bukti='".$no_bukti."' ");
                $del2 = DB::connection($this->db)->delete("delete from sis_mandiri_bill_d where no_bukti='".$no_bukti."' ");
            }else{
                $no_bukti = $this->generateKode("sis_mandiri_bill", "no_bukti", $kode_lokasi."-MB".$per.".", "0001");
            }

            $ins = DB::connection($this->db)->insert("insert into sis_mandiri_bill (no_bukti,nilai,response_log,status,nik_user,tgl_input,kode_lokasi,kode_pp,no_rekon,sts_rekon,bill_code,bill_cust_id) values ('".$no_bukti."',".$request->nilai.",'".$request->log."','".$request->status."','$nik',getdate(),'$kode_lokasi','$request->kode_pp',NULL,NULL,'$request->bill_code','$request->bill_cust_id') ");
            
            $ins2 = DB::connection($this->db)->insert("insert into sis_mandiri_bill_d (no_bukti,no_bill,nis,nilai,kode_param,kode_lokasi,kode_pp,nu,periode_bill) values ('".$no_bukti."','".$request->no_bill."','".$request->nis."',".$request->nilai.",'$request->kode_param','$kode_lokasi','$request->kode_pp',1,'$request->periode_bill') ");     

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

 

}
