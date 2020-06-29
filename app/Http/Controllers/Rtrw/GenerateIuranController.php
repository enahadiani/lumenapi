<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GenerateIuranController extends Controller
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

    public function getPeriodeNext($periode){
        $tahun = substr($periode,0,4);
        $bulan = intval(substr($periode,4,2))+1;
        if(strlen($bulan) == 1){
            $bulan = "0".$bulan;
        }else{
            $bulan = $bulan;
        }
        return $tahun.$bulan;
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
            $filter = "";
            if(isset($request->periode)){
                if($request->periode == "all" || $request->periode == ""){
                    $filter .= "";
                }else{

                    $filter .= " and a.periode ='$request->periode' ";
                }
            }else{
                $filter .= "";
            }

            $sql = "select a.kode_jenis,a.periode,a.kode_rumah,a.kode_pp,a.nilai_rt,a.nilai_rw 
            from rt_bill_d a 
            where a.kode_lokasi='$kode_lokasi' $filter ";

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
            'kode_jenis' => 'required',
            'tahun' => 'required',
            'bulan_awal' => 'required',
            'bulan_akhir' => 'required',
            'kode_pp' => 'required',
            'nilai_rt' => 'required',
            'nilai_rw' => 'required'
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
            $tahun = $request->tahun;
            $bulan_awal = $request->bulan_awal;
            $bulan_akhir = $request->bulan_akhir;

            $per_awal = $tahun.$bulan_awal;	
            $per_akhir = $tahun.$bulan_akhir;
            $del = DB::connection($this->sql)->table('rt_bill_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->whereBetween('periode', [$per_awal,$per_akhir])
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $res = DB::connection($this->sql)->select("select a.kode_rumah,isnull(b.nama,'-') as penghuni from rt_rumah a left join rt_warga b on a.kode_penghuni=b.nik where a.kode_lokasi='$kode_lokasi' and a.rt='$request->kode_pp' ");
            $res = json_decode(json_encode($res),true);

            for ($i=0;$i<count($res);$i++){									
                $blnAwal = $request->bulan_awal;
                $blnAkhir = $request->bulan_akhir;
                $period = $request->tahun.$request->bulan_awal;		
                
                for ($j=$blnAwal;$j <= $blnAkhir;$j++){	

                    $ins = DB::connection($this->sql)->insert("insert into rt_bill_d (kode_lokasi,kode_jenis,periode,kode_rumah,kode_pp,nilai_rt,nilai_rw) values('".$kode_lokasi."','".$request->kode_jenis."','".$period."','".$res[$i]['kode_rumah']."','".$request->kode_pp."', ".$request->nilai_rt.",".$request->nilai_rw.")");
                    $period = $this->getPeriodeNext($period);		
                }
            }
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Generate Iuran berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Generate Iuran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function getJenis(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $res = DB::connection($this->sql)->select("select kode_jenis,nama from rt_iuran_jenis where jenis='REGULER' and kode_lokasi='".$kode_lokasi."'			 
            ");

            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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

    public function getPPLogin(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where b.nik='".$nik."' and b.kode_lokasi='".$kode_lokasi."'
            ");

            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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

    public function getDetail(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection($this->sql)->select("select a.kode_rumah,isnull(b.nama,'-') as penghuni from rt_rumah a left join rt_warga b on a.kode_penghuni=b.nik where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$request->kode_pp' 
            ");

            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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

}
