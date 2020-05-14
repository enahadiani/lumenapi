<?php

namespace App\Http\Controllers\Aset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class AsetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getGedung(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection('sqlsrv2')->select("select a.kode_pp
                from karyawan a
                where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
                ");
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $kode_pp = $get[0]['kode_pp'];
                }else{
                    $kode_pp = "";
                }
            }

            $sql="SELECT a.id_gedung,a.kode_lokasi,a.nama_gedung,isnull(b.jumlah,0) as jumlah,isnull(b.nilai_perolehan,0) as nilai_perolehan
            from amu_gedung a
            left join (select b.id_gedung,a.kode_lokasi,a.kode_pp,count(a.no_bukti) as jumlah,sum(nilai_perolehan) as nilai_perolehan
                    from amu_asset_bergerak a
                    inner join amu_ruangan b on a.no_ruang=b.no_ruangan and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'
                    group by b.id_gedung,a.kode_lokasi,a.kode_pp
                    )b on a.id_gedung=b.id_gedung and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and isnull(b.jumlah,0)>0 
            order by a.id_gedung";

            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['sql'] = $sql;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getRuangan(Request $request){
        $this->validate($request, [
            'id_gedung' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection('sqlsrv2')->select("select a.kode_pp
                from karyawan a
                where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
                ");
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $kode_pp = $get[0]['kode_pp'];
                }else{
                    $kode_pp = "";
                }
            }

            $id_gedung = $request->id_gedung;

            $sql="SELECT a.no_ruangan,a.kode_lokasi,a.nama_ruangan,isnull(b.jumlah,0) as jumlah,isnull(b.nilai_perolehan,0) as nilai_perolehan
            from amu_ruangan a
            left join (select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jumlah,sum(nilai_perolehan) as nilai_perolehan
                    from amu_asset_bergerak a
                    where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'
                    group by a.no_ruang,a.kode_lokasi
                    )b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and isnull(b.jumlah,0)>0 AND a.id_gedung='$id_gedung'
            order by a.no_ruangan
            ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function getBarang(Request $request){
        $this->validate($request, [
            'id_ruangan' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection('sqlsrv2')->select("select a.kode_pp
                from karyawan a
                where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik."' 
                ");
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $kode_pp = $get[0]['kode_pp'];
                }else{
                    $kode_pp = "";
                }
            }

            $id_ruangan = $request->id_ruangan;
            
          
            $sql="SELECT a.kode_klp,a.kode_lokasi,a.nama_klp,isnull(b.jumlah,0) as jumlah,isnull(b.nilai_perolehan,0) as nilai_perolehan
            from amu_klp_brg a
            left join (select a.kode_klp,a.kode_lokasi,count(a.no_bukti) as jumlah,sum(nilai_perolehan) as nilai_perolehan
                    from amu_asset_bergerak a
                    where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' AND no_ruang='$id_ruangan'
                    group by a.kode_klp,a.kode_lokasi
                    )b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jumlah,0)>0
            order by a.kode_klp ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDaftarPengajuan(Request $request){
        $this->validate($request, [
            'id_ruangan' => 'required',
            'id_gedung' => 'required',
            'kode_klp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }

            $sql="SELECT no_bukti
            ,barcode
            ,no_seri
            ,merk
            ,tipe
            ,warna
            ,satuan
            ,spesifikasi
            ,id_gedung
            ,no_ruang
            ,kode_klp
            ,tanggal_perolehan
            ,kode_lokasi
            ,kode_pp
            ,nilai_perolehan
            ,kd_asset
            ,sumber_dana
            ,nama_inv as nama
            ,foto FROM amu_asset_bergerak a WHERE a.id_gedung='$request->id_gedung' AND a.id_ruangan='$request->id_ruangan' AND a.kode_klp='$request->kode_klp'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDetailBarang(Request $request){
        $this->validate($request, [
            'id_ruangan' => 'required',
            'id_gedung' => 'required',
            'id_nama' => 'required',
            'kode_klp' => 'required',
            'qrcode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }

            $sql="SELECT no_bukti
            ,barcode
            ,no_seri
            ,merk
            ,tipe
            ,warna
            ,satuan
            ,spesifikasi
            ,id_gedung
            ,no_ruang
            ,kode_klp
            ,tanggal_perolehan
            ,kode_lokasi
            ,kode_pp
            ,nilai_perolehan
            ,kd_asset
            ,sumber_dana
            ,nama_inv as nama
            ,foto FROM amu_asset_bergerak a WHERE a.id_gedung='$request->id_gedung' AND a.no_ruang='$request->id_ruangan' AND a.kode_klp='$request->kode_klp' AND a.no_bukti='$request->id_nama'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDaftarBarang(Request $request){
        $this->validate($request, [
            'id_ruangan' => 'required',
            'id_gedung' => 'required',
            'kode_klp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="SELECT no_bukti
            ,barcode
            ,no_seri
            ,merk
            ,tipe
            ,warna
            ,satuan
            ,spesifikasi
            ,id_gedung
            ,no_ruang
            ,kode_klp
            ,tanggal_perolehan
            ,kode_lokasi
            ,kode_pp
            ,nilai_perolehan
            ,kd_asset
            ,sumber_dana
            ,nama_inv as nama
            ,foto FROM amu_asset_bergerak a WHERE a.id_gedung='$request->id_gedung' AND a.no_ruang='$request->id_ruangan' AND a.kode_klp='$request->kode_klp'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    
    function getDataAset(Request $request){
        $this->validate($request, [
            'qrcode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="SELECT no_bukti
            ,barcode
            ,no_seri
            ,merk
            ,tipe
            ,warna
            ,satuan
            ,spesifikasi
            ,id_gedung
            ,no_ruang
            ,kode_klp
            ,tanggal_perolehan
            ,kode_lokasi
            ,kode_pp
            ,nilai_perolehan
            ,kd_asset
            ,sumber_dana
            ,nama_inv as nama
            ,foto FROM amu_asset_bergerak a WHERE a.no_bukti='$request->qrcode'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getPerbaikan(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="select a.mon_id,a.kd_asset,a.id_gedung,a.no_ruangan, case a.status when 'Berfungsi' then 'Baik' else 'Rusak' end as kondisi,a.tgl_input from amu_mon_asset_bergerak a where a.status='Tidak Berfungsi' and a.kode_lokasi='$kode_lokasi'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDetailPerbaikan(Request $request){
        $this->validate($request, [
            'mon_id' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="select b.mon_id,a.nama_inv as nama,a.kd_asset as kode, a.merk, a.warna, a.no_ruang, case status when 'Berfungsi' then 'Baik' when 'Tidak Berfungsi' then 'Rusak' end as kondisi,a.foto 
            from amu_asset_bergerak a
            left join amu_mon_asset_bergerak b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi where b.mon_id= '$request->mon_id' and a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getInvetarisBerjalan(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="select a.no_ruangan, isnull(b.jum_asset,0) as jum_asset, isnull(c.jum_mon,0) as jum_mon,CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 as persen, isnull(b.jum_asset,0) - isnull(c.jum_mon,0) as jum_belum,getdate() as tgl
            from amu_ruangan a
            left join ( select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jum_asset 
                        from amu_asset_bergerak a
                        group by a.no_ruang,a.kode_lokasi
                        ) b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi
            left join ( select a.no_ruangan,a.kode_lokasi,count(a.kd_asset) as jum_mon 
                        from amu_mon_asset_bergerak a
                        inner join amu_asset_bergerak b on a.kd_asset=b.no_bukti
                        group by a.no_ruangan,a.kode_lokasi
                    ) c on a.no_ruangan=c.no_ruangan and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jum_asset,0) <> 0 and CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 <> 100 ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getInvetarisLengkap(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }            
          
            $sql="select a.no_ruangan, isnull(b.jum_asset,0) as jum_asset, isnull(d.jum_rusak,0) as jum_rusak,isnull(c.jum_baik,0) as jum_baik,CAST(((isnull(d.jum_rusak,0)+isnull(c.jum_baik,0)) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 as persen, isnull(b.jum_asset,0) - (isnull(d.jum_rusak,0)+isnull(c.jum_baik,0)) as jum_belum,getdate() as tgl from amu_ruangan a left join ( select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jum_asset from amu_asset_bergerak a group by a.no_ruang,a.kode_lokasi ) b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi left join ( select a.no_ruangan,a.kode_lokasi,count(a.kd_asset) as jum_rusak  from amu_mon_asset_bergerak a inner join amu_asset_bergerak b on a.kd_asset=b.no_bukti where a.status='Tidak Berfungsi' group by a.no_ruangan,a.kode_lokasi) d on a.no_ruangan=d.no_ruangan and a.kode_lokasi=d.kode_lokasi left join ( select a.no_ruangan,a.kode_lokasi,count(a.kd_asset) as jum_baik from amu_mon_asset_bergerak a inner join amu_asset_bergerak b on a.kd_asset=b.no_bukti where a.status='Berfungsi' group by a.no_ruangan,a.kode_lokasi ) c on a.no_ruangan=c.no_ruangan and a.kode_lokasi=c.kode_lokasi where a.kode_lokasi='$kode_lokasi' and isnull(b.jum_asset,0) <> 0 and CAST(((isnull(d.jum_rusak,0)+isnull(c.jum_baik,0)) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 = 100 ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLokasi(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection('sqlsrv2')->select("select a.kode_pp
                from karyawan a
                where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
                ");
                $get = json_decode(json_encode($get),true);
                if(count($get) > 0){
                    $kode_pp = $get[0]['kode_pp'];
                }else{
                    $kode_pp = "";
                }
            }            
          
            $sql="SELECT a.no_ruangan, a.nama_ruangan, a.kode_pp FROM amu_ruangan a WHERE kode_pp='$kode_pp' ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getDaftarAset(Request $request){
        $this->validate($request, [
            'no_ruangan' => 'required',
            'kondisi' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // if(isset($request->kode_pp)){
            //     $kode_pp = $request->kode_pp;
            // }else{

            //     $get = DB::connection('sqlsrv2')->select("select a.kode_pp
            //     from karyawan a
            //     where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' 
            //     ");
            //     $get = json_decode(json_encode($get),true);
            //     if(count($get) > 0){
            //         $kode_pp = $get[0]['kode_pp'];
            //     }else{
            //         $kode_pp = "";
            //     }
            // }  
            
            if($request->kondisi == "All"){
                $filter = "";
            }else if($request->kondisi == "Selesai"){
                $filter = " and b.status in ('Tidak Berfungsi','Berfungsi') ";
            }else if($request->kondisi == "Belum"){
                $filter = " and isnull(b.status,'-') = '-' ";
            }
          
            $sql="select a.foto,a.no_bukti,a.no_ruang,a.nama_inv as nama,a.kd_asset,case b.status when 'Tidak Berfungsi' then 'Rusak' when 'Berfungsi' then 'Baik' else 'Belum diketahui' end as kondisi
            from amu_asset_bergerak a
            left join amu_mon_asset_bergerak b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi
            where a.no_ruang='$no_ruangan' $filter ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanInventaris(Request $request)
    {
        $this->validate($request, [
            'no_ruangan' => 'required',
            'kode_aset' => 'required',
            'kondisi' => 'required',
            'file_gambar' => 'file|max:3072'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_ruangan=$request->no_ruangan;
            $kode_aset=$request->kode_aset;
            $kondisi = $request->kondisi;
            $periode = date('Y').date('m');
            if($kondisi == "Baik"){
                $status = "Berfungsi";
            }else if($kondisi == "Rusak"){
                $status = "Tidak Berfungsi";
            }
            
            $id = $this->generateKode("amu_mon_asset_bergerak", "mon_id", $kode_lokasi."-NMA".$periode.".", "001");

            $sql = "select id_gedung from amu_ruangan where no_ruangan ='$no_ruangan' and kode_lokasi='$kode_lokasi' ";

            $ged = DB::connection('sqlsrv2')->select($sql);
            $ged = json_decode(json_encode($ged),true);
            if(count($ged) > 0){
                $id_gedung = $ged[0]['id_gedung'];
            }else{
                $id_gedung = "";
            }

            if($request->hasfile('file_gambar'))
            {
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('aset/'.$foto)){
                    Storage::disk('s3')->delete('aset/'.$foto);
                }
                Storage::disk('s3')->put('aset/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            $ins = DB::connection('sqlsrv2')->insert("insert into amu_mon_asset_bergerak (mon_id,kd_asset,id_gedung,no_ruangan,status,periode,kode_lokasi,tgl_input,foto) values (?, ?, ?, ?, ?, ?, ?, ?, ?)",[$id,$kode_aset,$id_gedung,$no_ruangan,$status,$periode,$kode_lokasi,date('Y-m-d'),$foto]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Aset berhasil disimpan. Id =".$id;
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aset gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function ubahGambarAset(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'no_urut' => 'required',
            'file_gambar' => 'file|max:3072'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti=$request->no_bukti;

            if($request->hasfile('file_gambar'))
            {
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('aset/'.$foto)){
                    Storage::disk('s3')->delete('aset/'.$foto);
                }
                Storage::disk('s3')->put('aset/'.$foto,file_get_contents($file));

                $sql3="select file_dok as foto from amu_asset_bergerak_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' and no_urut ='$request->no_urut' ";
                $res3 = DB::connection('sqlsrv2')->select($sql3);
                $res3 = json_decode(json_encode($res3),true);

                if(count($res3) > 0 ){
                    if($res3[0]['foto'] != "" && $res3[0]['foto'] != "-"){
                        Storage::disk('s3')->delete('aset/'.$res3[0]['foto']);
                    }
                }
            }else{

                $foto="-";
            }

            $upd3 =  DB::connection('sqlsrv2')->table('amu_asset_bergerak_dok')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_urut', $request->no_urut)
                    ->update(['file_dok' => $foto]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Aset berhasil disimpan ";
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aset gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function uploadDok(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'file_gambar.*'=>'file|max:3072'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection('sqlsrv2')->select("select a.kode_pp
                    from karyawan a
                    where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_pp = $get[0]['kode_pp'];
            }else{
                $kode_pp = "";
            }
            $no_bukti = $request->no_bukti;

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            if($request->hasfile('file_gambar'))
            {
                foreach($request->file('file_gambar') as $file)
                {                
                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('aset/'.$foto)){
                        Storage::disk('s3')->delete('aset/'.$foto);
                    }
                    Storage::disk('s3')->put('aset/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection('sqlsrv2')->insert("insert into amu_asset_bergerak_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_pp) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$i,$arr_foto[$i],$kode_pp]); 
                }
            }

            $success['status'] = true;
            $success['message'] = "Upload Dokumen berhasil disimpan";

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);

            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

}
