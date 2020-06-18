<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\AdminSatpam;

class AdminSatpamController extends Controller
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
        // if($data =  Auth::user()){
            $id_satpam= $data->id_satpam;
            $kode_lokasi= $data->kode_lokasi;

            $user = DB::connection('sqlsrvrtrw')->select("select id_satpam,kode_lokasi,nama,alamat,status,no_hp,flag_aktif from rt_satpam
            where id_satpam= '$id_satpam' 
            ");
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                return response()->json(['user' => $user], 200);
            }
            else{
                return response()->json(['user' => []], 200);
            }
        // }else{
        //     return response()->json(['user' => []], 200);
        // }
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
         return response()->json(['users' =>  AdminSatpam::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = AdminSatpam::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }
}
