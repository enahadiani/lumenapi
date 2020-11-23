<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

        // DB::connection($this->db)->beginTransaction();
        
        // try {
        //     if($data =  Auth::guard($this->guard)->user()){
        //         $nik= $data->nik;
        //         $kode_lokasi= $data->kode_lokasi;
        //     }

        //     if(count($request->data) > 0){
        //         $del = DB::connection($this->db)->update("delete from exs_glitem where substring(pstng_date,1,6) = '$request->periode' ");
    
        //         for($j=1;$j < count($request->data); $j++){
        //             $dt = $request->data[$j];
        //             $ins[$j] = DB::connection($this->db)->insert("insert into exs_glitem (glacc,doc_no,fisc_year,assignment,pstng_date,doc_date,curr,doc_type,bus_area,amount,item_text,cost_ctr,profit_ctr,local_amount,kode_lokasi,tgl_update,tp,dc) 
        //             values ('".$dt["glacc"]."','".$dt["doc_no"]."','".$dt["fisc_year"]."','".$dt["assignment"]."','".$dt["pstng_date"]."','".$dt["doc_date"]."','".$dt["curr"]."','".$dt["doc_type"]."','".$dt["bus_area"]."','".floatval($dt["amount"])."','".$dt["item_text"]."','".$dt["cost_ctr"]."','".$dt["profit_ctr"]."','".floatval($dt["local_amount"])."','".$dt["kode_lokasi"]."','".$dt["tgl_update"]."','".$dt["tp"]."','".$dt["dc"]."')
        //             ");
        //         }
    
        //         DB::connection($this->db)->commit();
        //         $success['status'] = true;
        //         $success['message'] = "Data GL Item berhasil disimpan";

        //     }else{
        //         $success['status'] = false;
        //         $success['message'] = "Data tidak valid. Data GL Item gagal disimpan";
        //     }
        //     return response()->json(['success'=>$success], $this->successStatus);     
        // } catch (\Throwable $e) {
        //     DB::connection($this->db)->rollback();
        //     $success['status'] = false;
        //     $success['message'] = "Data GL Item gagal disimpan. Internal Server Error.";
        //     Log::error($e);
        // }				
        $success['data'] = $request->data;
        $success['periode'] = $request->periode;
        return response()->json(['success'=>$success], $this->successStatus); 
    }


}
