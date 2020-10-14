<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JadwalUjianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

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

            $res = DB::connection($this->db)->select("select a.tanggal,a.jam,a.kode_matpel,b.nama,a.kode_tingkat,a.kode_jenis,a.kode_ta,a.kode_sem,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status  from sis_jadwal_ujian a 
            inner join sis_matpel b on a.kode_matpel=b.kode_matpel  
            where a.kode_lokasi='$kode_lokasi' $filter");
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
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_sem' => 'required',
            'kode_tingkat' => 'required',
            'kode_jenis' => 'required',
            'kode_slot.*' => 'required',
            'kode_matpel.*' => 'required',
            'tanggal.*' => 'required',
            'jam.*' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $req = $request->all();

            if (count($req['tanggal']) > 0){
                for ($i=0;$i < count($req['tanggal']);$i++){

                    $ins[$i] = DB::connection($this->db)->insert("insert into sis_jadwal_ujian(kode_pp,kode_lokasi,kode_ta,kode_sem,kode_tingkat,kode_jenis,tanggal,jam,kode_matpel) values (?, ?, ?, ?, ?, ?, ?, ?, ?) ",[$req['kode_pp'],$req['kode_lokasi'],$req['kode_ta'],$req['kode_sem'],$req['kode_tingkat'],$req['kode_jenis'],$req['tanggal'][$i],$req['jam'][$i],$req['kode_matpel'][$i]]);

                }						
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jadwal Ujian berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jadwal Ujian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_sem' => 'required',
            'kode_tingkat' => 'required',
            'kode_jenis' => 'required',
            'kode_slot.*' => 'required',
            'kode_matpel.*' => 'required',
            'tanggal.*' => 'required',
            'jam.*' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('sis_jadwal_ujian')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_ta', $request->kode_ta)
            ->where('kode_sem', $request->kode_sem)
            ->where('kode_tingkat', $request->kode_tingkat)
            ->where('kode_jenis', $request->kode_jenis)
            ->where('kode_pp', $request->kode_pp)
            ->delete();
            
            $req = $request->all();

            if (count($req['tanggal']) > 0){
                for ($i=0;$i < count($req['tanggal']);$i++){

                    $ins[$i] = DB::connection($this->db)->insert("insert into sis_jadwal_ujian(kode_pp,kode_lokasi,kode_ta,kode_sem,kode_tingkat,kode_jenis,tanggal,jam,kode_matpel) values (?, ?, ?, ?, ?, ?, ?, ?, ?) ",[$req['kode_pp'],$req['kode_lokasi'],$req['kode_ta'],$req['kode_sem'],$req['kode_tingkat'],$req['kode_jenis'],$req['tanggal'][$i],$req['jam'][$i],$req['kode_matpel'][$i]]);

                }						
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jadwal Ujian berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jadwal Ujian gagal disimpan ".$e;
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
            'kode_ta' => 'required',
            'kode_matpel' => 'required',
            'nik_guru' => 'required',
            'kode_kelas' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_jadwal_ujian')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_sem', $request->kode_sem)
                ->where('kode_tingkat', $request->kode_tingkat)
                ->where('kode_jenis', $request->kode_jenis)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jadwal Ujian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jadwal Ujian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;
            $kode_ta= $request->kode_ta;

            $res = DB::connection($this->db)->select("select nama, flag_aktif,tgl_mulai,tgl_akhir from sis_ta where kode_ta ='".$kode_ta."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."' 
            ");
            $res = json_decode(json_encode($res),true);
            
            if (count($res) > 0){
                $success['message'] = "Success!";
                $success['data'] = $res;
                $success['status'] = true;
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

}
