<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PesanController extends Controller
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

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,a.jenis,a.judul,a.pesan,a.tgl_input, case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp 
            from sis_pesan_m a
            where a.tipe='info' and a.kode_lokasi='$kode_lokasi' $filter");
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
            'jenis' => 'required',
            'judul' => 'required',
            'kode_pp' => 'required',
            'kontak' => 'required',
            'pesan' => 'required',
            'tipe' => 'required',
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $per = date('ym');
            $no_bukti = $this->generateKode("sis_pesan_m", "no_bukti", $kode_lokasi."-PSN".$per.".", "000001");
            
            $arr_foto = array();
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
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                        }     
                    }
                    
                    $del3 = DB::connection('sqlsrvtarbak')->table('sis_pesan_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $request->kode_pp)->delete();
                }
                
            }
            
            if($request->jenis == "Siswa"){
                $nis = $request->kontak;
                $kode_kelas = '-';
                $sql = "select id_device from sis_siswa where nis='$nis' and kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' --and isnull(id_device,'-') <> '-' ";
            }else{
                $nis = "-";
                $kode_kelas = $request->kontak;
                $sql = "select id_device from sis_siswa where kode_kelas='$kode_kelas' and kode_pp='$request->kode_pp' and kode_lokasi='$kode_lokasi' --and isnull(id_device,'-') <> '-' ";
            }
            
            $ref1 = (isset($request->ref1) && $request->ref1 != "" ? $request->ref1 : '-');
            $ref2 = (isset($request->ref2) && $request->ref2 != "" ? $request->ref2 : '-');
            $ref3 = (isset($request->ref3) && $request->ref3 != "" ? $request->ref3 : '-');
            $link = (isset($request->link) && $request->link != "" ? $request->link : '-');
            
            $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_pesan_m(no_bukti,jenis,nis,kode_akt,kode_kelas,judul,subjudul,pesan,kode_pp,kode_lokasi,ref1,ref2,ref3,link,tipe,tgl_input,nik_user) values ('$no_bukti','$request->jenis','$nis','-','$kode_kelas','$request->judul','-','$request->pesan','$request->kode_pp','$kode_lokasi','$ref1','$ref2','$ref3','$link','$request->tipe',getdate(),'$nik') ");
            
            $cek = DB::connection('sqlsrvtarbak')->select($sql);
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                for($i=0;$i<count($cek);$i++){
                    
                    $ins2[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_pesan_d(no_bukti,kode_lokasi,kode_pp,sts_read,sts_read_mob,id_device) values ('$no_bukti','$kode_lokasi','$request->kode_pp','0','0','".$cek[$i]['id_device']."') ");
                    
                }  
            }
            
            if(count($arr_foto) > 0){
                for($i=0; $i<count($arr_foto);$i++){
                    $ins3[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_pesan_dok (
                        no_bukti,kode_lokasi,file_dok,no_urut,kode_pp) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','$request->kode_pp') "); 
                }
            }
            
            DB::connection('sqlsrvtarbak')->commit();
            $sts = true;
            $msg = "Data Pesan berhasil disimpan.";

            // sendNotif();
        
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal disimpan ".$e;
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
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.no_bukti,case a.jenis when 'Siswa' then a.nis when 'Kelas' then a.kode_kelas end as kontak,a.judul,a.pesan,a.kode_pp,a.ref1,a.ref2,a.ref3,a.link,a.tgl_input
            from sis_pesan_m a
            where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' and a.tipe='info'
            ");
            $res = json_decode(json_encode($res),true);

            $res3 = DB::connection('sqlsrvtarbak')->select("select 
            a.no_bukti,a.kode_lokasi,a.file_dok,a.no_urut,a.kode_pp from sis_pesan_dok a where a.kode_lokasi = '".$kode_lokasi."' and a.no_bukti='$request->no_bukti' and a.kode_pp='$request->kode_pp' ");
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_dok'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_dok'] = [];
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
    // public function update(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_bukti' => 'required',
    //         'jenis' => 'required',
    //         'judul' => 'required',
    //         'pesan' => 'required',
    //         'tipe' => 'required',
    //     ]);
        
    //     DB::connection('sqlsrvtarbak')->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard('tarbak')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
            
    //         if(count($request->nis) > 0){
                
    //             $no_bukti = $request->no_bukti;
    //             $strSQL = "select nu as jumlah from sis_nilai_m where no_bukti='$no_bukti' ";	
    //             $cek = DB::connection('sqlsrvtarbak')->select($strSQL);
    //             if(count($cek) > 0){
    //                 $no_urut = $cek[0]->jumlah;
    //             }else{
    //                 $no_urut = 1;
    //             }

    //             // $arr_foto = array();
    //             // $arr_nama = array();
    //             // $arr_foto2 = array();
    //             // $arr_nama2 = array();
    //             // $i=0;
    //             // $cek = $request->file;
    //             // //cek upload file tidak kosong
    //             // if(!empty($cek)){

    //             //     if(count($request->nama_file) > 0){
    //             //         //looping berdasarkan nama dok
    //             //         for($i=0;$i<count($request->nama_file);$i++){
    //             //             //cek row i ada file atau tidak
    //             //             if(isset($request->file('file')[$i])){
    //             //                 $file = $request->file('file')[$i];

    //             //                 //kalo ada cek nama sebelumnya ada atau -
    //             //                 if($request->nama_file_seb[$i] != "-"){
    //             //                     //kalo ada hapus yang lama
    //             //                     Storage::disk('s3')->delete('sekolah/'.$request->nama_file_seb[$i]);
    //             //                 }
    //             //                 $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
    //             //                 $foto = $nama_foto;
    //             //                 if(Storage::disk('s3')->exists('sekolah/'.$foto)){
    //             //                     Storage::disk('s3')->delete('sekolah/'.$foto);
    //             //                 }
    //             //                 Storage::disk('s3')->put('sekolah/'.$foto,file_get_contents($file));
    //             //                 $arr_foto[] = $foto;
    //             //             }else{
    //             //                 $arr_foto[] = $request->nama_file_seb[$i];
    //             //             }     
    //             //             $arr_nama[] = $request->input('nama_file')[$i];
    //             //             $arr_nama2[] = count($request->nama_file).'|'.$i.'|'.isset($request->file('file')[$i]);
    //             //         }
    
    //             //         $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->where('kode_pp', $kode_pp)->delete();
    //             //     }
    //             // }

    //             $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_bukti', $request->no_bukti)
    //             ->delete();
    
    //             $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_bukti', $request->no_bukti)
    //             ->delete();


    //             $ins = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_m(no_bukti,kode_ta,kode_kelas,kode_matpel,kode_jenis,kode_sem,tgl_input,nu,kode_lokasi,kode_pp,kode_kd) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->kode_ta,$request->kode_kelas,$request->kode_matpel,$request->kode_jenis,$request->kode_sem,date('Y-m-d H:i:s'),$no_urut,$kode_lokasi,$request->kode_pp,$request->kode_kd));
    //             for($i=0;$i<count($request->nis);$i++){
    //                 $ins2[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_nilai(no_bukti,nis,nilai,kode_lokasi,kode_pp) values (?, ?, ?, ?, ?)', array($no_bukti,$request->nis[$i],$request->nilai[$i],$kode_lokasi,$request->kode_pp));
                    
    //             }  

    //             // if(count($arr_nama) > 0){
    //             //     for($i=0; $i<count($arr_nama);$i++){
    //             //         $ins3[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_nilai_dok (
    //             //         no_bukti,kode_lokasi,file_dok,no_urut,nama,kode_pp,nis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$i."','".$arr_nama[$i]."','$request->kode_pp','".$request->nis_dok[$i]."') "); 
    //             //     }
    //             // }

    //             DB::connection('sqlsrvtarbak')->commit();
    //             $sts = true;
    //             $msg = "Data Penilaian berhasil diubah.";
                
    //         }else{
    //             $sts = true;
    //             $no_bukti = "-";
    //             $msg = "Data Penilaian gagal diubah. Detail Penilaian tidak valid";
    //         }
            
    //         $success['no_bukti'] = $no_bukti;
    //         $success['status'] = $sts;
    //         $success['message'] = $msg;
     
    //         return response()->json(['success'=>$success], $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         DB::connection('sqlsrvtarbak')->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Data Penilaian gagal diubah ".$e;
    //         return response()->json(['success'=>$success], $this->successStatus); 
    //     }	
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    // public function destroy(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_bukti' => 'required',
    //         'kode_pp' => 'required'
    //     ]);
    //     DB::connection('sqlsrvtarbak')->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard('tarbak')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }		
            
    //         $del = DB::connection('sqlsrvtarbak')->table('sis_nilai_m')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_bukti', $request->no_bukti)
    //         ->where('kode_pp', $request->kode_pp)
    //         ->delete();

    //         $del2 = DB::connection('sqlsrvtarbak')->table('sis_nilai')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_bukti', $request->no_bukti)
    //         ->where('kode_pp', $request->kode_pp)
    //         ->delete();

    //         // $sql3="select no_bukti,nama,file_dok from sis_nilai_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti' and kode_pp='$kode_pp'  order by no_urut";
    //         // $res3 = DB::connection('sqlsrvtarbak')->select($sql3);
    //         // $res3 = json_decode(json_encode($res3),true);

    //         // if(count($res3) > 0){
    //         //     for($i=0;$i<count($res3);$i++){
    //         //         Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
    //         //     }
    //         // }

    //         // $del3 = DB::connection('sqlsrvtarbak')->table('sis_nilai_dok')
    //         // ->where('kode_lokasi', $kode_lokasi)
    //         // ->where('no_bukti', $no_bukti)
    //         // ->where('kode_pp', $request->kode_pp)
    //         // ->delete();

    //         DB::connection('sqlsrvtarbak')->commit();
    //         $success['status'] = true;
    //         $success['message'] = "Data Penilaian berhasil dihapus";
            
    //         return response()->json(['success'=>$success], $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         DB::connection('sqlsrvtarbak')->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Data Penilaian gagal dihapus ".$e;
            
    //         return response()->json(['success'=>$success], $this->successStatus); 
    //     }	
    // }

    public function deleteDokumen(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required',
            'nu' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,file_dok from sis_pesan_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and kode_pp='$request->kode_pp' and no_urut='$request->nu' ";
            $res3 = DB::connection('sqlsrvtarbak')->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sekolah/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection('sqlsrvtarbak')->table('sis_pesan_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('kode_pp', $request->kode_pp)
                ->where('no_urut', $request->nu)
                ->delete();

                DB::connection('sqlsrvtarbak')->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen gagal dihapus.";
            }

            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }


}
