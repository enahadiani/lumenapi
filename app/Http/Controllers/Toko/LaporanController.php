<?php

namespace App\Http\Controllers\Toko;

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
    public $guard = 'toko';
    public $sql = 'tokoaws';

    function getReportBarang(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti');
            $db_col_name = array('a.no_bukti');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.kode_barang,a.nama,a.sat_kecil as satuan,a.hna as harga,a.hrg_satuan,a.ppn,a.profit,a.barcode,a.kode_klp
            from brg_barang a $filter ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
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

    function getReportClosing(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','nik_kasir','no_bukti');
            $db_col_name = array("substring(convert(varchar(10),tgl_input,121),1,4)+''+substring(convert(varchar(10),tgl_input,121),6,2)",'a.nik_user','a.no_close');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_close as no_bukti,a.tgl_input as tanggal,a.saldo_awal,a.total_pnj,a.nik_user from kasir_close a $filter ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_bukti'";
                }else{

                    $nb .= ","."'$row->no_bukti'";
                }
                $i++;
            }

            $sql2="select no_jual,tanggal,keterangan,periode,nilai,diskon,no_close as no_bukti from brg_jualpiu_dloc
            where kode_lokasi = '".$kode_lokasi."' and no_close in ($nb) " ;
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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

    function getReportPenjualan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','nik_kasir','no_bukti');
            $db_col_name = array('a.periode','a.nik_user','a.no_jual');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_jual,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai,b.nilai as nilai2,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir,a.tobyr,a.diskon
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_dloc 
                        where kode_lokasi='".$kode_lokasi."' and form='BRGJUAL'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $filter
            order by a.no_jual";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_jual'";
                }else{

                    $nb .= ","."'$row->no_jual'";
                }
                $i++;
            }

            $sql2="select distinct a.no_bukti,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,a.jumlah,a.bonus,a.harga,a.diskon,(a.harga)*a.jumlah-a.diskon as total
            from brg_trans_dloc a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi = '".$kode_lokasi."'  and a.no_bukti in ($nb) 
            order by a.no_bukti
            " ;
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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

    function getReportPembelian(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','nik_kasir','no_bukti');
            $db_col_name = array('a.periode','a.nik_user','a.no_bukti');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select DISTINCT a.no_bukti,a.tanggal,e.keterangan,e.param2 as kode_vendor,b.nama as nama_vendor ,d.kode_gudang,c.nama as nama_pp
            ,a.nik_user,f.nama as nama_user,a.kode_lokasi,e.nilai1 as total,e.nilai3 as diskon,e.nilai2 as ppn
            from trans_j a
            left join brg_trans_d d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            inner join brg_gudang c on d.kode_gudang=c.kode_gudang and d.kode_lokasi=c.kode_lokasi
            inner join trans_m e on e.no_bukti=a.no_bukti and e.kode_lokasi=a.kode_lokasi
            left join vendor b on e.param2=b.kode_vendor and e.kode_lokasi=b.kode_lokasi
            inner join karyawan f on a.nik_user=f.nik and a.kode_lokasi=f.kode_lokasi
            $filter and e.form='BRGBELI' 
            order by a.no_bukti";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_bukti'";
                }else{

                    $nb .= ","."'$row->no_bukti'";
                }
                $i++;
            }

            $sql2="select a.no_bukti,a.kode_barang,b.nama as nama_brg,a.satuan,a.jumlah,a.bonus,a.tot_diskon,a.harga,a.total
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti in ($nb) 
            order by a.kode_barang
            " ;
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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

    function getReportPenjualanHarian(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','nik_kasir','tanggal');
            $db_col_name = array('a.periode','a.nik_user','a.tanggal');
            
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir,sum(a.nilai) as nilai
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_dloc 
                        where kode_lokasi='$kode_lokasi' and form='BRGJUAL'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $filter
            group by a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama,d.nama,e.nama,a.nik_user";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $tgl = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $tgl .= "'$row->tanggal'";
                }else{

                    $tgl .= ","."'$row->tanggal'";
                }
                $i++;
            }

            $sql2="select c.tanggal,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,sum(a.jumlah) as jumlah,sum(a.bonus) as bonus,a.harga,sum(a.diskon) as diskon,sum((a.harga*a.jumlah)-a.diskon) as total
            from brg_trans_dloc a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
			inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and c.tanggal in ($tgl)
			group by c.tanggal,a.kode_barang,b.nama,b.sat_kecil,a.harga
            order by c.tanggal
            " ;
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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

    function getReportReturBeli(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','nik_kasir','no_bukti');
            $db_col_name = array('a.periode','a.nik_user','a.no_bukti');
            
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai1,b.nilai as nilai2,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir
            from trans_m a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='$kode_lokasi' and form='BRGRETBELI'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $filter and a.form='BRGRETBELI'
            order by a.no_bukti";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_bukti'";
                }else{

                    $nb .= ","."'$row->no_bukti'";
                }
                $i++;
            }

            $sql2="select distinct a.no_bukti,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,a.jumlah,a.bonus,a.harga,a.diskon,(a.harga)*a.jumlah-a.diskon as total,a.stok
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'  and a.form='BRGRETBELI' and a.no_bukti in ($nb)
            order by a.no_bukti
            " ;
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
