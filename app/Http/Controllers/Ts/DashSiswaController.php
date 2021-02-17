<?php

namespace App\Http\Controllers\Ts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
                $periode_filter = " and a.periode='$request->periode' ";
            }else{
                $periode_filter = "";
            }

            $rs = DB::connection($this->db)->select("select a.* from (
                select a.no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
                from sis_rekon_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,sum(case dc when 'D' then a.nilai else -a.nilai end) as nilai
                            from sis_cd_d a
                            where a.nis ='$nik' 
                            group by a.no_bukti,a.kode_pp,a.kode_lokasi
                            )b on a.no_rekon=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                union all
                select a.no_kas as no_rekon,a.tgl_input,a.keterangan,modul,'-' as jenis, isnull(b.nilai,0) as nilai, convert(varchar,a.tgl_input,103) as tgl
                from kas_m a 
                inner join (select a.no_bukti,a.kode_pp,a.kode_lokasi,sum(case dc when 'D' then a.nilai else -a.nilai end) as nilai
                            from sis_cd_d a
                            where a.nis ='$nik'
                            group by a.no_bukti,a.kode_pp,a.kode_lokasi
                            )b on a.no_kas=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi'
                ) a
                order by tgl_input");
            $res = json_decode(json_encode($rs),true);
            
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i< count($res); $i++){
    
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
            a.kode_kelas,f.kode_jur,g.nama as nama_jur,isnull(a.foto,'-') as foto,a.hp_siswa as no_telp,a.id_bank
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
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,b.periode,
                b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
                from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,
                        0 as bayar from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                        group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,b.periode,b.keterangan,'PDD' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
                from (select x.kode_lokasi,x.no_rekon,x.kode_param,
                    case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
                    )a 
                inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,b.periode,b.keterangan,'KB' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param 
                from (select x.kode_lokasi,x.no_rekon,x.kode_param,
                    case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
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
				where a.tagihan - isnull(c.bayar,0) > 0
                ) a
                order by a.no_bukti desc");
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

    // Belum selesai
    public function getRincianTagihan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res3 = DB::connection($this->db)->select("select distinc a.periode
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
            order by a.tanggal ");
            $res3 = json_decode(json_encode($res3),true);

            $kode_ta = $res3[0]['kode_ta'];
            
            $res2 = DB::connection($this->db)->select("select distinct a.nik,a.kode_matpel,b.nama as nama_guru,c.nama as nama_matpel,c.skode as singkatan 
            from sis_guru_matpel_kelas a
            inner join sis_guru b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_matpel='$request->kode_matpel' and a.kode_kelas='$request->kode_kelas' and a.kode_ta='$kode_ta' ");
            $res2 = json_decode(json_encode($res2),true);

            $sql = "select distinct a.kode_kd,a.nama_kd,'-' as pelaksanaan,case a.kode_sem when '1' then 'Ganjil' else 'Genap' end as semester 
            from sis_nilai_m a 
            inner join sis_nilai c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_pp='$kode_pp' and c.nis='$nik' and a.kode_lokasi='$kode_lokasi'  and a.kode_matpel='$request->kode_matpel' and a.kode_ta='$kode_ta' $filter 
            order by a.kode_kd";
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($res);$i++){
                    $res[$i]['pelaksanaan'] = json_decode(json_encode(DB::connection($this->db)->select("select a.no_bukti,a.kode_jenis,b.nama as pelaksanaan,c.nilai,convert(varchar,a.tgl_input,103) as tgl,isnull(d.file_dok,'-') as file_dok,isnull(e.kkm,0) as kkm,a.kode_kd,case when c.nilai >= isnull(e.kkm,0) then 'lulus' else 'tidak' end as sts_kkm, case when c.nilai >= isnull(e.kkm,0) then 'Memuaskan, pertahankan diatas minimum nilai KKM '+convert(varchar,isnull(e.kkm,0)) else 'Belajar lebih giat jangan menyerah, minimum nilai KKM '+convert(varchar,isnull(e.kkm,0)) end as keterangan,a.pelaksanaan as deskripsi,case a.kode_sem when '1' then 'Ganjil' else 'Genap' end as semester
                    from sis_nilai_m a 
                    inner join sis_jenisnilai b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    inner join sis_nilai c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp 
					inner join sis_kelas f on a.kode_kelas=f.kode_kelas and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
                    left join sis_nilai_dok d on c.no_bukti=d.no_bukti and c.kode_lokasi=d.kode_lokasi and c.kode_pp=d.kode_pp and c.nis=d.nis 
					inner join sis_kd e on a.kode_kd=e.kode_kd and a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp and a.kode_matpel=e.kode_matpel and a.kode_sem=e.kode_sem and a.kode_ta=e.kode_ta and f.kode_tingkat=e.kode_tingkat
                    where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$request->kode_matpel' and a.kode_ta='$kode_ta' and a.kode_kd='".$res[$i]['kode_kd']."' and c.nis='$nik' $filter
                    order by a.kode_jenis")),true);
                }
                $success['status'] = true;
                $success['data_ta'] = $res3;
                $success['data_guru'] = $res2;
                $success['data_kompetensi'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data_ta'] = [];
                $success['data_guru'] = [];
                $success['data_kompetensi'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
