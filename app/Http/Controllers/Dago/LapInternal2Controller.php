<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LapInternal2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';
    
    function getMkuOperasional(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user())
            {
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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

            $sql="select a.no_reg,a.kode_lokasi,a.no_peserta,a.no_paket,a.no_jadwal, convert(varchar,b.tgl_berangkat,103)  as tgl_berangkat,c.nama as nama_paket,c.jenis,e.nama as nama_room,f.nama_agen as agen,datediff(year,d.tgl_lahir,getdate()) as usia,
            d.nama,d.jk,d.tempat,convert(varchar,d.tgl_lahir,103) as tgl_lahir,''''+d.id_peserta as id_peserta,d.alamat,d.hp,d.nopass,convert(varchar,d.issued,103) as issued,convert(varchar,d.ex_pass,103) as ex_pass,d.kantor_mig,d.ibu,d.ayah,
            d.pendidikan,h.nama as pekerjaan,d.status,a.brkt_dgn,convert(varchar,b.tgl_datang,103) as tgl_aktual,'-' as kakek,g.nama_marketing,
            isnull(i.no_gambar,'-') as dok_ktp,isnull(j.no_gambar,'-') as dok_ak,isnull(k.no_gambar,'-') as dok_ps,
			isnull(l.no_gambar,'-') as dok_kk,isnull(m.no_gambar,'-') as dok_sn,isnull(n.no_gambar,'-') as dok_pf
            from dgw_reg a
            inner join dgw_jadwal b on a.no_paket=b.no_paket and a.no_jadwal=b.no_jadwal and a.kode_lokasi=b.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_typeroom e on a.no_type=e.no_type and a.kode_lokasi=e.kode_lokasi
            inner join dgw_agent f on a.no_agen=f.no_agen and a.kode_lokasi=f.kode_lokasi
            inner join dgw_marketing g on a.no_marketing=g.no_marketing and a.kode_lokasi=g.kode_lokasi
            inner join dgw_pekerjaan h on d.pekerjaan=h.id_pekerjaan and d.kode_lokasi=h.kode_lokasi
            left join dgw_scan i on a.no_reg=i.no_bukti and a.kode_lokasi=i.kode_lokasi and i.modul='KTP'
            left join dgw_scan j on a.no_reg=j.no_bukti and a.kode_lokasi=j.kode_lokasi and j.modul='AK'
            left join dgw_scan k on a.no_reg=k.no_bukti and a.kode_lokasi=k.kode_lokasi and k.modul='PS'
            left join dgw_scan l on a.no_reg=l.no_bukti and a.kode_lokasi=l.kode_lokasi and k.modul='KK'
            left join dgw_scan m on a.no_reg=m.no_bukti and a.kode_lokasi=m.kode_lokasi and m.modul='SN'
            left join dgw_scan n on a.no_reg=n.no_bukti and a.kode_lokasi=n.kode_lokasi and n.modul='PF'
            $where ";
            $res = DB::connection($this->sql)->select($sql);
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

    function getDaftarJamaah(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user())
            {
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('jk','status');
            $db_col_name = array('a.jk','a.status');
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

            if($request->input('usia')[0] == "all"){
                $filter_usia = "";
            }else{
                $filter_usia = " and datediff(yy,a.tgl_lahir,getdate()) ".$request->input('usia')[1];
            }
            $sql="select a.no_peserta,a.id_peserta,a.nama,a.tgl_lahir,datediff(yy,a.tgl_lahir,getdate()) as usia,a.jk,status,a.hp, a.alamat 
            from dgw_peserta a
            $where $filter_usia ";
            $res = DB::connection($this->sql)->select($sql);
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

    function getDetailJamaah(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user())
            {
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_peserta');
            $db_col_name = array('a.no_peserta');
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

            $sql="select a.no_peserta,a.id_peserta,a.nama,a.jk,a.status,a.alamat,a.kode_pos,a.telp,a.hp,a.email,a.pekerjaan,a.bank,a.cabang,a.norek,a.namarek,a.nopass,a.ec_telp,a.ec_hp,a.tempat,a.tgl_lahir,a.th_haji,a.th_umroh,a.ibu,a.foto,a.ayah,a.pendidikan,a.kantor_mig
            from dgw_peserta a
            $where ";
            $res = DB::connection($this->sql)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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
            $where 
            order by a.no_reg ";
            $res = DB::connection($this->sql)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_paket');
            $db_col_name = array('a.no_paket');
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
            $sql="select a.no_paket,a.nama,a.kode_curr,a.jenis,a.kode_produk, a.tarif_agen from dgw_paket a $where";
           
            $res = DB::connection($this->sql)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_dokumen');
            $db_col_name = array('a.no_dokumen');
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
            $sql="select a.no_dokumen,a.deskripsi,a.jenis from dgw_dok a $where";
           
            $res = DB::connection($this->sql)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_peserta');
            $db_col_name = array('a.no_peserta');
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
            $sql="select a.no_peserta, a.kode_lokasi, a.id_peserta, a.nama, a.jk, a.status, a.alamat, a.kode_pos, a.telp, a.hp, a.email, a.pekerjaan, a.bank, a.cabang, a.norek, a.namarek, a.nopass, a.kantor_mig, a.sp, a.ec_telp, a.ec_hp, a.issued, a.ex_pass, a.tempat, a.tgl_lahir, a.th_haji, 
            a.th_umroh, a.ibu, a.foto, a.ayah, a.pendidikan from dgw_peserta a $where";
           
            $res = DB::connection($this->sql)->select($sql);
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

    function getFormRegistrasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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

            $url = url('api/dago/storage');
            $sql="select a.no_reg,b.alamat, a.no_quota, a.uk_pakaian, b.hp, a.no_peserta, b.nopass, b.norek, b.nama as peserta, b.status, a.no_paket, c.nama as namapaket, a.no_jadwal, d.tgl_berangkat, a.no_agen, e.nama_agen, a.no_type, f.nama as type, a.harga, h.nama_marketing, a.kode_lokasi,b.id_peserta,b.jk,b.tgl_lahir,b.tempat,b.th_umroh,b.th_haji,b.pekerjaan,b.kantor_mig,b.hp,b.telp,b.email,b.ec_telp,a.info,a.uk_pakaian,a.diskon,a.no_peserta_ref,isnull(a.brkt_dgn,'-') as brkt_dgn,isnull(a.hubungan,'-') as hubungan,isnull(a.referal,'-') as referal,g.nama as nama_pekerjaan,c.jenis as jenis_paket,a.harga_room,case when b.foto != '-' then '".$url."/'+b.foto else '-' end as foto,convert(varchar,a.tgl_input,103) as tgl_input
            from dgw_reg a
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi
            left join dgw_agent e on a.no_agen=e.no_agen and a.kode_lokasi=e.kode_lokasi 
            inner join dgw_typeroom f on a.no_type=f.no_type and a.kode_lokasi=f.kode_lokasi 
            left join dgw_marketing h on a.no_marketing=h.no_marketing and a.kode_lokasi=h.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi 
            inner join dgw_jadwal d on  a.no_paket=d.no_paket and a.no_jadwal=d.no_jadwal and a.kode_lokasi=d.kode_lokasi
            left join dgw_pekerjaan g on b.pekerjaan=g.id_pekerjaan and b.kode_lokasi=g.kode_lokasi
            $where  
            order by a.periode desc,a.no_reg desc";
            $res = DB::connection($this->sql)->select($sql);
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

    function getRegistrasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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

            $sql="select a.no_reg,a.no_peserta,b.nama,a.tgl_input,a.no_paket,c.tgl_berangkat,a.flag_group,d.nama as namapaket 
            from dgw_reg a
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi
            inner join dgw_paket d on a.no_paket=d.no_paket and a.kode_lokasi=d.kode_lokasi
            $where  ";
            $res = DB::connection($this->sql)->select($sql);
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

    function getPembayaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_kb','no_reg');
            $db_col_name = array('a.periode','a.no_kb','a.no_reg');
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

            $sql="select a.no_kwitansi, a.kurs,a.paket,b.no_type,c.nama as room,b.harga+b.harga_room as harga_paket,b.diskon,b.ket_diskon,a.jadwal,h.nama_marketing,e.nama_agen,isnull(b.referal,'-') as referal,a.no_reg,i.biaya_tambah,j.paket+j.tambahan+j.dokumen as bayar_lain,n.cicil_ke as cicil_ke, (a.kurs*(b.harga+b.harga_room-b.diskon)) as biaya_paket,(b.harga+b.harga_room-b.diskon)-(j.paket)+a.nilai_p as saldo, a.nilai_p as bayar,(b.harga+b.harga_room-b.diskon)-(j.paket) as sisa,CONVERT(varchar, a.tgl_bayar, 105) as tgl_bayar,k.nama as peserta,l.kode_curr,m.nik_user,o.kode_akun,p.nama as nama_akun,a.sistem_bayar,k.telp,a.no_kb, i.biaya_tambah - (j.tambahan)+ (a.nilai_t+nilai_m) as saldo_t, a.nilai_t+nilai_m as bayar_t, i.biaya_tambah - (j.tambahan) as sisa_t
            from dgw_pembayaran a
            inner join dgw_reg b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            inner join dgw_typeroom c on b.no_type=c.no_type and b.kode_lokasi=c.kode_lokasi
            inner join dgw_agent e on b.no_agen=e.no_agen and b.kode_lokasi=e.kode_lokasi 
            inner join dgw_marketing h on b.no_marketing=h.no_marketing and b.kode_lokasi=h.kode_lokasi
            inner join dgw_peserta k on b.no_peserta=k.no_peserta and b.kode_lokasi=k.kode_lokasi 
            inner join dgw_paket l on b.no_paket=l.no_paket and b.kode_lokasi=l.kode_lokasi 
            inner join trans_m m on a.no_kb=m.no_bukti and a.kode_lokasi=m.kode_lokasi
            inner join trans_j o on m.no_bukti=o.no_bukti and m.kode_lokasi=o.kode_lokasi and o.nu=0
			inner join masakun p on o.kode_akun=p.kode_akun and o.kode_lokasi=p.kode_lokasi
            left join ( select no_reg,kode_lokasi,sum(nilai) as biaya_tambah 
                        from dgw_reg_biaya 
                        group by no_reg,kode_lokasi ) i on b.no_reg=i.no_reg and b.kode_lokasi=i.kode_lokasi
            left join (select no_reg,kode_lokasi,isnull(sum(nilai_p),0) as paket, 
                        isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) j on b.no_reg=j.no_reg and b.kode_lokasi=j.kode_lokasi
			left join (select no_reg,kode_lokasi,count(no_kwitansi) as cicil_ke
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) n on b.no_reg=n.no_reg and b.kode_lokasi=n.kode_lokasi
            $where  and a.flag_ver='1' 
            order by a.no_kb desc";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['sql'] = $sql;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getTandaTerima(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_kwitansi','no_reg');
            $db_col_name = array('a.periode','a.no_kwitansi','a.no_reg');
                        
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

            $sql="select a.no_kwitansi, a.kurs,a.paket,b.no_type,c.nama as room,b.harga+b.harga_room as harga_paket,b.diskon,b.ket_diskon,a.jadwal,h.nama_marketing,e.nama_agen,isnull(b.referal,'-') as referal,a.no_reg,i.biaya_tambah,j.paket+j.tambahan+j.dokumen as bayar_lain,n.cicil_ke as cicil_ke, (a.kurs*(b.harga+b.harga_room-b.diskon)) as biaya_paket,(b.harga+b.harga_room-b.diskon)-(j.paket)+a.nilai_p as saldo, a.nilai_p as bayar,(b.harga+b.harga_room-b.diskon)-(j.paket) as sisa,CONVERT(varchar, a.tgl_bayar, 105) as tgl_bayar,k.nama as peserta,l.kode_curr,m.nik_user,o.kode_akun,p.nama as nama_akun,a.sistem_bayar,k.telp,a.no_kb, i.biaya_tambah - (j.tambahan)+ (a.nilai_t+nilai_m) as saldo_t, a.nilai_t+nilai_m as bayar_t, i.biaya_tambah - (j.tambahan) as sisa_t
            from dgw_pembayaran a
            inner join dgw_reg b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            inner join dgw_typeroom c on b.no_type=c.no_type and b.kode_lokasi=c.kode_lokasi
            inner join dgw_agent e on b.no_agen=e.no_agen and b.kode_lokasi=e.kode_lokasi 
            inner join dgw_marketing h on b.no_marketing=h.no_marketing and b.kode_lokasi=h.kode_lokasi
            inner join dgw_peserta k on b.no_peserta=k.no_peserta and b.kode_lokasi=k.kode_lokasi 
            inner join dgw_paket l on b.no_paket=l.no_paket and b.kode_lokasi=l.kode_lokasi 
            inner join trans_m m on a.no_kb=m.no_bukti and a.kode_lokasi=m.kode_lokasi
            inner join trans_j o on m.no_bukti=o.no_bukti and m.kode_lokasi=o.kode_lokasi and o.dc='D'
			inner join masakun p on o.kode_akun=p.kode_akun and o.kode_lokasi=p.kode_lokasi
            left join ( select no_reg,kode_lokasi,sum(nilai) as biaya_tambah 
                        from dgw_reg_biaya 
                        group by no_reg,kode_lokasi ) i on b.no_reg=i.no_reg and b.kode_lokasi=i.kode_lokasi
            left join (select no_reg,kode_lokasi,isnull(sum(nilai_p),0) as paket, 
                        isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) j on b.no_reg=j.no_reg and b.kode_lokasi=j.kode_lokasi
			left join (select no_reg,kode_lokasi,count(no_kwitansi) as cicil_ke
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) n on b.no_reg=n.no_reg and b.kode_lokasi=n.kode_lokasi
            $where 
            order by a.periode desc, a.no_kwitansi desc";
            $res = DB::connection($this->sql)->select($sql);
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

    function getRekapSaldo(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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

            $sql="select a.no_reg,b.id_peserta,a.no_peserta,b.nama as nama_peserta,a.no_paket,c.nama as nama_paket,c.kode_curr,d.nama as nama_room, convert(varchar,e.tgl_berangkat,103) tgl_berangkat,a.harga+a.harga_room-a.diskon as biaya_paket,isnull(f.nilai,0) as biaya_tambahan,isnull(g.nilai,0) as biaya_dok,isnull(h.bayar_paket,0) as bayar_paket,isnull(h.bayar_tambahan,0) as bayar_tambahan,isnull(h.bayar_dok,0) as bayar_dok, (a.harga+a.harga_room-a.diskon)-isnull(h.bayar_paket,0) as saldo_paket,isnull(f.nilai,0) - isnull(h.bayar_tambahan,0) as saldo_tambahan,isnull(g.nilai,0)-isnull(h.bayar_dok,0) as saldo_dok, (a.harga+a.harga_room-a.diskon)+isnull(f.nilai,0)+isnull(g.nilai,0) as tagihan,isnull(h.bayar_paket,0) + isnull(h.bayar_tambahan,0) + isnull(h.bayar_dok,0) as bayar,((a.harga+a.harga_room-a.diskon)-isnull(h.bayar_paket,0))+ (isnull(f.nilai,0) - isnull(h.bayar_tambahan,0)) + (isnull(g.nilai,0)-isnull(h.bayar_dok,0)) as saldo
            from dgw_reg a 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi
            inner join dgw_paket c on a.no_paket=c.no_paket and a.kode_lokasi=c.kode_lokasi
            inner join dgw_typeroom d on a.no_type=d.no_type and a.kode_lokasi=d.kode_lokasi
            inner join dgw_jadwal e on a.no_jadwal=e.no_jadwal and a.kode_lokasi=e.kode_lokasi and a.no_paket=e.no_paket
            left join (select a.no_reg,a.kode_lokasi, sum(a.nilai) as nilai
                        from dgw_reg_biaya a
                        inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='TAMBAHAN'
                        group by a.no_reg,a.kode_lokasi
                        ) f on a.no_reg=f.no_reg and a.kode_lokasi=f.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi, sum(a.nilai) as nilai
                        from dgw_reg_biaya a
                        inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                        where b.jenis='DOKUMEN'
                        group by a.no_reg,a.kode_lokasi
                        ) g on a.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi, sum(a.nilai_p) as bayar_paket,sum(a.nilai_t) as bayar_tambahan,sum(a.nilai_m) as bayar_dok
                        from dgw_pembayaran a
                        group by a.no_reg,a.kode_lokasi
                        ) h on a.no_reg=h.no_reg and a.kode_lokasi=h.kode_lokasi
            $where 
            order by a.no_reg";
            $res = DB::connection($this->sql)->select($sql);
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

    function getKartuPembayaran(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('a.periode','a.no_paket','a.no_jadwal','a.no_reg','a.no_peserta');
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

            $sql="select a.no_reg,a.no_peserta,a.no_paket,a.tgl_input,d.nama,e.nama_agen,convert(varchar(20),a.tgl_input,103) as tgl,
            a.harga+a.harga_room-a.diskon as paket,isnull(b.nilai,0) as tambahan,isnull(g.nilai,0) as dokumen,f.nama as nama_paket,a.no_agen
            from dgw_reg a
            inner join dgw_peserta d on a.no_peserta=d.no_peserta and a.kode_lokasi=d.kode_lokasi
            inner join dgw_agent e on a.no_agen=e.no_agen and a.kode_lokasi=e.kode_lokasi
            inner join dgw_paket f on a.no_paket=f.no_paket and a.kode_lokasi=f.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai
                        from dgw_reg_biaya a
                        inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.jenis='TAMBAHAN'
                        group by a.no_reg,a.kode_lokasi
            )b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_reg,a.kode_lokasi,sum(a.nilai) as nilai
                        from dgw_reg_biaya a
                        inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.jenis='DOKUMEN'
                        group by a.no_reg,a.kode_lokasi
            )g on a.no_reg=g.no_reg and a.kode_lokasi=g.kode_lokasi
            $where
            order by a.no_reg desc ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $resdata = array();
                $no_reg = "";
                $i=0;
                foreach($rs as $row){

                    $resdata[]=(array)$row;
                    if($i == 0){
                        $no_reg .= "'$row->no_reg'";
                    }else{

                        $no_reg .= ","."'$row->no_reg'";
                    }
                    $i++;
                }

                $sql="select a.no_reg,a.no_kb as no_kwitansi,a.kode_lokasi,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,a.nilai_p,a.nilai_t,a.nilai_m
                from dgw_pembayaran a
                inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi
                inner join trans_m b on a.no_kb=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.no_reg in ($no_reg) and a.flag_ver =1
                order by b.tanggal ";
                $rs2 = DB::connection($this->sql)->select($sql);
                $rs2 = json_decode(json_encode($rs2),true);
                
                $success['status'] = "SUCCESS";
                $success['data'] = $resdata;
                $success['data_detail'] = $rs2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    
    function getDetailSaldo(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('c.periode','c.no_paket','c.no_jadwal','c.no_reg','c.no_peserta');
             
            $where = "";
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
            
            $no_bukti = "";
            $sql="select 'ROOM' as kode_biaya, c.harga_room as tarif, c.harga_room as nilai,isnull(d.byr,0) as byr,c.harga_room-isnull(d.byr,0) as saldo, 
            1 as jml, 'ROOM' as nama, '-' as jenis
            from dgw_reg c 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) d on c.kode_lokasi=d.kode_lokasi 
                        and c.no_reg=d.no_reg 
            where c.kode_lokasi='$kode_lokasi' $where 
            union all
            select 'PAKET' as kode_biaya, c.harga-isnull(c.diskon,0) as tarif, c.harga-isnull(c.diskon,0) as nilai,isnull(d.byr,0) as byr,c.harga-isnull(c.diskon,0)-isnull(d.byr,0) as saldo, 1 as jml, 'PAKET' as nama, '-' as jenis
            from dgw_reg c
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) d on c.kode_lokasi=d.kode_lokasi 
                        and c.no_reg=d.no_reg 
            where c.kode_lokasi='$kode_lokasi' $where 
            union all
            select a.kode_biaya, a.tarif, a.nilai, isnull(d.byr,0) as byr,a.nilai-isnull(d.byr,0) as saldo,a.jml, b.nama, b.jenis 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.kode_lokasi='$kode_lokasi' and b.jenis='TAMBAHAN' $where 
            union all
            select a.kode_biaya, a.tarif, a.nilai, isnull(d.byr,0) as byr,a.nilai-isnull(d.byr,0) as saldo,a.jml, b.nama, b.jenis 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.kode_lokasi='$kode_lokasi' and b.jenis='DOKUMEN' $where ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
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

    function getDetailBayar(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('c.periode','c.no_paket','c.no_jadwal','c.no_reg','c.no_peserta');
            $no_bukti = "";
            $where = "";
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
            $sql="select 'ROOM' as kode_biaya, c.harga_room as tarif, c.harga_room as nilai,isnull(d.byr,0) as byr,
            1 as jml, 'ROOM' as nama, '-' as jenis
            from dgw_reg c
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) d on c.kode_lokasi=d.kode_lokasi 
                        and c.no_reg=d.no_reg 
            where c.kode_lokasi='$kode_lokasi' $where 
            union all
            select 'PAKET' as kode_biaya, c.harga-isnull(c.diskon,0) as tarif, c.harga-isnull(c.diskon,0) as nilai,isnull(d.byr,0) as byr,1 as jml, 'PAKET' as nama, '-' as jenis
            from dgw_reg c
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) d on c.kode_lokasi=d.kode_lokasi 
                        and c.no_reg=d.no_reg 
            where c.kode_lokasi='$kode_lokasi' $where
            union all
            select a.kode_biaya, a.tarif, a.nilai, isnull(d.byr,0) as byr,a.jml, b.nama, b.jenis
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.kode_lokasi='$kode_lokasi' and b.jenis='TAMBAHAN' $where 
            union all
            select a.kode_biaya, a.tarif, a.nilai, isnull(d.byr,0) as byr,a.jml, b.nama, b.jenis
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.kode_lokasi='$kode_lokasi' and b.jenis='DOKUMEN' $where ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
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

    function getDetailTagihan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_paket','no_jadwal','no_reg','no_peserta');
            $db_col_name = array('c.periode','c.no_paket','c.no_jadwal','c.no_reg','c.no_peserta');
            $where = "";
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
            $sql="select 'ROOM' as kode_biaya, c.harga_room as tarif, c.harga_room as nilai,1 as jml, 'ROOM' as nama,'-' as jenis
                from dgw_reg c 
                where c.kode_lokasi='$kode_lokasi' $where 
                union all
                select 'PAKET' as kode_biaya, c.harga-isnull(c.diskon,0) as tarif, c.harga-isnull(c.diskon,0) as nilai, 1 as jml, 'PAKET' as nama, '-' as jenis
                from dgw_reg c
                where c.kode_lokasi='$kode_lokasi' $where 
                union all
                select a.kode_biaya, a.tarif, a.nilai, a.jml, b.nama,b.jenis 
                from dgw_reg_biaya a 
                inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi
                inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi='$kode_lokasi' and b.jenis='TAMBAHAN' $where 
                union all
                select a.kode_biaya, a.tarif, a.nilai, a.jml, b.nama,b.jenis 
                from dgw_reg_biaya a 
                inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
                inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi='$kode_lokasi' and b.jenis='DOKUMEN' $where ";
                
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
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

    function getJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $col_array = array('periode','no_bukti');
            $db_col_name = array('a.periode','a.no_bukti');
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

            // if($request->input('tgl_awal') !="" && $request->input('tgl_akhir') !="")
            // {
                // $filter .=" and a.tanggal between '".$request->input('tgl_awal')."' and '".$request->input('tgl_akhir')."' ";
            // }


            $sql="select a.no_bukti,a.keterangan,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,
                        a.nik1,a.nik2,b.nama as nama1,c.nama as nama2
                from trans_m a 
                left join karyawan b on a.nik1=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi
                $where and a.modul in ('KB','MI') order by a.no_bukti ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
          
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $resdata = array();
                $no_bukti = "";
                $i=0;
                foreach($rs as $row){
                    
                    $resdata[]=(array)$row;
                    if($i == 0){
                        $no_bukti .= "'$row->no_bukti'";
                    }else{
                        
                        $no_bukti .= ","."'$row->no_bukti'";
                    }
                    $i++;
                }
    
                $sql2="select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                    case when a.dc='D' then a.nilai else 0 end as debet,
                    case when a.dc='C' then a.nilai else 0 end as kredit 
                    from trans_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    $where and a.no_bukti in ($no_bukti) order by a.no_bukti,a.nu ";
                $res2 = DB::connection($this->sql)->select($sql2);
                $res2 = json_decode(json_encode($res2),true);
                
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

}
