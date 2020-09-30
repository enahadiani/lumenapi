<?php

namespace App\Http\Controllers\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

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

            $res2 = DB::connection($this->sql)->select( "select a.kode_subjenis,a.nama, case when b.kode_subjenis is null then 'NON' else 'CEK' end as status from par_subjenis a 
                                                        inner join par_jenis c on a.kode_jenis=c.kode_jenis and a.kode_lokasi=c.kode_lokasi
                                                        inner join par_bidang d on a.kode_jenis=d.kode_jenis and a.kode_lokasi=d.kode_lokasi
                                                        left join par_mitra_subjenis b on a.kode_subjenis=b.kode_subjenis and a.kode_lokasi=b.kode_lokasi and b.kode_mitra='".$request->kode_mitra."' 
                                                        where a.kode_lokasi='".$kode_lokasi."' order by d.kode_bidang");
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select( "select * from par_mitra_dok where kode_mitra='".$request->kode_mitra."' and kode_lokasi='".$kode_lokasi."' ");
            $res3 = json_decode(json_encode($res3),true);


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
            'coor_x' => 'required',             
            'coor_y' => 'required',             
            'arrsub'=>'required|array',
            'arrsub.*.kode_subjenis' => 'required',                                 
            'file.*'=>'file|max:10240'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_mitra,$kode_lokasi)){

                $arr_foto = array();
                $arr_nama = array();
                $i=0;
                if($request->hasfile('file'))
                {
                    foreach($request->file('file') as $file)
                    {                
                        $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                        $foto = $nama_foto;
                        if(Storage::disk('s3')->exists('wisata/'.$foto)){
                            Storage::disk('s3')->delete('wisata/'.$foto);
                        }
                        Storage::disk('s3')->put('wisata/'.$foto,file_get_contents($file));
                        $arr_foto[] = $foto;
                        $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                        $i++;
                    }
                }

                $ins = DB::connection($this->sql)->insert("insert into par_mitra(kode_mitra,kode_lokasi,nama,alamat,no_tel,kecamatan,website,email,pic,no_hp,status,nik_user,tgl_input, coor_x,coor_y) values 
                                                           ('".$request->kode_mitra."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->no_tel."','".$request->kecamatan."','".$request->website."','".$request->email."','".$request->pic."','".$request->no_hp."','".$request->status."','".$nik."',getdate(),'".$request->coor_x."','".$request->coor_y."')");

                $arrsub = $request->arrsub;
                if (count($arrsub) > 0){
                    for ($i=0;$i <count($arrsub);$i++){                
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into par_mitra_subjenis(kode_mitra,kode_subjenis,kode_lokasi) values  
                                                                      ('".$request->kode_mitra."','".$arrsub[$i]['kode_subjenis']."','".$kode_lokasi."')");                    
                    }						
                }	    

                if(count($arr_nama) > 0){
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into par_mitra_dok (kode_lokasi,kode_mitra,nama,no_urut,file_dok) values 
                                                                      (".$kode_lokasi.",'".$request->kode_mitra."','".$arr_nama[$i]."',".$i.",'".$arr_foto[$i]."')"); 
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
            'coor_x' => 'required',             
            'coor_y' => 'required',             
            'arrsub'=>'required|array',
            'arrsub.*.kode_subjenis' => 'required',            
            'file.*'=>'file|max:10240'                                  
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $arr_foto = array();
            $arr_nama = array();
            $arr_foto2 = array();
            $arr_nama2 = array();
            $i=0;
            $cek = $request->file;

            //cek upload file tidak kosong
            if(!empty($cek)){
                if(count($request->nama_file) > 0){
                    //looping berdasarkan nama dok
                    for($i=0;$i<count($request->nama_file);$i++){
                        //cek row i ada file atau tidak
                        if(isset($request->file('file')[$i])){
                            $file = $request->file('file')[$i];

                            //kalo ada cek nama sebelumnya ada atau -
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('wisata/'.$request->nama_file_seb[$i]);
                            }
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('s3')->exists('wisata/'.$foto)){
                                Storage::disk('s3')->delete('wisata/'.$foto);
                            }
                            Storage::disk('s3')->put('wisata/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                        }else{
                            $arr_foto[] = $request->nama_file_seb[$i];
                        }     
                        $arr_nama[] = $request->input('nama_file')[$i];
                        $arr_nama2[] = count($request->nama_file).'|'.$i.'|'.isset($request->file('file')[$i]);
                    }

                    $del3 = DB::connection($this->sql)->table('par_mitra_dok')->where('kode_lokasi', $kode_lokasi)->where('kode_mitra', $request->kode_mitra)->delete();
                }
            }

            $del = DB::connection($this->sql)->table('par_mitra')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $del2 = DB::connection($this->sql)->table('par_mitra_subjenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_mitra', $request->kode_mitra)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into par_mitra(kode_mitra,kode_lokasi,nama,alamat,no_tel,kecamatan,website,email,pic,no_hp,status,nik_user,tgl_input,coor_x,coor_y) values 
                                                      ('".$request->kode_mitra."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->no_tel."','".$request->kecamatan."','".$request->website."','".$request->email."','".$request->pic."','".$request->no_hp."','".$request->status."','".$nik."',getdate(),'".$request->coor_x."','".$request->coor_y."')");
                                          
            $arrsub = $request->arrsub;
            if (count($arrsub) > 0){
                for ($i=0;$i <count($arrsub);$i++){                
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into par_mitra_subjenis(kode_mitra,kode_subjenis,kode_lokasi) values  
                                                                    ('".$request->kode_mitra."','".$arrsub[$i]['kode_subjenis']."','".$kode_lokasi."')");                    
                }						
            }

            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into par_mitra_dok (kode_lokasi,kode_mitra,nama,no_urut,file_dok) values 
                                                                  (".$kode_lokasi.",'".$request->kode_mitra."','".$arr_nama[$i]."',".$i.",'".$arr_foto[$i]."')"); 
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

            $sql3="select kode_mitra,nama,file_dok from par_mitra_dok where kode_lokasi='".$kode_lokasi."' and kode_mitra='".$request->kode_mitra."'  order by no_urut";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('wisata/'.$res3[$i]['file_dok']);
                }
            }

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
