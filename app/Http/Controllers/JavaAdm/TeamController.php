<?php
namespace App\Http\Controllers\JavaAdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller { 
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
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

            if(isset($request->kode_team)){
                if($request->kode_team == "all"){
                    $filter = "";
                }else{
                    $filter = " and id_team='$request->kode_team' ";
                }
                $sql= "select id_team, nama_team, jabatan_team, deskripsi, path_foto
                from javaadmin_team where kode_lokasi='".$kode_lokasi."' $filter ";

            }else{
                $sql = "select id_team, nama_team, jabatan_team,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status 
                from javaadmin_team
                where kode_lokasi= '$kode_lokasi'";
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_team' => 'required',
            'jabatan_team' => 'required',
            'deskripsi_team' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $id_team = $this->generateKode('javaadmin_team', 'id_team', $kode_lokasi."-TIM".".", '00001');

            if($request->hasfile('file')){
                $file = $request->file('file');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webjava/'.$foto)){
                    Storage::disk('s3')->delete('webjava/'.$foto);
                }
                Storage::disk('s3')->put('webjava/'.$foto,file_get_contents($file));
            }else{
                $foto = '-';
            }
            
            $insert= "insert into javaadmin_team(id_team, nama_team, jabatan_team, deskripsi, path_foto, kode_lokasi, tgl_input)
            values('".$id_team."', '".$request->nama_team."', '".$request->jabatan_team."', '".$request->deskripsi_team."', 
            '".$foto."', '$kode_lokasi', getdate())";
                
            DB::connection($this->sql)->insert($insert);
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $id_team;
            $success['message'] = "Data Team berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Team gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_team' => 'required',
            'nama_team' => 'required',
            'jabatan_team' => 'required',
            'deskripsi_team' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file')){
                $file = $request->file('file');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webjava/'.$foto)){
                    Storage::disk('s3')->delete('webjava/'.$foto);
                }
                Storage::disk('s3')->put('webjava/'.$foto,file_get_contents($file));
            }else{
                $getFoto = DB::connection($this->sql)->select("select top 1 path_foto from javaadmin_team where kode_lokasi = '$kode_lokasi' and id_team = '".$request->id_team."'");
                $res = json_decode(json_encode($getFoto),true);

                if($res[0]['path_foto'] == null) {
                    $foto = '-';
                } else {
                    $foto = $res[0]['path_foto'];
                }
            }
            
            $insert= "insert into javaadmin_team(id_team, nama_team, jabatan_team, deskripsi, path_foto, kode_lokasi, tgl_input)
            values('".$id_team."', '".$request->nama_team."', '".$request->jabatan_team."', '".$request->deskripsi_team."', 
            '".$foto."', '$kode_lokasi', getdate())";

            DB::connection($this->sql)->table('javaadmin_team')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_team', $request->id_team)
            ->delete();
                
            DB::connection($this->sql)->insert($insert);
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $id_team;
            $success['message'] = "Data Team berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Team gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'id_team' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select path_foto from javaadmin_team where kode_lokasi='".$kode_lokasi."' and id_team='".$request->id_team."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                for($i=0;$i<count($res);$i++) {
                    $foto = $res[$i]['path_foto'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('webjava/'.$foto);
                    }
                }
            }
            
            DB::connection($this->sql)->table('javaadmin_team')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_team', $request->id_team)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Team berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Team gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}

?>