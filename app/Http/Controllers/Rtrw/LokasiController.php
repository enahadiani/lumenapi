<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LokasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';

    public function isUnik($isi){
        
        $auth = DB::connection($this->sql)->select("select kode_lokasi from lokasi where kode_lokasi ='".$isi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $len = strlen($str_format)+1;
        $query =DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%' and LEN($kolom_acuan) = $len ");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }
	
    public function index(Request $request)
    {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $url = url('api/rtrw/storage');
            $filter = "";

            $sql = "select kode_lokasi,nama,alamat,kota,kodepos,no_telp,no_fax,flag_konsol,case when logo != '-' then '".$url."/'+logo else '-' end as logo,email,website,npwp,pic,kode_lokkonsol,tgl_pkp,flag_pusat from lokasi ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'kota' => 'required',
            'kodepos' => 'required',
            'no_telp' => 'required',
            'no_fax' => 'required',
            'flag_konsol' => 'required|in:0,1',
            'logo' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required',
            'website' => 'required',
            'npwp' => 'required',
            'pic' => 'required',
            'kode_lokkonsol' => 'required',
            'tgl_pkp' => 'required',
            'flag_pusat' => 'required|in:0,1'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->kode_lokasi)){
                if($request->hasfile('logo')){
                    $file = $request->file('logo');
                    
                    $nama_logo = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $logo = $nama_logo;
                    if(Storage::disk('s3')->exists('rtrw/'.$logo)){
                        Storage::disk('s3')->delete('rtrw/'.$logo);
                    }
                    Storage::disk('s3')->put('rtrw/'.$logo,file_get_contents($file));
                }else{
    
                    $logo="-";
                }

                $ins = DB::connection($this->sql)->insert("insert into lokasi (kode_lokasi,nama,alamat,kota,kodepos,no_telp,no_fax,flag_konsol,logo,email,website,npwp,pic,kode_lokkonsol,tgl_pkp,flag_pusat ) values ('$request->kode_lokasi','$request->nama','$request->alamat','$request->kota','$request->kodepos','$request->no_telp','$request->no_fax','$request->flag_konsol','$logo','$request->email','$request->website','$request->npwp','$request->pic','$request->kode_lokkonsol','$request->tgl_pkp','$request->flag_pusat') ");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Lokasi berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Lokasi sudah ada di database!";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            if(isset($logo)){
                if(Storage::disk('s3')->exists('rtrw/'.$logo)){
                    Storage::disk('s3')->delete('rtrw/'.$logo);
                }
            }
            $success['status'] = false;
            $success['message'] = "Data Lokasi gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
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
        $this->validate($request,[
            'kode_lokasi' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $url = url('api/rtrw/storage');
            $akun = DB::connection($this->sql)->select("select kode_lokasi,nama,alamat,kota,kodepos,no_telp,no_fax,flag_konsol,case when logo != '-' then '".$url."/'+logo else '-' end as logo,email,website,npwp,pic,kode_lokkonsol,tgl_pkp,flag_pusat from lokasi
            where kode_lokasi='$request->kode_lokasi'			 
            ");

            $akun = json_decode(json_encode($akun),true);

            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] =[];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'kota' => 'required',
            'kodepos' => 'required',
            'no_telp' => 'required',
            'no_fax' => 'required',
            'flag_konsol' => 'required|in:0,1',
            'logo' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required',
            'website' => 'required',
            'npwp' => 'required',
            'pic' => 'required',
            'kode_lokkonsol' => 'required',
            'tgl_pkp' => 'required',
            'flag_pusat' => 'required|in:0,1'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->table('lokasi')
            ->where('kode_lokasi',$request->kode_lokasi)
            ->get();
            $logo = $res[0]->logo;
            
            if($request->hasfile('logo')){
                if($logo != "" || $logo != "-"){
                    Storage::disk('s3')->delete('rtrw/'.$logo);
                }
                
                $file = $request->file('logo');
                
                
                $nama_logo = uniqid()."_".$file->getClientOriginalName();
                $logo = $nama_logo;
                if(Storage::disk('s3')->exists('rtrw/'.$logo)){
                    Storage::disk('s3')->delete('rtrw/'.$logo);
                }
                Storage::disk('s3')->put('rtrw/'.$logo,file_get_contents($file));
                
            }

            $del = DB::connection($this->sql)->table('lokasi')->where('kode_lokasi', $kode_lokasi)->delete();

            $ins = DB::connection($this->sql)->insert("insert into lokasi (kode_lokasi,nama,alamat,kota,kodepos,no_telp,no_fax,flag_konsol,logo,email,website,npwp,pic,kode_lokkonsol,tgl_pkp,flag_pusat ) values ('$request->kode_lokasi','$request->nama','$request->alamat','$request->kota','$request->kodepos','$request->no_telp','$request->no_fax','$request->flag_konsol','$logo','$request->email','$request->website','$request->npwp','$request->pic','$request->kode_lokkonsol','$request->tgl_pkp','$request->flag_pusat') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Lokasi berhasil diubah";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Lokasi gagal diubah ".$e;
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
            'kode_lokasi' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->table('lokasi')
            ->where('kode_lokasi',$request->kode_lokasi)
            ->get();
            $logo = $res[0]->logo;

            if(Storage::disk('s3')->exists('rtrw/'.$logo)){
                Storage::disk('s3')->delete('rtrw/'.$logo);
            }
            
            $del = DB::connection($this->sql)->table('lokasi')->where('kode_lokasi', $request->kode_lokasi)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Lokasi berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Lokasi gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
