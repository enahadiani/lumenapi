<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApvController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function pengajuan(Request $request){

        $kode_lokasi= $request->input('kode_lokasi');
        $aju = DB::select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
        from yk_pb_m a 
        inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
        inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
        where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBBAU','PBPR','PBINV') 					 
        union 			
        select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
        from panjar2_m a 
        inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
        inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
        where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') 
        order by tgl
        ");
        $aju = json_decode(json_encode($aju),true);
        // $siswa = DevSiswa::all();
        
        if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
            $success['status'] = true;
            $success['data'] = $aju;
            $success['env']= env('DB_CONNECTION');
            $success['message'] = "Success!";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        }
        else{
            $success['message'] = "Data Kosong!";
            $success['status'] = true;
            
            return response()->json(['success'=>$success], $this->successStatus);
        }
    }

}
