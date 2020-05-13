<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KalenderAkadController extends Controller
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
            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_sem,a.kode_ta,a.kode_pp+'-'+b.nama as pp 
            from sis_kalender_akad a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' 
            group by a.kode_sem,a.kode_ta,a.kode_pp+'-'+b.nama $filter ");
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
            'tanggal.*' => 'required',
            'agenda.*'=>'required'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(count($data['tanggal']) > 0){

                for($i=0;$i<count($data['tanggal']);$i++){
    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_kalender_akad(kode_pp,kode_lokasi,kode_ta,kode_sem,tanggal,agenda) values (?, ?, ?, ?, ?, ?)', [$request->kode_pp,$kode_lokasi,$request->kode_ta,$request->kode_sem,$request->tanggal[$i],$request->agenda[$i]]);
                    
                }
                
            }
            
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Kalender Akademik berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kalender Akademik gagal disimpan ".$e;
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
            'kode_sem' => 'required',
            'kode_ta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_sem= $request->kode_sem;
            $kode_ta= $request->kode_ta;

            $res = DB::connection('sqlsrvtarbak')->select("select '$kode_ta' as kode_ta, '$kode_sem' as kode_sem, '$kode_pp' as kode_pp ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select tanggal,agenda from sis_kalender_akad 
            where kode_ta='$kode_ta' and kode_sem='$kode_sem' and kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp'");
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
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_sem' => 'required',
            'tanggal.*' => 'required',
            'agenda.*'=>'required'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($data['tanggal']) > 0){
                $del = DB::connection('sqlsrvtarbak')->table('sis_kalender_akad')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_sem', $request->kode_sem)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

                for($i=0;$i<count($data['tanggal']);$i++){
    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_kalender_akad(kode_pp,kode_lokasi,kode_ta,kode_sem,tanggal,agenda) values (?, ?, ?, ?, ?, ?)', [$request->kode_pp,$kode_lokasi,$request->kode_ta,$request->kode_sem,$request->tanggal[$i],$request->agenda[$i]]);
                    
                }
                
            }          
                        
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Kalender Akademik berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kalender Akademik gagal diubah ".$e;
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
            'kode_ta' => 'required',
            'kode_sem' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_kalender_akad')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_sem', $request->kode_sem)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Kalender Akademik berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kalender Akademik gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
