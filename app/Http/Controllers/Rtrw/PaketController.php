<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaketController extends Controller
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

            if(isset($request->id_paket)){
                if($request->id_paket == "all" || $request->id_paket == ""){
                    $filter .= "";
                }else{
                    $filter .= " and a.id_paket='$request->id_paket' ";
                }
            }

            if(isset($request->no_paket)){
                if($request->no_paket == "all" || $request->no_paket == ""){
                    $filter .= "";
                }else{
                    $filter .= " and a.no_paket='$request->no_paket' ";
                }
            }

            if(isset($request->status_ambil)){
                if($request->status_ambil == "all" || $request->status_ambil == ""){
                    $filter .= "";
                }else{
                    $filter .= " and a.status_ambil='$request->status_ambil' ";
                }
            }
            $sql= "
            select a.no_paket,a.nama,a.nik,a.no_rumah,a.id_paket from rt_paket_m a where kode_lokasi='".$kode_lokasi."' $filter ";

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
            'no_rumah' => 'required',
            'blok'  => 'required',
            'nik'  => 'required',
            'nama'  => 'required',
            'foto'=>'required|file|max:3072'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik_user= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = date('Ym');

            $no_bukti = $this->generateKode("rt_paket_m", "no_paket", $kode_lokasi."-PKT".substr($periode,2,4).".", "000001");

            $id_paket = $this->generateKode("rt_paket_m", "id_paket", date('Ymd').".", "00001");

            if($request->hasfile('foto')){

                $file = $request->file('foto');
                
                $nama_foto = 'paket-'.uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $ins = DB::connection($this->sql)->insert("insert into rt_paket_m (no_paket,no_rumah,blok,nik,nama,kode_lokasi,tgl_input,nik_user,id_paket,foto,status_ambil) values ('$no_bukti','$request->no_rumah','$request->blok','$kode_lokasi',getdate(),'$nik_user','-','$id_paket','$foto','belum')");

            $success['status'] = true;
            $success['message'] = "Data Paket berhasil disimpan";
            $success['no_paket'] = $no_bukti;
            $success['no_urut'] = $id_tamu; 

            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                Storage::disk('s3')->delete('rtrw/'.$foto);
            }
            $success['status'] = false;
            $success['message'] = "Data Paket gagal disimpan ".$e;
            $success['no_paket'] = "";
            $success['no_urut'] = ""; 

            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required'
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
            
            $ins = DB::connection($this->sql)->update("update rt_paket_m set status_ambil='sudah' where no_paket='$request->no_paket' and kode_lokasi='$kode_lokasi' ");
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Update status paket berhasil. Paket sudah diambil";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Update status paket gagal ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }
}
