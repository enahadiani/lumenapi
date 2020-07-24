<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class TagihanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_dokumen from sai_bill_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."'  ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function isUnik2($isi,$kode_lokasi,$no_bukti){
        
        $auth = DB::connection($this->sql)->select("select no_dokumen from sai_bill_m where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '$no_bukti' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    function sendMail($email,$to_name,$data){
        try {

            
            $template_data = array("name"=>$to_name,"body"=>$data);
            Mail::send('mail', $template_data,
            function ($message) use ($email) {
                $message->to($email)
                ->subject('Pengajuan Justifikasi Kebutuhan (SAI LUMEN)');
            });
            
            return array('status' => 200, 'msg' => 'Sent successfully');
        } catch (Exception $ex) {
            return array('status' => 200, 'msg' => 'Something went wrong, please try later.');
        }  
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("
            select a.no_bill,a.no_dokumen,a.tanggal,a.keterangan,a.nilai,a.nilai_ppn
            from sai_bill_m a
            where a.kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
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
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'nilai_ppn' => 'required',
            'kode_cust' => 'required',
            'no_kontrak' => 'required',
            'bank' => 'required',
            'cabang'=> 'required',
            'no_rek'=> 'required',
            'nama_rek'=> 'required',
            'item'=> 'required|array',
            'harga'=> 'required|array',
            'jumlah'=> 'required|array',
            'file.*'=>'file|max:3072'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->no_dokumen,$kode_lokasi)){

                $arr_foto = array();
                $i=0;
                // $cek = $request->file;
                // if(!isEmpty($cek)){
                //     foreach($request->file('file') as $file)
                //     {   
                //         if($file->getClientOriginalName()){
                //             $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                //             $foto = $nama_foto;
                //             if(Storage::disk('s3')->exists('apv/'.$foto)){
                //                 Storage::disk('s3')->delete('apv/'.$foto);
                //             }
                //             Storage::disk('s3')->put('apv/'.$foto,file_get_contents($file));
                //             $arr_foto[] = $foto;
                //         }else if($request->file != "") {
                //             $arr_foto[] = str_replace(' ', '_', $request->file);
                //         }else{
                //             $arr_foto[] = "-";
                //         }          
                //         $i++;
                //     }
                // }
                if($request->hasfile('file'))
                {
                    foreach($request->file('file') as $file)
                    {                
                        $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                        $foto = $nama_foto;
                        if(Storage::disk('s3')->exists('sai/'.$foto)){
                            Storage::disk('s3')->delete('sai/'.$foto);
                        }
                        Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                        $arr_foto[] = $foto;
                        $i++;
                    }
                }
    
                $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                $per = substr($periode,2,4);

                $no_bukti = $this->generateKode("sai_bill_m", "no_bill", $kode_lokasi."-BILL".$per.".", "0001");

                $ins = DB::connection($this->sql)->insert("insert into sai_bill_m (no_bill,kode_lokasi,no_dokumen,tanggal,keterangan,kode_curr,kurs,nilai,nilai_ppn,kode_cust,no_kontrak,nik_buat,nik_app,periode,nik_user,tgl_input,bank,cabang,no_rek,nama_rek,progress,modul) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->keterangan','IDR','1',$request->nilai,$request->nilai_ppn,'$request->kode_cust','$request->no_kontrak','$nik_user','-','$periode','$nik_user',getdate(),'$request->bank','$request->cabang','$request->no_rek','$request->nama_rek','0','BILL') ");
    
                $item = $request->input('item');
                $harga = $request->input('harga');
                $jumlah = $request->input('jumlah');
    
                if(count($item) > 0){
                    $nu=1;
                    for($i=0; $i<count($item);$i++){
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bill_d (no_bill,kode_lokasi,nu,item,harga,jumlah) values ('$no_bukti','$kode_lokasi',$nu,'".$item[$i]."',".$harga[$i].",".$jumlah[$i].") ");
                        $nu++;
                    }
                }
    
                if(count($arr_foto) > 0){
                    $nu=1;
                    for($i=0; $i<count($arr_foto);$i++){
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into sai_bill_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi) values ('$no_bukti','".$arr_foto[$i]."',$nu,'DK02','$kode_lokasi') ");
                        $nu++; 
                    }
                }

                DB::connection($this->sql)->commit();
               
                $success['status'] = true;
                $success['message'] = "Data Tagihan berhasil disimpan. No Bukti:".$no_bukti;
                $success['no_bukti'] = $no_bukti;
              
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                $success['no_bukti'] = '-';
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal disimpan ".$e;
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
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql="select no_bill,no_dokumen,tanggal,keterangan,nilai,nilai_ppn,kode_cust,no_kontrak,bank,cabang,no_rek,nama_rek from sai_bill_m where kode_lokasi='".$kode_lokasi."' and no_bill='$no_bukti' ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select nu,item,harga,jumlah from sai_bill_d where kode_lokasi='".$kode_lokasi."' and no_bill='$no_bukti'  order by nu ";					
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,no_gambar,nu,kode_jenis from sai_bill_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by nu";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
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
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required',
            'nilai_ppn' => 'required',
            'kode_cust' => 'required',
            'no_kontrak' => 'required',
            'bank' => 'required',
            'cabang'=> 'required',
            'no_rek'=> 'required',
            'nama_rek'=> 'required',
            'item'=> 'required|array',
            'harga'=> 'required|array',
            'jumlah'=> 'required|array',
            'file.*'=>'file|max:3072'
        ]);


        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                $no_bukti = $request->no_bukti;
                $arr_foto = array();
                $i=0;
                if($request->hasfile('file'))
                {
                    foreach($request->file('file') as $file)
                    {                
                        $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                        $foto = $nama_foto;
                        if(Storage::disk('s3')->exists('sai/'.$foto)){
                            Storage::disk('s3')->delete('sai/'.$foto);
                        }
                        Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                        $arr_foto[] = $foto;
                        $i++;
                    }

                    $sql3="select no_bukti,no_gambar,nu from sai_bill_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by nu";
                    $res3 = DB::connection($this->sql)->select($sql3);
                    $res3 = json_decode(json_encode($res3),true);

                    if(count($res3) > 0){
                        for($i=0;$i<count($res3);$i++){
                            Storage::disk('s3')->delete('sai/'.$res3[$i]['no_gambar']);
                        }
                    }

                    $del3 = DB::connection($this->sql)->table('sai_bill_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                }

                $del = DB::connection($this->sql)->table('sai_bill_m')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
                $del2 = DB::connection($this->sql)->table('sai_bill_d')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
                
                $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                $per = substr($periode,2,4);

                $no_bukti = $request->no_bukti;

                $ins = DB::connection($this->sql)->insert("insert into sai_bill_m (no_bill,kode_lokasi,no_dokumen,tanggal,keterangan,kode_curr,kurs,nilai,nilai_ppn,kode_cust,no_kontrak,nik_buat,nik_app,periode,nik_user,tgl_input,bank,cabang,no_rek,nama_rek,progress,modul) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->keterangan','IDR','1',$request->nilai,$request->nilai_ppn,'$request->kode_cust','$request->no_kontrak','$nik_user','-','$periode','$nik_user',getdate(),'$request->bank','$request->cabang','$request->no_rek','$request->nama_rek','0','BILL') ");
    
                $item = $request->input('item');
                $harga = $request->input('harga');
                $jumlah = $request->input('jumlah');
    
                if(count($item) > 0){
                    $nu=1;
                    for($i=0; $i<count($item);$i++){
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bill_d (no_bill,kode_lokasi,nu,item,harga,jumlah) values ('$no_bukti','$kode_lokasi',$nu,'".$item[$i]."',".$harga[$i].",".$jumlah[$i].") ");
                        $nu++;
                    }
                }
    
                if(count($arr_foto) > 0){
                    $nu=1;
                    for($i=0; $i<count($arr_foto);$i++){
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into sai_bill_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi) values ('$no_bukti','".$arr_foto[$i]."',$nu,'DK02','$kode_lokasi') ");
                        $nu++; 
                    }
                }

                DB::connection($this->sql)->commit();
                
                $success['status'] = true;
                $success['message'] = "Data Tagihan berhasil diubah. No Bukti:".$no_bukti;
                $success['no_bukti'] = $no_bukti;
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
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
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            $del = DB::connection($this->sql)->table('sai_bill_m')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('sai_bill_d')->where('kode_lokasi', $kode_lokasi)->where('no_bill', $no_bukti)->delete();
            $sql3="select no_bukti,no_gambar from sai_bill_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$no_bukti'  order by nu";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){

                    Storage::disk('s3')->delete('sai/'.$res3[$i]['no_gambar']);
                }
            }

            $del3 = DB::connection($this->sql)->table('sai_bill_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Tagihan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPreview($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="";					
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            $sql3 = " ";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
