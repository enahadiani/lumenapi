<?php

namespace App\Http\Controllers\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MitraController extends Controller
{   
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getCamat() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_camat,nama from par_camat where kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function isUnik($isi,$kode_lokasi){        
        $auth = DB::connection($this->sql)->select("select kode_mitra from par_mitra where kode_mitra ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_mitra)){
                if($request->kode_mitra == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_mitra='".$request->kode_mitra."' ";
                }
                $sql= "select kode_mitra,nama,alamat,kecamatan,no_tel,pic,no_hp,website,email,status from par_mitra where kode_lokasi='".$kode_lokasi."' ".$filter;
            }
            else {
                $sql = "select kode_mitra,nama,alamat,kecamatan,no_tel,pic,no_hp,website,email,status from par_mitra where kode_lokasi= '".$kode_lokasi."'";
            }

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
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

    public function edit(Request $request)
    {
        $this->validate($request, [
            'kode_mitra' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select * from par_mitra where kode_mitra='".$request->kode_mitra."' and kode_lokasi='".$kode_lokasi."'");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select( "select a.kode_subjenis,a.nama, case when b.kode_subjenis is null then 'NON' else 'CEK' end as status from par_subjenis a left join par_mitra_subjenis b on a.kode_subjenis=b.kode_subjenis and a.kode_lokasi=b.kode_lokasi and b.kode_mitra='".$request->kode_mitra."' where a.kode_lokasi='".$kode_lokasi."' ");
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select( "select * from par_mitra_dok where kode_mitra='".$request->kode_mitra."' and kode_lokasi='".$kode_lokasi."' ");
            $res3 = json_decode(json_encode($res2),true);


            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['arrsub'] = $res2; 
                $success['arrdok'] = $res3;                                
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['arrsub'] = [];   
                $success['arrdok'] = [];                                             
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_mitra' => 'required|max:10',
            'nama' => 'required|max:100',
            'alamat' => 'required|max:200',            
            'no_tel' => 'required|max:50',            
            'kecamatan' => 'required|max:200',            
            'website' => 'required|max:200',            
            'email' => 'required|max:100',            
            'pic' => 'required|max:50', 
            'no_hp' => 'required|max:50', 
            'status' => 'required|max:50',             
            'arrsub'=>'required|array',
            'arrsub.*.kode_subjenis' => 'required',
            'arrdok'=>'required|array',            
            'arrdok.*.nama' => 'required',            
            'arrdok.*.file_dok' => 'required'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_mitra,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into par_mitra(kode_mitra,kode_lokasi,nama,alamat,no_tel,kecamatan,website,email,pic,no_hp,status,nik_user,tgl_input) values 
                                                           ('".$request->kode_mitra."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->no_tel."','".$request->kecamatan."','".$request->website."','".$request->email."','".$request->pic."','".$request->no_hp."','".$request->status."','".$nik."',getdate())");

                $arrsub = $request->arrsub;
                if (count($arrsub) > 0){
                    for ($i=0;$i <count($arrsub);$i++){                
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into par_mitra_subjenis(kode_mitra,kode_subjenis,kode_lokasi) values  
                                                                      ('".$request->kode_mitra."','".$arrsub[$i]['kode_subjenis']."','".$kode_lokasi."')");                    
                    }						
                }	    
                $arrdok = $request->arrdok;
                if (count($arrdok) > 0){
                    for ($i=0;$i <count($arrdok);$i++){                
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into par_mitra_sok(no_urut,kode_mitra,kode_lokasi,nama,file_dok) values  
                                                                        (".$i.",'".$request->kode_mitra."','".$kode_lokasi."','".$arrdok[$i]['nama']."','".$arrdok[$i]['file_dok']."')");                    
                    }						
                }	 

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Mitra berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Mitra sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mitra gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				 
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_mitra' => 'required|max:10',
            'nama' => 'required|max:100',
            'alamat' => 'required|max:200',            
            'no_tel' => 'required|max:50',            
            'kecamatan' => 'required|max:200',            
            'website' => 'required|max:200',            
            'email' => 'required|max:100',            
            'pic' => 'required|max:50', 
            'no_hp' => 'required|max:50', 
            'status' => 'required|max:50',
            'arrsub'=>'required|array',
            'arrsub.*.kode_subjenis' => 'required',
            'arrdok'=>'required|array',            
            'arrdok.*.nama' => 'required',            
            'arrdok.*.file_dok' => 'required'                                   
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('par_mitra')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $del2 = DB::connection($this->sql)->table('par_mitra_subjenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $del3 = DB::connection($this->sql)->table('par_mitra_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into par_mitra(kode_mitra,kode_lokasi,nama,alamat,no_tel,kecamatan,website,email,pic,no_hp,status,nik_user,tgl_input) values 
                                                      ('".$request->kode_mitra."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->no_tel."','".$request->kecamatan."','".$request->website."','".$request->email."','".$request->pic."','".$request->no_hp."','".$request->status."','".$nik."',getdate())");
                                          
            $arrsub = $request->arrsub;
            if (count($arrsub) > 0){
                for ($i=0;$i <count($arrsub);$i++){                
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into par_mitra_subjenis(kode_mitra,kode_subjenis,kode_lokasi) values  
                                                                    ('".$request->kode_mitra."','".$arrsub[$i]['kode_subjenis']."','".$kode_lokasi."')");                    
                }						
            }

            $arrdok = $request->arrdok;
            if (count($arrdok) > 0){
                for ($i=0;$i <count($arrdok);$i++){                
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into par_mitra_sok(no_urut,kode_mitra,kode_lokasi,nama,file_dok) values  
                                                                    (".$i.",'".$request->kode_mitra."','".$kode_lokasi."','".$arrdok[$i]['nama']."','".$arrdok[$i]['file_dok']."')");                    
                }						
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Mitra berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mitra gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_mitra' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('par_mitra')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $del2 = DB::connection($this->sql)->table('par_mitra_subjenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $del3 = DB::connection($this->sql)->table('par_mitra_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Mitra berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mitra gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
