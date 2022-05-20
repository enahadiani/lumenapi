<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use  App\AdminSiaga;
use Illuminate\Support\Facades\Storage; 

class AdminSiagaController extends Controller
{
    public $db = 'dbsiaga';
    public $guard = 'siaga';
    public function profile()
    {
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $user = DB::connection($this->db)->select("select a.kode_menu_saku as kode_klp_menu, a.nik, a.nama, a.pass, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
			b.kode_lokkonsol, c.foto,isnull(e.form,'-') as path_view,b.logo,'-' as kode_kota,isnull(c.background,'-') as background,a.flag_menu,isnull(c.email,'-') as email,c.no_telp,c.jabatan
            from hakakses a 
            inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
            left join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
            left join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi 
            left join m_form e on a.menu_mobile=e.kode_form 
            where a.nik= '$nik' 
            ");
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                $periode = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode),true);

                $fs = DB::connection($this->db)->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
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
         return response()->json(['users' =>  AdminSiaga::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminSiaga::findOrFail($id);

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

            $cek =  DB::connection($this->db)->select("select pass from hakakses where nik='$nik' and pass='$request->password_lama' ");
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

                $sql = "select foto as file_gambar from karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('siaga/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('siaga/'.$foto)){
                    Storage::disk('s3')->delete('siaga/'.$foto);
                }
                Storage::disk('s3')->put('siaga/'.$foto,file_get_contents($file));
                
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

                $sql = "select background as file_gambar from karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('siaga/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('siaga/'.$foto)){
                    Storage::disk('s3')->delete('siaga/'.$foto);
                }
                Storage::disk('s3')->put('siaga/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $upd =  DB::connection($this->db)->table('karyawan')
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
            inner join hakakses b on a.kode_klp=b.kode_klp_menu
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
            inner join hakakses b on a.kode_klp=b.kode_klp_menu
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

    public function updateDataPribadi(Request $request)
    {  
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
                $status_login = $data->status_login;
            }

            $res = DB::connection($this->db)->select("select email,no_telp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            $email = $res[0]->email;
            $no_telp = $res[0]->no_telp;

            if(isset($request->email)){
                $email = $request->email;
            }else{
                $email = $email;
            }

            if(isset($request->no_telp)){
                $no_telp = $request->no_telp;
            }else{
                $no_telp = $no_telp;
            }

            $update = DB::connection($this->db)->table('karyawan')
            ->where('nik',$nik)
            ->where('kode_lokasi',$kode_lokasi)
            ->update([
                'email' => $email,
                'no_telp' => $no_telp
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
