<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'admin';
    public $sql = 'sqlsrv2';

    function getReportTagihan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode');
            $db_col_name = array('b.kode_cust','b.periode');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select b.no_dokumen,b.kode_cust+' - '+c.nama as cust,e.keterangan as keterangan_kontrak,e.nilai as nilai_kontrak,e.nilai_ppn as nilai_ppn_kontrak
            from sai_bill_m a
            inner join sai_bill_d b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi
            left join sai_cust c on b.kode_cust=c.kode_cust and b.kode_lokasi=c.kode_lokasi
            left join sai_kontrak e on b.no_kontrak=e.no_kontrak and a.kode_lokasi=e.kode_lokasi
            $filter and b.status = '1' ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $kode_cust = "";
            $no_kontrak = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_dokumen'";
                    $kode_cust .= "'$row->cust'";
                    // $no_kontrak .= "'$row->no_kontrak'";
                }else{

                    $nb .= ","."'$row->no_dokumen'";
                    $kode_cust .= ","."'$row->cust'";
                    // $no_kontrak .= ","."'$row->no_kontrak'";
                }
                $i++;
            }

            // $sql2="select a.no_bill,a.nu,a.item,a.harga,a.jumlah,a.nilai,a.nilai_ppn 
            // from sai_bill_d a
            // where a.no_bill in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_cust in ($kode_cust) and a.no_kontrak in ($no_kontrak) ";
            // $res2 = DB::connection($this->sql)->select($sql2);
            // $res2 = json_decode(json_encode($res2),true);

            // $sql3="select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a
            // where a.kode_lokasi='$kode_lokasi' ";
            // $res3 = DB::connection($this->sql)->select($sql3);
            // $res3 = json_decode(json_encode($res3),true);

            // $sql4="select a.kode_lampiran,b.nama 
            // from sai_cust_d a
            // inner join sai_lampiran b on a.kode_lampiran=b.kode_lampiran and a.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.kode_cust in ($kode_cust) ";
            // $res4 = DB::connection($this->sql)->select($sql4);
            // $res4 = json_decode(json_encode($res4),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                // $success['data_detail'] = $res2;
                // $success['data_bank'] = $res3;
                // $success['data_lampiran'] = $res4;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                // $success['data_detail'] = [];
                // $success['data_bank'] = [];
                // $success['data_lampiran'] = [];
                // $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportKuitansi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','no_bayar','periode');
            $db_col_name = array('a.kode_cust','a.no_bayar','substring(convert(varchar,tanggal,112),1,6)');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_bayar,a.keterangan,convert(varchar,a.tanggal,103) as tanggal,a.kode_cust,b.nama as nama_cust,isnull(c.nilai,0) as nilai
            from sai_bayar_m a
            inner join sai_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_bayar,a.kode_lokasi, sum(a.nilai) as nilai
                        from sai_bayar_d a
                        group by a.no_bayar,a.kode_lokasi) c on a.no_bayar=c.no_bayar and a.kode_lokasi=c.kode_lokasi
            $filter ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $sql3="select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a
            where a.kode_lokasi='$kode_lokasi' ";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_bank'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_bank'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    

}
