<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\BarangFisikImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\BarangFisik;


class StockOpnameController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function joinNum($num){
        // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
        $num = str_replace(".", "", $num);
        $num = str_replace(",", ".", $num);
        return $num;
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
 
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
    
            // upload ke folder file_siswa di dalam folder public
            // $file->move('uploads',$nama_file);
    
            // import data
            $del = BarangFisik::where('nik_user',$nik)->where('kode_lokasi',$kode_lokasi)->delete();
            Excel::import(new BarangFisikImport, $nama_file);
            Storage::disk('local')->delete($nama_file);

            $res = DB::connection($this->sql)->select("select nu as no,kode_barang,jumlah from brg_fisik_tmp where kode_lokasi='$kode_lokasi' and nik_user='$nik' order by nu ");
            $res = json_decode(json_encode($res),true);
            $success['data'] = $res;
            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql="select a.no_bukti,convert(varchar,a.tanggal,103) as tanggal,a.keterangan as deskripsi
            from trans_m a inner join brg_gudang b on a.param1=b.kode_gudang and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan_pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi and c.nik='".$nik."' 
            where a.kode_lokasi='".$kode_lokasi."' and a.modul='IV' and a.form='BRGSOP'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function load(Request $request)
    {
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            DB::connection($this->sql)->beginTransaction();

            $del = DB::connection($this->sql)->table('brg_stok_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('nik_user', $nik)
                ->delete();

            $sql=DB::connection($this->sql)->update("insert into brg_stok_tmp (kode_barang,nama_barang,satuan,stok,jumlah,selisih,barcode,kode_lokasi,nik_user,nu)
            select a.kode_barang,a.nama,a.sat_kecil as satuan,isnull(b.stok,0) as stok, 0 as jumlah, 0 as selisih, a.barcode, '$kode_lokasi' as kode_lokasi, '$nik' as nik_user,row_number() over (order by (select NULL)) 
            from brg_barang a 
            inner join brg_stok b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and b.kode_gudang='".$request->kode_gudang."' and b.nik_user='".$nik."' 
            where a.kode_lokasi='".$kode_lokasi."' order by a.kode_barang");

            DB::connection($this->sql)->commit();

            $res = DB::connection($this->sql)->select("select 0 as no,kode_barang,nama_barang as nama,satuan,stok,jumlah,selisih,barcode from brg_stok_tmp where kode_lokasi='".$kode_lokasi."' and nik_user='$nik' ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = true;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

    public function simpanRekon(Request $request)
    {
        $this->validate($request, [
            'kode_barang' => 'required|array',
            'jumlah' => 'required|array'

        ]);        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            if(count($request->kode_barang) > 0){
                DB::connection($this->sql)->beginTransaction();
                for($i=0;$i < count($request->kode_barang);$i++){
                    $upd =  DB::connection($this->sql)->update("update brg_stok_tmp set jumlah=".intval($request->jumlah[$i]).", selisih=stok-".intval($request->jumlah[$i])." where kode_barang='".$request->kode_barang[$i]."' and kode_lokasi='$kode_lokasi' and nik_user='$nik' ");                    
                }

                DB::connection($this->sql)->commit();

                $sql="select 0 as no,kode_barang,nama_barang as nama,satuan,stok,jumlah,selisih,barcode from brg_stok_tmp where kode_lokasi='".$kode_lokasi."' and nik_user='$nik' ";
                $res = DB::connection($this->sql)->select($sql);
                $res = json_decode(json_encode($res),true);
                
                if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                    $success['status'] = true;
                    $success['data'] = $res;
                    $success['message'] = "Success!";     
                }
                else{
                    $success['message'] = "Data Kosong!";
                    $success['data'] = [];
                    $success['status'] = false;
                }
            }else{
                $success['message'] = "Data Barang Fisik Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Rekon data gagal ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $periode = date('Ym');
            $no_bukti= $request->no_bukti;

            $exec = DB::connection($this->sql)->update("exec sp_brg_stok2 '".$periode."','".$kode_lokasi."','".$nik."' ");

            $sql="select no_bukti,tanggal,keterangan,param1,periode from trans_m where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select 0 as no,a.kode_barang,b.barcode, b.nama,a.satuan,c.stok, case dc when 'D' then a.stok+a.jumlah else a.stok-a.jumlah end as jumlah, case dc when 'D' then -a.jumlah else a.jumlah end as selisih 
            from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
            inner join brg_stok c on a.kode_barang=c.kode_barang and a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi and c.nik_user='".$nik."' 
            where a.no_bukti='".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.kode_barang";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function execSP(Request $request)
    {
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $periode = date('Ym');
            $no_bukti= $request->no_bukti;

            $exec = DB::connection($this->sql)->update("exec sp_brg_stok2 '".$periode."','".$kode_lokasi."','".$nik."' ");
            $success['status'] = true;
            $success['message'] = "Sukses";
            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'kode_pp' => 'required',
            'deskripsi' => 'required',
            'kode_gudang' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $str_format="00000";
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $per=substr($periode,2,4);
            $prefix="OP/".$per."/";
            $sql="select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $no_bukti = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $no_bukti = "-";
            }
            
            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','IV','BRGSOP','X','0','0','".$request->kode_pp."','".$request->tanggal."','-','".$request->deskripsi."','IDR',1,0,0,0,'-','-','-','-','-','-','".$request->kode_gudang."','-','-')");

            $det = DB::connection($this->sql)->update("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) 
            select '".$no_bukti."','".$kode_lokasi."','".$periode."','BRGSOP','BRGSOP',nu,'".$request->kode_gudang."',kode_barang,'-',getdate(),satuan,case when selisih > 0 then 'C' else 'D' end as dc,stok,selisih,0,0,0,0,0,0,0 
            from brg_stok_tmp 
            where kode_lokasi ='$kode_lokasi' and nik_user='$nik' order by nu");

            $del3 = DB::connection($this->sql)->table('brg_stok_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $nik)
            ->delete();

            $del4 = DB::connection($this->sql)->table('brg_fisik_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $nik)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Stok Opname berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Stok Opname gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'kode_pp' => 'required',
            'deskripsi' => 'required',
            'kode_gudang' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            $no_bukti = $request->no_bukti;
            $del1 = DB::connection($this->sql)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();
            
            $del2 = DB::connection($this->sql)->table('brg_trans_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','IV','BRGSOP','X','0','0','".$request->kode_pp."','".$request->tanggal."','-','".$request->deskripsi."','IDR',1,0,0,0,'-','-','-','-','-','-','".$request->kode_gudang."','-','-')");

            $det = DB::connection($this->sql)->update("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) 
            select '".$no_bukti."','".$kode_lokasi."','".$periode."','BRGSOP','BRGSOP',nu,'".$request->kode_gudang."',kode_barang,'-',getdate(),satuan,case when selisih > 0 then 'C' else 'D' end as dc,stok,selisih,0,0,0,0,0,0,0 
            from brg_stok_tmp 
            where kode_lokasi ='$kode_lokasi' and nik_user='$nik' order by nu");

            $del3 = DB::connection($this->sql)->table('brg_stok_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $nik)
            ->delete();

            $del4 = DB::connection($this->sql)->table('brg_fisik_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $nik)
            ->delete();
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_jual'] = $id;
            $success['message'] = "Data Stok Opname berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Stok Opname gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $del2 = DB::connection($this->sql)->table('brg_trans_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Stok Opname berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Stok Opname gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


}
