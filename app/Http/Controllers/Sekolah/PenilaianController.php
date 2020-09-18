<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PenilaianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrvtarbak')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }    

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_ta)){
                $filter .= " and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_sem)){
                $filter .= " and a.kode_sem='$request->kode_sem' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,a.nu,a.tgl_input
            ,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status   
            from sis_nilai_m a
            where a.kode_lokasi='".$kode_lokasi."' $filter");
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
            'kode_ta' => 'required|date_format:Y-m-d',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kdoe_jenis'=>'required',
            'nis'=>'required|array',
            'nilai'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(count($request->nis) > 0){
                $no_bukti = $this->generateKode("sis_nilai_m", "no_bukti", $kode_lokasi."-BNSIS.", "0001");
                $strSQL = "select COUNT(*)+1 as jumlah from sis_nilai_m where kode_ta= '".$request->kode_ta."' and kode_sem= '".$request->kode_sem."' and kode_kelas= '".$request->kode_kelas."' and kode_matpel= '".$request->kode_matpel."' and kode_jenis= '".$request->kode_jenis."' and kode_pp='$request->kode_pp'";	
                $res = DB::connection('sqlsrvtarbak')->select($strSQL);
                $res = json_decode(json_encode($res),true);
            	if(count($res) > 0){
                    $no_urut = $res[0]['jumlah'];
                }else{
                    $no_urut = 1;
                }

                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$kode_pp));
                for($i=0;$i<count($request->nis);$i++){
                    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                    
                }  
                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil disimpan.";
            }else{
                $sts = true;
                $msg = "Data Penilaian gagal disimpan. Detail Penilaian tidak valid";
            }
            
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal disimpan ".$e;
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
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select * from sis_nilai_m a where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$request->no_bukti'  ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.nis,a.nilai,b.nama from sis_nilai a inner join sis_siswa b on a.nis=b.nis where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti'  ");
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
            'no_bukti' => 'required',
            'kode_ta' => 'required',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis'=>'required',
            'nis'=>'required|array',
            'nilai'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->nis) > 0){
                $no_bukti = $request->no_bukti;
                $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
    
                $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

                $strSQL = "select nu as jumlah from sis_nilai_m where no_bukti='$no_bukti' ";	
                $res = DB::connection('sqlsrvtarbak')->select($strSQL);
                $res = json_decode(json_encode($res),true);
            	if(count($res) > 0){
                    $no_urut = $res[0]['jumlah'];
                }

                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$kode_pp));
                for($i=0;$i<count($request->nis);$i++){
                    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                    
                }  
                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil diubah.";
            }else{
                $sts = true;
                $msg = "Data Penilaian gagal diubah. Detail Penilaian tidak valid";
            }
            
            $success['status'] = $sts;
            $success['message'] = $msg;
     
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal diubah ".$e;
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
            'no_bukti' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Penilaian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function loadSiswa(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select nis,nama from sis_siswa where kode_kelas = '".$request->kode_kelas."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."'");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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

}
