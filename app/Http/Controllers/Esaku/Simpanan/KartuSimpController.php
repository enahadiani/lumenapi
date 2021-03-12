<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KartuSimpController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select no_simp from kop_simp_m where no_simp ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function generateNo(Request $request) {
        $this->validate($request, [    
            'kode_param' => 'required',
            'no_simp' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $no_bukti = $this->generateKode("kop_simp_m", "no_simp", $kode_lokasi."-".$request->kode_param.''.$request->no_simp.".", "01");

            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_simp)){
                if($request->no_simp == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_simp='$request->no_simp' ";
                }
                $sql= "select a.no_simp,a.kode_lokasi,a.nama,a.tgl_lahir,a.alamat,a.no_tel,a.bank,a.cabang,a.no_rek,a.nama_rek,a.flag_aktif,a.id_lain 
                from kop_simp_m a 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{

                $sql ="select a.no_simp,a.no_agg,c.nama,a.jenis,a.nilai,a.p_bunga,convert(varchar,a.tgl_tagih,103) as tgl,a.status_bayar,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
                from kop_simp_m a 
                inner join kop_simp_param b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi 			 					 
                inner join kop_agg c on a.no_agg=c.no_agg and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi= '".$kode_lokasi."' ";
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
            'no_simp' => 'required|max:20',
            'nama' => 'required|max:50',
            'tgl_lahir'=>'required|date_format:Y-m-d',
            'alamat'=>'required|max:200',
            'no_tel'=>'required|max:50',
            'bank'=>'required|max:50',
            'cabang'=>'required|max:100',
            'no_rek'=>'required|max:50',
            'nama_rek'=>'required|max:50',
            'flag_aktif'=>'required|in:1,0',
            'id_lain'=> 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_simp,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into kop_simp_m(no_simp,kode_lokasi,nama,tgl_lahir,alamat,no_tel,bank,cabang,no_rek,nama_rek,flag_aktif,id_lain,tgl_input) values ('".$request->no_simp."','".$kode_lokasi."','".$request->nama."','".$request->tgl_lahir."','".$request->alamat."','".$request->no_tel."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->flag_aktif."','".$request->id_lain."',getdate()) ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->no_simp;
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
            'no_simp' => 'required|max:20',
            'nama' => 'required|max:50',
            'tgl_lahir'=>'required|date_format:Y-m-d',
            'alamat'=>'required|max:200',
            'no_tel'=>'required|max:50',
            'bank'=>'required|max:50',
            'cabang'=>'required|max:100',
            'no_rek'=>'required|max:50',
            'nama_rek'=>'required|max:50',
            'flag_aktif'=>'required|in:1,0',
            'id_lain'=> 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_simp_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_simp', $request->no_simp)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into kop_simp_m(no_simp,kode_lokasi,nama,tgl_lahir,alamat,no_tel,bank,cabang,no_rek,nama_rek,flag_aktif,id_lain,tgl_input) values ('".$request->no_simp."','".$kode_lokasi."','".$request->nama."','".$request->tgl_lahir."','".$request->alamat."','".$request->no_tel."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->flag_aktif."','".$request->id_lain."',getdate()) ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_simp;
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
            'no_simp' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_simp_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_simp', $request->no_simp)
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
