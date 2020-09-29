<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exports\NilaiExport;
use App\Imports\NilaiImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\NilaiTmp;

class PenilaianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrvtarbak')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }    

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

            if(isset($request->kode_ta)){
                $filter .= " and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_sem)){
                $filter .= " and a.kode_sem='$request->kode_sem' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,a.nu,a.tgl_input
            ,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status   
            from sis_nilai_m a
            where a.kode_lokasi='".$kode_lokasi."' $filter");
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
            'kode_ta' => 'required',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis'=>'required',
            'kode_kd'=>'required',
            'nis'=>'required|array',
            'nilai'=>'required|array',
            'nama_dok'=>'array|max:100',
            'nis_dok'=>'array|max:20',
            'file.*'=>'file|max:10240'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(count($request->nis) > 0){
                
                $per = date('ym');
                $no_bukti = $this->generateKode("sis_nilai_m", "no_bukti", $kode_lokasi."-NIL".$per.".", "00001");
                $strSQL = "select COUNT(*)+1 as jumlah from sis_nilai_m where kode_ta= '".$request->kode_ta."' and kode_sem= '".$request->kode_sem."' and kode_kelas= '".$request->kode_kelas."' and kode_matpel= '".$request->kode_matpel."' and kode_jenis= '".$request->kode_jenis."' and kode_pp='$request->kode_pp'";	
                $res = DB::connection('sqlsrvtarbak')->select($strSQL);
                $res = json_decode(json_encode($res),true);
            	if(count($res) > 0){
                    $no_urut = $res[0]['jumlah'];
                }else{
                    $no_urut = 1;
                }

                // $arr_foto = array();
                // $arr_nama = array();
                // $i=0;
                // if($request->hasfile('file'))
                // {
                //     foreach($request->file('file') as $file)
                //     {                
                //         $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                //         $foto = $nama_foto;
                //         if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                //             Storage::disk('s3')->delete('sekolah/'.$foto);
                //         }
                //         Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                //         $arr_foto[] = $foto;
                //         $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                //         $i++;
                //     }
                // }

                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp,kode_kd) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$request->kode_pp,$request->kode_kd));
                for($i=0;$i<count($request->nis);$i++){
                    
                    $ins2[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                    
                }  

                // if(count($arr_nama) > 0){
                //     for($i=0; $i<count($arr_nama);$i++){
                //         $ins3[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_dok (
                //         no_bukti,kode_lokasi,file_dok,no_urut,nama,kode_pp,nis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','".$arr_nama[$i]."','$request->kode_pp','".$request->nis_dok[$i]."') "); 
                //     }
                // }

                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil disimpan.";
            }else{
                $sts = true;
                $no_bukti = "-";
                $msg = "Data Penilaian gagal disimpan. Detail Penilaian tidak valid";
            }
            
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,b.nama as nama_pp,c.nama as nama_ta,d.nama as nama_kelas,f.nama as nama_jenis,g.nama as nama_matpel,isnull(j.jumlah,0)+1 as jumlah,h.nama as nama_kd,a.kode_kd
            from sis_nilai_m a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                left join sis_ta c on a.kode_ta=c.kode_ta and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                left join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
                left join sis_jenisnilai f on a.kode_jenis=f.kode_jenis and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
                left join sis_matpel g on a.kode_matpel=g.kode_matpel and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
                left join sis_kd h on a.kode_kd=h.kode_kd and a.kode_lokasi=h.kode_lokasi and a.kode_pp=h.kode_pp
                left join ( select kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi,COUNT(*) as jumlah from sis_nilai_m 
                    where no_bukti <> '$request->no_bukti'
                    group by kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi
                    ) j on a.kode_ta=j.kode_ta and a.kode_lokasi=j.kode_lokasi and a.kode_pp=j.kode_pp and a.kode_jenis=j.kode_jenis and a.kode_sem=j.kode_sem and a.kode_matpel=j.kode_matpel and a.kode_kelas=j.kode_kelas
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp'  ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.nis,a.nilai,b.nama from sis_nilai a inner join sis_siswa b on a.nis=b.nis where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp'  ");
            $res2 = json_decode(json_encode($res2),true);

            // $res3 = DB::connection('sqlsrvtarbak')->select("select 
            // a.no_bukti,a.kode_lokasi,a.file_dok,a.no_urut,a.nama,a.kode_pp,a.nis from sis_nilai_dok a where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' ");
            // $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                // $success['data_dok'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                // $success['data_dok'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_ta' => 'required',  
            'kode_pp' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis'=>'required',
            'kode_kd'=>'required',
            'nis'=>'required|array',
            'nilai'=>'required|array',
            'nama_dok'=>'array|max:100',
            'nis_dok'=>'array|max:20',
            'file.*'=>'file|max:10240'
        ]);
        
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->nis) > 0){
                
                $no_bukti = $request->no_bukti;
                $strSQL = "select nu as jumlah from sis_nilai_m where no_bukti='$no_bukti' ";	
                $cek = DB::connection('sqlsrvtarbak')->select($strSQL);
                if(count($cek) > 0){
                    $no_urut = $res[0]->jumlah;
                }else{
                    $no_urut = 1;
                }

                // $arr_foto = array();
                // $arr_nama = array();
                // $arr_foto2 = array();
                // $arr_nama2 = array();
                // $i=0;
                // $cek = $request->file;
                // //cek upload file tidak kosong
                // if(!empty($cek)){

                //     if(count($request->nama_file) > 0){
                //         //looping berdasarkan nama dok
                //         for($i=0;$i<count($request->nama_file);$i++){
                //             //cek row i ada file atau tidak
                //             if(isset($request->file('file')[$i])){
                //                 $file = $request->file('file')[$i];

                //                 //kalo ada cek nama sebelumnya ada atau -
                //                 if($request->nama_file_seb[$i] != "-"){
                //                     //kalo ada hapus yang lama
                //                     Storage::disk('s3')->delete('sekolah/'.$request->nama_file_seb[$i]);
                //                 }
                //                 $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                //                 $foto = $nama_foto;
                //                 if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                //                     Storage::disk('s3')->delete('sekolah/'.$foto);
                //                 }
                //                 Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                //                 $arr_foto[] = $foto;
                //             }else{
                //                 $arr_foto[] = $request->nama_file_seb[$i];
                //             }     
                //             $arr_nama[] = $request->input('nama_file')[$i];
                //             $arr_nama2[] = count($request->nama_file).'|'.$i.'|'.isset($request->file('file')[$i]);
                //         }
    
                //         $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $kode_pp)->delete();
                //     }
                // }

                $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
    
                $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();


                $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp,kode_kd) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$request->kode_pp,$request->kode_kd));
                for($i=0;$i<count($request->nis);$i++){
                    $ins2[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                    
                }  

                // if(count($arr_nama) > 0){
                //     for($i=0; $i<count($arr_nama);$i++){
                //         $ins3[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_dok (
                //         no_bukti,kode_lokasi,file_dok,no_urut,nama,kode_pp,nis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','".$arr_nama[$i]."','$request->kode_pp','".$request->nis_dok[$i]."') "); 
                //     }
                // }

                DB::connection('sqlsrvtarbak')->commit();
                $sts = true;
                $msg = "Data Penilaian berhasil diubah.";
                
            }else{
                $sts = true;
                $no_bukti = "-";
                $msg = "Data Penilaian gagal diubah. Detail Penilaian tidak valid";
            }
            
            $success['no_bukti'] = $no_bukti;
            $success['status'] = $sts;
            $success['message'] = $msg;
     
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            // $sql3="select no_bukti,nama,file_dok from sis_nilai_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' and kode_pp='$kode_pp'  order by no_urut";
            // $res3 = DB::connection('sqlsrvtarbak')->select($sql3);
            // $res3 = json_decode(json_encode($res3),true);

            // if(count($res3) > 0){
            //     for($i=0;$i<count($res3);$i++){
            //         Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
            //     }
            // }

            // $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')
            // ->where('kode_lokasi', $kode_lokasi)
            // ->where('no_bukti', $no_bukti)
            // ->where('kode_pp', $request->kode_pp)
            // ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Penilaian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function loadSiswa(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select nis,nama from sis_siswa where kode_kelas = '".$request->kode_kelas."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."'");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function validateNIS($nis,$kode_lokasi,$kode_pp,$kode_kelas){
        $keterangan = "";
        $auth = DB::connection('sqlsrvtarbak')->select("select nis from sis_siswa where nis='$nis' and kode_lokasi='$kode_lokasi' and kode_pp = '$kode_pp' and kode_kelas ='$kode_kelas'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "NIS $nis tidak valid. ";
        }

        return $keterangan;

    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required',
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection('sqlsrvtarbak')->table('sis_nilai_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->where('kode_pp', $request->kode_pp)->delete();

            $per = date('ym');

            $no_bukti = $this->generateKode("sis_nilai_m", "no_bukti", $kode_lokasi."-NIL".$per.".", "00001");

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new NilaiImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateNIS($row[0],$kode_lokasi,$request->kode_pp,$request->kode_kelas);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $x[] = NilaiTmp::create([
                        'no_bukti' => '-',
                        'nis' => strval($row[0]),
                        'nilai' => floatval($row[2]),
                        'kode_pp' => $request->kode_pp,
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $request->nik_user,
                        'status' => $sts,
                        'keterangan' => $ket,
                        'nu' => $no
                    ]);
                    $no++;
                }
            }
            
            DB::connection('sqlsrvtarbak')->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'kode_pp' => 'required',
            'nik' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $request->nik_user;
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        $kode_pp = $request->kode_pp;
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new NilaiExport($nik_user,$kode_lokasi,$kode_pp,$request->type,$request->kode_kelas), 'Nilai_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new NilaiExport($nik_user,$kode_lokasi,$kode_pp,$request->type), 'Nilai_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function getNilaiTmp(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_pp' => 'required'
        ]);

        $nik_user = $request->nik_user;
        $kode_pp = $request->kode_pp;
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.nis,b.nama,a.nilai
            from sis_nilai_tmp a
            inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'  order by a.nu");
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['detail'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function getKD(Request $request)
    {

        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_matpel) && $request->kode_matpel != ""){
                $filter .= " and a.kode_mapel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_kd,a.nama
            from sis_kd a
            where a.kode_lokasi='".$kode_lokasi."' $filter");
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPenilaianKe(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required',
            'kode_sem' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select COUNT(*)+1 as jumlah from sis_nilai_m where kode_ta= '".$request->kode_ta."' and kode_sem= '".$request->kode_sem."' and kode_kelas= '".$request->kode_kelas."' and kode_matpel= '".$request->kode_matpel."' and kode_jenis= '".$request->kode_jenis."' and kode_lokasi='".$kode_lokasi."' and kode_pp='".$request->kode_pp."' ");
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jumlah'] = $res[0]->jumlah;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Success!";
                $success['jumlah'] = 0;
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function showDokUpload(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $kode_pp = $request->kode_pp;

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel,a.kode_sem,a.kode_pp,b.nama as nama_pp,c.nama as nama_ta,d.nama as nama_kelas,f.nama as nama_jenis,g.nama as nama_matpel,isnull(j.jumlah,0)+1 as jumlah,h.nama as nama_kd,a.kode_kd
            from sis_nilai_m a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                left join sis_ta c on a.kode_ta=c.kode_ta and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
                left join sis_kelas d on a.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
                left join sis_jenisnilai f on a.kode_jenis=f.kode_jenis and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
                left join sis_matpel g on a.kode_matpel=g.kode_matpel and a.kode_lokasi=g.kode_lokasi and a.kode_pp=g.kode_pp
                left join sis_kd h on a.kode_kd=h.kode_kd and a.kode_lokasi=h.kode_lokasi and a.kode_pp=h.kode_pp
                left join ( select kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi,COUNT(*) as jumlah from sis_nilai_m 
                    where no_bukti <> '$no_bukti'
                    group by kode_pp,kode_ta,kode_kelas,kode_sem,kode_matpel,kode_jenis,kode_lokasi
                    ) j on a.kode_ta=j.kode_ta and a.kode_lokasi=j.kode_lokasi and a.kode_pp=j.kode_pp and a.kode_jenis=j.kode_jenis and a.kode_sem=j.kode_sem and a.kode_matpel=j.kode_matpel and a.kode_kelas=j.kode_kelas
            where a.kode_lokasi='".$kode_lokasi."' and a.no_bukti='$no_bukti' and a.kode_pp='$kode_pp'  ");
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_bukti,a.nis,c.nama,isnull(c.file_dok,'-') as fileaddres,b.nama as nama_siswa
            from sis_nilai a 
            inner join sis_siswa b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join sis_nilai_dok c on a.no_bukti=c.no_bukti and a.nis=c.nis and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' and a.kode_pp='$kode_pp' ";
            $res2 = DB::connection('sqlsrvtarbak')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['data_dokumen'] = $res2;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = "FAILED";
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function storeDokumen(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required|max:10',
            'no_bukti' => 'required|max:20'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $kode_pp = $request->kode_pp;
            $arr_foto = array();
            $arr_nama = array();
            $arr_nis = array();
            $i=0;
            $cek = $request->file;
            //cek upload file tidak kosong
            if(!empty($cek)){
                
                if(count($request->nama_file_seb) > 0){
                    //looping berdasarkan nama dok
                    for($i=0;$i<count($request->nama_file_seb);$i++){
                        //cek row i ada file atau tidak
                        if(isset($request->file('file')[$i])){
                            $file = $request->file('file')[$i];
                            //kalo ada cek nama sebelumnya ada atau -
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('sekolah/'.$request->nama_file_seb[$i]);
                            }
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('s3')->exists('sekolah/'.$foto)){
                                Storage::disk('s3')->delete('sekolah/'.$foto);
                            }
                            Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                            $arr_nama[] = $request->input('nama_file')[$i];
                            $arr_nis[] = $request->nis[$i];
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                            $arr_nama[] = $request->input('nama_file')[$i];
                            $arr_nis[] = $request->nis[$i];
                        }     
                    }
                    
                    $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $kode_pp)->delete();
                }

                if(count($arr_nama) > 0){
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins3[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_dok (no_bukti,kode_lokasi,file_dok,no_urut,nama,kode_pp,nis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','".$arr_nama[$i]."','$kode_pp','".$arr_nis[$i]."') "); 
                    }
                    DB::connection('sqlsrvtarbak')->commit();
                    $success['status'] = true;
                    $success['message'] = "Data Dokumen berhasil diupload.";
                    $success['no_bukti'] = $no_bukti;
                    $success['nis'] = $request->nis;
                }
                else{
                    $success['status'] = true;
                    $success['message'] = "Data Dokumen berhasil gagal diupload. Dokumen file tidak valid. (2)";
                    $success['no_bukti'] = $no_bukti;
                }
            }else{
                $success['status'] = true;
                $success['message'] = "Data Dokumen berhasil gagal diupload. Dokumen file tidak valid. (3)";
                $success['no_bukti'] = $no_bukti;
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumenn gagal diupload ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function deleteDokumen(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'nis' => 'required',
            'kode_pp' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,nama,file_dok from sis_nilai_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp' and nis='$request->nis' ";
            $res3 = DB::connection('sqlsrvtarbak')->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('kode_pp', $request->kode_pp)
                ->where('nis', $request->nis)
                ->delete();
                DB::connection('sqlsrvtarbak')->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen Penilaian berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen Penilaian gagal dihapus.";
            }

            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }


}
