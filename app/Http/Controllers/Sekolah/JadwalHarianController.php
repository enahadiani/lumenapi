<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JadwalHarianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_ta,a.kode_matpel,a.nik,a.kode_kelas,a.kode_pp+'-'+b.nama as pp
            from sis_jadwal a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter
            group by a.kode_ta,a.kode_matpel,a.nik,a.kode_kelas,a.kode_pp+'-'+b.nama 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
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
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_matpel' => 'required',
            'nik_guru' => 'required',
            'kode_kelas' => 'required',
            'kode_slot.*' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_jadwal')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_ta', $request->kode_ta)
            ->where('kode_kelas', $request->kode_kelas)
            ->where('kode_matpel', $request->kode_matpel)
            ->where('nik_guru', $request->nik_guru)
            ->where('kode_pp', $request->kode_pp)
            ->delete();
            
            $hari = array('senin','selasa','rabu','kamis','jumat','sabtu','minggu');
            $kodeHari = array('HR_01','HR_02','HR_03','HR_04','HR_05','HR_06','HR_07');
            
            $req = $request->all();
            if (count($req['kode_slot']) > 0){
                for ($i=0;$i < count($req['kode_slot']);$i++){
                    for ($j = 0; $j < count($hari);$j++){
                        if($req[$hari[$j]][$i] == "ISI"){
                            $ins[$i] = DB::connection('sqlsrv2')->insert("insert into sis_jadwal(kode_slot,kode_lokasi,kode_pp,kode_kelas,kode_hari,kode_ta,nik,kode_matpel) values (?, ?, ?, ?, ?, ?, ?, ?) ",[$req['kode_slot'][$i],$kode_lokasi,$req['kode_pp'],$req['kode_kelas'],$kodeHari[$j],$req['kode_ta'],$req['nik_guru'],$req['kode_matpel']]);
                            
                        }
                    }
                }						
            }
            
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Jadwal Harian berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jadwal Harian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_matpel' => 'required',
            'nik_guru' => 'required',
            'kode_kelas' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_jadwal')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_kelas', $request->kode_kelas)
                ->where('kode_matpel', $request->kode_matpel)
                ->where('nik_guru', $request->nik_guru)
                ->where('kode_pp', $request->kode_pp)
                ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Jadwal Harian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jadwal Harian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function loadJadwal(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_kelas' => 'required',
            'nik_guru' => 'required',
            'kode_matpel' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_slot,a.nama
            from  sis_slot a 
            where a.kode_pp='".$kode_pp."' and a.kode_lokasi='".$kode_lokasi."' order by a.kode_slot
            ");
            $res = json_decode(json_encode($res),true);
            
            if (count($res) > 0){
                $no=1;			
                foreach ($res as $row){
                    $senin=$selasa=$rabu=$kamis=$jumat=$sabtu=$minggu="KOSONG";
                    $strSQL2 = "select kode_hari,kode_matpel,nik from sis_jadwal where kode_slot='".$row['kode_slot']."' and kode_ta='".$kode_ta."' and kode_kelas='".$kode_kelas."' and kode_pp='".$kode_pp."' ";
                    // array_push($exec,$strSQL2);
                    $res2 = DB::connection('sqlsrvtarbak')->select($strSQL2); 
                    $res2 = json_decode(json_encode($res2),true);			
                    if (count($res2 > 0)){		
                        foreach ($res2 as $row2){
                            if ($row2['kode_hari'] == "HR_01") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $senin  = "ISI";
                                else $senin  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_02") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $selasa  = "ISI";
                                else $selasa  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_03") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $rabu  = "ISI";
                                else $rabu  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_04") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $kamis  = "ISI";
                                else $kamis  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_05") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $jumat  = "ISI";
                                else $jumat  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_06") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $sabtu  = "ISI";
                                else $sabtu  = "TERPAKAI";
                            }
                            if ($row2['kode_hari'] == "HR_07") {
                                if ($row2['kode_matpel'] == $kode_matpel && $row2['nik'] == $nik_guru) $minggu  = "ISI";
                                else $minggu  = "TERPAKAI";
                            }
                        }
                    }				
                     
                    $jadwal[] = array("no"=>$no,"kode_slot"=>$row['kode_slot'],"nama"=>$row['nama'],"senin"=>$senin,"selasa"=>$selasa,"rabu"=>$rabu,"kamis"=>$kamis,"jumat"=>$jumat,"sabtu"=>$sabtu,"minggu"=>$minggu); 
                    $no++;
                }
                $success['message'] = "Data Kosong!";
                $success['data'] = $jadwal;
                $success['status'] = true;
            } 
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
