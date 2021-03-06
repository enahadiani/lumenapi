<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KkmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
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
            }
            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter = "";
            }

            $res = DB::connection($this->db)->select("select a.kode_kkm, a.kode_ta,a.kode_tingkat,a.kode_jur,a.kode_pp+'-'+b.nama as pp,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as flag_aktif from sis_kkm a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter group by a.kode_kkm,kode_ta,a.kode_tingkat,a.kode_jur,a.kode_pp+'-'+b.nama,a.tgl_input,a.flag_aktif  ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
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
            'kode_ta' => 'required',
            'kode_tingkat' => 'required',
            'kode_pp' => 'required',
            'kode_jur' => 'required',
            'flag_aktif' => 'required',
            'kode_matpel' => 'required|array',
            'kkm'=>'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode = $this->generateKode("sis_kkm", "kode_kkm", $kode_lokasi."-KKM.", "0001");
            if(count($request->kode_matpel) > 0){
                date_default_timezone_set('Asia/Jakarta');
                $tgl_input = date('Y-m-d H:i:s');
                for($i=0;$i<count($request->kode_matpel);$i++){
    
                    $ins[$i] = DB::connection($this->db)->insert("insert into sis_kkm(kode_kkm,kode_ta,kode_tingkat, kode_matpel,kode_lokasi,kode_pp,kkm,flag_aktif,kode_jur,tgl_input) values ('$kode','$request->kode_ta','$request->kode_tingkat','".$request->kode_matpel[$i]."','$kode_lokasi','$request->kode_pp','".$request->kkm[$i]."','$request->flag_aktif','$request->kode_jur','$tgl_input')");
                    
                }
                
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_kkm'] = $kode;
            $success['message'] = "Data Kkm berhasil disimpan. Kode KKM:".$kode;
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kkm gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kkm' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_kkm= $request->kode_kkm;

            $res = DB::connection($this->db)->select("
            select a.kode_kkm, a.kode_tingkat,a.kode_ta,a.kode_jur,a.flag_aktif,a.kode_pp,b.nama as nama_pp,c.nama as nama_tingkat,d.nama as nama_jur,e.nama as nama_ta, case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as nama_status 
            from sis_kkm a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_tingkat c on a.kode_tingkat=c.kode_tingkat and a.kode_lokasi=c.kode_lokasi
            inner join sis_jur d on a.kode_jur=d.kode_jur and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
            inner join sis_ta e on a.kode_ta=e.kode_ta and a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp
            where a.kode_kkm='$kode_kkm' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'
            group by a.kode_kkm,a.kode_ta,a.kode_tingkat,a.kode_jur,a.flag_aktif,a.kode_pp,b.nama,c.nama,d.nama,e.nama");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select("select a.kode_kkm, a.kode_tingkat, a.kode_matpel,b.nama as nama_matpel, a.kkm from sis_kkm a inner join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp where a.kode_kkm='$kode_kkm' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
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
            'kode_kkm' => 'required',
            'kode_ta' => 'required',
            'kode_tingkat' => 'required',
            'kode_pp' => 'required',
            'kode_jur' => 'required',
            'flag_aktif' => 'required',
            'kode_matpel' => 'required|array',
            'kkm'=>'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->kode_matpel) > 0){
                $del = DB::connection($this->db)->table('sis_kkm')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_kkm', $request->kode_kkm)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

                date_default_timezone_set('Asia/Jakarta');
                $tgl_input = date('Y-m-d H:i:s');
                for($i=0;$i<count($request->kode_matpel);$i++){
                    
                    $ins[$i] = DB::connection($this->db)->insert("insert into sis_kkm(kode_kkm,kode_ta,kode_tingkat, kode_matpel,kode_lokasi,kode_pp,kkm,flag_aktif,kode_jur,tgl_input) values ('$request->kode_kkm','$request->kode_ta','$request->kode_tingkat','".$request->kode_matpel[$i]."','$kode_lokasi','$request->kode_pp','".$request->kkm[$i]."','$request->flag_aktif','$request->kode_jur','$tgl_input' ");                    
                }
                
            }          
                        
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_kkm'] = $request->kode_kkm;
            $success['message'] = "Data Kkm berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kkm gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'kode_pp' => 'required',
            'kode_kkm' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_kkm')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_kkm', $request->kode_kkm)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kkm berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kkm gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
