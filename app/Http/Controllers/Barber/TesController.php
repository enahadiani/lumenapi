<?php

namespace App\Http\Controllers\Barber;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class TesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_barang from brg_barang where kode_barang ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            $url = url('api/toko-auth/storage');
            if(isset($request->kode_barang)){
                if($request->kode_barang == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_barang='$request->kode_barang' ";
                }
                $sql= "select kode_barang,nama,sat_kecil as satuan,hna,pabrik as keterangan,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,case when file_gambar != '-' then '".$url."/'+file_gambar else '-' end as file_gambar,barcode,hrg_satuan,ppn,profit from brg_barang
                where kode_lokasi='".$kode_lokasi."' $filter";
            }else{
                $sql = "select kode_barang,nama,sat_kecil as satuan,hna,pabrik as keterangan,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,case when file_gambar != '-' then '".$url."/'+file_gambar else '-' end as file_gambar,barcode,hrg_satuan,ppn,profit from brg_barang where kode_lokasi= '".$kode_lokasi."'";
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

    /**
     * Show the from for creating a new resource.
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
            'kode_barang' => 'required',
            'nama' => 'required',
            'sat_kecil' => 'required',
            'sat_besar' => 'required',
            'jml_sat' => 'required',
            'hna' => 'required',
            'pabrik' => 'required',
            'flag_aktif' => 'required',
            'ss' => 'required',
            'sm1' => 'required',
            'sm2' => 'required',
            'mm1' => 'required',
            'mm2' => 'required',
            'fm1' => 'required',
            'fm2' => 'required',
            'kode_klp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'barcode' => 'required',
            'hrg_satuan' => 'required',
            'ppn' => 'required',
            'profit' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_barang,$kode_lokasi)){

                if($request->hasfile('file_gambar')){
                    $file = $request->file('file_gambar');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('toko/'.$foto)){
                        Storage::disk('s3')->delete('toko/'.$foto);
                    }
                    Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                }else{
    
                    $foto="-";
                }

                $ins = DB::connection($this->sql)->insert('insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->kode_barang,$request->nama,$kode_lokasi,$request->sat_kecil, $request->sat_besar, $request->jml_sat,$request->hna,$request->pabrik,$request->flag_gen, $request->flag_aktif,$request->ss, $request->sm1, $request->sm2, $request->mm1, $request->mm2, $request->fm1, $request->fm2, $request->kode_klp, $foto, $request->barcode,$request->hrg_satuan, $request->ppn, $request->profit));
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Barang berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Barang sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barang gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
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
            'kode_barang' => 'required',
            'nama' => 'required',
            'sat_kecil' => 'required',
            'sat_besar' => 'required',
            'jml_sat' => 'required',
            'hna' => 'required',
            'pabrik' => 'required',
            'flag_aktif' => 'required',
            'ss' => 'required',
            'sm1' => 'required',
            'sm2' => 'required',
            'mm1' => 'required',
            'mm2' => 'required',
            'fm1' => 'required',
            'fm2' => 'required',
            'kode_klp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'barcode' => 'required',
            'hrg_satuan' => 'required',
            'ppn' => 'required',
            'profit' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_gambar from brg_barang where kode_lokasi='".$kode_lokasi."' and kode_barang='$request->kode_barang' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }
            
            if($request->hasfile('foto')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
                
                $file = $request->file('foto');
                
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('toko/'.$foto)){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
                Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                
            }
            
            $del = DB::connection($this->sql)->table('brg_barang')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            $ins = DB::connection($this->sql)->insert('insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->kode_barang,$request->nama,$kode_lokasi,$request->sat_kecil, $request->sat_besar, $request->jml_sat,$request->hna,$request->pabrik,$request->flag_gen, $request->flag_aktif,$request->ss, $request->sm1, $request->sm2, $request->mm1, $request->mm2, $request->fm1, $request->fm2, $request->kode_klp, $foto, $request->barcode,$request->hrg_satuan, $request->ppn, $request->profit));
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barang berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barang gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
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
            'kode_barang' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select file_gambar from brg_barang where kode_lokasi='".$kode_lokasi."' and kode_barang='$request->kode_barang' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
            }

            $del = DB::connection($this->sql)->table('brg_barang')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barang berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barang gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
