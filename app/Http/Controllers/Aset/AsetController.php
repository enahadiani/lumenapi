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
    public $db = 'dbaset';
    public $guard = 'aset';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getGedung(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection($this->db)->select("select a.kode_pp
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
            where a.kode_lokasi='$kode_lokasi'  and isnull(b.jumlah,0)>0 
            order by a.id_gedung";

            $res = DB::connection($this->db)->select($sql);
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

    function getRuangan(Request $request){
        $this->validate($request, [
            'id_gedung' => 'required',
            'lantai' => 'array'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter="";

            if ($request->input('lantai') != "") {
                $lantai = $request->input('lantai'); 
                $this_in = "";
                if(count($lantai) > 0){
                    for($x=0;$x<count($lantai);$x++){
                        if($x == 0){
                            $this_in .= "'".$lantai[$x]."'";
                        }else{
                            
                            $this_in .= ","."'".$lantai[$x]."'";
                        }
                    }
                    $filter .= " and a.lantai in ($this_in) ";
                }             
                
            }else{
                $filter .= "";
            }

            

            $id_gedung = $request->id_gedung;

            $sql="SELECT a.no_ruangan,a.kode_lokasi,a.nama_ruangan,isnull(b.jumlah,0) as jumlah,isnull(b.nilai_perolehan,0) as nilai_perolehan
            from amu_ruangan a
            left join (select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jumlah,sum(nilai_perolehan) as nilai_perolehan
                    from amu_asset_bergerak a
                    where a.kode_lokasi='$kode_lokasi' 
                    group by a.no_ruang,a.kode_lokasi
                    )b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jumlah,0)>0 AND a.id_gedung='$id_gedung' $filter
            order by a.no_ruangan
            ";
            $res = DB::connection($this->db)->select($sql);
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

    function getLantai(Request $request){
        $this->validate($request, [
            'id_gedung' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

           
            $id_gedung = $request->id_gedung;

            $sql="select a.lantai,a.nama,a.id_gedung 
                from amu_lantai a 
                where a.kode_lokasi='$kode_lokasi' and a.id_gedung='$id_gedung' ";

            $res = DB::connection($this->db)->select($sql);
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

    function getKlpBarang(Request $request){
        $this->validate($request, [
            'id_gedung' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter="";

            if ($request->input('lantai') != "") {
                $lantai = $request->input('lantai');                
                $filter .= " and b.lantai='$lantai' ";
                
            }else{
                $filter .= "";
            }

            if ($request->input('kode_klp') != "") {
                $kode_klp = $request->input('kode_klp');                
                $filter .= " and a.kode_klp='$kode_klp' ";
                
            }else{
                $filter .= "";
            }

            $id_gedung = $request->id_gedung;

            $sql="select a.kode_klp,a.nama_klp,isnull(b.jumlah,0) as jumlah
            from amu_klp_brg a
            inner join (select a.kode_lokasi,a.kode_klp,count(no_bukti) as jumlah
                        from amu_asset_bergerak a
                        inner join amu_ruangan b on a.no_ruang=b.no_ruangan and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.id_gedung='$id_gedung' $filter
                        group by a.kode_lokasi,a.kode_klp
                        )b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'";

            $res = DB::connection($this->db)->select($sql);
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

    function getGedungPnj(Request $request){
        

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

           
            $id_gedung = $request->id_gedung;

            $sql="select top 1 a.id_gedung,b.nama_gedung 
            from amu_pnj_ruang a
            inner join amu_gedung b on a.id_gedung=b.id_gedung and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.nik='$nik_user'";
                
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2="select a.id_gedung,b.nama_gedung 
            from amu_pnj_ruang a
            inner join amu_gedung b on a.id_gedung=b.id_gedung and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.nik='$nik_user'";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);


            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                //$success['sql'] = $sql;
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['data_gedung'] = $res2;
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection($this->db)->select("select a.kode_pp
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

            if(isset($request->id_gedung)){
                $filter = " and a.id_gedung='$request->id_gedung' ";
            }else{
                $filter = "";
            }
            $id_ruangan = $request->id_ruangan;
            
          
            $sql="SELECT a.kode_klp,a.kode_lokasi,a.nama_klp,isnull(b.jumlah,0) as jumlah,isnull(b.nilai_perolehan,0) as nilai_perolehan
            from amu_klp_brg a
            left join (select a.kode_klp,a.kode_lokasi,count(a.no_bukti) as jumlah,sum(nilai_perolehan) as nilai_perolehan
                    from amu_asset_bergerak a
                    where a.kode_lokasi='$kode_lokasi'  AND no_ruang='$id_ruangan' $filter
                    group by a.kode_klp,a.kode_lokasi
                    )b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jumlah,0)>0
            order by a.kode_klp ";
            $res = DB::connection($this->db)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

          
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
            $res = DB::connection($this->db)->select($sql);
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
            'qrcode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $qrcode=$request->input('qrcode');

            $sql="select a.no_bukti,a.barcode,a.no_seri,a.merk,a.tipe,a.warna,a.satuan,a.spesifikasi,a.id_gedung,a.no_ruang,a.kode_klp,a.tanggal_perolehan,a.kode_lokasi,a.kode_pp,a.nilai_perolehan,
                a.kd_asset,a.sumber_dana,a.nama_inv as nama,b.maxid as mon_id,c.status,c.catatan,convert(varchar(10),c.tgl_input,103) as tgl_inventaris_last,'-' as tindakan, d.jum as jum_inventaris,
                e.nama_gedung,f.nama_ruangan as nama_ruang
            from amu_asset_bergerak a
            inner join amu_gedung e on a.id_gedung=e.id_gedung and a.kode_lokasi=e.kode_lokasi
            inner join amu_ruangan f on a.no_ruang=f.no_ruangan and a.kode_lokasi=f.kode_lokasi
            left join (SELECT kd_asset,kode_lokasi,MAX(mon_id) as maxid
                    FROM amu_mon_asset_bergerak
                    GROUP BY kd_asset,kode_lokasi) b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi
            left join amu_mon_asset_bergerak c on b.maxid=c.mon_id and b.kode_lokasi=c.kode_lokasi
            left join (SELECT kd_asset,kode_lokasi,count(mon_id) as jum
                    FROM amu_mon_asset_bergerak
                    GROUP BY kd_asset,kode_lokasi) d on a.no_bukti=d.kd_asset and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and  a.barcode='$request->qrcode' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2="select a.*
            from amu_asset_bergerak_dok a
            inner join amu_asset_bergerak b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='$kode_lokasi' and  b.barcode='$request->qrcode' ";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['data_gambar'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['data_gambar'] = [];
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
            'id_gedung' => 'required',
            'kode_klp' => 'array'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id_gedung=$request->id_gedung;

            $filter="";

            if ($request->input('no_ruang') != "") {
                $no_ruang = $request->input('no_ruang');                
                $filter .= " and a.no_ruang='$no_ruang' ";
                
            }    
            if ($request->input('lantai') != "") {
                $lantai = $request->input('lantai');                
                $filter .= " and b.lantai='$lantai' ";
                
            } 
            if ($request->input('kode_klp') != "") {
                $kode_klp = $request->input('kode_klp');  
                $this_in = "";
                if(count($kode_klp) > 0){
                    for($x=0;$x<count($kode_klp);$x++){
                        if($x == 0){
                            $this_in .= "'".$kode_klp[$x]."'";
                        }else{
                            
                            $this_in .= ","."'".$kode_klp[$x]."'";
                        }
                    }
                    $filter .= " and a.kode_klp in ($this_in) ";
                }      
                
            } 
          
            $sql="SELECT a.no_bukti,a.barcode,a.no_seri,a.merk,a.tipe,a.warna,a.satuan,a.spesifikasi,a.id_gedung,a.no_ruang,a.kode_klp
                    ,a.tanggal_perolehan,a.kode_lokasi,a.kode_pp,a.nilai_perolehan,a.kd_asset,a.sumber_dana,a.nama_inv as nama
                    --dbo.fnGetBuktiFoto(no_bukti) as foto 
                FROM amu_asset_bergerak a
                inner join amu_ruangan b on a.kode_lokasi=b.kode_lokasi and a.no_ruang=b.no_ruangan 
                WHERE a.kode_lokasi='$kode_lokasi' and a.id_gedung='$id_gedung' $filter ";
            $res = DB::connection($this->db)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.barcode,a.no_seri,a.merk,a.tipe,a.warna,a.satuan,a.spesifikasi,a.id_gedung,a.no_ruang,a.kode_klp,a.tanggal_perolehan,a.kode_lokasi,a.kode_pp,a.nilai_perolehan,
                a.kd_asset,a.sumber_dana,a.nama_inv as nama,b.maxid as mon_id,c.status,c.catatan,convert(varchar(10),c.tgl_input,103) as tgl_inventaris_last,'-' as tindakan, d.jum as jum_inventaris,
                e.nama_gedung,f.nama_ruangan as nama_ruang
            from amu_asset_bergerak a
            inner join amu_gedung e on a.id_gedung=e.id_gedung and a.kode_lokasi=e.kode_lokasi
            inner join amu_ruangan f on a.no_ruang=f.no_ruangan and a.kode_lokasi=f.kode_lokasi
            left join (SELECT kd_asset,kode_lokasi,MAX(mon_id) as maxid
                       FROM amu_mon_asset_bergerak
                       GROUP BY kd_asset,kode_lokasi) b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi
            left join amu_mon_asset_bergerak c on b.maxid=c.mon_id and b.kode_lokasi=c.kode_lokasi
            left join (SELECT kd_asset,kode_lokasi,count(mon_id) as jum
                       FROM amu_mon_asset_bergerak
                       GROUP BY kd_asset,kode_lokasi) d on a.no_bukti=d.kd_asset and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.barcode='$request->qrcode'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2="select a.*
            from amu_asset_bergerak_dok a 
            inner join amu_asset_bergerak b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.barcode='$request->qrcode'";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['data_gambar'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['data_gambar'] = [];
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

           
            $sql="select a.mon_id,a.kd_asset,a.id_gedung,a.no_ruangan, case a.status when 'Berfungsi' then 'Baik' else 'Rusak' end as kondisi,a.tgl_input from amu_mon_asset_bergerak a where a.status='Tidak Berfungsi' and a.kode_lokasi='$kode_lokasi'";
            $res = DB::connection($this->db)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

                
          
            $sql="select b.mon_id,a.nama_inv as nama,a.kd_asset as kode, a.merk, a.warna, a.no_ruang, case status when 'Berfungsi' then 'Baik' when 'Tidak Berfungsi' then 'Rusak' end as kondisi,a.foto 
            from amu_asset_bergerak a
            left join amu_mon_asset_bergerak b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi where b.mon_id= '$request->mon_id' and a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
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

    function getInventarisBerjalan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

               
          
            $sql="select a.no_ruangan, isnull(b.jum_asset,0) as jum_asset, isnull(c.jum_mon,0) as jum_mon,CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 as persen, isnull(b.jum_asset,0) - isnull(c.jum_mon,0) as jum_belum,getdate() as tgl
            from amu_ruangan a
            left join ( select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jum_asset 
                        from amu_asset_bergerak a
                        where a.kode_lokasi='$kode_lokasi'
                        group by a.no_ruang,a.kode_lokasi
                        ) b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi
            left join ( select a.no_ruangan,a.kode_lokasi,count(a.kd_asset) as jum_mon 
                        from amu_mon_asset_bergerak a
                        inner join amu_asset_bergerak b on a.kd_asset=b.no_bukti
                        where a.kode_lokasi='$kode_lokasi'
                        group by a.no_ruangan,a.kode_lokasi
                    ) c on a.no_ruangan=c.no_ruangan and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jum_asset,0) <> 0 and CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 <> 100 ";
            $res = DB::connection($this->db)->select($sql);
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

    function getInventarisLengkap(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

          
            $sql="select a.no_ruangan, isnull(b.jum_asset,0) as jum_asset, isnull(c.jum_mon,0) as jum_mon,CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 as persen, isnull(b.jum_asset,0) - isnull(c.jum_mon,0) as jum_belum,getdate() as tgl 
            from amu_ruangan a 
            left join ( select a.no_ruang,a.kode_lokasi,count(a.no_bukti) as jum_asset 
                        from amu_asset_bergerak a 
                        where a.kode_lokasi='$kode_lokasi'
                        group by a.no_ruang,a.kode_lokasi 
                      ) b on a.no_ruangan=b.no_ruang and a.kode_lokasi=b.kode_lokasi  
            left join ( select a.no_ruangan,a.kode_lokasi,count(a.kd_asset) as jum_mon 
                        from amu_mon_asset_bergerak a 
                        inner join amu_asset_bergerak b on a.kd_asset=b.no_bukti 
                        where a.kode_lokasi='$kode_lokasi'
                        group by a.no_ruangan,a.kode_lokasi 
                      ) c on a.no_ruangan=c.no_ruangan and a.kode_lokasi=c.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' and isnull(b.jum_asset,0) <> 0 and CAST((isnull(c.jum_mon,0) * 1.0 / isnull(b.jum_asset,0)) AS DECIMAL(6,2))*100 = 100 ";
            $res = DB::connection($this->db)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $kode_pp = $request->kode_pp;
            }else{

                $get = DB::connection($this->db)->select("select a.kode_pp
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
          
            $sql="SELECT a.no_ruangan, a.nama_ruangan, a.kode_pp FROM amu_ruangan a WHERE kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_ruangan = $request->no_ruangan;
            $kondisi = $request->kondisi;
            
            if($request->kondisi == "All"){
                $filter = "";
            }else if($request->kondisi == "Selesai"){
                $filter = " and b.status in ('Tidak Berfungsi','Berfungsi') ";
            }else if($request->kondisi == "Belum"){
                $filter = " and isnull(b.status,'-') = '-' ";
            }
          
            $sql="select a.foto,a.no_bukti,a.no_ruang,a.nama_inv as nama,a.kd_asset,case b.status when 'Tidak Berfungsi' then 'Rusak' when 'Berfungsi' then 'Baik' else 'Belum diketahui' end as kondisi,'".url('api/aset/storage')."/'+c.file_dok as foto
            from amu_asset_bergerak a
            left join amu_mon_asset_bergerak b on a.no_bukti=b.kd_asset and a.kode_lokasi=b.kode_lokasi
            left join amu_asset_bergerak_dok c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and c.no_urut = 0
            where a.no_ruang='$no_ruangan' $filter ";
            $res = DB::connection($this->db)->select($sql);
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
            'catatan' => 'required|max:300',
            'file_gambar.*' => 'file|max:3072|image|mimes:jpeg,png,jpg'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
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
            }else if ($kondisi == "Berfungsi"){
                $status = $kondisi;
            }else if ($kondisi == "Tidak Berfungsi"){
                $status = $kondisi;
            }
            
            $id = $this->generateKode("amu_mon_asset_bergerak", "mon_id", $kode_lokasi."-NMA".$periode.".", "001");

            $sql = "select id_gedung from amu_ruangan where no_ruangan ='$no_ruangan' and kode_lokasi='$kode_lokasi' ";

            $ged = DB::connection($this->db)->select($sql);
            $ged = json_decode(json_encode($ged),true);
            if(count($ged) > 0){
                $id_gedung = $ged[0]['id_gedung'];
            }else{
                $id_gedung = "";
            }

          

            $ins = DB::connection($this->db)->insert("insert into amu_mon_asset_bergerak (mon_id,kd_asset,id_gedung,no_ruangan,status,periode,kode_lokasi,tgl_input,foto,catatan) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",[$id,$kode_aset,$id_gedung,$no_ruangan,$status,$periode,$kode_lokasi,date('Y-m-d'),'-',$request->catatan]);

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
                    $arr_nama[] = str_replace(' ', '_', $file->getClientOriginalName());
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_asset_bergerak_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp'
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_asset_bergerak_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_pp) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_pp]); 
                    $no++;
                }
                $sts = true;
                $message = "Upload Dokumen berhasil disimpan";
            }else{
                $sts = false;
                $message = "Tidak ada dokumen yang disimpan";
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Inventaris berhasil disimpan. Id =".$id;
            $success['status_upload'] = $sts;
            $success['message_upload'] = $message;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Inventaris gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
     
    }

    public function ubahGambarAset(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'no_urut' => 'required',
            'file_gambar' => 'required|file|max:3072|image|mimes:jpeg,png,jpg'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
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
                $res3 = DB::connection($this->db)->select($sql3);
                $res3 = json_decode(json_encode($res3),true);

                if(count($res3) > 0 ){
                    if($res3[0]['foto'] != "" && $res3[0]['foto'] != "-"){
                        Storage::disk('s3')->delete('aset/'.$res3[0]['foto']);
                    }
                }
            }else{

                $foto="-";
            }

            $upd3 =  DB::connection($this->db)->table('amu_asset_bergerak_dok')
                    ->where('no_bukti', $no_bukti)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_urut', $request->no_urut)
                    ->update(['file_dok' => $foto]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Aset berhasil disimpan ";
          
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aset gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function uploadDok(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'kode_jenis.*'=>'required',
            'file_gambar.*' => 'required|file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select a.kode_pp
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
            $arr_jenis = array();
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
                    $arr_jenis[] = $request->kode_jenis[$i];
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_asset_bergerak_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $tmp = explode("-",$arr_jenis[$i]);
                    $kode_jenis = $tmp[0];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_asset_bergerak_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_pp,kode_jenis) values (?, ?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_pp,$kode_jenis]); 
                    $no++;
                }
                $success['status'] = true;
                $success['message'] = "Upload Dokumen berhasil disimpan";
            }else{
                
                $success['status'] = false;
                $success['message'] = "Upload Dokumen gagal disimpan";
            }

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);
            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function hapusDok($no_bukti,$no_urut){
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select a.kode_pp
                    from karyawan a
                    where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' ");
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $kode_pp = $get[0]['kode_pp'];
            }else{
                $kode_pp = "";
            }

            $cek = DB::connection($this->db)->select("select a.file_dok
                    from amu_asset_bergerak_dok a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('amu_asset_bergerak_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_pp', $kode_pp)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();

            if($file != ""){
                Storage::disk('s3')->delete('aset/'.$file);
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Delete dokumen berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Delete dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				 
    }

    public function uploadDokSingle(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file'=>'required',
            'file_gambar' => 'required|file|max:3072|image|mimes:jpeg,png,jpg'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select a.kode_pp
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
                $file = $request->file('file_gambar');              
                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('aset/'.$foto)){
                    Storage::disk('s3')->delete('aset/'.$foto);
                }
                Storage::disk('s3')->put('aset/'.$foto,file_get_contents($file));                
            }

            $cek = DB::connection($this->db)->select("
            select no_bukti,count(file_dok) as nomor
            from amu_asset_bergerak_dok 
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
            group by no_bukti");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $no = $cek[0]['nomor'];
            }else{
                $no = 0;
            }
            
            $ins = DB::connection($this->db)->insert("insert into amu_asset_bergerak_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_pp) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$request->nama_file,$no,$foto,$kode_pp]); 
            
            $success['status'] = true;
            $success['message'] = "Upload Dokumen berhasil disimpan";

            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    function getDetailUpload(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;
          
            $sql="select no_bukti,kode_lokasi,no_urut,nama,kode_pp,'".url('api/aset/storage')."/'+file_dok as foto
            from amu_asset_bergerak_dok 
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'
            order by no_bukti,no_urut ";
            $res = DB::connection($this->db)->select($sql);
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

    public function uploadDokLahan(Request $request){

        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'kode_jenis.*'=>'required',
            'file_gambar.*' => 'required|file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        
            $no_bukti = $request->no_bukti;

            $arr_foto = array();
            $arr_nama = array();
            $arr_jenis = array();
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
                    $arr_jenis[] = $request->kode_jenis[$i];
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_lahan_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $tmp = explode("-",$arr_jenis[$i]);
                    $kode_jenis = $tmp[0];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_lahan_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_jenis]); 
                    $no++;
                }
                $success['status'] = true;
                $success['message'] = "Upload Gambar berhasil disimpan";
            }else{
                
                $success['status'] = false;
                $success['message'] = "Upload Gambar gagal disimpan";
            }

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);
            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Gambar gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function hapusDokLahan($no_bukti,$no_urut){
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $get = DB::connection($this->db)->select("select a.kode_pp
            //         from karyawan a
            //         where a.kode_lokasi='$kode_lokasi' and a.nik='".$nik_user."' ");
            // $get = json_decode(json_encode($get),true);
            // if(count($get) > 0){
            //     $kode_pp = $get[0]['kode_pp'];
            // }else{
            //     $kode_pp = "-";
            // }

            $cek = DB::connection($this->db)->select("select a.file_dok
                    from amu_lahan_dok a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('amu_lahan_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();
            // ->where('kode_pp', $kode_pp)

            if($file != ""){
                Storage::disk('s3')->delete('aset/'.$file);
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Delete dokumen berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Delete dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				 
    }

    public function uploadDokGedung(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'kode_jenis.*'=>'required',
            'file_gambar.*' => 'required|file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $arr_foto = array();
            $arr_nama = array();
            $arr_jenis = array();
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
                    $arr_jenis[] = $request->input('kode_jenis')[$i];
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_gedung_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $tmp = explode("-",$arr_jenis[$i]);
                    $kode_jenis = $tmp[0];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_gedung_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_jenis]); 
                    $no++;
                }
                $success['status'] = true;
                $success['message'] = "Upload Gambar berhasil disimpan";
            }else{
                
                $success['status'] = false;
                $success['message'] = "Upload Gambar gagal disimpan";
            }

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);
            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Gambar gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function hapusDokGedung($no_bukti,$no_urut){
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        
            $cek = DB::connection($this->db)->select("select a.file_dok
                    from amu_gedung_dok a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('amu_gedung_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();
            // ->where('kode_pp', $kode_pp)

            if($file != ""){
                Storage::disk('s3')->delete('aset/'.$file);
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Delete dokumen berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Delete dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				 
    }

    public function uploadDokPbb(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'kode_jenis.*'=>'required',
            'file_gambar.*' => 'required|file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

          
            $no_bukti = $request->no_bukti;

            $arr_foto = array();
            $arr_nama = array();
            $arr_jenis = array();
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
                    $arr_jenis[] = $request->kode_jenis[$i];
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_pbb_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $tmp = explode("-",$arr_jenis[$i]);
                    $kode_jenis = $tmp[0];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_pbb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_jenis]); 
                    $no++;
                }
                $success['status'] = true;
                $success['message'] = "Upload Gambar berhasil disimpan";
            }else{
                
                $success['status'] = false;
                $success['message'] = "Upload Gambar gagal disimpan";
            }

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);
            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Gambar gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function hapusDokPbb($no_bukti,$no_urut){
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

           
            $cek = DB::connection($this->db)->select("select a.file_dok
                    from amu_pbb_dok a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('amu_pbb_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();
            // ->where('kode_pp', $kode_pp)

            if($file != ""){
                Storage::disk('s3')->delete('aset/'.$file);
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Delete dokumen berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Delete dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				 
    }

    public function uploadDokImb(Request $request){
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama_file.*'=>'required',
            'kode_jenis.*'=>'required',
            'file_gambar.*' => 'required|file|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

          
            $no_bukti = $request->no_bukti;

            $arr_foto = array();
            $arr_nama = array();
            $arr_jenis = array();
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
                    $arr_jenis[] = $request->kode_jenis[$i];
                    $i++;
                }
            }
    
            if(count($arr_nama) > 0){
                $cek = DB::connection($this->db)->select("
                select no_bukti,count(file_dok) as nomor
                from amu_imb_dok 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' 
                group by no_bukti");
                $cek = json_decode(json_encode($cek),true);
                if(count($cek) > 0){
                    $no = $cek[0]['nomor'];
                }else{
                    $no = 0;
                }
                for($i=0; $i<count($arr_nama);$i++){
                    $tmp = explode("-", $arr_jenis[$i]);
                    $kode_jenis = $tmp[0];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into amu_imb_dok (kode_lokasi,no_bukti,nama,no_urut,file_dok,kode_jenis) values (?, ?, ?, ?, ?, ?) ", [$kode_lokasi,$no_bukti,$arr_nama[$i],$no,$arr_foto[$i],$kode_jenis]); 
                    $no++;
                }
                $success['status'] = true;
                $success['message'] = "Upload Gambar berhasil disimpan";
            }else{
                
                $success['status'] = false;
                $success['message'] = "Upload Gambar gagal disimpan";
            }

            $success['arr_nama'] = $arr_nama;
            $success['count file'] = count($arr_foto);
            DB::connection($this->db)->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Gambar gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function hapusDokImb($no_bukti,$no_urut){
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

       

            $cek = DB::connection($this->db)->select("select a.file_dok
                    from amu_imb_dok a
                    where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('amu_imb_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();
            // ->where('kode_pp', $kode_pp)

            if($file != ""){
                Storage::disk('s3')->delete('aset/'.$file);
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Delete dokumen berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Delete dokumen gagal disimpan. ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				 
    }

    function getMapsGedung(Request $request){
        $this->validate($request,[
            'id_provinsi' => 'array'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->id_gedung)){
                $filter .= " and a.id_gedung = '$request->id_gedung' ";
            }else{
                $filter .= "";
            }

            if(isset($request->nama_gedung)){
                $filter .= " and a.nama_gedung = '$request->nama_gedung' ";
            }else{
                $filter .= "";
            }

            // if(isset($request->id_provinsi)){
            //     $filter .= " and b.id_provinsi = '$request->id_provinsi' ";
            // }else{
            //     $filter .= "";
            // }

            if ($request->input('id_provinsi') != "") {
                $id_provinsi = $request->input('id_provinsi');  
                $this_in = "";
                if(count($id_provinsi) > 0){
                    for($x=0;$x<count($id_provinsi);$x++){
                        if($x == 0){
                            $this_in .= "'".$id_provinsi[$x]."'";
                        }else{
                            
                            $this_in .= ","."'".$id_provinsi[$x]."'";
                        }
                    }
                    $filter .= " and b.id_provinsi in ($this_in) ";
                }      
                
            } 

            $sql="select a.id_gedung,a.nama_gedung,a.coor_y as latitude,a.coor_x as longitude,b.id_provinsi,a.status
            from amu_gedung a
            inner join amu_lahan b on a.id_lahan=b.id_lahan and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getMapsLahan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }


            $filter = "";
            if(isset($request->id_lahan)){
                $filter .= " and id_lahan = '$request->id_lahan' ";
            }else{
                $filter .= "";
            }

            if(isset($request->nama_lahan)){
                $filter .= " and nama_lahan = '$request->nama_lahan' ";
            }else{
                $filter .= "";
            }

            $sql="select id_lahan,nama_lahan,coor_y as latitude,coor_x as longitude 
            from amu_lahan
            where kode_lokasi='$kode_lokasi' $filter ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getMapsGedungDetail(Request $request){
        $this->validate($request,[
            'id_gedung' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.id_gedung,a.nama_gedung,a.alamat,isnull(a.luas_lantai,0) as luas_lantai,isnull(a.luas_lain,0) as luas_lain,isnull(a.jumlah_ruang,0) as jum_ruang,isnull(a.jumlah_lantai,0) as jum_lantai,a.status as status_milik,case a.flag_aktif when 1 then 'Aktif' else 'Non Aktif' end as status_aktif,b.nama as kawasan,isnull(c.file_dok,'-') as file_dok
            from amu_gedung a
			inner join amu_kawasan b on a.id_kawasan=b.id_kawasan and a.kode_lokasi = b.kode_lokasi
			left join amu_gedung_dok c on a.id_gedung=c.no_bukti and a.kode_lokasi=c.kode_lokasi and c.no_urut=0
            where a.kode_lokasi='$kode_lokasi' and a.id_gedung = '$request->id_gedung' ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getMapsLahanDetail(Request $request){
        $this->validate($request,[
            'id_lahan' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.id_lahan,a.nama_lahan,a.alamat,isnull(a.luas,0) as luas,a.status_dokumen as status_milik,case a.flag_aktif when 1 then 'Aktif' else 'Non Aktif' end as status_aktif,b.nama as kawasan,isnull(c.file_dok,'-') as file_dok
            from amu_lahan a
			inner join amu_kawasan b on a.id_kawasan=b.id_kawasan and a.kode_lokasi = b.kode_lokasi
			left join amu_lahan_dok c on a.id_lahan=c.no_bukti and a.kode_lokasi=c.kode_lokasi and c.no_urut=0
            where a.kode_lokasi='$kode_lokasi' and a.id_lahan = '$request->id_lahan' ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getProvinsi(Request $request){
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter="";

            

            $sql="select id,nama from amu_provinsi";

            $res = DB::connection($this->db)->select($sql);
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


}
