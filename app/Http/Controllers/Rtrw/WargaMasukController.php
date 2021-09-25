<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class WargaMasukController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

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
                if($request->kode_pp != ""){

                    $filter = " and a.kode_pp='$request->kode_pp' ";
                }
                $filter = "";
            }else{
                $filter = "";
            }

            $res = DB::connection($this->db)->select("select a.kode_blok,a.no_rumah,a.nama,a.no_urut,a.alias,a.tgl_masuk,a.no_bukti,a.sts_masuk,a.kode_jk as jk,a.tempat_lahir,convert(varchar,a.tgl_lahir,103) as tgl_lahir,a.kode_agama as agama,a.kode_kerja as pekerjaan,a.kode_sts_nikah as sts_nikah,b.alamat 
            from rt_warga_d a 
            inner join rt_rumah b on a.no_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter and a.sts_masuk in ('DATANG','LAHIR') and isnull(a.sts_keluar,'-') = '-' and a.flag_aktif=1
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function generateIDWarga(Request $request)
    {
        $this->validate($request,[
            'tgl_masuk' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $per = substr($request->tgl_masuk,8,2).substr($request->tgl_masuk,3,2);
            $id_warga = $this->generateKode("rt_warga_d", "no_bukti", $kode_lokasi.'-IN'.$per.".", "0001");
            
            $success['status'] = true;
            $success['no_bukti'] = $id_warga;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
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
            'kode_blok' => 'required',
            'id_warga' => 'required',
            'no_rumah' => 'required',
            'nama' => 'required',
            'alias' => 'required',
            'nik' => 'required',
            'jk' => 'required',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required',
            'agama' => 'required',
            'goldar' => 'required',
            'pendidikan' => 'required',
            'pekerjaan' => 'required',
            'sts_nikah' => 'required',
            'sts_hub' => 'required',
            'sts_domisili' => 'required',
            'no_hp' => 'required',
            'emerg_call' => 'required',
            'ket_emergency' => 'required',
            'tgl_masuk' => 'required',
            'sts_masuk' => 'required',
            'kode_rt' => 'required',
            'alamat_asal' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            if($request->hasfile('ktp')){
                $file = $request->file('ktp');
                
                $nama_ktp = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $ktp = $nama_ktp;
                if(Storage::disk('s3')->exists('rtrw/'.$ktp)){
                    Storage::disk('s3')->delete('rtrw/'.$ktp);
                }
                Storage::disk('s3')->put('rtrw/'.$ktp,file_get_contents($file));
            }else{

                $ktp="-";
            }

            if($request->hasfile('kk')){
                $file = $request->file('kk');
                
                $nama_kk = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $kk = $nama_kk;
                if(Storage::disk('s3')->exists('rtrw/'.$kk)){
                    Storage::disk('s3')->delete('rtrw/'.$kk);
                }
                Storage::disk('s3')->put('rtrw/'.$kk,file_get_contents($file));
            }else{

                $kk="-";
            }

            $per = substr($request->tgl_masuk,8,2).substr($request->tgl_masuk,3,2);
            $id_warga = $this->generateKode("rt_warga_d", "no_bukti", $kode_lokasi.'-IN'.$per.".", "0001");

            $res = DB::connection($this->db)->select("select max(no_urut) as no_urut from rt_warga_d where kode_lokasi='$kode_lokasi' and no_rumah='$request->no_rumah' and kode_pp='$request->kode_rt' ");
            if(count($res) > 0){
                $no_urut = intval($res[0]->no_urut) + 1;
            }else{
                $no_urut = 1;
            }

            if($request->no_hp != "" && $request->no_hp != "-"){
                $pass = substr($request->no_hp[$i],6);
                $password = app('hash')->make($pass);
            }else{
                $pass = "-";
                $password = "-";
            }
            
            $ins = DB::connection($this->db)->insert("insert into rt_warga_d(kode_lokasi,no_bukti,kode_blok,no_rumah,nama,alias,nik,kode_jk,tempat_lahir,tgl_lahir,kode_agama,kode_goldar,kode_didik,kode_kerja,kode_sts_nikah,kode_sts_hub,no_hp,no_telp_emergency,ket_emergency,tgl_masuk,sts_masuk,foto,kode_pp,sts_domisili,no_urut,pass,password,flag_aktif,alamat_asal,ktp,kk) values ('".$kode_lokasi."','".$id_warga."','".$request->kode_blok."','".$request->no_rumah."','".$request->nama."','".$request->alias."','".$request->nik."','".$request->jk."','".$request->tempat_lahir."','".$this->reverseDate($request->tgl_lahir,"/","-")."','".$request->agama."','".$request->goldar."','".$request->pendidikan."','".$request->pekerjaan."','".$request->sts_nikah."','".$request->sts_hub."','".$request->no_hp."','".$request->emerg_call."','".$request->ket_emergency."','".$this->reverseDate($request->tgl_masuk,"/","-")."','".$request->sts_masuk."','$foto','$request->kode_rt','$request->sts_domisili',$no_urut,'$pass','$password','2','$request->alamat_asal','$ktp','$kk') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Warga Masuk berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Masuk gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
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
        $this->validate($request,[
            'id_warga' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/rtrw/storage');

            $sql = "
            select kode_blok,no_bukti as id_warga,no_rumah,nama,alias,nik,kode_jk as jk,tempat_lahir,convert(varchar,tgl_lahir,103) as tgl_lahir,kode_agama as agama,kode_goldar as goldar,kode_didik as pendidikan,kode_kerja as pekerjaan,kode_sts_nikah as sts_nikah,sts_domisili,kode_sts_hub as sts_hub,no_hp,no_telp_emergency as emerg_call,ket_emergency,convert(varchar,tgl_masuk,103) as tgl_masuk,sts_masuk,kode_pp as kode_rt,kode_lokasi as kode_rw,ktp,kk,case when foto != '-' then '".$url."/'+foto else '-' end as foto
            from rt_warga_d a 
            where no_bukti='".$request->id_warga."' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function showDetList(Request $request)
    {
        $this->validate($request,[
            'no_rumah' => 'required',
            'kode_blok' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "
            select kode_blok,no_bukti as id_warga,no_rumah,nama,alias,nik,kode_jk as jk,tempat_lahir,convert(varchar,tgl_lahir,103) as tgl_lahir,kode_agama as agama,kode_goldar as goldar,kode_didik as pendidikan,kode_kerja as pekerjaan,kode_sts_nikah as sts_nikah,sts_domisili,kode_sts_hub as sts_hub,no_hp,no_telp_emergency as emerg_call,ket_emergency,convert(varchar,tgl_masuk,103) as tgl_masuk,sts_masuk,kode_pp as kode_rt,kode_lokasi as kode_rw,foto,no_urut,kk,ktp
            from rt_warga_d  
            where no_rumah='$request->no_rumah' and kode_blok='$request->kode_blok' and kode_lokasi='$kode_lokasi' and flag_aktif <> '3' and isnull(sts_keluar,'-') = '-'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
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
            'kode_blok' => 'required',
            'id_warga' => 'required',
            'no_rumah' => 'required',
            'nama' => 'required',
            'alias' => 'required',
            'nik' => 'required',
            'jk' => 'required',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required',
            'agama' => 'required',
            'goldar' => 'required',
            'pendidikan' => 'required',
            'pekerjaan' => 'required',
            'sts_nikah' => 'required',
            'sts_hub' => 'required',
            'sts_domisili' => 'required',
            'no_hp' => 'required',
            'emerg_call' => 'required',
            'ket_emergency' => 'required',
            'tgl_masuk' => 'required',
            'sts_masuk' => 'required',
            'kode_rt' => 'required',
            'alamat_asal' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select foto as file_gambar from rt_warga_d where no_bukti='".$request->id_warga."' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            if($request->hasfile('ktp')){

                $sql = "select ktp as ktp from rt_warga_d where no_bukti='".$request->id_warga."' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $ktp = $res[0]['ktp'];
                    if($ktp != ""){
                        Storage::disk('s3')->delete('rtrw/'.$ktp);
                    }
                }else{
                    $ktp = "-";
                }
                
                $file = $request->file('ktp');
                
                $nama_ktp = uniqid()."_".$file->getClientOriginalName();
                $ktp = $nama_ktp;
                if(Storage::disk('s3')->exists('rtrw/'.$ktp)){
                    Storage::disk('s3')->delete('rtrw/'.$ktp);
                }
                Storage::disk('s3')->put('rtrw/'.$ktp,file_get_contents($file));
                
            }else{

                $ktp="-";
            }

            if($request->hasfile('kk')){

                $sql = "select kk as kk from rt_warga_d where no_bukti='".$request->id_warga."' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $kk = $res[0]['kk'];
                    if($kk != ""){
                        Storage::disk('s3')->delete('rtrw/'.$kk);
                    }
                }else{
                    $kk = "-";
                }
                
                $file = $request->file('kk');
                
                $nama_kk = uniqid()."_".$file->getClientOriginalName();
                $kk = $nama_kk;
                if(Storage::disk('s3')->exists('rtrw/'.$kk)){
                    Storage::disk('s3')->delete('rtrw/'.$kk);
                }
                Storage::disk('s3')->put('rtrw/'.$kk,file_get_contents($file));
                
            }else{

                $kk="-";
            }

            $id_warga = $request->id_warga;
            $res = DB::connection($this->db)->select("select no_urut from rt_warga_d where kode_lokasi='$kode_lokasi' and no_rumah='$request->no_rumah' and kode_pp='$request->kode_rt' and no_bukti='$request->id_warga' ");
            if(count($res) > 0){
                $no_urut = intval($res[0]->no_urut);
            }else{
                $no_urut = 1;
            }

            
            if($request->no_hp != "" && $request->no_hp != "-"){
                $pass = substr($request->no_hp,6);
                $password = app('hash')->make($pass);
            }else{
                $pass = "-";
                $password = "-";
            }

            $del = DB::connection($this->db)->table('rt_warga_d')->where('no_bukti', $request->id_warga)->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_warga_d(kode_lokasi,no_bukti,kode_blok,no_rumah,nama,alias,nik,kode_jk,tempat_lahir,tgl_lahir,kode_agama,kode_goldar,kode_didik,kode_kerja,kode_sts_nikah,kode_sts_hub,no_hp,no_telp_emergency,ket_emergency,tgl_masuk,sts_masuk,foto,kode_pp,sts_domisili,no_urut,pass,password,flag_aktif,alamat_asal,ktp,kk) values ('".$kode_lokasi."','".$id_warga."','".$request->kode_blok."','".$request->no_rumah."','".$request->nama."','".$request->alias."','".$request->nik."','".$request->jk."','".$request->tempat_lahir."','".$this->reverseDate($request->tgl_lahir,"/","-")."','".$request->agama."','".$request->goldar."','".$request->pendidikan."','".$request->pekerjaan."','".$request->sts_nikah."','".$request->sts_hub."','".$request->no_hp."','".$request->emerg_call."','".$request->ket_emergency."','".$this->reverseDate($request->tgl_masuk,"/","-")."','".$request->sts_masuk."','$foto','$request->kode_rt','$request->sts_domisili',$no_urut,'$pass','$password','2','$request->alamat_asal','$ktp','$kk') ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Warga Masuk berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Masuk gagal diubah ".$e;
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
        $this->validate($request,[
            'id_warga' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $update = DB::connection($this->db)->update("update rt_warga_d 
            set flag_aktif='3' 
            where no_bukti='$request->id_warga' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga Masuk berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Masuk gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function updateStatus(Request $request)
    {
        $this->validate($request,[
            'no_rumah' => 'required',
            'kode_blok' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_warga_d')
            ->where('no_rumah', $request->no_rumah)
            ->where('kode_blok', $request->kode_blok)
            ->where('kode_lokasi', $kode_lokasi)
            ->where('flag_aktif', '3')
            ->delete();

            $update = DB::connection($this->db)->update("update rt_warga_d set flag_aktif='1' where flag_aktif='2' and kode_blok='$request->kode_blok' and no_rumah='$request->no_rumah' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga Masuk berhasil disimpan";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga Masuk gagal disimpan ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
