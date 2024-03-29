<?php

namespace App\Http\Controllers\Ts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DashSiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "ts";
    public $db = "sqlsrvyptkug";

    // public function getKartuPiutang(Request $request)
    // {
    //     try {
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //             $kode_pp= $data->kode_pp;
    //         }

    //         if(isset($request->periode) && $request->periode != ""){
    //             $periode_filter = " where a.periode='$request->periode' ";
    //         }else{
    //             $periode_filter = "";
    //         }
    //         $res = DB::connection($this->db)->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
    //         from sis_siswa a
    //         inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
    //         inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
    //         where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
    //         order by a.nis ");
    //         $res = json_decode(json_encode($res),true);

    //         $res2 = DB::connection($this->db)->select("select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,
	// 		b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
	// 		 from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,0 as bayar from sis_bill_d x 
	// 		inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
	// 		 where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
	// 		group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
	// 		inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
	// 		union all select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
	// 		convert(varchar(10),b.tanggal,103) as tgl,b.keterangan,'PDD' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
	// 		 from (select x.kode_lokasi,x.no_rekon,x.kode_param,
	// 		case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
	// 		 from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
	// 		where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
	// 		 )a 
	// 		inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
	// 		union all 
	// 		select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
	// 		convert(varchar(10),b.tanggal,103) as tgl,b.keterangan,'KB' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param 
	// 		from (select x.kode_lokasi,x.no_rekon,x.kode_param,
	// 		case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
	// 		 from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
	// 		where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
	// 		)a
	// 		 inner join kas_m b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
	// 		order by tanggal,modul,kode_param  ");
    //         $res2 = json_decode(json_encode($res2),true);
            
    //         if(count($res) > 0){ //mengecek apakah data kosong atau tidak
    //             $success['status'] = true;
    //             $success['data'] = $res;
    //             $success['detail'] = $res2;
    //             $success['message'] = "Success!";     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!";
    //             $success['data'] = [];
    //             $success['detail'] = [];
    //             $success['status'] = true;
    //         }
    //         return response()->json($success, $this->successStatus);
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
        
    // }

    public function getPP(Request $request)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                if($request->kode_pp != "" ){

                    $filter = " and a.kode_pp='$request->kode_pp' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            if(isset($request->nis)){
                if($request->nis != "" ){

                    $filter .= " and b.nik='$request->nis' ";
                }else{
                    $filter .= "";
                }
            }else{
                $filter .= "";
            }


            $res = DB::connection($this->db)->select("SELECT distinct a.kode_pp,a.nama from sis_sekolah a left join sis_hakakses b on a.kode_pp = b.kode_pp where a.kode_lokasi = '12' $filter order by a.kode_pp	 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKartuPiutang(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            if(isset($request->periode) && $request->periode != ""){
                $periode_filter = " where a.periode='$request->periode' ";
            }else{
                $periode_filter = "";
            }

            $rs = DB::connection($this->db)->select("select a.* from (select a.no_bill,a.tgl_input,a.keterangan,modul,a.jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
            from sis_bill_m a 
            inner join (select a.no_bill,a.kode_pp,a.kode_lokasi,sum(a.nilai) as nilai 
                        from sis_bill_d a
                        where a.nis ='$nik'
                        group by a.no_bill,a.kode_pp,a.kode_lokasi 
                        )b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            union all
            select a.no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
            from sis_rekon_m a 
            inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,sum(a.nilai) as nilai 
                        from sis_rekon_d a
                        where a.nis ='$nik'
                        group by a.no_rekon,a.kode_pp,a.kode_lokasi 
                        )b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            union all
            select a.no_kas as no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
            from kas_m a 
            inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,sum(a.nilai) as nilai 
                        from sis_rekon_d a
                        where a.nis ='$nik'
                        group by a.no_rekon,a.kode_pp,a.kode_lokasi 
                        )b on a.no_kas=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            ) a
            order by tgl_input");
            $res = json_decode(json_encode($rs),true);

            $rslok = DB::connection($this->db)->select("select a.kode_pp,a.nama,a.alamat,a.alamat2 from sis_sekolah a where a.kode_pp = '$kode_pp' and a.kode_lokasi='$kode_lokasi' ");
            $reslok = json_decode(json_encode($rslok),true);
            $success['lokasi'] = $reslok;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){
                    $res[$i]['detail'] = json_decode(json_encode(DB::connection($this->db)->select("select a.* from (select a.no_bill, isnull(b.nilai,0) as nilai,b.kode_param,a.tgl_input
                    from sis_bill_m a 
                    inner join (select a.no_bill,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                                from sis_bill_d a
                                where a.nis ='$nik'
                                )b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                    union all
                    select a.no_rekon, isnull(b.nilai,0) as nilai,b.kode_param,a.tgl_input
                    from sis_rekon_m a 
                    inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                                from sis_rekon_d a
                                where a.nis ='$nik' 
                                )b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                    union all
                    select a.no_kas as no_rekon, isnull(b.nilai,0) as nilai,b.kode_param,a.tgl_input
                    from kas_m a 
                    inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                                from sis_rekon_d a
                                where a.nis ='$nik'
                                )b on a.no_kas=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                    ) a
                    where a.no_bill = '".$res[$i]['no_bill']."'
                    order by tgl_input")),true);
                }
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKartuPiutangDetail(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            if(isset($request->id) && $request->id != ""){
                $id_filter = " where a.no_bill='$request->id' ";
            }else{
                $id_filter = "";
            }

            $res2 = DB::connection($this->db)->select("select a.* from (select a.no_bill,a.tgl_input,a.keterangan,modul,a.jenis, isnull(b.nilai,0) as nilai,b.kode_param
            from sis_bill_m a 
            inner join (select a.no_bill,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                        from sis_bill_d a
                        where a.nis ='$nik'
                        )b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            union all
            select a.no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai,b.kode_param
            from sis_rekon_m a 
            inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                        from sis_rekon_d a
                        where a.nis ='$nik' 
                        )b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            union all
            select a.no_kas as no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai,b.kode_param
            from kas_m a 
            inner join (select a.no_rekon,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param
                        from sis_rekon_d a
                        where a.nis ='$nik'
                        )b on a.no_kas=b.no_rekon and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            ) a
            $id_filter
            order by tgl_input");
            $res = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    // public function getKartuPDD(Request $request)
    // {
    //     try {
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //             $kode_pp= $data->kode_pp;
    //         }

    //         if(isset($request->periode) && $request->periode != ""){
    //             $periode_filter = " and a.periode='$request->periode' ";
    //         }else{
    //             $periode_filter = "";
    //         }
    //         $res = DB::connection($this->db)->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
    //         from sis_siswa a
    //         inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
    //         inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
    //         where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
    //         order by a.nis ");
    //         $res = json_decode(json_encode($res),true);

    //         $res2 = DB::connection($this->db)->select("select a.kode_pp,a.kode_lokasi,a.no_bukti,a.tgl,a.keterangan,a.modul,a.debet,a.kredit,a.dc
    //         from (select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
    //                    a.nilai as debet,0 as kredit,a.dc
    //             from sis_cd_d a
    //             inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
    //             where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D' $periode_filter
    //             union all
    //             select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
    //                    case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
    //             from sis_cd_d a
    //             inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
    //             where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' $periode_filter
    //             union all
    //             select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
    //                    0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
    //             from sis_cd_d a
    //             inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
    //             where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C' $periode_filter
                            
    //             )a
    //         order by a.tanggal ");
    //         $res2 = json_decode(json_encode($res2),true);
            
    //         if(count($res) > 0){ //mengecek apakah data kosong atau tidak
    //             $success['status'] = true;
    //             $success['data'] = $res;
    //             $success['detail'] = $res2;
    //             $success['message'] = "Success!";     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!";
    //             $success['data'] = [];
    //             $success['detail'] = [];
    //             $success['status'] = true;
    //         }
    //         return response()->json($success, $this->successStatus);
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
        
    // }

    public function getKartuPDD(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            if(isset($request->periode) && $request->periode != ""){
                if($request->periode == "all"){
                    $periode_filter = "";
                }else{
                    $periode_filter = " and a.periode='$request->periode' ";
                }
            }else{
                $periode_filter = "";
            }

            $rs = DB::connection($this->db)->select("select a.*,case when a.nilai > 0 then 'Deposit' else 'Pembayaran' end as jenis from (
                select a.no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
                from sis_rekon_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,sum(case dc when 'D' then a.nilai else -a.nilai end) as nilai
                            from sis_cd_d a
                            where a.nis ='$nik' 
                            group by a.no_bukti,a.kode_pp,a.kode_lokasi
                            )b on a.no_rekon=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' $periode_filter
                union all
                select a.no_kas as no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
                from kas_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,sum(case dc when 'D' then a.nilai else -a.nilai end) as nilai
                            from sis_cd_d a
                            where a.nis ='$nik'
                            group by a.no_bukti,a.kode_pp,a.kode_lokasi
                            )b on a.no_kas=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' $periode_filter
                ) a
                order by tgl_input");
            $res = json_decode(json_encode($rs),true);
            
            
            $rslok = DB::connection($this->db)->select("select a.kode_pp,a.nama,a.alamat,a.alamat2 from sis_sekolah a where a.kode_pp = '$kode_pp' and a.kode_lokasi='$kode_lokasi' ");
            $reslok = json_decode(json_encode($rslok),true);
            $success['lokasi'] = $reslok;
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i< count($res); $i++){
    
                    if(!isset($request->tipe)){
                        $res2 = DB::connection($this->db)->select("select a.* from (
                            select a.no_rekon,a.tgl_input,a.keterangan,isnull(b.nilai,0) as nilai,b.kode_param,b.dc
                            from sis_rekon_m a 
                            inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param,a.dc
                                        from sis_cd_d a
                                        where a.nis ='$nik' 
                                        )b on a.no_rekon=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                            union all
                            select a.no_kas as no_rekon,a.tgl_input,a.keterangan,isnull(b.nilai,0) as nilai,b.kode_param,b.dc
                            from kas_m a 
                            inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param,a.dc
                                        from sis_cd_d a
                                        where a.nis ='$nik'
                                        )b on a.no_kas=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                            ) a
                            where a.no_rekon='".$res[$i]['no_rekon']."'
                            order by tgl_input");
                        $res[$i]["detail"] = json_decode(json_encode($res2),true);
                    }
                }
    
    
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKartuPDDDetail(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            if(isset($request->id) && $request->id != ""){
                $id_filter = " where a.no_rekon='$request->id' ";
            }else{
                $id_filter = "";
            }

            $res2 = DB::connection($this->db)->select("select a.* from (
                select a.no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai,b.kode_param,b.dc
                from sis_rekon_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param,a.dc
                            from sis_cd_d a
                            where a.nis ='$nik' 
                            )b on a.no_rekon=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                union all
                select a.no_kas as no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai,b.kode_param,b.dc
                from kas_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,a.nilai,a.kode_param,a.dc
                            from sis_cd_d a
                            where a.nis ='$nik'
                            )b on a.no_kas=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                ) a
                $id_filter
                order by tgl_input");
            $res = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getProfile(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $get = DB::connection($this->db)->select("select max(a.periode) as periode from ( select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
            union all
            select max(periode) as periode from sis_cd_d where kode_lokasi='$kode_lokasi' and nis='$nik'
            union all
            select max(periode) as periode from sis_bill_d where kode_lokasi='$kode_lokasi' and nis='$nik' 
            union all
            select max(periode) as periode from sis_rekon_d where kode_lokasi='$kode_lokasi' and nis='$nik'
            ) a");
            if(count($get) > 0){
                $periode = $get[0]->periode;
            }else{
                $periode = $request->periode;
            }

            $res = DB::connection($this->db)->select("select a.nis,a.nama,a.kode_lokasi,a.kode_pp,a.kode_akt
            ,isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total,
            a.kode_kelas,f.kode_jur,g.nama as nama_jur,isnull(a.foto,'-') as foto,a.hp_siswa as no_telp,a.id_bank,a.email
            from sis_siswa a 
            inner join sis_kelas f on a.kode_kelas=f.kode_kelas and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
            inner join sis_jur g on f.kode_jur=g.kode_jur and f.kode_lokasi=g.kode_lokasi and f.kode_pp=g.kode_pp
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )c on a.nis=c.nis and a.kode_lokasi=c.kode_lokasi
            left join (select y.nis,y.kode_lokasi,  
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode')	and x.kode_pp='$kode_pp'		
                        group by y.nis,y.kode_lokasi 			
                        )d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )e on a.nis=e.nis and a.kode_lokasi=e.kode_lokasi
            where a.nis='$nik'
            order by a.kode_kelas,a.nis ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("select a.nis,a.nama,
            isnull(c.nilai,0)+isnull(d.nilai,0)-isnull(e.nilai,0) as so_akhir
            from sis_siswa a 
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            inner join (select a.nis,a.kode_pp,a.kode_lokasi
                        from sis_cd_d a
                        where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'
                        group by a.nis,a.kode_pp,a.kode_lokasi
                        )g on a.nis=g.nis and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
            left join (select a.nis,a.kode_lokasi,a.kode_pp,sum(case when a.dc='D' then nilai else -nilai end) as nilai
                    from sis_cd_d a			
                    inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi='$kode_lokasi' and a.periode<'$periode' and a.kode_pp='$kode_pp'
                    group by a.nis,a.kode_lokasi,a.kode_pp
                    )c on a.nis=c.nis and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            left join (select a.nis,a.kode_lokasi,a.kode_pp,sum(a.nilai) as nilai
                    from sis_cd_d a			
                    inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi='$kode_lokasi' and a.dc='D' and a.periode='$periode' and a.kode_pp='$kode_pp'
                    group by a.nis,a.kode_lokasi,a.kode_pp
                    )d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
            left join (select a.nis,a.kode_lokasi,a.kode_pp,sum(a.nilai) as nilai
                    from sis_cd_d a			
                    inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' and a.dc='C' and a.kode_pp='$kode_pp'
                    group by a.nis,a.kode_lokasi,a.kode_pp
                    )e on a.nis=e.nis and a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp
            where a.nis='$nik' 
            order by a.kode_kelas,a.nis");
            $res2 = json_decode(json_encode($res2),true);


            $res3 = DB::connection($this->db)->select("select  top 5 a.* from (
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,
                'BILL' as modul, isnull(a.tagihan,0) as tagihan, 0 as bayar, a.id_bank
                from (select x.kode_lokasi,x.no_bill,sum(x.nilai) as tagihan, '-' as id_bank
						from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0  
                        group by x.kode_lokasi,x.no_bill )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'PDD' as modul,0 as tagihan,isnull(a.bayar,0) as bayar,a.id_bank
                from (select x.kode_lokasi,x.no_rekon,sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar,'-' as id_bank
                    from sis_rekon_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.kode_lokasi,x.no_rekon
                    )a 
                inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'KB' as modul, 0 as tagihan,isnull(a.bayar,0) as bayar, a.id_bank 
                from (select x.kode_lokasi,x.no_rekon,
					sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar,x.id_bank
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0  
					group by x.kode_lokasi,x.no_rekon,x.id_bank
                )a
                inner join kas_m b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            ) a
            order by a.tanggal desc ");
            $res3 = json_decode(json_encode($res3),true);


            $res4 = DB::connection($this->db)->select("select a.* from (
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,b.periode,
                b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(c.bayar,0) as bayar,a.kode_param,isnull(a.tagihan,0)-isnull(c.bayar,0) as sisa
                from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan 
						from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                        group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi
				left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                        group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_param=c.kode_param
                left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar 
                        from sis_mandiri_bill_d x 
                        inner join sis_mandiri_bill z on x.no_bukti=z.no_bukti and x.kode_lokasi=z.kode_lokasi
                                inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 and z.status not in ('CANCEL','WAITING','FAILED')
                                group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param ) d on a.no_bill=d.no_bill and a.kode_lokasi=d.kode_lokasi and a.kode_param=d.kode_param
                        where (a.tagihan - isnull(c.bayar,0) - isnull(d.bayar,0) > 0)
                        ) a
                order by a.no_bukti desc,a.kode_param asc");
            $res4 = json_decode(json_encode($res4),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['data3'] = $res3;
                $success['data4'] = $res4;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data2'] = [];
                $success['data3'] = [];
                $success['data4'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSaldoPiutang(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $get = DB::connection($this->db)->select("select max(a.periode) as periode from ( select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
            union all
            select max(periode) as periode from sis_cd_d where kode_lokasi='$kode_lokasi' and nis='$nik'
            union all
            select max(periode) as periode from sis_bill_d where kode_lokasi='$kode_lokasi' and nis='$nik' 
            union all
            select max(periode) as periode from sis_rekon_d where kode_lokasi='$kode_lokasi' and nis='$nik'
            ) a");
            if(count($get) > 0){
                $periode = $get[0]->periode;
            }else{
                $periode = $request->periode;
            }

            $res = DB::connection($this->db)->select("select a.nis,a.nama,a.kode_lokasi,a.kode_pp,isnull(b.total,0)-isnull(d.total,0)-isnull(f.total,0)+isnull(c.total,0)-isnull(e.total,0)-isnull(g.total,0) as piutang,a.id_bank
            from sis_siswa a 
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )c on a.nis=c.nis and a.kode_lokasi=c.kode_lokasi
            left join (select y.nis,y.kode_lokasi,  
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode')	and x.kode_pp='$kode_pp'		
                        group by y.nis,y.kode_lokasi 			
                        )d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )e on a.nis=e.nis and a.kode_lokasi=e.kode_lokasi
			left join (select y.nis,y.kode_lokasi,  
                                sum(x.nilai) as total				
                        from sis_mandiri_bill_d x 	
						inner join sis_mandiri_bill z on x.no_bukti=z.no_bukti and x.kode_lokasi=z.kode_lokasi
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode_bill <'$periode') and x.kode_pp='$kode_pp' and z.status='PAID'		
                        group by y.nis,y.kode_lokasi 			
                        )f on a.nis=f.nis and a.kode_lokasi=d.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(x.nilai) as total		
                        from sis_mandiri_bill_d x 	
						inner join sis_mandiri_bill z on x.no_bukti=z.no_bukti and x.kode_lokasi=z.kode_lokasi		
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode_bill ='$periode') and x.kode_pp='$kode_pp' and z.status='PAID'			
                        group by y.nis,y.kode_lokasi 			
                        )g on a.nis=g.nis and a.kode_lokasi=e.kode_lokasi
            where a.nis='$nik'
             ");
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getTA(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $res = DB::connection($this->db)->select("select a.kode_ta,a.nama
            from sis_ta a 
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' 
            order by a.kode_ta desc
            ");
            $res = json_decode(json_encode($res),true);

            $resget = DB::connection($this->db)->select("select a.kode_ta,a.nama
            from sis_ta a 
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.flag_aktif=1
            ");
            $resget = json_decode(json_encode($resget),true);
            $success['tahun_akademik_aktif'] = $resget[0]['kode_ta'];
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPeriode(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $res = DB::connection($this->db)->select("select distinct a.periode,dbo.fnNamaBulan(a.periode) as nama_periode
            from periode a 
            where a.kode_lokasi='$kode_lokasi'
            order by a.periode desc
            ");
            $res = json_decode(json_encode($res),true);

            $resget = DB::connection($this->db)->select("select max(a.periode) as periode
            from periode a
            where a.kode_lokasi='$kode_lokasi' 
            ");
            $resget = json_decode(json_encode($resget),true);
            $success['periode_aktif'] = $resget[0]['periode'];
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRiwayatTransaksi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $filter_jenis = "where a.kode_lokasi='$kode_lokasi' ";
            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == "all"){
                    $filter_jenis .="";
                }else{  
                    if($request->jenis == "tagihan"){
                        $jenis = " and a.modul = 'BILL' ";
                    }else{
                        $jenis = " and a.modul <> 'BILL' ";
                    }
                    $filter_jenis .= $jenis;
                }
            }else{
                $filter_jenis .="";
            }

            $filter_bill = "";
            $filter_midtrans = "";
            if(isset($request->kode_sem) && $request->kode_sem != ""){
                if($request->kode_sem == "all"){
                    $filter_bill .="";
                    $filter_midtrans .="";
                }else{  
                    $filter_bill .=" and x.kode_sem='$request->kode_sem' ";
                    $filter_midtrans .=" and y.kode_sem='$request->kode_sem' ";
                }
            }else{
                $filter_bill .="";
                $filter_midtrans .="";
            }

            if(isset($request->kode_ta) && $request->kode_ta != ""){
                if($request->kode_ta == "all"){
                    $filter_bill .="";
                    $filter_midtrans .="";
                }else{  
                    $filter_bill .=" and x.kode_ta='$request->kode_ta' ";
                    $filter_midtrans .=" and y.kode_ta='$request->kode_ta' ";
                }
            }else{
                $filter_bill .="";
                $filter_midtrans .="";
            }


            $filter_rekon = "";
            if(isset($request->kode_sem) && $request->kode_sem != ""){
                if($request->kode_sem == "all"){
                    $filter_rekon .="";
                }else{  
                    $filter_rekon .=" and z.kode_sem='$request->kode_sem' ";
                }
            }else{
                $filter_rekon .="";
            }

            if(isset($request->kode_ta) && $request->kode_ta != ""){
                if($request->kode_ta == "all"){
                    $filter_rekon .="";
                }else{  
                    $filter_rekon .=" and z.kode_ta='$request->kode_ta' ";
                }
            }else{
                $filter_rekon .="";
            }

            $filter_top = "";
            if(isset($request->top) && $request->top != ""){
                if($request->top == "all"){
                    $filter_top .="";
                }else{  
                    $filter_top .=" top $request->top ";
                }
            }else{
                $filter_top .="";
            }

            if(isset($request->status) && $request->status != ""){
                if($request->status == "all"){
                    $filter_jenis .="";
                }else{  
                    $filter_jenis .=" and a.status='$request->status' ";
                }
            }else{
                $filter_jenis .="";
            }


            $sql = "select $filter_top a.*,case modul when 'BILL' then 'Tagihan' when 'KB' then 'Pembayaran' when 'PDD' then 'Auto Bayar' when 'KBMID' then 'Pembayaran Midtrans' end as jenis from (
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,
                'BILL' as modul, isnull(a.tagihan,0) as total,a.id_bank,'success' as status,'-' as snap_token
                from (select x.kode_lokasi,x.no_bill,sum(x.nilai) as tagihan, '-' as id_bank
						from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0  $filter_bill
                        group by x.kode_lokasi,x.no_bill )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'PDD' as modul,isnull(a.bayar,0) as bayar,a.id_bank,'success' as status,'-' as snap_token
                from (select x.kode_lokasi,x.no_rekon,sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar, x.id_bank
                    from sis_rekon_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 $filter_rekon
					group by x.kode_lokasi,x.no_rekon,x.id_bank
                    )a 
                inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'KB' as modul, isnull(a.bayar,0) as bayar, a.id_bank,'success' as status,'-' as snap_token
                from (select x.kode_lokasi,x.no_rekon,
					sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar,x.id_bank
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0  $filter_rekon
					group by x.kode_lokasi,x.no_rekon,x.id_bank
                )a
                inner join kas_m b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi
                union all
                select a.no_bukti,a.kode_lokasi,b.tgl_input,convert(varchar(10),b.tgl_input,103) as tgl,
                'KBMID' as modul, isnull(a.tagihan,0) as total,a.id_bank,b.status,b.snap_token
                from (select x.kode_lokasi,x.kode_pp,x.no_bukti,sum(x.nilai) as tagihan, '-' as id_bank
                    from sis_mid_bayar_d x 
					inner join sis_mid_bayar m on x.no_bukti=m.no_bukti and x.kode_pp=m.kode_pp and x.kode_lokasi=m.kode_lokasi
                    inner join sis_bill_d y on x.no_bill=y.no_bill and x.kode_pp=y.kode_pp and x.kode_lokasi=y.kode_lokasi and m.nis=y.nis and x.kode_param=y.kode_param
                    where x.kode_lokasi = '$kode_lokasi' and x.kode_pp='$kode_pp' and x.nilai<>0 $filter_midtrans
                    group by x.kode_lokasi,x.kode_pp,x.no_bukti )a 
                inner join sis_mid_bayar b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.nis='$nik' 
                union all
                select a.no_bukti,a.kode_lokasi,b.tgl_input,convert(varchar(10),b.tgl_input,103) as tgl,
                'MANDIRI' as modul, isnull(a.tagihan,0) as total,a.id_bank,b.status,b.bill_cust_id
                from (select x.kode_lokasi,x.kode_pp,x.no_bukti,sum(x.nilai) as tagihan, '-' as id_bank
                    from sis_mandiri_bill_d x 
					inner join sis_mandiri_bill m on x.no_bukti=m.no_bukti and x.kode_pp=m.kode_pp and x.kode_lokasi=m.kode_lokasi
                    inner join sis_bill_d y on x.no_bill=y.no_bill and x.kode_pp=y.kode_pp and x.kode_lokasi=y.kode_lokasi and m.nik_user=y.nis and x.kode_param=y.kode_param
                    where x.kode_lokasi = '$kode_lokasi' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.kode_pp,x.no_bukti )a 
                inner join sis_mandiri_bill b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.nik_user='$nik' 
            ) a
            $filter_jenis
            order by a.tanggal desc";
            // $success['sql'] = $sql;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRincianTagihan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection($this->db)->select("select distinct dbo.fnNamaBulan(a.periode) as nama_periode,a.periode as periode
            from sis_bill_m a
            inner join (select x.kode_lokasi,x.kode_pp,x.no_bill,sum(x.nilai) as tagihan 
                                    from sis_bill_d x 
                                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                    group by x.kode_lokasi,x.kode_pp,x.no_bill,x.nis) b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join (select x.kode_lokasi,x.no_bill,x.kode_pp,sum(x.nilai) as bayar from sis_rekon_d x 
                                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                    group by x.kode_lokasi,x.no_bill,x.kode_pp) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi = '$kode_lokasi' and a.kode_pp='$kode_pp' and b.tagihan - isnull(c.bayar,0) <> 0
            order by a.periode desc ");
            $res = json_decode(json_encode($res),true);
        

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($res);$i++){
                    $res[$i]['daftar'] = json_decode(json_encode(DB::connection($this->db)->select("
                    select a.no_bill,convert(varchar,a.tanggal,103) as tgl,b.tagihan - isnull(c.bayar,0) as sisa_tagihan,a.tgl_input
                    from sis_bill_m a
                    inner join (select x.kode_lokasi,x.kode_pp,x.no_bill,sum(x.nilai) as tagihan 
                                            from sis_bill_d x 
                                            inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                            where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                            group by x.kode_lokasi,x.kode_pp,x.no_bill,x.nis) b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    left join (select x.kode_lokasi,x.no_bill,x.kode_pp,sum(x.nilai) as bayar from sis_rekon_d x 
                                            inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                            where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                            group by x.kode_lokasi,x.no_bill,x.kode_pp) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                    where a.kode_lokasi = '$kode_lokasi' and a.kode_pp='$kode_pp' and b.tagihan - isnull(c.bayar,0) <> 0 and a.periode='".$res[$i]['periode']."'
                    order by a.tgl_input desc
                    ")),true);
                    for($j=0;$j < count($res[$i]['daftar']);$j++)
                    {
                        $no_bill = $res[$i]['daftar'][$j]['no_bill'];
                        $res[$i]['daftar'][$j]['detail'] = json_decode(json_encode(DB::connection($this->db)->select("
                        select a.kode_param,isnull(a.tagihan,0)-isnull(c.bayar,0) as sisa
                        from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan 
                                from sis_bill_d x 
                                inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
                        
                        left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar from sis_rekon_d x 
                                inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_param=c.kode_param
                        where a.tagihan - isnull(c.bayar,0) > 0 and a.no_bill='".$no_bill."'
                        order by a.kode_param
                        ")),true);
                    }
                }
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

    public function  getDetailTransaksi(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required' 
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }


            $res = DB::connection($this->db)->select("select a.no_bukti,a.tgl,a.tanggal,a.id_bank,a.total,case modul when 'BILL' then 'Tagihan' when 'KB' then 'Pembayaran' when 'PDD' then 'Auto Bayar' when 'KBMID' then 'Pembayaran Midtrans' end as jenis from (
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,
                'BILL' as modul, isnull(a.tagihan,0) as total,a.id_bank
                from (select x.kode_lokasi,x.no_bill,sum(x.nilai) as tagihan, '-' as id_bank
						from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                        group by x.kode_lokasi,x.no_bill )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'PDD' as modul,isnull(a.bayar,0) as bayar,a.id_bank
                from (select x.kode_lokasi,x.no_rekon,sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar,'-' as id_bank
                    from sis_rekon_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
					group by x.kode_lokasi,x.no_rekon
                    )a 
                inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,'KB' as modul, isnull(a.bayar,0) as bayar, a.id_bank 
                from (select x.kode_lokasi,x.no_rekon,
					sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar,x.id_bank
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					inner join sis_bill_d z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp and x.periode_bill=z.periode and x.nis=z.nis and x.kode_param=z.kode_param 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.kode_lokasi,x.no_rekon,x.id_bank
                )a
                inner join kas_m b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
                union all
                select a.no_bukti,a.kode_lokasi,b.tgl_input,convert(varchar(10),b.tgl_input,103) as tgl,
                'KBMID' as modul, isnull(a.tagihan,0) as total,a.id_bank
                from (select x.kode_lokasi,x.no_bukti,sum(x.nilai) as tagihan, '-' as id_bank
                    from sis_mid_bayar_d x 
                    where x.kode_lokasi = '$kode_lokasi' and x.kode_pp='$kode_pp' and x.nilai<>0  
                    group by x.kode_lokasi,x.no_bukti )a 
                inner join sis_mid_bayar b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and b.nis='$nik' 
                union all
				select a.no_bukti,a.kode_lokasi,b.tgl_input,convert(varchar(10),b.tgl_input,103) as tgl,
                'MANDIRI' as modul, isnull(a.tagihan,0) as total,b.bill_cust_id as id_bank
                from (select x.kode_lokasi,x.no_bukti,sum(x.nilai) as tagihan, '-' as id_bank
                    from sis_mandiri_bill_d x 
                    where x.kode_lokasi = '$kode_lokasi' and x.kode_pp='$kode_pp' and x.nilai<>0  
                    group by x.kode_lokasi,x.no_bukti )a 
                inner join sis_mandiri_bill b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and b.nik_user='$nik_user' 
            ) a
            where a.no_bukti = '$request->no_bukti'
            order by a.tanggal desc ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($res);$i++){
                    $res[$i]['detail'] = json_decode(json_encode(DB::connection($this->db)->select("
                    select a.kode_param,a.no_bukti,a.no_bill,convert(varchar,a.tanggal,103) as tgl,a.nilai from (select x.kode_param,x.no_bill as no_bukti,x.no_bill,b.tanggal,sum(x.nilai) as nilai
                                from sis_bill_d x 
                                inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                                inner join sis_bill_m b on x.no_bill=b.no_bill and x.kode_lokasi=b.kode_lokasi 
                                where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                group by x.kode_param,x.no_bill,x.no_bill,b.tanggal 
                        union all 
                        select x.kode_param,x.no_rekon,x.no_bill,c.tanggal,sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
                            from sis_rekon_d x 
                            inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                            inner join sis_rekon_m b on x.no_rekon=b.no_rekon and x.kode_lokasi=b.kode_lokasi 
                            inner join sis_bill_m c on x.no_bill=c.no_bill and x.kode_lokasi=c.kode_lokasi 
                            where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
                            group by x.kode_param,x.no_rekon,x.no_bill,c.tanggal
                        union all 
                        select x.kode_param,x.no_rekon,x.no_bill,c.tanggal,
                            sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
                            from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                            inner join kas_m b on x.no_rekon=b.no_kas and x.kode_lokasi=b.kode_lokasi 
                            inner join sis_bill_m c on x.no_bill=c.no_bill and x.kode_lokasi=c.kode_lokasi 
                            where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                            group by x.kode_param,x.no_rekon,x.no_bill,c.tanggal
                        union all
                        select x.kode_param,x.no_bukti,x.no_bill,b.tgl_input,sum(x.nilai) as nilai
                                from sis_mid_bayar_d x 
								inner join sis_mid_bayar b on x.no_bukti=b.no_bukti and x.no_bill=b.no_bill and x.kode_lokasi=b.kode_lokasi 
                                inner join sis_siswa y on b.nis=y.nis and b.kode_lokasi=y.kode_lokasi and b.kode_pp=y.kode_pp
                                where x.kode_lokasi = '$kode_lokasi' and b.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                group by x.kode_param,x.no_bukti,x.no_bill,b.tgl_input 
                        union all
                        select x.kode_param,x.no_bukti,x.no_bill,b.tgl_input,sum(x.nilai) as nilai
                                from sis_mandiri_bill_d x 
								inner join sis_mandiri_bill b on x.no_bukti=b.no_bukti and x.kode_lokasi=b.kode_lokasi 
                                inner join sis_siswa y on b.nik_user=y.nis and b.kode_lokasi=y.kode_lokasi and b.kode_pp=y.kode_pp
                                where x.kode_lokasi = '$kode_lokasi' and b.nik_user='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                                group by x.kode_param,x.no_bukti,x.no_bill,b.tgl_input 
                    ) a
                    where a.no_bukti='".$res[$i]['no_bukti']."'
                    order by a.no_bukti,a.kode_param
                    ")),true);
                }
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function generatePriority(Request $request){
        $this->validate($request, [
            'no_bill' => 'required|array',
            'nilai' => 'required'
        ]);
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $no_bill = $request->input('no_bill');  
            $this_in = "";
            $filter_in = "";
            if(count($no_bill) > 0){
                for($x=0;$x<count($no_bill);$x++){
                    if($x == 0){
                        $this_in .= "'".$no_bill[$x]."'";
                    }else{
                        
                        $this_in .= ","."'".$no_bill[$x]."'";
                    }
                }
                $filter_in = " and a.no_bill in ($this_in) ";
            }     

            $get = DB::connection($this->db)->select("select a.kode_param,isnull(a.tagihan,0)-isnull(c.bayar,0) as sisa,a.no_bill
            from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan 
                    from sis_bill_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
            
            left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar from sis_rekon_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_param=c.kode_param
            where a.tagihan - isnull(c.bayar,0) > 0 $filter_in
            order by a.no_bill,a.kode_param");
            $get = json_decode(json_encode($get),true);
            $item_details = array();
            $total_bayar = intval($request->nilai);
            $total_tmp =0;
            if(count($get) > 0){
                $sisa_bayar = $total_bayar;
                for($i=0;$i < count($get); $i++){
                    $row = $get[$i];
                    if($sisa_bayar > 0){
                        if($sisa_bayar >= intval($row['sisa'])){
                            
                            $item_details[] = array(
                                'id'       => $row['no_bill'],
                                'price'    => intval($row['sisa']),
                                'quantity' => 1,
                                'name'     => $row['kode_param']
                            );
                        }else{
                            $item_details[] = array(
                                'id'       => $row['no_bill'],
                                'price'    => $sisa_bayar,
                                'quantity' => 1,
                                'name'     => $row['kode_param']
                            );
                        }
                        $sisa_bayar = $sisa_bayar - intval($row['sisa']);
                    }else if($sisa_bayar == 0){
                        break;
                    }
                }
            }


            // $orderId = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");
            // date_default_timezone_set('Asia/Jakarta');
            // $start_time = date( 'Y-m-d H:i:s O', time() );
            // $payload = [
            //     'transaction_details' => [
            //         'order_id'      => $orderId,
            //         'gross_amount'  => $request->nilai,
            //     ],
            //     'customer_details' => [
            //         'first_name'    => $request->nis,
            //         'email' => "tes@gmail.com"
            //     ],
            //     'item_details' => $item_details,
            //     // 'item_details' => [
            //     //     [
            //     //         'id'       => $request->no_bill,
            //     //         'price'    => $request->nilai,
            //     //         'quantity' => 1,
            //     //         'name'     => $request->keterangan
            //     //     ]
            //     // ],
            //     'enabled_payments' => ['echannel'],
            //     'expiry' => [
            //         'start_time' => $start_time,
            //         'unit' => 'minutes',
            //         'duration' => 180
            //     ],
            //     'callbacks'=> [
            //         'finish'=> 'https://app.simkug.com/ts-auth/finish-trans'
            //     ]
            // ];

            return response()->json($item_details, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $result['status'] = false;
            $result['message'] = $res;
            return response()->json($result, 200);
        } 
    }


    public function getTransaksiPending(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $filter = "where a.kode_lokasi='$kode_lokasi' ";
            
            $sql = "select a.no_bukti,a.snap_token,a.status from sis_mid_bayar a $filter and a.status='pending' and a.nis='$nik' ";
            // $success['sql'] = $sql;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getInformasi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }
            $sql = "select a.kontak,a.no_bukti,a.judul as kelompok_notifikasi,a.info as notifikasi,convert(varchar,a.tgl_input,105) as tgl, isnull(b.file_dok,'-') as gambar 
            from sis_info_m a
            left join sis_info_dok b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.no_urut=0
            where a.kontak = '$nik' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
            union all
            select a.kontak,a.no_bukti,a.judul as kelompok_notifikasi,a.info as notifikasi,convert(varchar,a.tgl_input,105) as tgl, isnull(b.file_dok,'-') as gambar 
            from sis_info_m a
            left join sis_info_dok b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.no_urut=0
            where a.jenis = 'Semua' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPesan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }
            $sql = "
            select a.nik_buat as nik,b.nama,d.pesan,isnull(e.jum_not_read,0) as jum_not_read
            from sis_msg_m a
            left join (
                select a.kode_pp as nik,a.kode_lokasi,a.nama from sis_sekolah a 
                union all
                select a.nis,a.kode_lokasi,a.nama from sis_siswa a 
                ) b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
            inner join (SELECT a.kode_lokasi,MAX(no_bukti) as no_bukti
                        FROM sis_msg_m a
                        where a.nik_tuju='$nik' or a.nik_buat='$nik'
                        GROUP BY a.kode_lokasi
            ) c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi
            inner join (SELECT a.kode_lokasi,a.no_bukti,a.pesan
                        FROM sis_msg_m a
            ) d on c.no_bukti=d.no_bukti and c.kode_lokasi=d.kode_lokasi
            left join (SELECT a.kode_lokasi,count(*) as jum_not_read
                        FROM sis_msg_m a
                        where (a.nik_tuju='$nik' or a.nik_buat='$nik') and a.sts_read=0
                        GROUP BY a.kode_lokasi
            ) e on a.kode_lokasi=e.kode_lokasi
            where a.nik_tuju='$nik' and a.kode_lokasi='$kode_lokasi' 
             ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPesanDetail(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }
            $sql = "
            select a.*, isnull(b.file_dok,'-') as dokumen from sis_msg_m a 
            left join sis_msg_dok b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.no_urut=0
            where (a.nik_tuju='$nik' or a.nik_buat='$nik') 
            order by a.tgl_input 
             ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['rows'] = count($res);
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
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function simpanBuktiBayar(Request $request)
    {
        $this->validate($request, [
            'file_dok' => 'file|max:10000'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_dok')){
                $file = $request->file('file_dok');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('ts/'.$foto)){
                    Storage::disk('s3')->delete('ts/'.$foto);
                }
                Storage::disk('s3')->put('ts/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            $per = substr(date('Ym'),2,2);
            $no_bukti = $this->generateKode("sis_msg_m", "no_bukti", $kode_lokasi."-".$per.".", "00001");
            $ins = DB::connection($this->sql)->insert("insert into sis_msg_m(no_bukti,nik_buat,nik_tuju,pesan,tgl_input,kode_lokasi,kode_pp,sts_read) values (?, ?, ?, ?, getdate(), ?, ?, ?)",array($no_bukti,$nik,$kode_pp,'Bukti Pembayaran',$kode_lokasi,$kode_pp,0));
            
            $insdok = DB::connection($this->sql)->insert("insert into sis_msg_dok(no_bukti,file_dok,no_urut,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)",array($no_bukti,$foto,0,$kode_lokasi,$kode_pp));
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Bukti Bayar berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bukti Bayar gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
     
    }

    public function updateStatusReadPesan(Request $request)
	{
		if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $kode_pp = $data->kode_pp;
		}

		$this->validate($request,[
            'no_bukti' => 'required'
		]);

		DB::connection($this->db)->beginTransaction();
        try{
            
			$upd = DB::connection($this->db)->insert("update sis_msg_m set sts_read = '1' where no_bukti='$request->no_bukti' and (nik_tuju='$nik' or nik_buat='$nik') and kode_lokasi='$kode_lokasi' ");

			DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function updateStatusReadInfo(Request $request)
	{
		if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $kode_pp = $data->kode_pp;
		}

		$this->validate($request,[
            'no_bukti' => 'required'
		]);

		DB::connection($this->db)->beginTransaction();
        try{
            
			$upd = DB::connection($this->db)->insert("update sis_info_d set sts_read = '1' where no_bukti='$request->no_bukti' and nik='$nik' and kode_lokasi='$kode_lokasi' ");

			DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }
}
