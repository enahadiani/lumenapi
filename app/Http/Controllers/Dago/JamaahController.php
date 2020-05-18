<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JamaahController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select no_peserta from dgw_paket where id_peserta ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->no_peserta)){
                if($request->no_peserta == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_peserta='$request->no_peserta' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select("select no_peserta, kode_lokasi, id_peserta, nama, jk, status, alamat, kode_pos, telp, hp, email, pekerjaan, bank, cabang, norek, namarek, nopass, kantor_mig, sp, ec_telp, ec_hp, issued, ex_pass, tempat, tgl_lahir, th_haji, 
            th_umroh, ibu, foto, ayah, pendidikan
            from dgw_peserta
            where kode_lokasi='".$kode_lokasi."' $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i < count($res);$i++){
                    $res2 = DB::connection('sqlsrvdago')->select("select case when a.kode_curr = 'IDR' then a.nilai_p+a.nilai_t+a.nilai_m else (a.nilai_p*a.kurs)+a.nilai_t+a.nilai_m end as nilai_bayar
                    from dgw_pembayaran a
                    inner join dgw_reg c on a.no_reg=c.no_reg and a.kode_lokasi=c.kode_lokasi
                    inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and c.no_peserta = '".$res[$i]['no_peserta']."'
                    order by b.tanggal");
                    $res[$i]['payments'] = array();
                    $no=1;
                    foreach ($res2 as $row) {
                        $res[$i]['payments'][] = array($no => $row->nilai_bayar);
                        $no++;
                    }   

                    $res3 = DB::connection('sqlsrvdago')->select("select ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS id,a.deskripsi as name,case when isnull(c.no_gambar,'-') ='-' then 'not uploaded' else 'uploaded' end as status, case when isnull(c.no_gambar,'-') ='-' then '-' else isnull(c.no_gambar,'-') end as url
                    from dgw_dok a 
                    left join dgw_reg_dok b on a.no_dokumen=b.no_dok
                    left join dgw_reg d on b.no_reg = d.no_reg  
                    left join dgw_scan c on a.no_dokumen=c.modul and c.no_bukti = b.no_reg and c.no_bukti=d.no_reg
                    where d.no_peserta = '".$res[$i]['no_peserta']."'
                    order by a.no_dokumen ");
                    $res3 = json_decode(json_encode($res3),true);
                    if(count($res3) > 0){
                        $res[$i]['documents'] = $res3;
                    }else{
                        $res[$i]['documents'] = array();
                    }
                }
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "SUCCESS";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    // /**
    //  * Store a newly created resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function store(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_paket' => 'required',
    //         'nama' => 'required',
    //         'kode_curr' => 'required',
    //         'jenis' => 'required',
    //         'kode_produk' => 'required',
    //         'tarif_agen' => 'required',
    //         'data_harga.*.kode_harga' => 'required',
    //         'data_harga.*.harga' => 'required',
    //         'data_harga.*.harga_se' => 'required',
    //         'data_harga.*.harga_e' => 'required',
    //         'data_harga.*.fee' => 'required',
    //         'data_harga.*.curr_fee' => 'required',
    //         'data_jadwal.*.tgl_berangkat' => 'required',
    //         'data_jadwal.*.lama_hari' => 'required',
    //         'data_jadwal.*.quota' => 'required',
    //         'data_jadwal.*.quota_se' => 'required',
    //         'data_jadwal.*.quota_e' => 'required',
    //         'data_jadwal.*.tgl_datang' => 'required'
    //     ]);

    //     DB::connection('sqlsrvdago')->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard('dago')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
    //         if($this->isUnik($request->no_paket,$kode_lokasi)){

    //             $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_paket(
    //             no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen,kode_lokasi) values values (?, ?, ?, ?, ?, ?, ?)', array($request->no_paket,$request->nama,$request->kode_curr,$request->jenis, $request->kode_produk, $request->tarif_agen,$kode_lokasi));

    //             $detHarga = $request->data_harga;

    //             if (count($detHarga) > 0){
    //                 for ($i=0;$i < count($detHarga);$i++){
    //                     $ins2[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_harga(no_paket,kode_harga,harga,harga_se,harga_e,fee,kode_lokasi,curr_fee) values (?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detHarga[$i]['kode_harga'],$detHarga[$i]['harga'],$detHarga[$i]['harga_se'],$detHarga[$i]['harga_e'],$detHarga[$i]['fee'],$kode_lokasi,$detHarga[$i]['curr_fee']));
    //                 }						
    //             }

    //             $strSQL = "select isnull(max(no_jadwal),0) + 1 as id_jadwal from dgw_jadwal where no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";	
                
    //             $res = DB::connection('sqlsrvdago')->select($strSQL); 
    //             $res = json_decode(json_encode($res),true);
    //             if (count($res) > 0){
    //                 $line = $res[0];							
    //                 $idJadwal = intval($line['id_jadwal']);
    //             } 

    //             $detJadwal = $request->data_jadwal;
    //             if (count($detJadwal) > 0){
    //                 for ($i=0;$i < count($detJadwal);$i++){
                       
    //                     $ins3[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_jadwal(no_paket,no_jadwal,tgl_berangkat,lama_hari,quota,quota_se,quota_e,kode_lokasi, no_closing,kurs_closing,id_pbb,tgl_datang) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$idJadwal,$detJadwal[$i]['tgl_berangkat'],$detJadwal[$i]['lama_hari'],$detJadwal[$i]['quota'],$detJadwal[$i]['quota_se'],$detJadwal[$i]['quota_e'],$kode_lokasi,'-',0,'-',$detJadwal[$i]['tgl_datang']));   
    //                     $idJadwal = $idJadwal + 1;	
    //                 }						
    //             }
                
    //             DB::connection('sqlsrvdago')->commit();
    //             $success['status'] = "SUCCESS";
    //             $success['message'] = "Data Paket berhasil disimpan";
    //         }else{
    //             $success['status'] = "FAILED";
    //             $success['message'] = "Error : Duplicate entry. No Paket sudah ada di database!";
    //         }
            
    //         return response()->json($success, $this->successStatus);     
    //     } catch (\Throwable $e) {
    //         DB::connection('sqlsrvdago')->rollback();
    //         $success['status'] = "FAILED";
    //         $success['message'] = "Data Paket gagal disimpan ".$e;
    //         return response()->json($success, $this->successStatus); 
    //     }				
        
        
    // }


    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  \App\Fs  $Fs
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_paket' => 'required'
    //     ]);
    //     try {
            
    //         if($data =  Auth::guard('dago')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

    //         $res = DB::connection('sqlsrvdago')->select( "select 
    //         no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen where kode_lokasi='".$kode_lokasi."' and no_paket='$request->no_paket' ");
    //         $res = json_decode(json_encode($res),true);

    //         $res2 = DB::connection('sqlsrvdago')->select("select kode_harga,no_paket,harga,harga_se,fee,curr_fee from dgw_harga where kode_lokasi='".$kode_lokasi."' and no_paket='$request->no_paket' ");
    //         $res2 = json_decode(json_encode($res2),true);

    //         $res3 = DB::connection('sqlsrvdago')->select("select no_jadwal,tgl_berangkat,no_paket,lama_hari,quota,quota_se,quota_e,no_closing,kurs_closing,id_pbb,tgl_datang,tgl_cetak from dgw_jadwal where kode_lokasi='".$kode_lokasi."' and no_paket='$request->no_paket' ");
    //         $res3 = json_decode(json_encode($res3),true);
            
    //         if(count($res) > 0){ //mengecek apakah data kosong atau tidak
    //             $success['status'] = "SUCCESS";
    //             $success['data'] = $res;
    //             $success['data_harga'] = $res2;
    //             $success['data_jadwal'] = $res3;
    //             $success['message'] = "Success!";     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!";
    //             $success['data'] = [];
    //             $success['data_harga'] = [];
    //             $success['data_jadwal'] = [];
    //             $success['status'] = "FAILED";
    //         }
    //         return response()->json($success, $this->successStatus);
    //     } catch (\Throwable $e) {
    //         $success['status'] = "FAILED";
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }

    // }

    // /**
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  \App\Fs  $Fs
    //  * @return \Illuminate\Http\Response
    //  */
    // public function update(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_paket' => 'required',
    //         'nama' => 'required',
    //         'kode_curr' => 'required',
    //         'jenis' => 'required',
    //         'kode_produk' => 'required',
    //         'tarif_agen' => 'required',
    //         'data_harga.*.kode_harga' => 'required',
    //         'data_harga.*.harga' => 'required',
    //         'data_harga.*.harga_se' => 'required',
    //         'data_harga.*.harga_e' => 'required',
    //         'data_harga.*.fee' => 'required',
    //         'data_harga.*.curr_fee' => 'required',
    //         'data_jadwal.*.tgl_berangkat' => 'required',
    //         'data_jadwal.*.lama_hari' => 'required',
    //         'data_jadwal.*.quota' => 'required',
    //         'data_jadwal.*.quota_se' => 'required',
    //         'data_jadwal.*.quota_e' => 'required',
    //         'data_jadwal.*.tgl_datang' => 'required'
    //     ]);

    //     DB::connection('sqlsrvdago')->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard('dago')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
    //         //update idJadwal
    //         $detJadwal = $request->data_jadwal;
    //         if (count($detJadwal) > 0){
    //             for ($i=0;$i < count($detJadwal);$i++){
                   
    //                 $strSQL = "select no_jadwal from dgw_jadwal where tgl_berangkat='".$detJadwal[$i]['tgl_berangkat']."' and no_paket='".$detJadwal[$i]['no_paket']."' and kode_lokasi='".$kode_lokasi."'";					
    //                 $res = DB::connection('sqlsrvdago')->select($strSQL); 
    //                 $res = json_decode(json_encode($res),true);
    //                 if (count($res) > 0){
    //                     $line = $res[0];
    //                     $detJadwal[$i]['no_jadwal'] = $line[$i]['no_jadwal'];
    //                 }								
    //                 else $detJadwal[$i]['no_jadwal'] = "ID";
    //             }
    //         }


    //         $del = DB::connection('sqlsrvdago')->table('dgw_paket')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_paket', $request->no_paket)
    //         ->delete();		

    //         $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_paket(
    //         no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen,kode_lokasi) values values (?, ?, ?, ?, ?, ?, ?)', array($request->no_paket,$request->nama,$request->kode_curr,$request->jenis, $request->kode_produk, $request->tarif_agen,$kode_lokasi));
            
    //         $del2 = DB::connection('sqlsrvdago')->table('dgw_harga')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_paket', $request->no_paket)
    //         ->delete();		
    //         $detHarga = $request->data_harga;
    //         if (count($detHarga) > 0){
    //             for ($i=0;$i < count($detHarga);$i++){
    //                 $ins2[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_harga(no_paket,kode_harga,harga,harga_se,harga_e,fee,kode_lokasi,curr_fee) values (?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detHarga[$i]['kode_harga'],$detHarga[$i]['harga'],$detHarga[$i]['harga_se'],$detHarga[$i]['harga_e'],$detHarga[$i]['fee'],$kode_lokasi,$detHarga[$i]['curr_fee']));
    //             }						
    //         }
            
    //         $strSQL = "select isnull(max(no_jadwal),0) + 1 as id_jadwal from dgw_jadwal where no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";	
            
    //         $res = DB::connection('sqlsrvdago')->select($strSQL); 
    //         $res = json_decode(json_encode($res),true);
    //         if (count($res) > 0){
    //             $line = $res[0];							
    //             $idJadwal = intval($line['id_jadwal']);
    //         } 
            
    //         $del3 = DB::connection('sqlsrvdago')->table('dgw_jadwal')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_paket', $request->no_paket)
    //         ->delete();		
    //         $detJadwal = $request->data_jadwal;
    //         if (count($detJadwal) > 0){
    //             for ($i=0;$i < count($detJadwal);$i++){
                    
    //                 $ins3[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_jadwal(no_paket,no_jadwal,tgl_berangkat,lama_hari,quota,quota_se,quota_e,kode_lokasi, no_closing,kurs_closing,id_pbb,tgl_datang) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$idJadwal,$detJadwal[$i]['tgl_berangkat'],$detJadwal[$i]['lama_hari'],$detJadwal[$i]['quota'],$detJadwal[$i]['quota_se'],$detJadwal[$i]['quota_e'],$kode_lokasi,'-',0,'-',$detJadwal[$i]['tgl_datang']));   
    //                 $idJadwal = $idJadwal + 1;	
    //             }						
    //         }
            
    //         DB::connection('sqlsrvdago')->commit();
    //         $success['status'] = "SUCCESS";
    //         $success['message'] = "Data Paket berhasil diubah";
    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         DB::connection('sqlsrvdago')->rollback();
    //         $success['status'] = "FAILED";
    //         $success['message'] = "Data Paket gagal diubah ".$e;
    //         return response()->json($success, $this->successStatus); 
    //     }	
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  \App\Fs  $Fs
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroy(Request $request)
    // {
    //     $this->validate($request, [
    //         'no_paket' => 'required'
    //     ]);
    //     DB::connection('sqlsrvdago')->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard('dago')->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $del = DB::connection('sqlsrvdago')->table('dgw_paket')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('no_paket', $request->no_paket)
    //         ->delete();

    //         $strSQL = "select count(*) as jml from dgw_jadwal where no_closing <> '-' and  no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";					
    //         $res = DB::connection('sqlsrvdago')->select($strSQL); 
    //         $res = json_decode(json_encode($res),true);
    //         if (count($res) > 0){
    //             $line = $res[0];							
    //             if ($line['jml'] != 0) {
    //                 $msg = "Paket tidak dapat dihapus. Terdapat jadwal yang sudah di closing.";
    //                 $sts = "FAILED";		
    //             }
    //         }else{
    //             $msg = "Data Paket berhasil dihapus";
    //             $sts = "SUCCESS";
    //             $del = DB::connection('sqlsrvdago')->table('dgw_paket')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_paket', $request->no_paket)
    //             ->delete();

    //             $del2 = DB::connection('sqlsrvdago')->table('dgw_harga')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_paket', $request->no_paket)
    //             ->delete();

    //             $del3 = DB::connection('sqlsrvdago')->table('dgw_jadwal')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_paket', $request->no_paket)
    //             ->delete();

    //             DB::connection('sqlsrvdago')->commit();
    //         } 

    //         $success['status'] = $sts;
    //         $success['message'] = $msg;
            
    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         DB::connection('sqlsrvdago')->rollback();
    //         $success['status'] = "FAILED";
    //         $success['message'] = "Data Paket gagal dihapus ".$e;
            
    //         return response()->json($success, $this->successStatus); 
    //     }	
    // }
}
