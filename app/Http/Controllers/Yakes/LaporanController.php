<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yakes';
    public $sql = 'dbyakes';

    public function sendMail(Request $request){
        $this->validate($request,[
            'email' => 'required'
        ]);
  
        $email = $request->email;
        try {
            $rs = $this->getNrcLajur($request);
            $res = json_decode(json_encode($rs),true);
            $data_array = $res["original"]["success"]["data"];
            Mail::to($email)->send(new LaporanNrcLajur($data_array));
            
            return response()->json(array('status' => true, 'message' => 'Sent successfully'), $this->successStatus); 
        } catch (Exception $ex) {
            return response()->json(array('status' => false, 'message' => 'Something went wrong, please try later.'), $this->successStatus); 
        } 
    }

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
            $res2 = DB::connection($this->sql)->select($sql2);
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
            $res2 = DB::connection($this->sql)->select($sql2);
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
            $res2 = DB::connection($this->sql)->select($sql2);
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
            $res2 = DB::connection($this->sql)->select($sql2);
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
            $res2 = DB::connection($this->sql)->select($sql2);
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

    function getGlReportBukuBesar(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $get = DB::connection($this->sql)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            if($periode == ""){
                $periode = date('Ym');
            }

            $sql="exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);

            $tmp = "";
            if (isset($request->jenis) && $request->jenis == "Tidak")
            {
                $tmp =" and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }
            
            $sql="select a.kode_lokasi,a.kode_akun,a.kode_pp,a.nama,a.so_awal,a.periode,b.nama as nama_pp
            from glma_pp_tmp a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.nik_user='$nik_user' and a.kode_lokasi='$kode_lokasi'  $tmp
            order by a.kode_akun
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql2="select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $filter order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql2);
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
                $success['status'] = true;
                $success['sql'] = $sql;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportNeracaLajur(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_pp');
            $db_col_name = array('a.periode','a.kode_akun','a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $get = DB::connection($this->sql)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            if($periode == ""){
                $periode = date('Ym');
            }

            $sql="exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,a.nama,a.kode_pp,b.nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from glma_pp_tmp a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            $filter and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
            if($request->input('trail') != ""){

                if ($request->input('trail') =="1")
                {
                    $sql = "select a.kode_akun,a.nama,a.kode_lokasi,a.kode_pp,c.nama as nama_pp,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
                if ($request->input('trail')=="2")
                {
                    $sql = "select a.kode_akun,a.nama,a.kode_pp,c nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
            }
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportLabaRugi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }
            
            $nik_user=$nik."_".uniqid();
            $periode=$request->input('periode');
            $kode_fs=$request->input('kode_fs');

            $sql="exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);
            $success['sql'] = $sql;
            $sql="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                        case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
            $success['sql2'] = $sql;
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
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBuktiJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul','no_bukti');
            $db_col_name = array('a.periode','a.modul','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }


            $sql="select a.no_bukti,a.keterangan,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,
                        a.nik1,a.nik2,b.nama as nama1,c.nama as nama2
                from trans_m a 
                left join karyawan b on a.nik1=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi
                $where order by a.no_bukti ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul','no_bukti');
            $db_col_name = array('a.periode','a.modul','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
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
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBukuBesar(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun');
            $db_col_name = array('a.periode','a.kode_akun');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            $sqlex="exec sp_glma_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sqlex);

            $tmp = "";
            if (isset($request->mutasi[1]) && $request->mutasi[1] == "Tidak")
            {
                $tmp =" and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }
            
            $sql="select a.kode_lokasi,a.kode_akun,b.nama,a.so_awal,a.periode
                from glma_tmp a
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where and a.nik_user='$nik_user' $tmp
                order by a.kode_akun ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !=""){
                $where .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            }

            $sql2="select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql2);
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
                $success['status'] = true;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNrcLajur(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            // for($i = 0; $i<count($col_array); $i++){
            //     if($request->input($col_array[$i]) !=""){
            //         $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
            //     }
            // }
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];

            $sqlex="exec sp_glma_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sqlex);

            $mutasi="";
            if($request->input('jenis') != ""){

                if ($request->input('jenis')=="Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql="select a.kode_akun,b.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from glma_tmp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
            if(isset($request->trail[1])){
                if($request->input('trail')[1] != ""){
    
                    if ($request->input('trail')[1] == "1")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from glma_tmp a
                        inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where and a.nik_user='$nik_user' $mutasi
                        order by a.kode_akun";
                    }
                    if ($request->input('trail')[1] == "2")
                    {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from glma_tmp a
                        inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where and a.nik_user='$nik_user' $mutasi
                        order by a.kode_akun";
                    }
                }
            }
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNeraca(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];

            $sql= "exec sp_neraca_dw '$kode_fs','A','K','$level','$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->getPdo()->exec($sql);

            $sql2="select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->sql)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode="";
            if ($periode > $periode_aktif)
            {
                $nama_periode="<br>(UnClosing)";
            }

            $sql3 = "select '$kode_lokasi' as kode_lokasi,kode_neraca1,kode_neraca2,nama1,tipe1,nilai1,level_spasi1,nama2,tipe2,nilai2,level_spasi2 
				from neraca_skontro 
				where nik_user='$nik_user' order by rowindex ";
            $nama="";
            if ($format=="Mutasi")
            {
                $sql3 = "select '$kode_lokasi' as kode_lokasi,kode_neraca1,kode_neraca2,nama1,tipe1,nilai3 as nilai1,level_spasi1,nama2,tipe2,nilai4 as nilai2,level_spasi2 
                    from neraca_skontro 
                    where nik_user='$nik_user' order by rowindex ";
                $nama="(MUTASI)";
            }
         
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['res']=$res;
                $success['sql3'] = $sql3;
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

    function getLabaRugi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $nik_user=$request->nik_user;
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];

            $sql="exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);
            $success['sql'] = $sql;
            $sql="select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                        case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
            $success['sql2'] = $sql;
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
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    

}
