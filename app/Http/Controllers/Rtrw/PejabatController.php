<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PejabatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    
    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }
    
    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_lokasi,a.kode_pp,b.nama as nama_pp,a.nama_rt,a.nama_rw,a.flag_aktif,a.no_sk,a.tanggal_sk as tgl_sk,a.cap_rt,a.ttd_rt 
            from rt_jabat a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
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
            'kode_pp' => 'required',
            'nama_rt' => 'required',
            'nama_rw' => 'required',
            'tgl_sk' => 'required',
            'no_sk' => 'required',
            'flag_aktif' => 'required',
            'cap_rt' => 'file|image|mimes:jpeg,png,jpg|max:3092',
            'ttd_rt' => 'file|image|mimes:jpeg,png,jpg|max:3092'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('cap_rt')){
                $file = $request->file('cap_rt');
                
                $nama_cap = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $cap = $nama_cap;
                if(Storage::disk('s3')->exists('rtrw/'.$cap)){
                    Storage::disk('s3')->delete('rtrw/'.$cap);
                }
                Storage::disk('s3')->put('rtrw/'.$cap,file_get_contents($file));
            }else{

                $cap="-";
            }

            if($request->hasfile('ttd_rt')){
                $file = $request->file('ttd_rt');
                
                $nama_ttd = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $ttd = $nama_ttd;
                if(Storage::disk('s3')->exists('rtrw/'.$ttd)){
                    Storage::disk('s3')->delete('rtrw/'.$ttd);
                }
                Storage::disk('s3')->put('rtrw/'.$ttd,file_get_contents($file));
            }else{

                $ttd="-";
            }

            $ins = DB::connection($this->db)->insert("insert into rt_jabat(
                kode_lokasi,kode_pp,nama_rt,nama_rw,tanggal_sk,flag_aktif,no_sk,cap_rt,ttd_rt) values ('".$kode_lokasi."','".$request->kode_pp."','".$request->nama_rt."','".$request->nama_rw."','".$this->reverseDate($request->tgl_sk,"/","-")."','".$request->flag_aktif."','".$request->no_sk."','".$cap."','".$ttd."') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Pejabat RT berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pejabat RT gagal disimpan ".$e;
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
            'kode_pp' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/rtrw/storage');

            $sql = "select a.kode_lokasi,a.kode_pp,b.nama as nama_pp,a.nama_rt,a.nama_rw,a.flag_aktif,a.no_sk,a.tanggal_sk as tgl_sk,case when a.cap_rt != '-' then a.cap_rt else '-' end as cap_rt,case when a.ttd_rt != '-' then a.ttd_rt else '-' end as ttd_rt  
            from rt_jabat a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$request->kode_pp'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['sql'] = $sql;
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
            'kode_pp' => 'required',
            'nama_rt' => 'required',
            'nama_rw' => 'required',
            'tgl_sk' => 'required',
            'no_sk' => 'required',
            'flag_aktif' => 'required',
            'cap_rt' => 'file|image|mimes:jpeg,png,jpg|max:3092',
            'ttd_rt' => 'file|image|mimes:jpeg,png,jpg|max:3092'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('cap_rt')){

                $sql = "select cap_rt from rt_jabat where kode_pp='".$request->kode_pp."' and kode_lokasi='$kode_lokasi'
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $cap = $res[0]['cap_rt'];
                    if($cap != ""){
                        Storage::disk('s3')->delete('rtrw/'.$cap);
                    }
                }else{
                    $cap = "-";
                }
                
                $file = $request->file('cap_rt');
                
                $nama_cap = uniqid()."_".$file->getClientOriginalName();
                $cap = $nama_cap;
                if(Storage::disk('s3')->exists('rtrw/'.$cap)){
                    Storage::disk('s3')->delete('rtrw/'.$cap);
                }
                Storage::disk('s3')->put('rtrw/'.$cap,file_get_contents($file));
                
            }else{

                $cap="-";
            }

            if($request->hasfile('ttd_rt')){

                $sql = "select ttd_rt from rt_jabat where kode_pp='".$request->kode_pp."' and kode_lokasi='$kode_lokasi' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $ttd = $res[0]['ttd_rt'];
                    if($ttd != ""){
                        Storage::disk('s3')->delete('rtrw/'.$ttd);
                    }
                }else{
                    $ttd = "-";
                }
                
                $file = $request->file('ttd_rt');
                
                $nama_ttd = uniqid()."_".$file->getClientOriginalName();
                $ttd = $nama_ttd;
                if(Storage::disk('s3')->exists('rtrw/'.$ttd)){
                    Storage::disk('s3')->delete('rtrw/'.$ttd);
                }
                Storage::disk('s3')->put('rtrw/'.$ttd,file_get_contents($file));
                
            }else{

                $ttd="-";
            }
            
            $del = DB::connection($this->db)->table('rt_jabat')->where('kode_lokasi', $kode_lokasi)->where('kode_pp', $request->kode_pp)->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_jabat(
                kode_lokasi,kode_pp,nama_rt,nama_rw,tanggal_sk,flag_aktif,no_sk,cap_rt,ttd_rt) values ('".$kode_lokasi."','".$request->kode_pp."','".$request->nama_rt."','".$request->nama_rw."','".$this->reverseDate($request->tgl_sk,"/","-")."','".$request->flag_aktif."','".$request->no_sk."','".$cap."','".$ttd."') ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Pejabat RT berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pejabat RT gagal diubah ".$e;
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
        $this->validate($request,[
            'kode_pp' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }            

            $get = DB::connection($this->sql)->select("select cap_rt,ttd_rt from rt_jabat where kode_lokasi='$kode_lokasi' and kode_pp ='$request->kode_pp' ");
            if(count($get) > 0){
                $cap_rt = $get[0]->cap_rt;
                $ttd_rt = $get[0]->ttd_rt;
                if($cap_rt != ""){
                    Storage::disk('s3')->delete('rtrw/'.$cap_rt);
                }
                if($ttd_rt != ""){
                    Storage::disk('s3')->delete('rtrw/'.$ttd_rt);
                }
            }
            
            $del = DB::connection($this->db)->table('rt_jabat')->where('kode_lokasi', $kode_lokasi)->where('kode_pp', $request->kode_pp)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pejabat RT berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pejabat RT gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
