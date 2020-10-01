<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KdController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvtarbak';
    public $guard = 'tarbak';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_matpel)){
                $filter .= " and a.kode_mapel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kd)){
                $filter .= " and a.kode_kd='$request->kode_kd' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("
            select a.kode_mapel,a.kode_pp,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp+'-'+a.nama as pp   
            from sis_kd a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter
            group by a.kode_mapel,a.kode_pp,a.tgl_input");
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
        $this->validate($request,[
            'kode_matpel' => 'required',
            'kode_pp' => 'required',
            'kode_kd' => 'array',
            'nama' => 'array'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_mapel,kode_pp from sis_kd where kode_mapel ='$request->kode_matpel' and kode_pp = '$request->kode_pp' and kode_lokasi='$kode_lokasi'");
            $res = json_decode(json_encode($res),true);
            
            if (count($res) > 0){					
                $line = $res[0];				
                $msg = "Transaksi tidak valid. Data KD untuk Kode Mata Pelajaran ".$request->kode_matpel." dan Kode PP ".$request->kode_pp." sudah ada di database";
                $sts = false;						
            }
            else {
                
                date_default_timezone_set('Asia/Jakarta');
                $tgl_input = date('Y-m-d H:i:s');
                if (count($request->kode_kd) > 0){
                    for ($i=0;$i < count($request->kode_kd);$i++){
                        $ins[$i] = DB::connection($this->db)->insert("insert into sis_kd(kode_kd,kode_lokasi,kode_mapel,kode_pp,nama,tgl_input) values ('".$request->kode_kd[$i]."','".$kode_lokasi."','".$request->kode_matpel."','".$request->kode_pp."','".$request->nama[$i]."','".$tgl_input."')");	
                    }				
                }
                
                DB::connection($this->db)->commit();
                $msg = "Data Kd berhasil disimpan";
                $sts = true;	
                
            }			
            $success['kode'] = $request->kode_matpel;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kd gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    public function update(Request $request)
    {
        $this->validate($request,[
            'kode_matpel' => 'required',
            'kode_pp' => 'required',
            'kode_kd' => 'array',
            'nama' => 'array'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('sis_kd')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_pp', $request->kode_pp)
            ->where('kode_mapel', $request->kode_matpel)
            ->delete();
            
            date_default_timezone_set('Asia/Jakarta');
            $tgl_input = date('Y-m-d H:i:s');
            if (count($request->kode_kd) > 0){
                for ($i=0;$i < count($request->kode_kd);$i++){
                    $ins[$i] = DB::connection($this->db)->insert("insert into sis_kd(kode_kd,kode_lokasi,kode_mapel,kode_pp,nama,tgl_input) values ('".$request->kode_kd[$i]."','".$kode_lokasi."','".$request->kode_matpel."','".$request->kode_pp."','".$request->nama[$i]."','".$tgl_input."')");	
                }				
            }

            DB::connection($this->db)->commit();
            $msg = "Data Kd berhasil diubah";
            $sts = true;		
            
            $success['kode'] = $request->kode_matpel;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kd gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_matpel' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_kd')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('kode_mapel', $request->kode_matpel)
                ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data KD berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data KD gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_matpel' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;
            $kode_matpel= $request->kode_matpel;

            $res = DB::connection($this->db)->select(" select a.kode_mapel,a.kode_pp,a.tgl_input,b.nama as nama_matpel,c.nama as nama_pp   
            from sis_kd a
            inner join sis_matpel b on a.kode_mapel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join pp c on a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_mapel='".$kode_matpel."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'
            group by a.kode_mapel,a.kode_pp,a.tgl_input,b.nama,c.nama
            ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("
            select a.kode_kd,a.nama 
            from sis_kd a
            where a.kode_mapel='".$kode_matpel."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'
            order by a.kode_kd
            ");
            $res2 = json_decode(json_encode($res2),true);
            if (count($res) > 0){
                $success['message'] = "Success!";
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['status'] = true;
            } 
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
