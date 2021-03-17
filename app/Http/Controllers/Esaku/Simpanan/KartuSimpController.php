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
    public $db = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->db)->select("select no_simp from kop_simp_m where no_simp ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            'no_agg' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $no_bukti = $this->generateKode("kop_simp_m", "no_simp", $kode_lokasi."-".$request->kode_param.''.$request->no_agg.".", "01");

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
                $sql= "select a.no_simp,a.kode_lokasi,a.no_agg,a.kode_param,a.jenis,a.nilai,a.p_bunga,a.tgl_tagih,a.status_bayar,a.periode_gen,a.flag_aktif,a.nik_user,a.tgl_input,a.periode_bunga,b.nama as nama_anggota
                from kop_simp_m a 
                inner join kop_agg b on a.no_agg=b.no_agg and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{

                $sql ="select a.no_simp,a.no_agg,c.nama,a.jenis,a.nilai,a.p_bunga,convert(varchar,a.tgl_tagih,103) as tgl,a.status_bayar,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
                from kop_simp_m a 
                inner join kop_simp_param b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi 			 					 
                inner join kop_agg c on a.no_agg=c.no_agg and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi= '".$kode_lokasi."' ";
            }

            $res = DB::connection($this->db)->select($sql);
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
            'kode_param' => 'required|max:10',
            'jenis' => 'required|max:10',
            'nilai' => 'required',
            'p_bunga' => 'required',
            'tgl_tagih' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|max:10',
            'flag_aktif' => 'required|max:1|in:1,0'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $this->generateKode("kop_simp_m", "no_simp", $kode_lokasi."-".$request->kode_param.''.$request->no_agg.".", "01");
            
            $thnBln = substr($request->tgl_tagih,0,4).''.substr($request->tgl_tagih,5,2);

            $ins = DB::connection($this->db)->insert("insert into kop_simp_m (no_simp,kode_lokasi,no_agg,kode_param,jenis,nilai,p_bunga,tgl_tagih,status_bayar,periode_gen,flag_aktif,nik_user,tgl_input,periode_bunga) values ('$no_bukti','$kode_lokasi','$request->no_agg','$request->kode_param','$request->jenis','".floatval($request->nilai)."','".floatval($request->p_bunga)."','$request->tgl_tagih','$request->status_bayar','$thnBln','$request->flag_aktif','$nik',getdate(),'$thnBln') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Kartu Simpanan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Kartu Simpanan gagal disimpan ".$e;
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
            'no_agg' => 'required|max:20',
            'kode_param' => 'required|max:10',
            'jenis' => 'required|max:10',
            'nilai' => 'required',
            'p_bunga' => 'required',
            'tgl_tagih' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|max:10',
            'flag_aktif' => 'required|max:1|in:1,0'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('kop_simp_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_simp', $request->no_simp)
            ->delete();

            $thnBln = substr($request->tgl_tagih,0,4).''.substr($request->tgl_tagih,5,2);
            
            $ins = DB::connection($this->db)->insert("insert into kop_simp_m (no_simp,kode_lokasi,no_agg,kode_param,jenis,nilai,p_bunga,tgl_tagih,status_bayar,periode_gen,flag_aktif,nik_user,tgl_input,periode_bunga) values ('$request->no_simp','$kode_lokasi','$request->no_agg','$request->kode_param','$request->jenis','".floatval($request->nilai)."','".floatval($request->p_bunga)."','$request->tgl_tagih','$request->status_bayar','$thnBln','$request->flag_aktif','$nik',getdate(),'$thnBln') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $request->no_simp;
            $success['message'] = "Data Kartu Simpanan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Kartu Simpanan gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('kop_simp_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_simp', $request->no_simp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kartu Simpanan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kartu Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
