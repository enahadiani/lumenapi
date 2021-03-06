<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class JamaahController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_peserta from dgw_paket where id_peserta ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    
    public function cekKTP($no_ktp, $kode_lokasi) {
        $auth = DB::connection($this->sql)->select("select id_peserta from dgw_peserta where id_peserta ='".$no_ktp."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return true;
        }else{
            return false;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function cekKTPChange(Request $request) {
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $ktp = $request->no_ktp;
        $cek = $this->cekKTP($ktp, $kode_lokasi);
        if($cek) {
            $success['status'] = "FAILED";
            $success['message'] = "KTP Jamaah sudah tersimpan di sistem";
            return response()->json($success, $this->successStatus);
        }else{
            $success['status'] = "SUCCESS";
            $success['message'] = "";
            return response()->json($success, $this->successStatus);
        }
    }

    public function index(Request $request)
    {
        // $this->validate($request, [
        //     'no_peserta' => 'required'
        // ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            // if(isset($request->no_peserta)){
            //     if($request->no_peserta == "all"){
            //         $filter = "";
            //     }else{

            //         $filter = " and no_peserta='$request->no_peserta' ";
            //     }
            // }else{
            //     $filter = "";
            // }

            $sql = "select no_peserta, kode_lokasi, id_peserta, nama, jk, status, alamat, kode_pos, telp, hp, email, pekerjaan, bank, cabang, norek, namarek, nopass, kantor_mig, sp, ec_telp, ec_hp, issued, ex_pass, tempat, tgl_lahir, th_haji, 
            th_umroh, ibu, '".url('dago/storage')."/'+foto as foto, ayah, pendidikan
            from dgw_peserta
            where kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                // for($i=0;$i < count($res);$i++){
                //     $res2 = DB::connection($this->sql)->select("select case when a.kode_curr = 'IDR' then a.nilai_p+a.nilai_t+a.nilai_m else (a.nilai_p*a.kurs)+a.nilai_t+a.nilai_m end as nilai_bayar
                //     from dgw_pembayaran a
                //     inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi
                //     inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                //     where a.kode_lokasi='$kode_lokasi' and c.no_peserta = '".$res[$i]['no_peserta']."'
                //     order by b.tanggal");
                //     $res[$i]['payments'] = array();
                //     $no=1;
                //     foreach ($res2 as $row) {
                //         $res[$i]['payments'][] = array($no => $row->nilai_bayar);
                //         $no++;
                //     }   

                //     $res3 = DB::connection($this->sql)->select("select ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS id,a.deskripsi as name,case when isnull(c.no_gambar,'-') ='-' then 'not uploaded' else 'uploaded' end as status, case when isnull(c.no_gambar,'-') ='-' then '-' else isnull(c.no_gambar,'-') end as url
                //     from dgw_dok a 
                //     left join dgw_reg_dok b on a.no_dokumen=b.no_dok
                //     left join dgw_reg d on b.no_reg = d.no_reg  
                //     left join dgw_scan c on a.no_dokumen=c.modul and c.no_bukti = b.no_reg and c.no_bukti=d.no_reg
                //     where d.no_peserta = '".$res[$i]['no_peserta']."'
                //     order by a.no_dokumen ");
                //     $res3 = json_decode(json_encode($res3),true);
                //     if(count($res3) > 0){
                //         $res[$i]['documents'] = $res3;
                //     }else{
                //         $res[$i]['documents'] = array();
                //     }
                // }
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['sql']= $sql;
                $success['status'] = "SUCCESS";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
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
            'id_peserta' => 'required',
            'nama' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required|date_format:Y-m-d',
            'jk' => 'required|in:Perempuan,Laki-laki',
            'status' => 'required|in:-,Menikah,Belum Menikah,Cerai Mati,Cerai Hidup',
            'ibu' => 'required',
            'ayah' => 'required',
            'alamat' => 'required',
            'kode_pos' => 'required',
            'telp' => 'required',
            'hp' => 'required',
            'email' => 'required|email',
            'pekerjaan' => 'required',
            'ukuran_pakaian' => 'required',
            'bank' => 'required',
            'norek' => 'required',
            'cabang' => 'required',
            'namarek' => 'required',
            'nopass' => 'required',
            'issued' => 'required|date_format:Y-m-d',
            'ex_pass' => 'required|date_format:Y-m-d',
            'kantor_mig' => 'required',
            'ec_telp' => 'required',
            'ec_hp' => 'required',
            'sp' => 'required',
            'th_haji' => 'required',
            'th_umroh' => 'required',
            'brkt_dgn' => 'required',
            'hubungan' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'pendidikan' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $cekKtp = $this->cekKTP($request->id_peserta, $kode_lokasi);
            if($cekKtp) {
                 $success['status'] = "FAILED";
                 $success['message'] = "KTP Jamaah sudah tersimpan di sistem";
                 return response()->json($success, $this->successStatus);
            }else {
                $tahun = date('y');
                $no_peserta = $this->generateKode("dgw_peserta", "no_peserta", $tahun, "00001");

                if($request->hasfile('foto')){

                    $file = $request->file('foto');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('dago/'.$foto)){
                        Storage::disk('s3')->delete('dago/'.$foto);
                    }
                    Storage::disk('s3')->put('dago/'.$foto,file_get_contents($file));
                    
                }else{

                    $foto="-";
                }

                $ins = DB::connection($this->sql)->insert('insert into dgw_peserta(no_peserta,kode_lokasi,id_peserta,nama,tempat,tgl_lahir,jk,status,ibu,alamat,kode_pos,telp,hp,email,pekerjaan,bank,norek,cabang,namarek,nopass,issued,ex_pass,kantor_mig,ec_telp,ec_hp,sp,th_haji,th_umroh,foto,ayah,pendidikan,brkt_dgn,hubungan,ukuran_pakaian) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($no_peserta,$kode_lokasi,$request->id_peserta,$request->nama, $request->tempat, $request->tgl_lahir,$request->jk,$request->status,$request->ibu,$request->alamat,$request->kode_pos,$request->telp,$request->hp,$request->email,$request->pekerjaan,$request->bank,$request->norek,$request->cabang,$request->namarek,$request->nopass,$request->issued,$request->ex_pass,$request->kantor_mig,$request->ec_telp,$request->hp,$request->sp,$request->th_haji,$request->th_umroh,$foto,$request->ayah,$request->pendidikan,$request->brkt_dgn,$request->hubungan,$request->ukuran_pakaian));
                
                DB::connection($this->sql)->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Jamaah berhasil disimpan. No jamaah:".$no_peserta;
                
                return response()->json($success, $this->successStatus);
            }     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Jamaah gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $this->validate($request, [
            'no_peserta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select no_peserta,id_peserta,nama,tempat,tgl_lahir,jk,status,ibu,ayah,alamat,kode_pos,telp,hp,email,pekerjaan,bank,norek,cabang,namarek,nopass,issued,ex_pass,kantor_mig,ec_telp,ec_hp,sp,th_haji,th_umroh,brkt_dgn,hubungan,'".url('api/dago-auth/storage')."/'+foto as foto,pendidikan,ukuran_pakaian from dgw_peserta where kode_lokasi='".$kode_lokasi."' and no_peserta='$request->no_peserta' ");
            $res = json_decode(json_encode($res),true);

           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

    public function editById(Request $request)
    {
        $this->validate($request, [
            'id_peserta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select no_peserta,id_peserta,nama,tempat,tgl_lahir,jk,status,ibu,ayah,alamat,kode_pos,telp,hp,email,pekerjaan,bank,norek,cabang,namarek,nopass,issued,ex_pass,kantor_mig,ec_telp,ec_hp,sp,th_haji,th_umroh,brkt_dgn,hubungan,'".url('api/dago-auth/storage')."/'+foto as foto,pendidikan,ukuran_pakaian from dgw_peserta where kode_lokasi='".$kode_lokasi."' and id_peserta='$request->id_peserta' ");
            $res = json_decode(json_encode($res),true);

           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

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
            'no_peserta' => 'required',
            'id_peserta' => 'required',
            'nama' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required|date_format:Y-m-d',
            'jk' => 'required|in:Perempuan,Laki-laki',
            'status' => 'required|in:-,Menikah,Belum Menikah,Cerai Mati,Cerai Hidup',
            'ibu' => 'required',
            'ayah' => 'required',
            'alamat' => 'required',
            'kode_pos' => 'required',
            'telp' => 'required',
            'hp' => 'required',
            'email' => 'required|email',
            'pekerjaan' => 'required',
            'ukuran_pakaian' => 'required',
            'bank' => 'required',
            'norek' => 'required',
            'cabang' => 'required',
            'namarek' => 'required',
            'nopass' => 'required',
            'issued' => 'required|date_format:Y-m-d',
            'ex_pass' => 'required|date_format:Y-m-d',
            'kantor_mig' => 'required',
            'ec_telp' => 'required',
            'ec_hp' => 'required',
            'sp' => 'required',
            'th_haji' => 'required',
            'th_umroh' => 'required',
            'brkt_dgn' => 'required',
            'hubungan' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'pendidikan' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_peserta = $request->no_peserta;

            $sql = "select foto as file_gambar from dgw_peserta where kode_lokasi='".$kode_lokasi."' and no_peserta='$no_peserta' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }
            
            if($request->hasfile('foto')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('dago/'.$foto);
                }
                
                $file = $request->file('foto');
                
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('dago/'.$foto)){
                    Storage::disk('s3')->delete('dago/'.$foto);
                }
                Storage::disk('s3')->put('dago/'.$foto,file_get_contents($file));
                
            }
            
            $del = DB::connection($this->sql)->table('dgw_peserta')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_peserta', $no_peserta)
            ->delete();		
            
            $ins = DB::connection($this->sql)->insert('insert into dgw_peserta(no_peserta,kode_lokasi,id_peserta,nama,tempat,tgl_lahir,jk,status,ibu,alamat,kode_pos,telp,hp,email,pekerjaan,bank,norek,cabang,namarek,nopass,issued,ex_pass,kantor_mig,ec_telp,ec_hp,sp,th_haji,th_umroh,foto,ayah,pendidikan,brkt_dgn,hubungan,ukuran_pakaian) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($no_peserta,$kode_lokasi,$request->id_peserta,$request->nama, $request->tempat, $request->tgl_lahir,$request->jk,$request->status,$request->ibu,$request->alamat,$request->kode_pos,$request->telp,$request->hp,$request->email,$request->pekerjaan,$request->bank,$request->norek,$request->cabang,$request->namarek,$request->nopass,$request->issued,$request->ex_pass,$request->kantor_mig,$request->ec_telp,$request->hp,$request->sp,$request->th_haji,$request->th_umroh,$foto,$request->ayah,$request->pendidikan,$request->brkt_dgn,$request->hubungan, $request->ukuran_pakaian));
            
            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Paket berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Paket gagal diubah ".$e;
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
        $this->validate($request, [
            'no_peserta' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select count(*) as jml from dgw_reg where no_peserta='".$request->no_peserta."' and kode_lokasi='".$kode_lokasi."'";					
            $res = DB::connection($this->sql)->select($strSQL); 
            $res = json_decode(json_encode($res),true);
            if (count($res) > 0){
                $line = $res[0];							
                if ($line['jml'] != 0) {
                    $msg = "Jamaah tidak dapat dihapus. Jamaah telah melakukan registrasi umroh/haji";
                    $sts = "FAILED";	
                }else{
                    $sql = "select foto as file_gambar from dgw_peserta where kode_lokasi='".$kode_lokasi."' and no_peserta='$request->no_peserta' 
                    ";
                    $res = DB::connection($this->sql)->select($sql);
                    $res = json_decode(json_encode($res),true);
                    if(count($res) > 0){
                        $foto = $res[0]['file_gambar'];
                        if($foto != "" || $foto != "-"){
                            Storage::disk('s3')->delete('dago/'.$foto);
                        }
                    }else{
                        $foto = "-";
                    }
                    $del = DB::connection($this->sql)->table('dgw_peserta')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_peserta', $request->no_peserta)
                    ->delete();
                    
                    DB::connection($this->sql)->commit();
                    $msg = "Data Jamaah berhasil dihapus";
                    $sts = "SUCCESS";
                } 	
            }
            

            $success['status'] = $sts;
            $success['message'] = $msg;
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Paket gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
