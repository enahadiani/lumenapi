<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_ta)){
                $filter .= " and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_kelas, a.kode_ta, b.nama,a.tanggal from sis_presensi a inner join sis_kelas b on a.kode_kelas=b.kode_kelas where a.jenis_absen='HARIAN' and a.kode_lokasi='".$kode_lokasi."' $filter group by a.kode_kelas,b.nama,a.kode_ta,a.tanggal");
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
            'tanggal' => 'required|date_format:Y-m-d',  
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_kelas' => 'required',
            'nis' => 'required|array',
            'status'=>'required|array',
            'keterangan'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(count($request->nis) > 0){

                for($i=0;$i<count($request->nis);$i++){
                    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_presensi(nis,kode_kelas,kode_ta,tgl_input,status,kode_lokasi,kode_pp,keterangan,tanggal,jenis_absen) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->nis[$i],$request->kode_kelas,$request->kode_ta,date('Y-m-d H:i:s'),$request->status[$i],$kode_lokasi,$request->kode_pp,$request->keterangan[$i],$request->tanggal,'HARIAN'));
                    
                }  
                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Presensi berhasil disimpan.";
            }else{
                $sts = true;
                $msg = "Data Presensi gagal disimpan. Detail presensi tidak valid";
            }
            
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Presensi gagal disimpan ".$e;
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
            'tanggal' => 'required|date_format=Y-m-d',
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select '$request->kode_kelas' as kode_kelas, '$request->kode_pp' as kode_pp, '$request->kode_ta' as kode_ta, '$request->tanggal' as tanggal ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.nis, a.status,a.keterangan from sis_presensi a  where a.kode_kelas = '".$request->kode_kelas."' and a.kode_ta = '".$request->kode_ta."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$request->kode_pp."' and a.jenis_absen='HARIAN' and a.tanggal='$request->tanggal' ");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
            'tanggal' => 'required|date_format:Y-m-d',  
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_kelas' => 'required',
            'nis' => 'required|array',
            'status'=>'required|array',
            'keterangan'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->nis) > 0){

                $del = DB::connection('sqlsrvtarbak')->table('sis_presensi')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_kelas', $request->kode_kelas)
                ->where('tanggal', $request->tanggal)
                ->where('jenis_absen', 'HARIAN')
                ->delete();

                for($i=0;$i<count($request->nis);$i++){
                    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_presensi(nis,kode_kelas,kode_ta,tgl_input,status,kode_lokasi,kode_pp,keterangan,tanggal,jenis_absen) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->nis[$i],$request->kode_kelas,$request->kode_ta,date('Y-m-d H:i:s'),$request->status[$i],$kode_lokasi,$request->kode_pp,$request->keterangan[$i],$request->tanggal,'HARIAN'));
                    
                }  
                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Presensi berhasil diubah.";
            }else{
                $sts = true;
                $msg = "Data Presensi gagal diubah. Detail presensi tidak valid";
            }
            
            $success['status'] = $sts;
            $success['message'] = $msg;
     
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Presensi gagal diubah ".$e;
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
            'tanggal' => 'required|date_format:Y-m-d',
            'kode_ta' => 'required',
            'kode_kelas' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_presensi')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_kelas', $request->kode_kelas)
                ->where('tanggal', $request->tanggal)
                ->where('jenis_absen', 'HARIAN')
                ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Presensi berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Presensi gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function loadPresensi(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format=Y-m-d',
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select 'HADIR' as stsapp,'-' as ket, nis from sis_siswa where flag_aktif='1' and kode_kelas = '".$request->kode_kelas."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."'");
            $res = json_decode(json_encode($res),true);
            
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

}
