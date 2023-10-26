<?php

namespace App\Http\Controllers\Ts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Log;
use App\Helper\SaiHelpers;

class SisHakaksesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "ts";
    public $db = "sqlsrvyptkug";

    function isUnik($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from sis_hakakses where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }

    // public function index(Request $r)
    // {
    //     try {
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik_user= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //             $status_lokasi = $data->status_lokasi;
    //         }

    //         $res = DB::connection($this->db)->select("select a.nik,b.nama,a.kode_menu,a.path_view,a.kode_lokasi,a.kode_pp,a.status_login,a.no_hp,a.tgl_selesai,a.flag_aktif 
    //         from sis_hakakses a
    //         left join (
    //             select a.nis as nik, a.nama, a.kode_lokasi, a.kode_pp
    //             from sis_siswa a
    //             union all
    //             select a.nik, a.nama, a.kode_lokasi, a.kode_pp
    //             from karyawan a
    //         ) b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
    //         where a.kode_lokasi=? and a.flag_aktif='1'
    //         ",[$kode_lokasi]);
    //         $res = json_decode(json_encode($res),true);
            
    //         if(count($res) > 0){ //mengecek apakah data kosong atau tidak
    //             $success['status'] = true;
    //             $success['data'] = $res;
    //             $success['message'] = "Success!";
    //             return response()->json($success, $this->successStatus);     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!";
    //             $success['data'] = [];
    //             $success['status'] = true;
    //             return response()->json($success, $this->successStatus);
    //         }
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['data'] = "Error ".$e;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
        
    // }

    public function index(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $req = $r->all();
            $query = "select a.nik,b.nama,a.kode_menu,a.path_view,a.kode_lokasi,a.kode_pp,a.status_login,a.no_hp,a.tgl_selesai,a.flag_aktif 
            from sis_hakakses a
            left join (
                select a.nis as nik, a.nama, a.kode_lokasi, a.kode_pp
                from sis_siswa a
                union all
                select a.nik, a.nama, a.kode_lokasi, a.kode_pp
                from karyawan a
            ) b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1'
            ";
            $q_count2 = "select count(*) as total
            from sis_hakakses a
            left join (
                select a.nis as nik, a.nama, a.kode_lokasi, a.kode_pp
                from sis_siswa a
                union all
                select a.nik, a.nama, a.kode_lokasi, a.kode_pp
                from karyawan a
            ) b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1'";

            $column_array = array('a.nik','b.nama','a.kode_pp','a.kode_lokasi');
            $column_string = join(',', $column_array);
            if(!empty($req['search']['value']))
            {
                $search = $req['search']['value'];
                $filter_string = " and (";
                for($i=0; $i<count($column_array); $i++){
                    if($i == (count($column_array) - 1)){
                        $filter_string .= $column_array[$i]." like '".$search."%' )";
                    }else{
                        $filter_string .= $column_array[$i]." like '".$search."%' or ";
                    }
                }
                $query.=" $filter_string ";
                $q_count2.=" $filter_string ";
            }
            
            if(isset($req["order"]))
            {
                $query .= ' ORDER BY '.$column_array[$req['order'][0]['column']].' '.$req['order'][0]['dir'];
            }
            else
            {
                $query .= ' ORDER BY a.nik ';
            }
            if($req["length"] != -1)
            {
                $query .= ' OFFSET ' . $req['start'] . ' ROWS FETCH FIRST ' . $req['length'] . ' ROWS ONLY ';
            }
            $res = DB::connection($this->db)->select($query);
            $resN = DB::connection($this->db)->select($q_count2);

            $q_count = "select count(*) as total
            from sis_hakakses a
            left join (
                select a.nis as nik, a.nama, a.kode_lokasi, a.kode_pp
                from sis_siswa a
                union all
                select a.nik, a.nama, a.kode_lokasi, a.kode_pp
                from karyawan a
            ) b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1'";
            $rs_count = DB::connection($this->db)->select($q_count);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success = [
                    'status' => true,
                    'draw' => $r->input('draw'),
                    'recordsTotal' => $rs_count[0]->total,
                    'recordsFiltered' => $resN[0]->total,
                    'data' => $res,
                    'message' => 'Success!'
                ];
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success = [
                    'status' => true,
                    'draw' => $r->input('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'message' => 'Success!'
                ];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error($e);
            $success = [
                'status' => false,
                'draw' => $r->input('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'message' => "Internal Server Error".$e
            ];
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
     * @param  \Illuminate\Http\Request  $r
     * @return \Illuminate\Http\Response
     */
    public function store(Request $r)
    {
        $this->validate($r, [
            'nik' => 'required|max:10',
            'kode_pp' => 'required|max:10',
            'kode_lokasi' => 'required|max:10',
            'pass' => 'required',
            'status_login' => 'required|max:1',
            'kode_menu' => 'required|max:20',
            'kode_form' => 'required|max:10',
            'flag_aktif' => 'required',
            'tgl_selesai' => 'string',
            'no_hp' => 'required|string',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(!$this->isUnik($r->nik,$r->kode_lokasi)){
                $tmp=" error:Duplicate Entry. NIK sudah terdaftar di database !";
                $sts=false;
            }else{
                $sts= true;
            }
            $success['kode'] = $r->input('nik');
            if($sts){
                
                $pass = NULL;
                $password = NULL;
                if(isset($r->pass) && $r->pass != ""){
                    $pass = $r->input('pass');
                    $password = app('hash')->make($r->pass);
                }
                $tgl_selesai = NULL;
                if(isset($r->tgl_selesai) && $r->input('tgl_selesai') != ""){
                    $tgl_selesai = SaiHelpers::reverseDate($r->input('tgl_selesai'),'/','-');
                }

                if($r->hasfile('foto')){
                    $file = $r->file('foto');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('ts/'.$foto)){
                        Storage::disk('s3')->delete('ts/'.$foto);
                    }
                    Storage::disk('s3')->put('ts/'.$foto,file_get_contents($file));
                }else{
    
                    $foto="-";
                }
    
                $ins = DB::connection($this->db)->insert("insert into sis_hakakses (nik, pass, status_login, no_hp, kode_lokasi, kode_pp, tgl_selesai, flag_aktif, menu_mobile, tgl_input, nik_user, kode_menu, foto, path_view, password, background) values (?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?) ", [$r->input('nik'),$pass,$r->input('status_login'),$r->input('no_hp'),$r->input('kode_lokasi'),$r->input('kode_pp'),$tgl_selesai,$r->input('flag_aktif'),$r->input('kode_form'),$nik_user,$r->input('kode_menu'),$foto,$r->input('kode_form'),$pass,'-']);
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Hakakses berhasil disimpan";
            }else{
                $success['status'] = $sts;
                $success['message'] = $tmp;
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $r, $nik)
    {
        
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.nik,s.nama,a.kode_menu,a.pass,a.status_login,a.path_view as kode_form,b.nama as nama_menu,e.nama_form as nama_form,convert(varchar,a.tgl_selesai,103) as tgl_selesai,a.kode_lokasi,a.kode_pp,a.flag_aktif,a.foto,a.no_hp,isnull(p.nama,'-') as nama_pp
            from sis_hakakses a 
            left join menu_klp b on a.kode_menu=b.kode_klp
            left join lokasi c on a.kode_lokasi=c.kode_lokasi
            left join m_form e on a.path_view=e.kode_form
            left join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            left join (
                select a.nis as nik, a.nama, a.kode_lokasi, a.kode_pp
                from sis_siswa a
                union all
                select a.nik, a.nama, a.kode_lokasi, a.kode_pp
                from karyawan a
            ) s on a.nik=s.nik and a.kode_lokasi=s.kode_lokasi and a.kode_pp=s.kode_pp
            where a.kode_lokasi=? and a.nik=?
            ";
            $res = DB::connection($this->db)->select($sql,[$kode_lokasi, $nik]);
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
     * @param  \Illuminate\Http\Request  $r
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $r)
    {
        $this->validate($r, [
            'nik' => 'required|max:10',
            'kode_pp' => 'required|max:10',
            'kode_lokasi' => 'required|max:10',
            'pass' => 'required',
            'status_login' => 'required|max:1',
            'kode_menu' => 'required|max:20',
            'kode_form' => 'required|max:10',
            'flag_aktif' => 'required',
            'tgl_selesai' => 'string',
            'no_hp' => 'required|string',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $success['kode'] = $r->input('nik');

            $pass = NULL;
            $password = NULL;
            if(isset($r->pass) && $r->pass != ""){
                $pass = $r->input('pass');
                $password = app('hash')->make($r->pass);
            }

            if($r->hasfile('foto')){

                $sql = "select foto as file_gambar from sis_hakakses where kode_lokasi=? and nik=?
                ";
                $res = DB::connection($this->db)->select($sql,[$r->input('kode_lokasi'), $r->input('nik')]);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('ts/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $r->file('foto');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('ts/'.$foto)){
                    Storage::disk('s3')->delete('ts/'.$foto);
                }
                Storage::disk('s3')->put('ts/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $tgl_selesai = NULL;
            if(isset($r->tgl_selesai) && $r->input('tgl_selesai') != ""){
                $tgl_selesai = SaiHelpers::reverseDate($r->input('tgl_selesai'),'/','-');
            }

            $upd = DB::connection($this->db)->insert("update sis_hakakses set 
            pass = ?, status_login =?, no_hp =?, kode_pp =?, tgl_selesai =?, flag_aktif =?, menu_mobile =?, tgl_input = getdate(), nik_user =?, kode_menu =?, foto =?, path_view =?, password =?
            where nik=? and kode_lokasi=? ",array($pass,$r->input('status_login'),$r->input('no_hp'),$r->input('kode_pp'),$tgl_selesai,$r->input('flag_aktif'),$r->input('kode_form'),$nik_user,$r->input('kode_menu'),$foto,$r->input('kode_form'),$password,$r->input('nik'),$r->input('kode_lokasi')));
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($nik)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_hakakses')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik', $nik)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getMenu(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            $filter_arr = [];
            if(isset($r->kode_menu) && $r->input('kode_menu') != ""){
                $filter .= " where a.kode_klp=?";
                array_push($filter_arr,$r->input('kode_menu'));
            }
            $res = DB::connection($this->db)->select("select kode_klp,nama from menu_klp
            $filter",$filter_arr);
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

    public function getForm(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            $filter_arr = [];
            if(isset($r->kode_form) && $r->input('kode_form') != ""){
                $filter .= " where a.kode_form=?";
                array_push($filter_arr,$r->input('kode_form'));
            }

            $res = DB::connection($this->db)->select("select kode_form,nama_form from m_form 
            $filter",$filter_arr);
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

    public function getPP(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            $filter_arr = [$kode_lokasi];
            if(isset($r->kode_pp) && $r->input('kode_pp') != ""){
                $filter .= " and a.kode_pp=?";
                array_push($filter_arr,$r->input('kode_pp'));
            }
            $res = DB::connection($this->db)->select("select a.kode_pp,a.nama 
            from sis_sekolah a 
            where a.kode_lokasi = ?
            order by a.kode_pp
            ",$filter_arr);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, 200);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
        
    }


    public function getNIK(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            $filter_arr = [$kode_lokasi];
            if(isset($r->nik) && $r->input('nik') != ""){
                $filter .= " and a.nik=?";
                array_push($filter_arr,$r->input('nik'));
            }
            if(isset($r->kode_pp) && $r->input('kode_pp') != ""){
                $filter .= " and a.kode_pp=?";
                array_push($filter_arr,$r->input('kode_pp'));
            }

            $res = DB::connection($this->db)->select("select a.* 
            from (
                select nis as nik,nama,kode_pp,kode_lokasi 
                from sis_siswa
                union all
                select nik,nama,kode_pp,kode_lokasi 
                from karyawan
            ) a
            where a.kode_lokasi=? $filter",$filter_arr);
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
            $success['data'] = "Error ".$e;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
