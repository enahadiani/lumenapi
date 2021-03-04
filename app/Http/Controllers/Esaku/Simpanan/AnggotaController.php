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
                $sql= "select a.no_agg,a.nama,a.alamat,a.no_tel,a.no_fax,a.email,a.npwp,a.alamat2,a.pic,a.akun_hutang,a.bank,a.cabang,a.no_rek,a.nama_rek,a.no_pictel,b.nama as nama_akun 
                from kop_agg a left join masakun b on a.akun_hutang=b.kode_akun and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ";
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
            'no_agg' => 'required|max:10',
            'nama' => 'required|max:50',
            'alamat' => 'required|max:200',
            'no_tel' => 'required|max:50',
            'email' => 'required|max:50',
            'npwp' => 'required|max:50',
            'pic' => 'required|max:50',
            'alamat2' => 'required|max:200',
            'bank' => 'required|max:50',
            'cabang' => 'required|max:50',
            'no_rek' => 'required|max:50',
            'nama_rek' => 'required|max:50',
            'no_fax' => 'required|max:50',
            'no_pictel' => 'required|max:50',
            'spek' => 'required|max:200',
            'kode_klpkop_agg' => 'required|max:10',
            'penilaian' => 'required|max:50',
            'bank_trans' => 'required|max:50',
            'akun_hutang' => 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_agg,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into kop_agg(no_agg,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpkop_agg,penilaian,bank_trans,akun_hutang,tgl_input) values ('$request->no_agg','$kode_lokasi','$request->nama','$request->alamat',' $request->no_tel',' $request->email','$request->npwp','$request->pic','$request->alamat2','$request->bank','$request->cabang','$request->no_rek','$request->nama_rek','$request->no_fax','$request->no_pictel','$request->spek','$request->kode_klpkop_agg','$request->penilaian','$request->bank_trans','$request->akun_hutang',getdate()) ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->no_agg;
                $success['message'] = "Data kop_agg berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No kop_agg sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data kop_agg gagal disimpan ".$e;
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
            'no_agg' => 'required|max:10',
            'nama' => 'required|max:50',
            'alamat' => 'required|max:200',
            'no_tel' => 'required|max:50',
            'email' => 'required|max:50',
            'npwp' => 'required|max:50',
            'pic' => 'required|max:50',
            'alamat2' => 'required|max:200',
            'bank' => 'required|max:50',
            'cabang' => 'required|max:50',
            'no_rek' => 'required|max:50',
            'nama_rek' => 'required|max:50',
            'no_fax' => 'required|max:50',
            'no_pictel' => 'required|max:50',
            'spek' => 'required|max:200',
            'kode_klpkop_agg' => 'required|max:10',
            'penilaian' => 'required|max:50',
            'bank_trans' => 'required|max:50',
            'akun_hutang' => 'required|max:20'
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

            $ins = DB::connection($this->sql)->insert("insert into kop_agg(no_agg,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpkop_agg,penilaian,bank_trans,akun_hutang,tgl_input) values ('$request->no_agg','$kode_lokasi','$request->nama','$request->alamat',' $request->no_tel',' $request->email','$request->npwp','$request->pic','$request->alamat2','$request->bank','$request->cabang','$request->no_rek','$request->nama_rek','$request->no_fax','$request->no_pictel','$request->spek','$request->kode_klpkop_agg','$request->penilaian','$request->bank_trans','$request->akun_hutang',getdate()) ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_agg;
            $success['message'] = "Data kop_agg berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Data kop_agg gagal diubah ".$e;
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
            $success['message'] = "Data kop_agg berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data kop_agg gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_akun)){
                if($request->kode_akun != "" ){

                    $filter = " and a.kode_akun='$request->kode_akun' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '024' where a.kode_lokasi='$kode_lokasi' $filter ";

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

}
