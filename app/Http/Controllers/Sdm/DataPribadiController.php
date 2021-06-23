<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Menu;

class DataPribadiController extends Controller
{
	public $successStatus = 200;
    public $guard = 'tarbak';
    public $db = 'sqlsrvtarbak';

    public function index(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select nik, kode_lokasi, nama, alamat,  no_telp, email, kode_pp, npwp, bank, cabang, no_rek, nama_rek, grade, kota, kode_pos, no_hp, flag_aktif, foto,kode_sdm,kode_gol,kode_jab,kode_loker,kode_pajak,kode_unit,kode_profesi,kode_agama,jk,tahun_masuk, no_sk,tgl_sk,gelar_depan,gelar_belakang,status_nikah,tgl_nikah,gol_darah,no_kk,kelurahan,kecamatan,ibu_kandung,tempat,convert(varchar,tgl_lahir,23) as tgl_lahir,tgl_masuk,no_ktp,no_bpjs,kode_strata,ijht,bpjs,jp,mk_gol,mk_ytb,tgl_kontrak,no_kontrak 
            from hr_karyawan
            where kode_lokasi ='$kode_lokasi' and nik='$nik' ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nik'=>'required',
            'nama'=>'required',
            'jk'=>'required',
            'kode_agama'=>'required',
            'no_telp'=>'required',
            'no_hp'=>'required',
            'email'=>'required',
            'alamat'=>'required',
            'kota'=>'required',
            'kode_pos'=>'required',
            'kelurahan'=>'required',
            'kecamatan'=>'required',
            'npwp'=>'required',
            'no_ktp'=>'required',
            'no_bpjs'=>'required',
            'kode_profesi'=>'required',
            'kode_strata'=>'required',
            'kode_pajak'=>'required',
            'tempat'=>'required',
            'tgl_lahir'=>'required',
            'no_kk'=>'required',
            'gol_darah'=>'required',
            'status_nikah'=>'required',
            'tgl_nikah'=>'required',
            'ibu_kandung'=>'required',
            'bank'=>'required',
            'cabang'=>'required',
            'no_rek'=>'required',
            'nama_rek'=>'required'
        ]);


        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select foto as file_gambar from hr_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' 
                ";
                $res = DB::connection($this->sql)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('sdm/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sdm/'.$foto)){
                    Storage::disk('s3')->delete('sdm/'.$foto);
                }
                Storage::disk('s3')->put('sdm/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            $del = DB::connection($this->sql)->table('hr_karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $request->nik)->delete();

            $ins = DB::connection($this->sql)->insert("insert into karyawan('nik','nama','jk','kode_agama','no_telp','no_hp','email','alamat','kota','kode_pos','kelurahan','kecamatan','npwp','no_ktp','no_bpjs','kode_profesi','kode_strata','kode_pajak','tempat','tgl_lahir','no_kk','gol_darah','status_nikah','tgl_nikah','ibu_kandung','bank','cabang','no_rek','nama_rek',foto,kode_lokasi) values ('".$request->nik."','".$request->nama."','".$request->jk."','".$request->kode_agama."','".$request->no_telp."','".$request->no_hp."','".$request->email."','-','".$request->alamat."','".$request->kota."','".$request->kode_pos."','".$request->kelurahan."','".$request->kecamatan."','".$request->npwp."','".$request->no_ktp."','".$request->no_bpjs."','".$request->kode_profesi."','".$request->kode_strata."','".$request->kode_pajak."','".$request->tempat."','".$request->tgl_lahir.",'".$request->no_kk."','".$request->gol_darah."','".$request->status_nikah."','".$request->tgl_nikah."','".$request->ibu_kandung."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$foto."','$kode_lokasi') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Pribadi berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pribadi gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getAgama(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_agama,nama from hr_agama where kode_lokasi = '".$kode_lokasi."'  ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getProfesi(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_profesi,nama from hr_profesi where kode_lokasi = '".$kode_lokasi."'  ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getStrata(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_strata,nama from hr_strata where kode_lokasi = '".$kode_lokasi."'  ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getStatusPajak(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_pajak,nama from hr_pajak where kode_lokasi = '".$kode_lokasi."'  ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

        
}
