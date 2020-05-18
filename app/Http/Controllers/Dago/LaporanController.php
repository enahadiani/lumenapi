<?php

namespace App\Http\Controllers\Dago;

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

    function getMkuOperasional(Request $request){
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_reg,a.kode_lokasi,a.no_peserta,a.no_paket,a.no_jadwal, convert(varchar,b.tgl_berangkat,103)  as tgl_berangkat,c.nama as nama_paket,c.jenis,e.nama as nama_room,f.nama_agen as agen,datediff(year,d.tgl_lahir,getdate()) as usia,
            d.nama,d.jk,d.tempat,convert(varchar,d.tgl_lahir,103) as tgl_lahir,d.id_peserta,d.alamat,d.hp,d.nopass,convert(varchar,d.issued,103) as issued,convert(varchar,d.ex_pass,103) as ex_pass,d.kantor_mig,d.ibu,d.ayah,
            d.pendidikan,h.nama as pekerjaan,d.status,a.brkt_dgn,convert(varchar,b.tgl_datang,103) as tgl_aktual,'-' as kakek,g.nama_marketing
            from dgw_reg a
            inner join dgw_jadwal b on a.no_paket=b.no_paket and a.no_jadwal=b.no_jadwal and a.kode_lokasi=b.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_typeroom e on a.no_type=e.no_type and a.kode_lokasi=e.kode_lokasi
            inner join dgw_agent f on a.no_agen=f.no_agen and a.kode_lokasi=f.kode_lokasi
            inner join dgw_marketing g on a.no_marketing=g.no_marketing and a.kode_lokasi=g.kode_lokasi
            inner join dgw_pekerjaan h on d.pekerjaan=h.id_pekerjaan and d.kode_lokasi=h.kode_lokasi
            $filter ";
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getMkuKeuangan(Request $request){
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
            
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_reg,a.kode_lokasi,a.no_peserta,a.no_paket,a.no_jadwal,d.nama,d.hp,
            b.tgl_berangkat,c.nama as nama_paket,c.jenis,e.nama as nama_room,f.nama_agen,datediff(year,d.tgl_lahir,getdate()) as umur,
            a.harga,isnull(g.nilai_tambahan,0) as nilai_tambahan,isnull(g.nilai_dokumen,0) as nilai_dokumen,
            isnull(h.bayar,0) as bayar,isnull(i.jumlah,0) as jumlah,
            a.harga+a.harga_room+isnull(g.nilai_tambahan,0)+isnull(g.nilai_dokumen,0) as total,
            a.harga+a.harga_room+isnull(g.nilai_tambahan,0)+isnull(g.nilai_dokumen,0)-isnull(h.bayar,0) as sisa,j.nama as nama_harga,k.nama_marketing
            from dgw_reg a
            inner join dgw_jadwal b on a.no_paket=b.no_paket and a.no_jadwal=b.no_jadwal and a.kode_lokasi=b.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_typeroom e on a.no_type=e.no_type and a.kode_lokasi=e.kode_lokasi
            inner join dgw_agent f on a.no_agen=f.no_agen and a.kode_lokasi=f.kode_lokasi
            inner join dgw_jenis_harga j on a.kode_harga=j.kode_harga and a.kode_lokasi=j.kode_lokasi
            inner join dgw_marketing k on a.no_marketing=k.no_marketing and a.kode_lokasi=k.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,
                            sum(case when b.jenis='TAMBAHAN' then a.nilai else 0 end) as nilai_tambahan,
                            sum(case when b.jenis='DOKUMEN' then a.nilai else 0 end) as nilai_dokumen
                        from dgw_reg_biaya a 
                        inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                        group by a.no_reg,a.kode_lokasi
                        )g on a.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,
                            sum(a.nilai) as bayar
                        from dgw_pembayaran_d a 
                        group by a.no_reg,a.kode_lokasi
                        )h on a.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,
                            count(a.no_reg) as jumlah
                        from dgw_pembayaran a 
                        group by a.no_reg,a.kode_lokasi
                        )i on a.no_reg=i.no_reg and a.kode_lokasi=i.kode_lokasi
            $filter 
            order by a.no_reg ";
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getPaket(Request $request){
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_paket');
            $db_col_name = array('a.no_paket');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }
            $sql="select a.no_paket,a.nama,a.kode_curr,a.jenis,a.kode_produk, a.tarif_agen from dgw_paket a $filter";
           
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDokumen(Request $request){
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_dokumen');
            $db_col_name = array('a.no_dokumen');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }
            $sql="select a.no_dokumen,a.deskripsi,a.jenis from dgw_dok a $filter";
           
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJamaah(Request $request){
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_peserta');
            $db_col_name = array('a.no_peserta');

            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }
            $sql="select a.no_peserta, a.kode_lokasi, a.id_peserta, a.nama, a.jk, a.status, a.alamat, a.kode_pos, a.telp, a.hp, a.email, a.pekerjaan, a.bank, a.cabang, a.norek, a.namarek, a.nopass, a.kantor_mig, a.sp, a.ec_telp, a.ec_hp, a.issued, a.ex_pass, a.tempat, a.tgl_lahir, a.th_haji, 
            a.th_umroh, a.ibu, a.foto, a.ayah, a.pendidikan from dgw_peserta a $filter";
           
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
