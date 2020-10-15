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
    public $db = "sqlsrvtarbak";
    public $guard = "siswa";

    public function profile()
    {
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $status_login = $data->status_login;

            if($status_login == "S"){
                $user = DB::connection($this->db)->select("select a.nik, a.kode_menu, a.kode_lokasi, a.kode_pp, b.nis, b.nama, isnull(a.foto,'-') as foto, a.status_login, b.kode_kelas, isnull(e.form,'-') as path_view,x.nama as nama_pp,isnull(b.email,'-') as email,isnull(b.hp_siswa,'-')  as no_hp,a.pass,isnull(convert(varchar,b.tgl_lahir,103),'-') as tgl_lahir
                from sis_hakakses a 
                left join sis_siswa b on a.nik=b.nis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                left join m_form e on a.path_view=e.kode_form  
                left join pp x on a.kode_pp=x.kode_pp and a.kode_lokasi=x.kode_lokasi
                where a.nik='$nik' 
                ");
            }else if($status_login == "G"){
                $user = DB::connection($this->db)->select("select a.kode_menu as kode_klp_menu, a.nik, c.nama, a.status_login as status_admin, a.kode_lokasi,b.nama as nmlok, a.kode_pp,d.nama as nama_pp, isnull(a.foto,'-') as foto,isnull(e.form,'-') as path_view,b.logo,c.no_hp,'-' as jabatan
                from sis_hakakses a 
                inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
                inner join sis_guru c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
                inner join pp d on a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi 
                inner join m_form e on a.path_view=e.kode_form 
                where a.nik='$nik' 
                ");
            }else{
                $user = DB::connection($this->db)->select("
                select a.kode_menu as kode_klp_menu, a.nik, c.nama, a.status_login as status_admin, a.kode_lokasi,b.nama as nmlok, a.kode_pp,d.nama as nama_pp, isnull(a.foto,'-') as foto,isnull(e.form,'-') as path_view,b.logo,c.no_hp,'-' as jabatan
                from sis_hakakses a 
                inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
                inner join sis_guru c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
                inner join pp d on a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi 
                inner join m_form e on a.path_view=e.kode_form 
                where a.nik='$nik' 
                union all
                select a.kode_menu as kode_klp_menu, a.nik, c.nama, a.status_login as status_admin, a.kode_lokasi,b.nama as nmlok, a.kode_pp,d.nama as nama_pp, isnull(a.foto,'-') as foto,isnull(e.form,'-') as path_view,b.logo,c.no_hp,'-' as jabatan
                from sis_hakakses a 
                inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
                inner join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
                inner join pp d on a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi 
                inner join m_form e on a.path_view=e.kode_form 
                where a.nik='$nik' 
                ");
            }
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                $periode = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode),true);

                $fs = DB::connection($this->db)->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
                ");
                $fs = json_decode(json_encode($fs),true);

                $ta = DB::connection($this->db)->select("select kode_ta from sis_ta where kode_lokasi='$kode_lokasi' and kode_pp = '".$user[0]['kode_pp']."' and flag_aktif=1
                ");
                $ta = json_decode(json_encode($ta),true);

                return response()->json(['user' => $user,'periode' => $periode, 'kode_fs'=>$fs,'kode_ta'=>$ta], 200);
            }
            else{
                return response()->json(['user' => [],'periode' => [], 'kode_fs'=>[], 'kode_ta'=>[]], 200);
            }
        }else{
            return response()->json(['user' => [],'periode' => [], 'kode_fs'=>[], 'kode_ta'=>[]], 200);
        }
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
         return response()->json(['users' =>  AdminSiswa::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminSiswa::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::guard($this->guard)->payload();
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

            $upd =  DB::connection($this->db)->table('sis_hakakses')
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

    public function updateBackground(Request $request){
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

                $sql = "select background as file_gambar from sis_hakakses where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
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

            $upd =  DB::connection($this->db)->table('sis_hakakses')
            ->where('nik', $nik)
            ->update(['background' => $foto]);
            
            if($upd){ //mengecek apakah data kosong atau tidak
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['foto'] = $foto;
                $success['message'] = "Background berhasil diubah";
                return response()->json($success, 200);     
            }
            else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['foto'] = "-";
                $success['message'] = "Background gagal diubah";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function searchForm(Request $request){
        $this->validate($request,[
            'cari' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $rs = DB::connection($this->db)->select(" select distinct a.kode_form as id,a.nama,c.form 
            from menu a
            inner join m_form c on a.kode_form=c.kode_form
            inner join sis_hakakses b on a.kode_klp=b.kode_menu
            where b.nik='$nik' and a.kode_form<>'-' and a.nama = '$request->cari' 
            ");
            $rs = json_decode(json_encode($rs),true);
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $rs;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], 200);     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], 200);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function searchFormList(Request $request){
        // $this->validate($request,[
        //     'cari' => 'required'
        // ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            if(isset($request->cari)){
                $filter = " and a.nama like '%$request->cari%' ";
            }else{
                $filter = " ";
            }

            $rs = DB::connection($this->db)->select(" select distinct a.kode_form as id,a.nama,c.form 
            from menu a
            inner join m_form c on a.kode_form=c.kode_form
            inner join sis_hakakses b on a.kode_klp=b.kode_menu
            where b.nik='$nik' and a.kode_form<>'-' $filter
            ");
            $rs = json_decode(json_encode($rs),true);
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $rs;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], 200);     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], 200);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function getDaftarPP(Request $request)
    {
        $this->validate($request,[
            'nik' => 'required'
        ]);
        try {
            
            $res = DB::connection('sqlsrvtarbak')->select("SELECT a.kode_pp,a.nama from pp a left join sis_hakakses b on a.kode_pp = b.kode_pp where a.kode_lokasi = '01' and b.nik='$request->nik' order by a.kode_pp
            ");
            $res = json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['pp'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['pp'] = [];
                $success['status'] = true;
            }    
            
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
        
    }

}
