<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\BarangFisikImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 


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
            Excel::import(new BarangFisikImport, $nama_file);
            Storage::disk('local')->delete($nama_file);
            
            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    

}
