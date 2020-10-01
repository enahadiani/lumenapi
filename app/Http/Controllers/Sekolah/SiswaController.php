<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SiswaController extends Controller
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
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->flag_aktif)){
                $filter .= " and a.flag_aktif='$request->flag_aktif' ";
            }else{
                $filter .= "";
            }
            
            if(isset($request->kode_kelas)){
                $filter .= " and a.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.nis,a.nama,a.kode_kelas,a.kode_akt,a.kode_pp,a.kode_pp+'-'+b.nama as pp,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.flag_aktif,c.nama as nama_status
            from sis_siswa a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_siswa_status c on a.flag_aktif=c.kode_ss and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter
            order by a.nis ");
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
        $this->validate($request,[
            'nis' => 'required',
            'nama' => 'required',
            'kode_kelas' => 'required',
            'kode_pp' => 'required',
            'kode_akt' => 'required',
            'id_bank' => 'required',
            'tgl_lulus' => 'required',
            'kode_param' => 'array',
            'per_awal' => 'array',
            'per_akhir' => 'array',
            'tarif' => 'array'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select nis from sis_siswa where id_bank ='$request->id_bank' and nis <> '$request->nis' and kode_lokasi='$kode_lokasi'");
            $res = json_decode(json_encode($res),true);
            
            if (count($res) > 0){					
                $line = $res[0];				
                $msg = "Transaksi tidak valid. ID Bank Duplikasi dengan NIS Lain : ".$line['nis'];
                $sts = false;						
            }
            else {
                
                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_siswa(nis,kode_lokasi,nama,flag_aktif,kode_kelas,kode_pp,kode_akt,id_bank,tgl_lulus,tgl_input) values ('$request->nis','$kode_lokasi','$request->nama','$request->flag_aktif','$request->kode_kelas','$request->kode_pp','$request->kode_akt','$request->id_bank','$request->tgl_lulus',getdate())");
                
                if (count($request->kode_param) > 0){
                    for ($i=0;$i < count($request->kode_param);$i++){		
                        if ($request->tarif[$i] > 0) {	
                            $ins2[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_siswa_tarif(nis,kode_kelas,kode_param,per_awal,per_akhir,tarif,kode_lokasi,kode_pp,kode_akt) values ('".$request->nis."','".$request->kode_kelas."','".$request->kode_param[$i]."','".$request->per_awal[$i]."','".$request->per_akhir[$i]."','".$request->tarif[$i]."','".$kode_lokasi."','".$request->kode_pp."','".$request->kode_akt."')");		
                        }
                    }				
                }
                
                DB::connection('sqlsrvtarbak')->commit();
                $msg = "Data Siswa berhasil disimpan";
                $sts = true;	
                
            }			
            $success['nis'] = $request->nis;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Siswa gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }
    }

    public function update(Request $request)
    {
        $this->validate($request,[
            'nis' => 'required',
            'nama' => 'required',
            'kode_kelas' => 'required',
            'kode_pp' => 'required',
            'kode_akt' => 'required',
            'id_bank' => 'required',
            'tgl_lulus' => 'required',
            'kode_param' => 'array',
            'per_awal' => 'array',
            'per_akhir' => 'array',
            'tarif' => 'array'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select nis from sis_siswa where id_bank ='$request->id_bank' and nis <> '$request->nis' and kode_lokasi='$kode_lokasi'");
            $res = json_decode(json_encode($res),true);
            
            if (count($res) > 0){					
                $line = $res[0];							
                $msg = "Transaksi tidak valid. ID Bank Duplikasi dengan NIS Lain : ".$line['nis'];
                $sts = false;						
            }
            else {
                $del = DB::connection('sqlsrvtarbak')->table('sis_siswa')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();

                $del2 = DB::connection('sqlsrvtarbak')->table('sis_siswa_tarif')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();

                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_siswa(nis,kode_lokasi,nama,flag_aktif,kode_kelas,kode_pp,kode_akt,id_bank,tgl_lulus,tgl_input) values ('$request->nis','$kode_lokasi','$request->nama','$request->flag_aktif','$request->kode_kelas','$request->kode_pp','$request->kode_akt','$request->id_bank','$request->tgl_lulus',getdate())");
                
                if (count($request->kode_param) > 0){
                    for ($i=0;$i < count($request->kode_param);$i++){		
                        if ($request->tarif[$i] > 0) {	
                            $ins2[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_siswa_tarif(nis,kode_kelas,kode_param,per_awal,per_akhir,tarif,kode_lokasi,kode_pp,kode_akt) values ('".$request->nis."','".$request->kode_kelas."','".$request->kode_param[$i]."','".$request->per_awal[$i]."','".$request->per_akhir[$i]."','".$request->tarif[$i]."','".$kode_lokasi."','".$request->kode_pp."','".$request->kode_akt."')");		
                        }
                    }				
                }
                
                DB::connection('sqlsrvtarbak')->commit();
                $msg = "Data Siswa berhasil diubah";
                $sts = true;	
                
            }			
            
            $success['nis'] = $request->nis;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Siswa gagal diubah ".$e;
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
            'nis' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_siswa')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();
                
            $del2 = DB::connection('sqlsrvtarbak')->table('sis_siswa_tarif')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();


            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Siswa berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Siswa gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'nis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp= $request->kode_pp;
            $nis= $request->nis;

            $res = DB::connection('sqlsrvtarbak')->select(" select a.nis,a.id_bank,a.nama,a.kode_pp,b.nama as nama_pp,a.kode_akt,c.nama as nama_akt,a.kode_kelas,d.nama as nama_kelas
            ,d.kode_jur,e.nama as nama_jur,d.kode_tingkat, f.nama as nama_tingkat
            ,a.flag_aktif,g.nama as nama_status,a.tgl_lulus
            from sis_siswa a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_angkat c on a.kode_akt=c.kode_akt and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            inner join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
            inner join sis_jur e on d.kode_jur=e.kode_jur and d.kode_lokasi=e.kode_lokasi and d.kode_pp=e.kode_pp
            inner join sis_tingkat f on d.kode_tingkat=f.kode_tingkat and d.kode_lokasi=f.kode_lokasi 
            inner join sis_siswa_status g on a.flag_aktif=g.kode_ss and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
            where a.nis='".$nis."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.kode_param,a.nama,isnull(b.tarif ,0) as tarif,isnull(b.per_awal ,'-') as per_awal, isnull(b.per_akhir ,'-') as per_akhir 
            from sis_param a 
            inner join sis_siswa_tarif b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi and b.nis='".$nis."' and b.kode_pp='".$kode_pp."' 
            where a.kode_lokasi = '".$kode_lokasi."'
            order by a.idx 
            ");
            $res2 = json_decode(json_encode($res2),true);
            if (count($res) > 0){
                $success['message'] = "Success!";
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['status'] = true;
            } 
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getParam(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_akt' => 'required',
            'kode_jur' => 'required',
            'kode_tingkat' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_param) && $request->kode_param != ""){
                $filter = " and a.kode_param = '$request->kode_param' ";
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_param,a.nama,isnull(b.tarif ,0) as tarif,isnull(b.bulan1 ,'-') as per_awal, isnull(b.bulan2 ,'-') as per_akhir 
            from sis_param a 
            left join sis_param_tarif b on a.kode_param=b.kode_param and a.kode_lokasi=b.kode_lokasi 
                    and b.kode_akt='".$request->kode_akt."' and b.kode_jur='".$request->kode_jur."' 		
                    and b.kode_tingkat='".$request->kode_tingkat."' and b.kode_pp='".$request->kode_pp."' 
            where a.kode_lokasi = '".$kode_lokasi."' and b.tarif <> 0  $filter
            order by a.idx ");
            $res = json_decode(json_encode($res),true);

            if (count($res) > 0){
                $success['message'] = "Success!";
                $success['data'] = $res;
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

    public function getPeriodeParam(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select substring(convert(varchar,tgl_mulai,112),1,6) as bulanawal, substring(convert(varchar,tgl_akhir,112),1,6) as bulanakhir from sis_ta 
            where flag_aktif ='1' and kode_pp='".$request->kode_pp."' and kode_lokasi='".$kode_lokasi."' ");
            if (count($res) > 0){
                $success['periodeAwal']= $res[0]->bulanawal;
                $success['periodeAkhir'] = $res[0]->bulanakhir;
            }else{
                $success['periodeAwal']= "";
                $success['periodeAkhir'] = "";
            }

            $success['message'] = "Success!";
            $success['status'] = true;

            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
