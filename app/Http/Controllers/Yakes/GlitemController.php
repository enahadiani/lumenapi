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
            'data' => 'array', 
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $success['data'] = count($request->data);
            $success['data_list'] = $request->data;
            // $del = DB::connection($this->db)->update("delete from anggaran_d where substring(periode,1,4)='".$request->tahun."' and kode_lokasi='$kode_lokasi' ");

            // for($j=1;$j <= 12;$j++){
            //     $periode = ( $j < 10 ? $request->tahun."0".$j : $request->tahun.$j );
            //     $det[$j] = DB::connection($this->db)->insert("insert into anggaran_d (no_agg,
            //         kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai,nilai_sat,dc,satuan,tgl_input,nik_user,modul,nilai_kas,no_sukka) 
            //         select '$no_bukti',kode_lokasi,$j,kode_pp,kode_akun,'-',1 as volume,'".$periode."' as periode,n".$j." as nilai,n".$j." as nilai,'D','-',getdate(),'$request->nik_user','RRA',0 as nilai_kas,'-'
            //         from anggaran_tmp 
            //         where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user'
            //     ");
            // }
                
            

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data GL Item berhasil disimpan";
            $success['no_bukti'] = $no_bukti;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data GL Item gagal disimpan. Internal Server Error.";
            Log::error($e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
       
    }


}
