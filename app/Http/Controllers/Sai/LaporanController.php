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
            
            $col_array = array('no_bill','kode_cust','no_kontrak','periode','no_bill');
            $db_col_name = array('a.no_bill','b.kode_cust','b.no_kontrak','a.periode','a.no_bill');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_dokumen,a.no_bill,b.kode_cust,c.nama as nama_cust,a.nik_app,d.nama,a.nilai,a.nilai_ppn,a.nilai+a.nilai_ppn as total,b.no_kontrak,e.tgl_awal as tgl_kontrak,e.keterangan as keterangan_kontrak,c.alamat as alamat_cust
            from sai_bill_m a
            inner join sai_bill_d b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and b.nu='1'
            left join sai_cust c on b.kode_cust=c.kode_cust and b.kode_lokasi=c.kode_lokasi
            left join sai_karyawan d on a.nik_app=d.nik and a.kode_lokasi=d.kode_lokasi
            left join sai_kontrak e on b.no_kontrak=e.no_kontrak and a.kode_lokasi=e.kode_lokasi
            $filter ";
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
                    $nb .= "'$row->no_bill'";
                    $kode_cust .= "'$row->kode_cust'";
                    $no_kontrak .= "'$row->no_kontrak'";
                }else{

                    $nb .= ","."'$row->no_bill'";
                    $kode_cust .= ","."'$row->kode_cust'";
                    $no_kontrak .= ","."'$row->no_kontrak'";
                }
                $i++;
            }

            $sql2="select a.nu,a.item,a.harga,a.jumlah,a.nilai,a.nilai_ppn 
            from sai_bill_d a
            where a.no_bill in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_cust in ($kode_cust) and a.no_kontrak in ($no_kontrak) ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a
            where a.kode_lokasi='$kode_lokasi' ";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_bank'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
