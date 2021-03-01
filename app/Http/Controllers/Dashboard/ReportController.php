<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Menu;

class ReportController extends Controller
{
    public $successStatus = 200;

    public function getLokasi(){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_lokasi,a.nama
            from lokasi a
            where a.kode_lokasi='$kode_lokasi' 
            order by a.kode_lokasi            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAkun(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $filter="";
            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');
                $filter = " and a.kode_akun='$kode_akun' ";
            }

            $sql="select a.kode_akun,a.nama
            from masakun a
            where a.kode_lokasi='$kode_lokasi' $filter
            order by a.kode_akun";

            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPp(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $filter="";
            if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filter = " and a.kode_pp='$kode_pp' ";
            
            }

            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_pp,a.nama
            from pp a
            where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1' $filter
            order by a.kode_pp            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getDrk(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $tahun=$request->input('tahun');
            $filter="";

            if ($request->input('kode_drk') != "") {
                $kode_drk = $request->input('kode_drk');                
                $filter .= " and a.kode_drk='$kode_drk' ";
                
            }else{
                $filter .= "";
            }
            
            $sql="select a.kode_drk,a.nama
            from drk a
            where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun' $filter
             order by a.kode_drk ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPeriodeAktif(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
           
            
            $sql="select a.periode from periode a
            where a.kode_lokasi='$kode_lokasi' 
             order by a.periode ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTb(Request $request){
        $this->validate($request,[
            'kode_pp' => 'required',
            'periode' => 'required'
        ]);
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;

            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            

            $kode_pp=$request->input('kode_pp');
            $periode=$request->input('periode');

            $filter="";

            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');                
                $filter .= " and a.kode_akun='$kode_akun' ";
                
            }else{
                $filter .= "";
            }
            
            $sql="select a.kode_akun,b.nama as nama_akun,a.so_awal,a.debet,a.kredit,a.so_akhir
            from exs_glma_pp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and periode='$periode'
            order by a.kode_akun ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAnggaran(Request $request){
        $this->validate($request,[
            'kode_pp' => 'required',
            'tahun' => 'required'
        ]);
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;

            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            

            $kode_pp=$request->input('kode_pp');
            $tahun=$request->input('tahun');

            $filter="";

            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');                
                $filter .= " and x.kode_akun='$kode_akun' ";
                
            }else{
                $filter .= "";
            }
            
            $sql="select a.kode_akun,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp,a.kode_drk,f.nama as nama_drk,
            isnull(e.agg_01,0) as n1,isnull(e.agg_02,0) as n2,isnull(e.agg_03,0) as n3,isnull(e.agg_04,0) as n4,
            isnull(e.agg_05,0) as n5,isnull(e.agg_06,0) as n6,isnull(e.agg_07,0) as n7,isnull(e.agg_08,0) as n8,
            isnull(e.agg_09,0) as n9,isnull(e.agg_10,0) as n10,isnull(e.agg_11,0) as n11,isnull(e.agg_12,0) as n12,isnull(e.total,0) as total
     from (select x.kode_lokasi,x.kode_akun,x.kode_pp,x.kode_drk
           from anggaran_d x
           inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi
           where x.kode_lokasi='$kode_lokasi' and x.kode_pp='$kode_pp' and substring(x.periode,1,4)='$tahun' $filter
           group by x.kode_lokasi,x.kode_akun,x.kode_pp,x.kode_drk
           ) a
     inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
     inner join drk f on a.kode_drk=f.kode_drk and a.kode_lokasi=f.kode_lokasi
     left join (select x.kode_lokasi,x.kode_akun,x.kode_pp,x.kode_drk
                           , sum(case when substring(x.periode,5,2) between '01' and '01' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_01
                       , sum(case when substring(x.periode,5,2) between '02' and '02' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_02
                       , sum(case when substring(x.periode,5,2) between '03' and '03' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_03
                       , sum(case when substring(x.periode,5,2) between '04' and '04' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_04
                       , sum(case when substring(x.periode,5,2) between '05' and '05' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_05
                       , sum(case when substring(x.periode,5,2) between '06' and '06' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_06
                       , sum(case when substring(x.periode,5,2) between '07' and '07' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_07
                       , sum(case when substring(x.periode,5,2) between '08' and '08' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_08
                       , sum(case when substring(x.periode,5,2) between '09' and '09' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_09
                       , sum(case when substring(x.periode,5,2) between '10' and '10' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_10
                       , sum(case when substring(x.periode,5,2) between '11' and '11' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_11
                       , sum(case when substring(x.periode,5,2) between '12' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as agg_12
                       , sum(case when substring(x.periode,5,2) between '01' and '12' then case when dc='D' then nilai else -nilai end else 0 end ) as total
                from anggaran_d x
                  inner join masakun y on x.kode_akun=y.kode_akun and x.kode_lokasi=y.kode_lokasi 
                where x.kode_lokasi='$kode_lokasi' and x.kode_pp='$kode_pp' and substring(x.periode,1,4)='$tahun' $filter
                group by x.kode_lokasi,x.kode_akun,x.kode_pp,x.kode_drk
                ) e on a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp and a.kode_lokasi=e.kode_lokasi and a.kode_drk=e.kode_drk
    order by a.kode_akun,a.kode_pp ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
    public function getAnggaranRealBulan(Request $request){
        $this->validate($request,[
            'kode_pp' => 'required',
            'tahun' => 'required'
        ]);
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;

            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            

            $kode_pp=$request->input('kode_pp');
            $tahun=$request->input('tahun');

            $filter="";

            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');                
                $filter .= " and a.kode_akun='$kode_akun' ";
                
            }else{
                $filter .= "";
            }
            
            $sql="select a.kode_akun,a.kode_pp,a.kode_drk,a.nama_akun,a.nama_pp,a.nama_drk,a.periode as tahun,
                    a.n1 as gar1,a.n13 as real1,a.n1-a.n13 as sisa1,
                    a.n2 as gar2,a.n14 as real2,a.n2-a.n14 as sisa2,
                    a.n3 as gar3,a.n15 as real3,a.n3-a.n15 as sisa3,
                    a.n4 as gar4,a.n16 as real4,a.n4-a.n16 as sisa4,
                    a.n5 as gar5,a.n17 as real5,a.n5-a.n17 as sisa5,
                    a.n6 as gar6,a.n18 as real6,a.n6-a.n18 as sisa6,
                    a.n7 as gar7,a.n19 as real7,a.n7-a.n19 as sisa7,
                    a.n8 as gar8,a.n20 as real8,a.n8-a.n20 as sisa8,
                    a.n9 as gar9,a.n21 as real9,a.n9-a.n21 as sisa9,
                    a.n10 as gar10,a.n22 as real10,a.n10-a.n22 as sisa10,
                    a.n11 as gar11,a.n23 as real11,a.n11-a.n23 as sisa11,
                    a.n12 as gar12,a.n24 as real12,a.n12-a.n24 as sisa12
                    
            from exs_glma_drk a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and substring(a.periode,1,4)='$tahun' $filter
            order by a.kode_akun ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
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
        $this->validate($request,[
            'kode_pp' => 'required',
            'nis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->periode) && $request->periode != ""){
                $periode_filter = " where a.periode='$request->periode' ";
            }else{
                $periode_filter = "";
            }
            $kode_pp = $request->kode_pp;
            $nis = $request->nis;
            $res = DB::connection('sqlsrvyptkug')->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
            from sis_siswa a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nis'
            order by a.nis ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvyptkug')->select("select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,a.periode,
						b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param,
						0 as masuk,0 as keluar
			 from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,0 as bayar,x.periode 
					from sis_bill_d x 
					inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nis' and x.kode_pp='$kode_pp' and x.nilai<>0 
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
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nis' and x.kode_pp='$kode_pp' and x.nilai<>0
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
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nis' and x.kode_pp='$kode_pp' and x.nilai<>0 
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
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nis' and x.kode_pp='$kode_pp' and x.nilai<>0 
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
					where x.kode_lokasi = '$kode_lokasi' and x.nis='$nis' and x.kode_pp='$kode_pp' and x.nilai<>0 
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
        $this->validate($request,[
            'kode_pp' => 'required',
            'nis' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;
            $nis= $request->nis;

            if(isset($request->periode) && $request->periode != ""){
                $periode_filter = " and a.periode='$request->periode' ";
            }else{
                $periode_filter = "";
            }
            $res = DB::connection('sqlsrvyptkug')->select("select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,f.nama as nama_jur,a.id_bank,a.kode_akt
            from sis_siswa a
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join sis_jur f on b.kode_jur=f.kode_jur and b.kode_lokasi=f.kode_lokasi and b.kode_pp=f.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nis'
            order by a.nis ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvyptkug')->select("select a.kode_pp,a.kode_lokasi,a.no_bukti,a.tgl,a.keterangan,a.modul,a.debet,a.kredit,a.dc
            from (select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       a.nilai as debet,0 as kredit,a.dc
                from sis_cd_d a
                inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nis' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D' $periode_filter
                union all
                select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
                from sis_cd_d a
                inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nis' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' $periode_filter
                union all
                select a.kode_pp,a.kode_lokasi,a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
                       0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
                from sis_cd_d a
                inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
                where a.nis='$nis' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C' $periode_filter
                            
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

    public function getSaldoPiutang(Request $request){
        $this->validate($request,[
            'kode_pp' => 'required',
            'nis' => 'required',
        ]);
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $get = DB::connection('sqlsrvyptkug')->select("select max(a.periode) as periode from ( select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
            union all
            select max(periode) as periode from sis_cd_d where kode_lokasi='$kode_lokasi' and nis='$request->nis'
            union all
            select max(periode) as periode from sis_bill_d where kode_lokasi='$kode_lokasi' and nis='$request->nis' 
            union all
            select max(periode) as periode from sis_rekon_d where kode_lokasi='$kode_lokasi' and nis='$request->nis'
            ) a");

            if(count($get) > 0){
                $periode = $get[0]->periode;
            }else{
                $periode = date('Ym');
            }

            $sql="select isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total
            from sis_siswa a 
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') and x.kode_pp='$request->kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') and x.kode_pp='$request->kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )c on a.nis=c.nis and a.kode_lokasi=c.kode_lokasi
            left join (select y.nis,y.kode_lokasi,  
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode')	and x.kode_pp='$request->kode_pp'		
                        group by y.nis,y.kode_lokasi 			
                        )d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') and x.kode_pp='$request->kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )e on a.nis=e.nis and a.kode_lokasi=e.kode_lokasi
            where a.nis='$request->nis'
            order by a.kode_kelas,a.nis";

            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['saldo'] = floatval($res[0]['sak_total']);
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['saldo'] = 0;
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    

}
