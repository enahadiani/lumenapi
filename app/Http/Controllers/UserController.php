<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use  App\User;
use Barryvdh\DomPDF\PDF;

class UserController extends Controller
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
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $user = DB::connection('sqlsrv')->select("select a.kode_klp_menu, a.nik, a.nama, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
			b.kode_lokkonsol,d.kode_bidang, c.foto,isnull(e.form,'-') as path_view,b.logo,c.no_telp,c.jabatan
            from hakakses a 
            inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
            left join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
            left join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi 
            left join m_form e on a.path_view=e.kode_form 
            where a.nik= '$nik' 
            ");
            $user = json_decode(json_encode($user),true);
            
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                $periode = DB::connection('sqlsrv')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode),true);

                $fs = DB::connection('sqlsrv')->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
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
         return response()->json(['users' =>  User::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

    public function cekPayload(){
        $payload = Auth::guard('user')->payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }

    public function export() 
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }

    public function exportpdf(){
        // return (new UsersExport)->download('users.pdf', \Maatwebsite\Excel\Excel::DomPdf);
        //GET DATA BERDASARKAN ID
        $user = User::all();
        // //LOAD PDF YANG MERUJUK KE VIEW PRINT.BLADE.PHP DENGAN MENGIRIMKAN DATA DARI INVOICE
        // //KEMUDIAN MENGGUNAKAN PENGATURAN LANDSCAPE A4
        // // $pdf = PDF::loadView('user.print', ['user'=>$user])->setPaper('a4', 'landscape');
        // // return $pdf->stream();
        $pdf = app('dompdf.wrapper')->loadView('user.print', ['user'=>$user]);
        $pdf->setPaper('a4', 'lanscape');
        return $pdf->stream();
    }
}
