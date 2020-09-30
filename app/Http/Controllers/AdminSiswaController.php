<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use  App\AdminSiswa;
use Illuminate\Support\Facades\Storage; 

class AdminSiswaController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware();
    // }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile()
    {
        if($data =  Auth::guard('siswa')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $user = DB::connection('sqlsrvtarbak')->select("select a.nik, a.kode_menu, a.kode_lokasi, a.kode_pp, b.nis, b.nama, isnull(a.foto,'-') as foto, a.status_login, b.kode_kelas, isnull(e.form,'-') as path_view,x.nama as nama_pp,isnull(b.email,'-') as email,isnull(b.hp_siswa,'-')  as no_hp,a.pass,isnull(convert(varchar,b.tgl_lahir,103),'-') as tgl_lahir
            from sis_hakakses a 
            left join sis_siswa b on a.nik=b.nis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join m_form e on a.path_view=e.kode_form  
            left join pp x on a.kode_pp=x.kode_pp and a.kode_lokasi=x.kode_lokasi
            where a.nik='$nik' 
            ");
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                $periode = DB::connection('sqlsrvtarbak')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode),true);

                $fs = DB::connection('sqlsrvtarbak')->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
                ");
                $fs = json_decode(json_encode($fs),true);

                return response()->json(['user' => $user,'periode' => $periode, 'kode_fs'=>$fs], 200);
            }
            else{
                return response()->json(['user' => [],'periode' => [], 'kode_fs'=>[]], 200);
            }
        }else{
            return response()->json(['user' => [],'periode' => [], 'kode_fs'=>[]], 200);
        }
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
         return response()->json(['users' =>  AdminTarbak::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminTarbak::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::guard('siswa')->payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }

    public function updatePassword(Request $request){
        $this->validate($request,[
            'password_lama' => 'required',
            'password_baru' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->beginTransaction();

            $cek =  DB::connection($this->db)->select("select pass from sis_hakakses where nik='$nik' and pass='$request->password_lama' ");
            if(count($cek) > 0){

                $upd =  DB::connection($this->db)->table('hakakses')
                ->where('nik', $nik)
                ->where('pass', $request->password_lama)
                ->update(['pass' => $request->password_baru, 'password' => app('hash')->make($request->password_baru)]);
                
                if($upd){ //mengecek apakah data kosong atau tidak
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Password berhasil diubah";
                    return response()->json($success, 200);     
                }
                else{
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['message'] = "Password gagal diubah";
                    return response()->json($success, 200);
                }
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = "Password lama tidak valid";
                return response()->json($success, 200);
            }
        }catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function updatePhoto(Request $request){
        $this->validate($request,[
            'foto' => 'required|image|mimes:jpeg,png,jpg'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->beginTransaction();

            if($request->hasfile('foto')){

                $sql = "select foto as file_gambar from sis_hakakses where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('sekolah/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                    Storage::disk('s3')->delete('sekolah/'.$foto);
                }
                Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $upd =  DB::connection($this->db)->table('karyawan')
            ->where('nik', $nik)
            ->update(['foto' => $foto]);
            
            if($upd){ //mengecek apakah data kosong atau tidak
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['foto'] = $foto;
                $success['message'] = "Foto berhasil diubah";
                return response()->json($success, 200);     
            }
            else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['foto'] = "-";
                $success['message'] = "Foto gagal diubah";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }
}
