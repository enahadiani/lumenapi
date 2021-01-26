<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KasBankDokController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "toko";
    public $db = "tokoaws";

    public function show(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;

            $sql="
            select a.no_bukti,a.kode_lokasi,a.jenis,a.file_dok as fileaddres,a.no_urut,a.nama from trans_dok a
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' order by a.no_urut ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data_dokumen'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data_dokumen'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required|max:20'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $arr_foto = array();
            $arr_jenis = array();
            $arr_no_urut = array();
            $arr_nama_dok = array();
            $i=0;
            $cek = $request->file;
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
                                Storage::disk('s3')->delete('toko/'.$request->nama_file_seb[$i]);
                            }
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            if(Storage::disk('s3')->exists('toko/'.$foto)){
                                Storage::disk('s3')->delete('toko/'.$foto);
                            }
                            Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                            $arr_jenis[] = $request->jenis[$i];
                            $arr_no_urut[] = $request->no_urut[$i];
                            $arr_nama_dok[] = $request->nama_dok[$i];
                        }else if($request->nama_file_seb[$i] != "-"){
                            $arr_foto[] = $request->nama_file_seb[$i];
                            $arr_jenis[] = $request->jenis[$i];
                            $arr_no_urut[] = $request->no_urut[$i];
                            $arr_nama_dok[] = $request->nama_dok[$i];
                        }     
                    }
                    
                    $del3 = DB::connection($this->db)->table('trans_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                }

                if(count($arr_no_urut) > 0){
                    for($i=0; $i<count($arr_no_urut);$i++){
                        $ins3[$i] = DB::connection($this->db)->insert("insert into trans_dok (no_bukti,kode_lokasi,file_dok,no_urut,nama,jenis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$arr_no_urut[$i]."','".$arr_nama_dok[$i]."','".$arr_jenis[$i]."') "); 
                    }
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Data Dokumen berhasil diupload.";
                    $success['no_bukti'] = $no_bukti;
                    $success['jenis'] = $request->jenis;
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
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumenn gagal diupload ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_jenis' => 'required',
            'no_urut' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,kode_lokasi,file_dok,no_urut,nama,jenis from trans_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_bukti' and jenis='$request->kode_jenis' and no_urut='$request->no_urut' ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('toko/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection($this->db)->table('trans_dok')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('jenis', $request->kode_jenis)
                ->where('no_urut', $request->no_urut)
                ->delete();
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen Kas Bank berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen Kas Bank gagal dihapus.";
            }

            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen Kas Bank gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
