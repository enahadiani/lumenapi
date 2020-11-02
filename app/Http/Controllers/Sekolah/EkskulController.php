<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\DataImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 

class EkskulController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }  

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter = "";
            }

            if(isset($request->nis)){
                $filter = "and a.nis='$request->nis' ";
            }else{
                $filter = "";
            }

            if(isset($request->kode_jenis)){
                $filter .= "and a.kode_jenis='$request->kode_jenis' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("a.no_bukti,a.kode_pp,a.nis,a.keterangan,a.kode_jenis,a.tgl_mulai,a.tgl_selesai,a.predikat,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp+'-'+b.nama as pp 
            from sis_ekskul a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."'  $filter ");
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
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nis' => 'required',
            'kode_pp' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'keterangan' => 'required',
            'kode_jenis' => 'required',
            'predikat' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $this->generateKode("sis_ekskul", "no_bukti", $kode_lokasi."-EKS.", "0001");
                
            $ins = DB::connection($this->db)->insert("insert into sis_ekskul(
                no_bukti,nik_user,tgl_input,kode_lokasi,kode_pp,nis,tgl_mulai,tgl_selesai,keterangan,kode_jenis,predikat) values ('$no_bukti','$nik',getdate(),'$kode_lokasi','$request->kode_pp','$request->nis','$request->tgl_mulai','$request->tgl_selesai','$request->keterangan','$request->kode_jenis','$request->predikat' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_jenis;
            $success['message'] = "Data Ekstrakulikuler berhasil disimpan";

            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Ekstrakulikuler gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $no_bukti= $request->no_bukti;

            $sql = "select no_bukti,nik_user,tgl_input,kode_lokasi,kode_pp,nis,tgl_mulai,tgl_selesai,keterangan,kode_jenis,predikat from sis_ekskul where no_bukti ='".$no_bukti."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['sql'] = $sql;
            
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'nis' => 'required',
            'no_bukti' => 'required',
            'kode_pp' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'keterangan' => 'required',
            'kode_jenis' => 'required',
            'predikat' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_ekskul')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into sis_ekskul(
            no_bukti,nik_user,tgl_input,kode_lokasi,kode_pp,nis,tgl_mulai,tgl_selesai,keterangan,kode_jenis,predikat) values ('$request->no_bukti','$nik',getdate(),'$kode_lokasi','$request->kode_pp','$request->nis','$request->tgl_mulai','$request->tgl_selesai','$request->keterangan','$request->kode_jenis','$request->predikat' ");  
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_bukti;
            $success['message'] = "Data Ekstrakulikuler berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Ekstrakulikuler gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_ekskul')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Ekstrakulikuler berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Ekstrakulikuler gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'kode_pp' => 'required',
            'kode_tingkat' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('sis_kd')->where('kode_lokasi', $kode_lokasi)->where('kode_pp', $request->kode_pp)->where('kode_tingkat', $request->kode_tingkat)->delete();

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new DataImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            $i=0;
            foreach($excel as $row){

                $ins[$i] = DB::connection($this->db)->insert("insert into sis_kd (kode_kd,kode_lokasi,kode_matpel,kode_pp,kode_tingkat,nama,tgl_input,kode_sem) values ('".$row[1]."','$kode_lokasi','".$row[0]."','$request->kode_pp','$request->kode_tingkat','".$row[2]."',getdate(),'".$row[3]."') ");
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function importExcelSiswa(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'kode_pp' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
        
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new DataImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            $i=0;
            $success['upd'] = array();
            foreach($excel as $row){
                $upd[$i] = DB::connection($this->db)->update("update sis_siswa set nis2= '".$row[0]."' where nis='".$row[1]."' and kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' ");
                $success['upd'][] = "update sis_siswa set nis2= '".$row[0]."' where nis='".$row[1]."' and kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' ";
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
