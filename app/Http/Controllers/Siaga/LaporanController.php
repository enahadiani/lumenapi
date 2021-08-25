<?php

namespace App\Http\Controllers\Siaga;

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
    public $guard = 'siaga';
    public $db = 'dbsiaga';

    public function getPosisi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti','periode','kode_pp');
            $db_col_name = array('a.no_pb','a.periode','a.kode_pp');

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

            $sql="select a.no_pb,a.kode_lokasi,a.no_dokumen,convert(varchar(20),a.tanggal,103) as tgl_aju,a.keterangan,a.kode_pp,b.nama as nama_pp,a.nilai,case when a.progress = '1' then 'Verifikasi' 
            when a.progress='B' then 'Return Approval' 
            when a.progress='R' then 'Return Approval' 
            else isnull(x.nama_jab,'-') end as posisi
            from gr_pb_m a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_bukti,b.nama as nama_jab
                        from apv_flow a
                        inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_pb=x.no_bukti
            $where 
            order by a.no_pb  
            ";
            $res = DB::connection($this->db)->select($sql);
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getHistoryApp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti[1];

            $sql="select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,a.no_pb as no_bukti,'Pengajuan' as status,-4 as nu, '-' as urut,a.keterangan
			from gr_pb_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.keterangan
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi and a.nik=c.nik
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            ";
            $res = DB::connection($this->db)->select($sql);
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAjuForm(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti','periode','kode_pp');
            $db_col_name = array('a.no_pb','a.periode','a.kode_pp');

            $no_bukti = $request->no_bukti[1];

            $sql="select a.no_pb ,a.keterangan,a.nik_buat,b.nama as nama_buat,a.atensi as ref1,'Jakarta' as kota,tanggal,convert(varchar(20),a.tanggal,103) as tgl,
            a.nilai,a.kurs,a.nilai,a.nilai_curr,d.nama as nama_curr,a.kode_curr,a.kode_pp,c.nama as nama_pp,a.kode_lokasi,
            a.latar, a.strategis, a.bisnis, a.teknis, a.lain,a.nik_tahu,e.nama as nama_tahu,
            a.nik_sah,a.nik_ver,f.nama as nama_sah,g.nama as nama_ver,a.jenis,a.jab1,a.jab2,a.jab3,a.jab4
            from gr_pb_m a
            inner join karyawan b on a.nik_buat=b.nik
            inner join pp c on a.kode_pp=c.kode_pp
            inner join curr d on a.kode_curr=d.kode_curr
            inner join karyawan e on a.nik_tahu=e.nik
            left join karyawan f on a.nik_sah=f.nik
            left join karyawan g on a.nik_ver=g.nik
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_pb,a.nu,a.nama_brg,a.satuan,a.jumlah,a.harga,a.nu
            from gr_pb_boq a   
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti' ";					
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql="select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
			from gr_pb_m a
            inner join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_pb='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi and a.nik=c.nik
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            ";
            $res3 = DB::connection($this->db)->select($sql);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['histori'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['histori'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPosisiSPB(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_bukti','periode');
            $db_col_name = array('a.no_spb','a.periode');

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

            $sql="select a.no_spb,a.kode_lokasi,a.nama,convert(varchar(20),a.tanggal,103) as tgl_aju,a.keterangan,a.nilai,case when a.progress = 'S' then 'Pembayaran SPB' 
            when a.progress='B' then 'Return Approval' 
            when a.progress='R' then 'Return Approval' 
            else isnull(x.nama_jab,'-') end as posisi
            from gr_spb2_m a
            left join (select a.no_bukti,b.nama as nama_jab
                        from apv_flow a
                        inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.status='1'
                        )x on a.no_spb=x.no_bukti
            $where 
            order by a.no_spb  
            ";
            $res = DB::connection($this->db)->select($sql);
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getHistoryAppSPB(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti[1];

            $sql="select 'Dibuat oleh' as ket,c.kode_jab,a.nik_user as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,a.no_spb as no_bukti,'Pengajuan' as status,-4 as nu, '-' as urut,a.keterangan
			from gr_spb2_m a
            inner join apv_karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_spb='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.keterangan
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi and a.nik=c.nik
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            ";
            $res = DB::connection($this->db)->select($sql);
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
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAjuFormSPB(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti[1];

            $sql="select a.no_spb,a.kode_lokasi,a.periode,a.tanggal,a.keterangan,a.kode_lokasi,f.kota,a.nilai,a.nama,a.alamat,
            a.nik_user,b.nama as nama_user,a.nik_bdh,c.nama as nama_bdh,a.nik_ver,d.nama as nama_ver,cat_pajak,cat_bdh,
            convert(varchar(20),a.tanggal,103) as tgl,f.kota, a.rek, a.jtran, a.bank, a.norek, a.alrek,a.no_po,a.no_dok,
            convert(varchar(20),a.tgl_po,103) as tgl_po,convert(varchar(20),a.tgl_dok,103) as tgl_dok,isnull(e.pph,0) as pph,
            a.nilai+isnull(e.pph,0)-isnull(g.ppn,0) as tagihan,isnull(g.ppn,0) as ppn,a.kode_curr,h.nama as nama_curr,'-' as tahun,'-' as tgl_ba,'-' as no_ba,'-' as no_ref
            from gr_spb2_m a
            inner join lokasi f on a.kode_lokasi=f.kode_lokasi
            left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
            left join karyawan c on a.nik_bdh=c.nik and a.kode_lokasi=c.kode_lokasi
            left join karyawan d on a.nik_ver=d.nik and a.kode_lokasi=d.kode_lokasi 
            inner join curr h on a.kode_curr=h.kode_curr
            left join (select b.no_spb,a.kode_lokasi,sum(a.nilai) as pph
            from gr_beban_j a
            inner join gr_beban_m b on a.no_beban=b.no_beban and a.kode_lokasi=b.kode_lokasi
            where a.kode_akun='2103.03'
            group by b.no_spb,a.kode_lokasi
            )	e	on a.no_spb=e.no_spb and a.kode_lokasi=e.kode_lokasi
            left join (select b.no_spb,a.kode_lokasi,sum(a.nilai) as ppn
            from gr_beban_j a
            inner join gr_beban_m b on a.no_beban=b.no_beban and a.kode_lokasi=b.kode_lokasi
            where a.kode_akun='1107.07'
            group by b.no_spb,a.kode_lokasi
            )	g	on a.no_spb=g.no_spb and a.kode_lokasi=g.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_spb='$no_bukti'
            order by a.no_spb
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select 'Dibuat oleh' as ket,c.kode_jab,a.nik_user as nik, c.nama as nama_kar,b.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut
			from gr_spb2_m a
            inner join apv_karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi
			inner join apv_jab b on c.kode_jab=b.kode_jab and c.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_spb='$no_bukti'
			union all
			select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,b.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut
            from apv_flow a
            inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi and a.nik=c.nik
			inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            ";
            $res3 = DB::connection($this->db)->select($sql);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['histori'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['histori'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['histori'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
    

}
