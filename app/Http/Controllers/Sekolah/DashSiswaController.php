<?php

namespace App\Http\Controllers\Sekolah;

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
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

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
            $res = DB::connection($this->db)->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
            from sis_siswa a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            order by a.nis ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,a.periode,
						b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param,
						0 as masuk,0 as keluar
			 from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,0 as bayar,x.periode 
					from sis_bill_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param,x.periode
					 )a 
			inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
			$periode_filter
			union all 
			select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
				convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'PDD' as modul, isnull(a.tagihan,0) as tagihan,
				isnull(a.bayar,0) as bayar,a.kode_param,0 as masuk,0 as keluar
			from (select x.kode_lokasi,x.no_rekon,x.kode_param,x.periode,
						sum(case when x.modul in ('BTLREKON') then x.nilai else 0 end) as tagihan,
						sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
					from sis_rekon_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
					group by x.modul,x.nilai,x.kode_lokasi,x.no_rekon,x.nis,x.kode_param,x.periode
				 )a 
			inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
			$periode_filter
			union all 
			select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
				convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'KB' as modul, 
				isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param,0 as masuk,0 as keluar
			from (select x.kode_lokasi,x.no_rekon,x.kode_param,x.periode,
						sum(case when x.modul in ('BTLREKON') then x.nilai else 0 end) as tagihan,
						sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
				    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.modul,x.nilai,x.kode_lokasi,x.no_rekon,x.nis,x.kode_param ,x.periode
					)a
					inner join (select tanggal,keterangan,no_kas,kode_lokasi from kas_m where kode_lokasi='$kode_lokasi' union select tanggal,keterangan,no_ju as no_kas,kode_lokasi from ju_m where kode_lokasi='$kode_lokasi') b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
			$periode_filter
			union all 
			select a.no_bukti as no_bukti,a.kode_lokasi,b.tanggal,
				convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'KB' as modul, 
				0 as tagihan,0 as bayar,'PDD' as kode_param,isnull(a.masuk,0) as masuk,isnull(a.keluar,0)  as keluar
			from (select x.kode_lokasi,x.no_bukti,x.kode_param,x.periode,x.modul,
						sum(case when x.dc='D' then x.nilai else 0 end) as masuk,
						sum(case when x.dc='C' then x.nilai else 0 end) as keluar
				    from sis_cd_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.modul,x.nilai,x.kode_lokasi,x.no_bukti,x.nis,x.kode_param ,x.periode
				  )a
			inner join (select tanggal,keterangan,no_kas,kode_lokasi from kas_m where kode_lokasi='$kode_lokasi' union select tanggal,keterangan,no_ju as no_kas,kode_lokasi from ju_m where kode_lokasi='$kode_lokasi') b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi 
			$periode_filter
			union all 
			select a.no_bukti as no_bukti,a.kode_lokasi,b.tanggal,
				convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,a.modul, 
				0 as tagihan,0 as bayar,'PDD' as kode_param ,isnull(a.masuk,0) as masuk,isnull(a.keluar,0)  as keluar
			from (select x.kode_lokasi,x.no_bukti,x.kode_param,x.periode,x.modul,
						sum(case when x.dc='D' then x.nilai else 0 end) as masuk,
						sum(case when x.dc='C' then x.nilai else 0 end) as keluar
				    from sis_cd_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
					group by x.modul,x.nilai,x.kode_lokasi,x.no_bukti,x.nis,x.kode_param ,x.periode
					)a
			inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
			$periode_filter
			order by tanggal,modul ");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

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
            $res = DB::connection($this->db)->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
            from sis_siswa a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            order by a.nis ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("select a.kode_pp,a.kode_lokasi,a.no_bukti,a.tgl,a.keterangan,a.modul,a.debet,a.kredit,a.dc
            from (select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       a.nilai as debet,0 as kredit,a.dc
                from sis_cd_d a
                inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D' $periode_filter
                union all
                select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
                from sis_cd_d a
                inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' $periode_filter
                union all
                select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
                from sis_cd_d a
                inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C' $periode_filter
                            
                )a
            order by a.tanggal ");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
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
            $res = DB::connection($this->db)->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
            from sis_siswa a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            order by a.nis ");
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


}
