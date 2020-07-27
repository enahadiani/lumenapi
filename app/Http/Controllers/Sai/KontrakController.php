<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class KontrakController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select no_dokumen from sai_kontrak where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
                ->subject('Pengajuan Kontrak (SAI LUMEN)');
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

            $res = DB::connection($this->sql)->select("select a.no_kontrak,a.no_dokumen,a.tgl_awal,a.tgl_akhir,a.keterangan,a.nilai 
            from sai_kontrak a
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
            'no_dokumen' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'kode_cust' => 'required',
            'keterangan' => 'required',
            'nilai'=>'required',
            'deskripsi_modul'=>'required|array',
            'nilai_modul'=>'required|array',
            'nama_file'=>'array',
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
                $arr_nama = array();
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
                        $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                        $i++;
                    }
                }

                $periode = date('Ym');
                $per = substr($periode,2,4);
                $no_bukti = $this->generateKode("sai_kontrak", "no_kontrak", $kode_lokasi."-KTR".$per.".", "0001");

                $tgl_awal = $request->tgl_awal.date(" H:i:s");
                $tgl_akhir = $request->tgl_akhir.date(" H:i:s");
                $periode_tagih = substr($request->tgl_awal,0,4).substr($request->tgl_awal,5,2);
                $ins = DB::connection($this->sql)->insert("insert into sai_kontrak (no_kontrak,no_dokumen,tgl_awal,tgl_akhir,keterangan,nilai,kode_lokasi,periode_tagih,kode_cust) values ('$no_bukti','$request->no_dokumen','$tgl_awal','$tgl_akhir','$request->keterangan',".$request->nilai.",'$kode_lokasi','$periode_tagih','$request->kode_cust') ");

                if(count($request->deskripsi_modul) > 0){
                    $nu=1;
                    for($i=0; $i<count($request->deskripsi_modul);$i++){
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into sai_kontrak_d (no_kontrak,kode_lokasi,nu,keterangan,nilai) values ('$no_bukti','".$kode_lokasi."',$nu,'".$request->deskripsi_modul[$i]."',".$request->nilai_modul[0].") ");
                        $nu++; 
                    }
                }

                if(count($arr_nama) > 0){
                    $nu=1;
                    for($i=0; $i<count($arr_nama);$i++){
                        $ins4[$i] = DB::connection($this->sql)->insert("insert into sai_bill_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,nama) values ('$no_bukti','".$arr_foto[$i]."',$nu,'DK01','$kode_lokasi','".$arr_nama[$i]."') ");
                        $nu++; 
                    }
                }
    
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Kontrak berhasil disimpan. No Kontrak:".$no_bukti;
                $success['no_kontrak'] = $no_bukti;
              
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database !";
                $success['no_kontrak'] = '-';
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal disimpan ".$e;
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
            'no_kontrak' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_kontrak,a.no_dokumen,a.tgl_awal,a.tgl_akhir,a.keterangan,a.nilai from sai_kontrak a where a.kode_lokasi='".$kode_lokasi."' and a.no_kontrak='$request->no_kontrak' ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_kontrak,a.nu,a.keterangan,a.nilai from sai_kontrak_d a where a.kode_lokasi='".$kode_lokasi."' and a.no_kontrak='$request->no_kontrak' ";
            
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3="select no_bukti,no_gambar,nu,kode_jenis,nama from sai_bill_dok where kode_lokasi='".$kode_lokasi."' and no_bukti='$request->no_kontrak'  order by nu";
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
            'no_kontrak' => 'required',
            'no_dokumen' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'kode_cust' => 'required',
            'keterangan' => 'required',
            'nilai'=>'required',
            'nama_file'=>'array',
            'file.*'=>'file|max:3072'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_kontrak;

            $arr_foto = array();
            $arr_nama = array();
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
                    $arr_nama[] = $request->input('nama_file')[$i];
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
            
            $del = DB::connection($this->sql)->table('sai_kontrak')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $request->no_kontrak)->delete();
            $del2 = DB::connection($this->sql)->table('sai_kontrak_d')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $request->no_kontrak)->delete();

            $periode = date('Ym');
            $per = substr($periode,2,4);

            $tgl_awal = $request->tgl_awal.date(" H:i:s");
            $tgl_akhir = $request->tgl_akhir.date(" H:i:s");
            $periode_tagih = substr($request->tgl_awal,0,4).substr($request->tgl_awal,5,2);
            $ins = DB::connection($this->sql)->insert("insert into sai_kontrak (no_kontrak,no_dokumen,tgl_awal,tgl_akhir,keterangan,nilai,kode_lokasi,periode_tagih,kode_cust) values ('$no_bukti','$request->no_dokumen','$tgl_awal','$tgl_akhir','$request->keterangan',".$request->nilai.",'$kode_lokasi','$periode_tagih','$request->kode_cust') ");

            if(count($request->deskripsi_modul) > 0){
                $nu=1;
                for($i=0; $i<count($request->deskripsi_modul);$i++){
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into sai_kontrak_d (no_kontrak,kode_lokasi,nu,keterangan,nilai) values ('$no_bukti','".$kode_lokasi."',$nu,'".$request->deskripsi_modul[$i]."',".$request->nilai_modul[0].") ");
                    $nu++; 
                }
            }

            if(count($arr_nama) > 0){
                $nu=1;
                for($i=0; $i<count($arr_nama);$i++){
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into sai_bill_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,nama) values ('$no_bukti','".$arr_foto[$i]."',$nu,'DK01','$kode_lokasi','".$arr_nama[$i]."') ");
                    $nu++; 
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kontrak berhasil diubah. No Kontrak:".$no_bukti;
            $success['no_kontrak'] = $no_bukti;
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal diubah ".$e;
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
            'no_kontrak' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti= $request->no_kontrak;
            $del = DB::connection($this->sql)->table('sai_kontrak')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $no_bukti)->delete();

            
            $del2 = DB::connection($this->sql)->table('sai_kontrak_d')->where('kode_lokasi', $kode_lokasi)->where('no_kontrak', $request->no_kontrak)->delete();

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
            $success['message'] = "Data Kontrak berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kontrak gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPreview(Request $request)
    {
        $this->validate($request,[
            'no_kontrak' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql=" ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
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
