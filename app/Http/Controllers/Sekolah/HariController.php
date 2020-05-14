<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HariController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi,$kode_pp){
        
        $auth = DB::connection('sqlsrvtarbak')->select("select kode_hari from sis_hari where kode_hari ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_pp)){
                $filter = "and kode_pp='$request->kode_pp' ";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select kode_hari, nama,kode_pp from sis_hari
            where kode_lokasi='$kode_lokasi' $filter");
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
            'kode_hari' => 'required',
            'nama' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $filter = "";
            }
            
            $req = $request->all();
            if($this->isUnik($req['kode_hari'],$kode_lokasi,$req['kode_pp'])){

                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_hari(kode_hari,nama,kode_lokasi,kode_pp) values (?, ?, ?, ?) ",[$req['kode_hari'],$req['nama'],$kode_lokasi,$req['kode_pp']]);
                
                DB::connection('sqlsrvtarbak')->commit();
                $success['status'] = true;
                $success['message'] = "Data Hari berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error: Duplicate entry. Kode Hari sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hari gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_hari' => 'required',
            'nama' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection('sqlsrvtarbak')->table('sis_hari')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_hari', $request->kode_hari)
            ->where('kode_pp', $request->kode_pp)
            ->delete();
            
            
            $req = $request->all();
            $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_hari(kode_hari,nama,kode_lokasi,kode_pp) values (?, ?, ?, ?) ",[$req['kode_hari'],$req['nama'],$kode_lokasi,$req['kode_pp']]);

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Hari berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hari gagal disimpan ".$e;
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
            'kode_hari' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_hari')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_hari', $request->kode_hari)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Hari berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hari gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_hari' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;
            $kode_hari= $request->kode_hari;

            $res = DB::connection('sqlsrvtarbak')->select("select kode_hari, nama,kode_pp from sis_hari where kode_hari ='".$kode_hari."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'
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
