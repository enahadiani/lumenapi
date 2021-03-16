<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AnggotaController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select no_agg from kop_agg where no_agg ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->no_agg)){
                if($request->no_agg == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_agg='$request->no_agg' ";
                }
                $sql= "select a.no_agg,a.kode_lokasi,a.nama,a.tgl_lahir,a.alamat,a.no_tel,a.bank,a.cabang,a.no_rek,a.nama_rek,a.flag_aktif,a.id_lain,a.email,a.provinsi,a.kota,a.kecamatan,a.kode_pos,a.tgl_input 
                from kop_agg a 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select a.no_agg,a.nama,a.alamat,a.no_tel,a.bank+' - '+a.cabang as bank,a.no_rek+' - '+a.nama_rek as rek,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input from kop_agg a where a.kode_lokasi= '".$kode_lokasi."'";
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
            'no_agg' => 'required|max:20',
            'nama' => 'required|max:50',
            'tgl_lahir'=>'required|date_format:Y-m-d',
            'alamat'=>'required|max:200',
            'no_tel'=>'required|max:50',
            'bank'=>'required|max:50',
            'cabang'=>'required|max:100',
            'no_rek'=>'required|max:50',
            'nama_rek'=>'required|max:50',
            'flag_aktif'=>'required|in:1,0',
            'id_lain'=> 'required|max:20',
            'email' => 'required|max:50',
            'provinsi' => 'required|max:200',
            'kota' => 'required|max:200',
            'kecamatan' => 'required|max:200',
            'kode_pos' => 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_agg,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into kop_agg(no_agg,kode_lokasi,nama,tgl_lahir,alamat,no_tel,bank,cabang,no_rek,nama_rek,flag_aktif,id_lain,tgl_input,email,provinsi,kota,kecamatan,kode_pos) values ('".$request->no_agg."','".$kode_lokasi."','".$request->nama."','".$request->tgl_lahir."','".$request->alamat."','".$request->no_tel."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->flag_aktif."','".$request->id_lain."',getdate(),'".$request->email."','".$request->provinsi."','".$request->kota."','".$request->kecamatan."','".$request->kode_pos."') ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->no_agg;
                $success['message'] = "Data Anggota berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Anggota sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Anggota gagal disimpan ".$e;
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
            'no_agg' => 'required|max:20',
            'nama' => 'required|max:50',
            'tgl_lahir'=>'required|date_format:Y-m-d',
            'alamat'=>'required|max:200',
            'no_tel'=>'required|max:50',
            'bank'=>'required|max:50',
            'cabang'=>'required|max:100',
            'no_rek'=>'required|max:50',
            'nama_rek'=>'required|max:50',
            'flag_aktif'=>'required|in:1,0',
            'id_lain'=> 'required|max:20',
            'email' => 'required|max:50',
            'provinsi' => 'required|max:200',
            'kota' => 'required|max:200',
            'kecamatan' => 'required|max:200',
            'kode_pos' => 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_agg')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_agg', $request->no_agg)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into kop_agg(no_agg,kode_lokasi,nama,tgl_lahir,alamat,no_tel,bank,cabang,no_rek,nama_rek,flag_aktif,id_lain,tgl_input,email,provinsi,kota,kecamatan,kode_pos) values ('".$request->no_agg."','".$kode_lokasi."','".$request->nama."','".$request->tgl_lahir."','".$request->alamat."','".$request->no_tel."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->flag_aktif."','".$request->id_lain."',getdate(),'".$request->email."','".$request->provinsi."','".$request->kota."','".$request->kecamatan."','".$request->kode_pos."') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_agg;
            $success['message'] = "Data Anggota berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Data Anggota gagal diubah ".$e;
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
            'no_agg' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_agg')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_agg', $request->no_agg)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Anggota berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Anggota gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
