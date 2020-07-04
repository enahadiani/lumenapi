<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\AdminWarga;

use Tymon\JWTAuth\Facades\JWTAuth;

class AdminWargaController extends Controller
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
        if($data =  Auth::guard('warga')->user()){
            $no_hp= $data->no_hp;
            $kode_lokasi= $data->kode_lokasi;
            
            $url = url('api/rtrw/storage');
            $user = DB::connection('sqlsrvrtrw')->select("select a.no_hp,a.nama,a.tgl_lahir,a.kode_jk,a.kode_agama,a.kode_pp,a.kode_blok,a.no_urut,a.no_bukti,a.no_rumah,case when a.foto != '-' then '".$url."/'+a.foto else '-' end as foto,b.status_huni from rt_warga_d a
            inner join rt_rumah b on a.no_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
            where a.no_hp= '$no_hp' 
            ");
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                return response()->json(['user' => $user], 200);
            }
            else{
                return response()->json(['user' => []], 200);
            }
        }else{
            return response()->json(['user' => []], 200);
        }
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
         return response()->json(['users' =>  AdminWarga::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminWarga::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::guard('warga')->payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }
}
