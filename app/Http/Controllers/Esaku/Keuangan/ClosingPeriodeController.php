<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ClosingPeriodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'modul' => 'required|array|max:10',
            'keterangan' => 'required|array|max:50',
            'per_awal1' => 'required|array|max:6',
            'per_akhir1' => 'required|array|max:6',
            'per_awal2' => 'required|array|max:6',
            'per_akhir2' => 'required|array|max:6',
            'per_next' => 'required|max:6',
            'per_aktif' => 'required|max:6'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('periode_aktif')
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            for($i=0; $i < count($request->modul) ; $i++){

                $ins[$i] = DB::connection($this->sql)->insert("insert into periode_aktif(modul,keterangan,per_awal1,per_akhir1,per_awal2,per_akhir2,tgl_input,kode_lokasi,nik_user) values ('".$request->modul[$i]."','".$request->keterangan[$i]."','".$request->per_awal1[$i]."','".$request->per_akhir1[$i]."','".$request->per_awal2[$i]."','".$request->per_akhir2[$i]."',getdate(),'".$kode_lokasi."','".$nik."') ");
            }

            $sql =  "select periode from periode where periode='".$request->per_next."' and kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            if(count($res) == 0){
                $ins2 = DB::connection($this->sql)->insert("insert into periode (periode,keterangan,kode_lokasi) values  ('$request->per_next','Closing Periode $request->per_aktif','$kode_lokasi') ");
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = '-';
            $success['message'] = "Closing Periode berhasil disimpan";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Closing Periode gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select cast(value1 as varchar) as value1 from spro where kode_spro='MAXPRD' and kode_lokasi='".$kode_lokasi."' ");
            if(count($res) > 0){
                if(isset($res[0]->value1)){
                    $maxPeriode = intval($res[0]->value1);
                }else{
                    $maxPeriode = 0;
                }
            }else{
                $maxPeriode = 0;
            }
            
            $res = DB::connection($this->sql)->select("select * from periode_aktif where kode_lokasi='".$kode_lokasi."' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['per_aktif'] = $res[0]["per_awal1"];
                $success['max_periode'] = $maxPeriode;
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['per_aktif'] = 0;
                $success['max_periode'] = $maxPeriode;
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

}
