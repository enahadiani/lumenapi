<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Imports\GlitemImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 

use Log; 

class GlitemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "yakes";
    public $db = "dbsapkug";

    
    public function store(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required', 
            'data' => 'required|array', 
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(count($request->data) > 0){
                // $del = DB::connection($this->db)->update("delete from exs_glitem where substring(pstng_date,1,6) = '$request->periode' ");
    
                // for($j=1;$j < count($request->data); $j++){
                //     $dt = $request->data[$j];
                //     $ins[$j] = DB::connection($this->db)->insert("insert into exs_glitem (glacc,doc_no,fisc_year,assignment,pstng_date,doc_date,curr,doc_type,bus_area,amount,item_text,cost_ctr,profit_ctr,local_amount,kode_lokasi,tgl_update,tp,dc) 
                //     values ('".$dt["glacc"]."','".$dt["doc_no"]."','".$dt["fisc_year"]."','".$dt["assignment"]."','".$dt["pstng_date"]."','".$dt["doc_date"]."','".$dt["curr"]."','".$dt["doc_type"]."','".$dt["bus_area"]."','".floatval($dt["amount"])."','".$dt["item_text"]."','".$dt["cost_ctr"]."','".$dt["profit_ctr"]."','".floatval($dt["local_amount"])."','".$dt["kode_lokasi"]."','".$dt["tgl_update"]."','".$dt["tp"]."','".$dt["dc"]."')
                //     ");
                // }
                $success['data'] = count($request->data);
    
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data GL Item berhasil disimpan";

            }else{
                $success['status'] = false;
                $success['message'] = "Data tidak valid. Data GL Item gagal disimpan";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data GL Item gagal disimpan. Internal Server Error.";
            Log::error($e);
        }				
      
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file_dok' => 'required|mimes:csv,xls,xlsx',
            'periode' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
    
            // menangkap file excel
            $file = $request->file('file_dok');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new GlitemImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            $success['data'] = count($excel);
            // foreach($excel as $row){
                
            // }
            
            // DB::connection($this->db)->commit();
            // Storage::disk('local')->delete($nama_file);
            // if($status_validate){
            //     $msg = "File berhasil diupload!";
            // }else{
            //     $msg = "Ada error!";
            // }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            // $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
