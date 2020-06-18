<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;  
    public $sql = 'tokoaws';
    public $sql2 = 'sqlsrv2';
    public $guard = 'toko';

    public function getTopSelling(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection($this->sql)->select(" select top 5 a.kode_barang,a.nama,isnull(b.jumlah,0) as jumlah
            from brg_barang a
            left join (select a.kode_barang,a.kode_lokasi,count(a.kode_barang) as jumlah
            from brg_trans_dloc a
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGJUAL'
            group by a.kode_barang,a.kode_lokasi
                    )b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            order by jumlah desc");
            $aju = json_decode(json_encode($aju),true);

            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $aju;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSellingCtg(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col = DB::connection($this->sql)->select(" select distinct LTRIM(RTRIM(b.kode_klp)) as kode_klp
            from brg_trans_dloc a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGJUAL' ");
            $col = json_decode(json_encode($col),true);
            $grouping = array();
            $series = array();
            foreach($col as $row){
                $kode_klp= trim($row["kode_klp"],'\r\n');
                $kode_klp= trim($kode_klp," ");
                $sql[$kode_klp] = "select distinct a.tgl_ed,isnull(b.jumlah,0) as jumlah from brg_trans_dloc a
				left join (select b.kode_klp,a.kode_lokasi,a.tgl_ed,count(a.kode_barang) as jumlah
                from brg_trans_dloc a
                inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.modul='BRGJUAL' and b.kode_klp='$kode_klp' 
                group by b.kode_klp,a.kode_lokasi,a.tgl_ed
                        )b on a.tgl_ed=b.tgl_ed and a.kode_lokasi=b.kode_lokasi
				where a.kode_lokasi='$kode_lokasi' and modul='BRGJUAL' 
				order by a.tgl_ed ";
                $sell = DB::connection($this->sql)->select($sql[$kode_klp]);
                foreach($sell as $row1){
                    $sellctg[$kode_klp][] =floatval($row1->jumlah);
                    
                }
                $series[] = array("name"=>$kode_klp,"data"=>$sellctg[$kode_klp]);
            }

            $rs = DB::connection($this->sql)->select("select distinct tgl_ed from brg_trans_dloc where kode_lokasi='$kode_lokasi' and modul='BRGJUAL' order by tgl_ed
            ");
            $ctg = array(); 
            foreach($rs as $row2){
                $ctg[] = $row2->tgl_ed;
            }
            if(count($series) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $series;
                $success['ctg'] = $ctg;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['ctg'] = $ctg;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getTopVendor(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection($this->sql)->select(" select top 5 a.kode_vendor,a.nama,isnull(b.total,0) as total
            from vendor a
            left join (select b.param2,a.kode_lokasi,sum(a.total) as total
            from brg_trans_d a
            inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGBELI'
            group by b.param2,a.kode_lokasi
                    )b on a.kode_vendor=b.param2 and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.total,0)>0
            order by total desc");
            $aju = json_decode(json_encode($aju),true);

            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $aju;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataBox(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $kode_lokasi= '04';
            }

            $periode = $request->periode;
            $res = DB::connection($this->sql2)->select(" select a.kode_grafik,a.nama,case format when 'Satuan' then isnull(b.nilai,0) when 'Ribuan' then isnull(b.nilai/1000,0) when 'Jutaan' then isnull(b.nilai/1000000,0) end as nilai 
            from db_grafik_m a
            left join (select a.kode_grafik,a.kode_lokasi,sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as nilai
                       from db_grafik_d a
                       inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                       where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.kode_fs='FS1'
                       group by a.kode_grafik,a.kode_lokasi
                      )b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_grafik in ('DB11') and a.kode_lokasi='$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->sql2)->select("   select a.kode_grafik,a.nama,case format when 'Satuan' then isnull(b.nilai,0) when 'Ribuan' then isnull(b.nilai/1000,0) when 'Jutaan' then isnull(b.nilai/1000000,0) end as nilai 
            from db_grafik_m a
            left join (select a.kode_grafik,a.kode_lokasi,sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as nilai
                       from db_grafik_d a
                       inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                       where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.kode_fs='FS1'
                       group by a.kode_grafik,a.kode_lokasi
                      )b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_grafik in ('DB13') and a.kode_lokasi='$kode_lokasi' ");
            $res2 = json_decode(json_encode($res2),true);
            
            $success['status'] = true;
            $success['cash'] = $res[0]['nilai'];
            $success['pend'] = $res2[0]['nilai'];
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getBukuBesar(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $kode_lokasi= '04';
            }

            $tmp = explode("|",$request->param);
            $kode_akun = $tmp[0];
            $tgl1=$tmp[1];
            $tgl2=$tmp[2];
            if($kode_akun == "All" OR $kode_akun == ""){
                $kode_akun="";
                $filterakun="";
            }else{
                $kode_akun=$kode_akun;
                $filterakun=" and c.kode_akun='$kode_akun' ";
            }

            $rs = DB::connection($this->sql2)->select("select c.kode_lokasi,c.kode_akun,d.nama,c.so_awal,c.periode,case when c.so_awal>=0 then c.so_awal else 0 end as so_debet,case when c.so_awal<0 then c.so_awal else 0 end as so_kredit
            from db_grafik_d a
            inner join relakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs 
            inner join exs_glma c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join masakun d on c.kode_akun=d.kode_akun and c.kode_lokasi=d.kode_lokasi
            inner join db_grafik_m e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi
            where c.kode_lokasi='$kode_lokasi' and b.kode_fs='FS1' and c.periode='$periode' and a.kode_grafik in ('DB11') and c.so_akhir<>0 $filterakun 
            order by c.kode_akun");
            $res = json_decode(json_encode($rs),true);

            $tahun = substr($periode,0,4);
            $bulan = substr($periode,5,2);
            
            $sql2 = "SELECT DATEADD(s,-1,DATEADD(mm, DATEDIFF(m,0,'$tahun-$bulan-01')+1,0)) as tgl";
            
            $rs2=DB::connection($this->sql2)->select($sql2);
            $temp = explode(" ",$rs2[0]->tgl);
            $tgl_akhir=$temp[0];
            
            if($request->tgl1 == "" AND $request->tgl2 == ""){
                $filtertgl="";
            }else if ($request->tgl1 != ""  AND $request->tgl2 == ""){
                $filtertgl=" and a.tanggal between '".$request->tgl1."' AND '".$tgl_akhir."' ";
            }else if ($request->tgl1 == "" AND $request->tgl2 != ""){
                $filtertgl=" and a.tanggal between '$tahun-$bulan-01' AND '".$request->tgl2."' ";
            }else{
                $filtertgl=" and a.tanggal between '".$request->tgl1."' AND '".$request->tgl2."' ";
            }

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $hasil = array();
                $kode = "";
                $resdata = array();
                $i=0;

                foreach($rs as $row){

                    $resdata[]=(array)$row;
                    if($i == 0){
                        $kode .= "'$row->kode_akun'";
                    }else{

                        $kode .= ","."'$row->kode_akun'";
                    }
                    $i++;
                }

                $sqlx = "select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.kode_akun,a.keterangan,a.kode_pp,
                case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.kode_drk,a.no_dokumen
                from gldt a
                where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' $filtertgl and a.kode_akun in ($kode)
                order by a.tanggal,a.no_bukti,a.dc ";
                $hasil= DB::connection($this->sql2)->select($sqlx); 
                $hasil = json_decode(json_encode($hasil),true);

                $success['status'] = true;
                $success['daftar'] = $res;
                $success['daftar2'] = $hasil;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['daftar2'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getIncomeChart(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $kode_lokasi= '04';
            }
            
            $periode = $request->periode;

            $tmp = explode("|",$request->param);
            $kode_akun = $tmp[0];
            $tgl1=$tmp[1];
            $tgl2=$tmp[2];
            if($kode_akun == "All" OR $kode_akun == ""){
                $kode_akun="";
                $filterakun="";
            }else{
                $kode_akun=$kode_akun;
                $filterakun=" and c.kode_akun='$kode_akun' ";
            }

            $rs = DB::connection($this->sql2)->select("select a.kode_lokasi,
            sum(case when substring(a.periode,5,2)='01' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n1,
            sum(case when substring(a.periode,5,2)='02' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n2,   
            sum(case when substring(a.periode,5,2)='03' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n3,
            sum(case when substring(a.periode,5,2)='04' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n4,
            sum(case when substring(a.periode,5,2)='05' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n5,
            sum(case when substring(a.periode,5,2)='06' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n6,
            sum(case when substring(a.periode,5,2)='07' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n7,
            sum(case when substring(a.periode,5,2)='08' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n8,
            sum(case when substring(a.periode,5,2)='09' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n9,
            sum(case when substring(a.periode,5,2)='10' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n10,
            sum(case when substring(a.periode,5,2)='11' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n11,  
            sum(case when substring(a.periode,5,2) in ('12','13','14','15') then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n12
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='".substr($periode,0,4)."' and b.kode_grafik='DB13'
            group by a.kode_lokasi");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $series = array();
                foreach ($rs as $row){
                    $series[] = array("name" =>"Income", "data" => array(
                        round(floatval($row->n1)), round(floatval($row->n2)), round(floatval($row->n3)), 
                        round(floatval($row->n4)), round(floatval($row->n5)), round(floatval($row->n6)),
                        round(floatval($row->n7)), round(floatval($row->n8)), round(floatval($row->n9)), 
                        round(floatval($row->n10)), round(floatval($row->n11)), round(floatval($row->n12))
                    ));
                }	
    
                $sqlbox1 = "select 
                    sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end) as n2, 
                    sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as n4, (sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) - sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end))/sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as persen
                    from exs_neraca a
                    inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.periode='".$periode."' and b.kode_grafik in ('DB13')
                ";
                $rowAcvp = DB::connection($this->sql2)->select($sqlbox1);
                $success["budpend"] = $rowAcvp[0]->n2;
                $success["actpend"] = $rowAcvp[0]->n4;
                $success["persen"] = round($rowAcvp[0]->persen*100,2);
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['daftar2'] = $hasil;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['daftar2'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNetProfitChart(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;

            $rs = DB::connection($this->sql2)->select("select a.kode_lokasi,
            sum(case when substring(a.periode,5,2)='01' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n1,
            sum(case when substring(a.periode,5,2)='02' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n2,   
            sum(case when substring(a.periode,5,2)='03' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n3,
            sum(case when substring(a.periode,5,2)='04' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n4,
            sum(case when substring(a.periode,5,2)='05' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n5,
            sum(case when substring(a.periode,5,2)='06' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n6,
            sum(case when substring(a.periode,5,2)='07' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n7,
            sum(case when substring(a.periode,5,2)='08' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n8,
            sum(case when substring(a.periode,5,2)='09' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n9,
            sum(case when substring(a.periode,5,2)='10' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n10,
            sum(case when substring(a.periode,5,2)='11' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n11,  
            sum(case when substring(a.periode,5,2) in ('12','13','14','15') then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n12
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='".substr($periode,0,4)."' and b.kode_grafik='DB13'
            group by a.kode_lokasi");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $series = array();
                foreach ($rs as $row){
                    $series[] = array("name" =>"Income", "data" => array(
                        round(floatval($row->n1)), round(floatval($row->n2)), round(floatval($row->n3)), 
                        round(floatval($row->n4)), round(floatval($row->n5)), round(floatval($row->n6)),
                        round(floatval($row->n7)), round(floatval($row->n8)), round(floatval($row->n9)), 
                        round(floatval($row->n10)), round(floatval($row->n11)), round(floatval($row->n12))
                    ));
                }	
    
                $sqlbox1 = "select 
                sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end) as n2, 
                sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as n4, (sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) - sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end))/sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as persen
                from exs_neraca a
                inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.periode='".$periode."' and b.kode_grafik in ('DB12')
                ";
                $rowAcvp = DB::connection($this->sql2)->select($sqlbox1);
                $success["budpend"] = $rowAcvp[0]->n2;
                $success["actpend"] = $rowAcvp[0]->n4;
                $success["persen"] = round($rowAcvp[0]->persen*100,2);
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getCOGSChart(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;

            $rs = DB::connection($this->sql2)->select("select a.kode_lokasi,
            sum(case when substring(a.periode,5,2)='01' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n1,
            sum(case when substring(a.periode,5,2)='02' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n2,   
            sum(case when substring(a.periode,5,2)='03' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n3,
            sum(case when substring(a.periode,5,2)='04' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n4,
            sum(case when substring(a.periode,5,2)='05' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n5,
            sum(case when substring(a.periode,5,2)='06' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n6,
            sum(case when substring(a.periode,5,2)='07' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n7,
            sum(case when substring(a.periode,5,2)='08' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n8,
            sum(case when substring(a.periode,5,2)='09' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n9,
            sum(case when substring(a.periode,5,2)='10' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n10,
            sum(case when substring(a.periode,5,2)='11' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n11,  
            sum(case when substring(a.periode,5,2) in ('12','13','14','15') then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) n12
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='".substr($periode,0,4)."' and b.kode_grafik='DB15'
            group by a.kode_lokasi");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $series = array();
                foreach ($rs as $row){
                    $series[] = array("name" =>"COGS", "data" => array(
                        round(floatval($row->n1)), round(floatval($row->n2)), round(floatval($row->n3)), 
                        round(floatval($row->n4)), round(floatval($row->n5)), round(floatval($row->n6)),
                        round(floatval($row->n7)), round(floatval($row->n8)), round(floatval($row->n9)), 
                        round(floatval($row->n10)), round(floatval($row->n11)), round(floatval($row->n12))
                    ));
                }	
    
                $sqlbox1 = "select 
                sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end) as n2, 
                sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as n4, (sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) - sum(case a.jenis_akun when 'Pendapatan' then -a.n2 else a.n2 end))/sum(case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end) as persen
                from exs_neraca a
                inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.periode='".$periode."' and b.kode_grafik in ('DB15')
                ";
                $rowAcvp = DB::connection($this->sql)->select($sqlbox1);
                $response["budcogs"] = $rowAcvp[0]->n2;
                $response["actcogs"] = $rowAcvp[0]->n4;
                $response["persen"] = round($rowAcvp[0]->persen*100,2);
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getLapPnj(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;
            $tmp = explode("|",$request->param);
            $per1 = $tmp[0];
            $per2=$tmp[1];
            $kode_klp=$tmp[2];
            $order=$tmp[3];
            $filterper = "";
            if($per1 == ""){
                $filterper.="";
            }else{
                $filterper.=" and a.periode >= '$per1' ";
            }

            if($per2 == ""){
                $filterper.="";
            }else{
                $filterper.=" and a.periode <= '$per2' ";
            }

            if($kode_klp == ""){
                $filter2.="";
            }else{
                $filter2.=" and a.kode_klp = '$kode_klp' ";
            }

            if($order == ""){
                $filter2.="";
            }else{
                $filter2.=" order by $order ";
            }


            $rs = DB::connection($this->sql2)->select("select top 20 a.kode_barang,a.nama,isnull(b.jumlah,0) as jumlah,0 as stok,0 as persen
            from brg_barang a
            left join (select a.kode_barang,a.kode_lokasi,sum(a.jumlah) as jumlah
            from brg_trans_dloc a
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGJUAL' $filterper
            group by a.kode_barang,a.kode_lokasi
                    )b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' 
            $filter2");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getLapVendor(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;
            $tmp = explode("|",$request->param);
            $vendor = $tmp[1];
            $order = $tmp[0];
            $filterper = "";
            if($vendor == ""){
                $filterper.="";
            }else{
                $filterper.=" and a.kode_vendor like '%$vendor%' or a.nama like '%$vendor%' ";
            }

            if($order == ""){
                $filterper.="";
            }else{
                $filterper.=" order by $order ";
            }

            $rs = DB::connection($this->sql)->select("select a.kode_vendor,a.nama,isnull(b.total,0) as total
            from vendor a
            left join (select b.param2,a.kode_lokasi,sum(a.total) as total
            from brg_trans_d a
            inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGBELI'
            group by b.param2,a.kode_lokasi
                    )b on a.kode_vendor=b.param2 and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.total,0)>0
            $filterper ");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getJurnal(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;
            $param = explode("|",$request->param);
            $no_bukti = $param[0];
            $tgl = $param[1];

            $rs = DB::connection($this->sql2)->select("select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.kode_akun,a.keterangan,a.kode_pp,b.nama as nama_akun,a.kode_drk,
            case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
            from gldt a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' 
            union all
            select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.kode_akun,a.keterangan,a.kode_pp,b.nama as nama_akun,a.kode_drk,
            case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
            from gldt_h a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
            order by a.dc desc");
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }    
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


    // function cek(Request $request){
    //     $result = $this->getPosisi($request);
    //     $tmp = json_decode(json_encode($result),true);
    //     $data = $tmp["original"]["success"];
    //     dd($data);
    // }

}
