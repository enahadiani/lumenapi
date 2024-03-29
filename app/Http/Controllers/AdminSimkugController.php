<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\AdminSimkug;
use Illuminate\Support\Facades\Storage; 

class AdminSimkugController extends Controller
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
        if($data =  Auth::guard('simkug')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $user = DB::connection('dbsimkug')->select("select a.kode_menu_lab as kode_klp_menu, a.nik, a.nama, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
			b.kode_lokkonsol,d.kode_bidang, c.foto,isnull(e.form,'-') as path_view,b.logo,c.no_telp,c.jabatan,a.flag_menu,isnull(c.email,'-') as email,a.pass as password,isnull(c.background,'-') as backgroundi
            from hakakses a 
            inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
            left join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
            left join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi 
            left join m_form e on a.menu_mobile=e.kode_form 
            where a.nik= ? 
            ",[$nik]);
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                $periode = DB::connection('dbsimkug')->select("select max(periode) as periode from periode where kode_lokasi=?",[$kode_lokasi]);
                $periode = json_decode(json_encode($periode),true);

                $fs = DB::connection('dbsimkug')->select("select kode_fs from fs where kode_lokasi=?",[$kode_lokasi]);
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
         return response()->json(['users' =>  AdminSimkug::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminSimkug::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::guard('simkug')->payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }

    public function updatePassword(Request $request){
        $this->validate($request,[
            'password_lama' => 'required',
            'password_baru' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection('dbsimkug')->beginTransaction();

            $cek =  DB::connection('dbsimkug')->select("select pass from hakakses where nik=? and pass=? ",[$nik,$request->input('password_lama')]);
            if(count($cek) > 0){

                $upd =  DB::connection('dbsimkug')->table('hakakses')
                ->where('nik', $nik)
                ->where('pass', $request->input('password_lama'))
                ->update(['pass' => $request->input('password_baru'), 'password' => app('hash')->make($request->input('password_baru'))]);
                
                if($upd){ //mengecek apakah data kosong atau tidak
                    DB::connection('dbsimkug')->commit();
                    $success['status'] = true;
                    $success['message'] = "Password berhasil diubah";
                    return response()->json($success, 200);     
                }
                else{
                    DB::connection('dbsimkug')->rollback();
                    $success['status'] = false;
                    $success['message'] = "Password gagal diubah";
                    return response()->json($success, 200);
                }
            }else{
                DB::connection('dbsimkug')->rollback();
                $success['status'] = false;
                $success['message'] = "Password lama tidak valid";
                return response()->json($success, 200);
            }
        }catch (\Throwable $e) {
            
            DB::connection('dbsimkug')->rollback();
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
            
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection('dbsimkug')->beginTransaction();

            if($request->hasfile('foto')){

                $sql = "select foto as file_gambar from karyawan where kode_lokasi=? and nik=? 
                ";
                $res = DB::connection('dbsimkug')->select($sql,[$kode_lokasi,$nik]);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('simkug/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('simkug/'.$foto)){
                    Storage::disk('s3')->delete('simkug/'.$foto);
                }
                Storage::disk('s3')->put('simkug/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $upd =  DB::connection('dbsimkug')->table('karyawan')
            ->where('nik', $nik)
            ->update(['foto' => $foto]);
            
            if($upd){ //mengecek apakah data kosong atau tidak
                DB::connection('dbsimkug')->commit();
                $success['status'] = true;
                $success['foto'] = $foto;
                $success['message'] = "Foto berhasil diubah";
                return response()->json($success, 200);     
            }
            else{
                DB::connection('dbsimkug')->rollback();
                $success['status'] = false;
                $success['foto'] = "-";
                $success['message'] = "Foto gagal diubah";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection('dbsimkug')->rollback();
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
            
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection('dbsimkug')->beginTransaction();

            if($request->hasfile('foto')){

                $sql = "select background as file_gambar from karyawan where kode_lokasi=? and nik=? 
                ";
                $res = DB::connection('dbsimkug')->select($sql,[$kode_lokasi,$nik]);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('simkug/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('simkug/'.$foto)){
                    Storage::disk('s3')->delete('simkug/'.$foto);
                }
                Storage::disk('s3')->put('simkug/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $upd =  DB::connection('dbsimkug')->table('karyawan')
            ->where('nik', $nik)
            ->update(['background' => $foto]);
            
            if($upd){ //mengecek apakah data kosong atau tidak
                DB::connection('dbsimkug')->commit();
                $success['status'] = true;
                $success['foto'] = $foto;
                $success['message'] = "Background berhasil diubah";
                return response()->json($success, 200);     
            }
            else{
                DB::connection('dbsimkug')->rollback();
                $success['status'] = false;
                $success['foto'] = "-";
                $success['message'] = "Background gagal diubah";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection('dbsimkug')->rollback();
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
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $rs = DB::connection('dbsimkug')->select(" select distinct a.kode_form as id,a.nama,c.form 
            from menu a
            inner join m_form c on a.kode_form=c.kode_form
            inner join hakakses b on a.kode_klp=b.kode_klp_menu
            where b.nik=? and a.kode_form<>'-' and a.nama like '%'+?+'%' 
            ",[$nik,$request->input('cari')]);
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
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $filter_arr = [$nik];
            if(isset($request->cari)){
                $filter = " and a.nama like '%'+?+'%' ";
                array_push($filter,$request->input('cari'));
            }else{
                $filter = " ";
            }

            $rs = DB::connection('dbsimkug')->select(" select distinct a.kode_form as id,a.nama,c.form 
            from menu a
            inner join m_form c on a.kode_form=c.kode_form
            inner join hakakses b on a.kode_klp=b.kode_klp_menu
            where b.nik=? and a.kode_form<>'-' $filter
            ",$filter_arr);
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

    
    public function updateDataPribadi(Request $request)
    {  
        try {
            if($data =  Auth::guard('simkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_login = $data->status_login;
            }
            $res = DB::connection('dbsimkug')->select("select email from karyawan where nik=? and kode_lokasi=? ",[$nik,$kode_lokasi]);
            $email = $res[0]->email;

            if(isset($request->email)){
                $email = $request->email;
            }else{
                $email = $email;
            }

            $update = DB::connection('dbsimkug')->table('karyawan')
            ->where('nik',$nik)
            ->where('kode_lokasi',$kode_lokasi)
            ->update([
                'email' => $email
            ]);
            
            if($update){
                $success['status'] = true;
                $success['message'] = "Data Pribadi berhasil diubah";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Pribadi gagal diubah";
            }
           
            return response()->json($success, 200);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Pribadi gagal diubah ".$e;
            return response()->json($success, 200); 
        }	
    }

}
