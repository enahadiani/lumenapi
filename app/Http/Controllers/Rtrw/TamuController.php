<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use SimpleSoftwareIO\QrCode\Facade as QrCode;

class TamuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function cekValid($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_tamu as kode from rt_tamu_m where no_tamu ='$isi' and kode_lokasi='$kode_lokasi' 
        union all
        select id_satpam as kode from rt_satpam where id_satpam ='$isi' and kode_lokasi='$kode_lokasi' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return true;
        }else{
            return false;
        }
    }

    public function cekValidSatpam($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select id_satpam as kode from rt_satpam where id_satpam ='$isi' and kode_lokasi='$kode_lokasi' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return true;
        }else{
            return false;
        }
    }
    
    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.no_tamu,a.id_tamu,b.nama,b.nik,a.keperluan,b.blok+'-'+b.nomor as rumah,a.tgljam_in,a.tgljam_out,case when isnull(convert(varchar,a.tgljam_out,103),'-') = '-' then '-' else DATEDIFF(second,a.tgljam_in,a.tgljam_out) end as selisih
                        from rt_tamu_m a
                        inner join rt_tamu_d b on a.no_tamu=b.no_tamu and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi= '".$kode_lokasi."'";

            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res);$i++){
                    if(intval($res[$i]['selisih']) <=60){
                        $res[$i]['selisih'] = $res[$i]['selisih'].' detik';
                    }else if(intval($res[$i]['selisih']) > 60){
                        if((intval($res[$i]['selisih'])/60) <= 3600){
                            $res[$i]['selisih'] = intval(intval($res[$i]['selisih'])/60).' menit';
                        }else{
                            $res[$i]['selisih'] = intval(intval($res[$i]['selisih'])/3600).' jam';
                        }
                    }
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
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
            'keperluan' => 'required',
            'nama'=>'required|array',
            'nik'=>'required|array',
            'no_rumah' => 'required|array',
            'blok' => 'required|array',
            'ktp'=>'required|file|max:3072'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik_user= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = date('Ym');

            $no_bukti = $this->generateKode("rt_tamu_m", "no_tamu", $kode_lokasi."-IN".substr($periode,2,4).".", "000001");

            $id_tamu = $this->generateKode("rt_tamu_m", "id_tamu", date('Ymd').".", "00001");

            if($request->hasfile('ktp')){

                $file = $request->file('ktp');
                
                $nama_foto = 'ktp-'.uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            // $image = QrCode::size(300)->generate($no_bukti);
            $image = QrCode::format('png')
                            ->size(300)->errorCorrection('H')
                            ->generate($no_bukti);
            $output_file = 'tamuqr-' .uniqid(). '.png';
            Storage::disk('s3')->put('rtrw/'.$output_file, $image);

            $ins = DB::connection($this->sql)->insert("insert into rt_tamu_m (no_tamu, periode, jenis, kode_lokasi, tgljam_in, nik_user, no_kartu, id_tamu, nama, alamat, no_hp, ktp, keperluan, no_keluar, tgljam_out, qrcode) values ('$no_bukti','$periode','UMUM','$kode_lokasi',getdate(),'$nik_user','-','$id_tamu','-','-','-','$foto','$request->keperluan','-',NULL,'$output_file')");

            if(count($request->nama) > 0){
                $no_urut = 1;
                for($i=0; $i<count($request->nama);$i++){
                    $ins = DB::connection($this->sql)->insert("insert into rt_tamu_d (no_tamu,nu,kode_lokasi,blok,nomor,nama,nik) values ('$no_bukti',$no_urut,'$kode_lokasi','".$request->blok[$i]."','".$request->no_rumah[$i]."','".$request->nama[$i]."','".$request->nik[$i]."')");
                    $no_urut++;
                }
            }

            $success['status'] = true;
            $success['message'] = "Data Tamu Masuk berhasil disimpan";
            $success['qrcode'] = url("api/portal/storage")."/".$output_file;
            $success['no_tamu'] = $no_bukti;
            $success['no_urut'] = $id_tamu; 

            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            if(isset($foto)){
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
            }
            
            if(isset($output_file)){
                if(Storage::disk('s3')->exists('rtrw/'.$output_file)){
                    Storage::disk('s3')->delete('rtrw/'.$output_file);
                }
            }
            $success['status'] = false;
            $success['message'] = "Data Tamu Masuk gagal disimpan ".$e;
            $success['no_tamu'] = "";
            $success['no_urut'] = ""; 

            return response()->json($success, $this->successStatus); 
        }			
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'qrcode' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik_user= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = date('Ym');

            $sql = "select a.id_tamu as no_urut
            from rt_tamu_m a
            inner join rt_tamu_d b on a.no_tamu=b.no_tamu and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi= '".$kode_lokasi."' and a.no_tamu='$request->qrcode'";

            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            if(count($rs) > 0){
                $no_urut = $rs[0]['no_urut'];
            }else{
                $no_urut = "-";
            }
            
            if($this->cekValid($request->qrcode,$kode_lokasi)){
                $no_bukti = $this->generateKode("rt_tamu_m", "no_tamu", $kode_lokasi."-OUT".substr($periode,2,4).".", "000001");
                
                $update = DB::connection($this->sql)->update("update rt_tamu_m set no_keluar ='$no_bukti', tgljam_out=getdate(),status_keluar='normal' where no_tamu='$request->qrcode' and kode_lokasi='$kode_lokasi' ");
                
                $success['status'] = true;
                $success['no_urut'] = $no_urut;
                $success['message'] = "Data Tamu Keluar berhasil disimpan";
                
                DB::connection($this->sql)->commit();
            }
            else if($this->cekValid2($request->qrcode,$kode_lokasi)){
                
                $update = DB::connection($this->sql)->update("update rt_tamu_m set no_keluar ='$request->qrcode', tgljam_out=getdate(), status_keluar='satpam' where no_tamu='$request->no_tamu' and kode_lokasi='$kode_lokasi' ");
                
                $success['status'] = true;
                $success['no_urut'] = $no_urut;
                $success['message'] = "Data Tamu Keluar berhasil disimpan";
                
                DB::connection($this->sql)->commit();
            }
            else{
                $success['status'] = false;
                $success['no_urut'] = $no_urut;
                $success['message'] = "Qrcode tidak valid";
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['no_urut'] = "-";
            $success['message'] = "Data Tamu Keluar gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }   
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
    }
}
