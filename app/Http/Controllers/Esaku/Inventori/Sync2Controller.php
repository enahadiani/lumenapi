<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

function joinNum($num){
    // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
    $num = str_replace(".", "", $num);
    $num = str_replace(",", ".", $num);
    return $num;
}

class Sync2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';
    public $sql2 = 'sqlsrv2';
    public $guard2 = 'admin';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function syncMaster(Request $request)
    // {
    //     DB::connection($this->sql)->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

    //         if(isset($request->nik) && $request->nik != ""){
    //             $nik= $request->nik;
    //         }

    //         $sql = "";
    //         $begin = "SET NOCOUNT on;
    //         BEGIN tran;
    //         ";
    //         $commit = "commit tran;";
    //         $sql_vendor = "";

    //         $vendor = DB::connection($this->sql2)->select("select kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang from vendor where kode_lokasi='$kode_lokasi' ");
    //         $jum_vendor = count($vendor);
    //         if($jum_vendor > 0){
    //             $sql_vendor .= " delete from vendor where kode_lokasi='$kode_lokasi'; ";
    //             foreach($vendor as $row){
    //                 $sql_vendor .= " insert into vendor(kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang) values ('".$row->kode_vendor."','".$kode_lokasi."','".$row->nama."','".$row->alamat."','".$row->no_tel."','".$row->email."','".$row->npwp."','".$row->pic."','".$row->alamat2."','".$row->bank."','".$row->cabang."','".$row->no_rek."','".$row->nama_rek."','".$row->no_fax."','".$row->no_pictel."','-','-','-','-','".$row->akun_hutang."'); ";
    //             }
    //         }
            
    //         $insvendor = DB::connection($this->sql)->insert($begin.$sql_vendor.$commit);
    //         //BARANG
            
    //         $sql_barang = "";
    //         $barang = DB::connection($this->sql2)->select("select kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli from brg_barang where kode_lokasi='$kode_lokasi' ");
    //         $jum_barang = count($barang);
    //         if($jum_barang > 0){
    //             $sql_barang .= " delete from brg_barang where kode_lokasi='$kode_lokasi'; ";
    //             foreach($barang as $row){                    
    //                 $sql_barang .= "insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli) values ('".$row->kode_barang."','".$row->nama."','".$kode_lokasi."','".$row->sat_kecil."','$row->sat_besar',$row->jml_sat,".floatval($row->hna).",'".$row->pabrik."','$row->flag_gen','$row->flag_aktif',".floatval($row->ss).",".floatval($row->sm1).",".floatval($row->sm2).",".floatval($row->mm1).",".floatval($row->mm2).",".floatval($row->fm1).",".floatval($row->fm2).",'".$row->kode_klp."','".$row->file_gambar."','".$row->barcode."',".floatval($row->hrg_satuan).",".floatval($row->ppn).",".floatval($row->profit).",".floatval($row->nilai_beli)."); ";
    //             }
    //         }
            
    //         $insbarang = DB::connection($this->sql)->insert($begin.$sql_barang.$commit);

    //         $sql_gudang = "";
    //         $gudang = DB::connection($this->sql2)->select("select kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp from brg_gudang where kode_lokasi='$kode_lokasi' ");
    //         $jum_gudang = count($gudang);
    //         if($jum_gudang > 0){
    //             $sql_gudang .= " delete from brg_gudang where kode_lokasi='$kode_lokasi'; ";
    //             foreach($gudang as $row){
        
    //                 $sql_gudang .= "insert into brg_gudang(kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp) values ('".$row->kode_gudang."','".$kode_lokasi."','".$row->nama."','".$row->pic."','".$row->telp."','".$row->alamat."','".$row->kode_pp."'); ";
    //             }
                
    //         }
            
    //         $insgudang = DB::connection($this->sql)->insert($begin.$sql_gudang.$commit);

    //         //BARANG KLP
    //         $sql_klp = "";
    //         $klp = DB::connection($this->sql2)->select("select kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp from brg_barangklp where kode_lokasi='$kode_lokasi' ");
    //         $jum_klp = count($klp);
    //         if($jum_klp > 0){
    //             $sql_klp .= "delete from brg_barangklp where kode_lokasi='$kode_lokasi';";

    //             foreach($klp as $row){
    //                 $sql_klp .= "insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp) values ('".$row->kode_klp."','".$kode_lokasi."','".$row->nama."','".$row->akun_pers."','".$row->akun_pdpt."','".$row->akun_hpp."'); ";
    //             }
                
    //         }

            
    //         $insklp = DB::connection($this->sql)->insert($begin.$sql_klp.$commit);

    //         //SATUAN
    //         $sql_satuan = "";
    //         $satuan = DB::connection($this->sql2)->select("select kode_satuan,kode_lokasi,nama from brg_satuan where kode_lokasi='$kode_lokasi' ");
    //         $jum_satuan = count($satuan);
    //         if($jum_satuan > 0){
                
    //             $sql_satuan .= "delete from brg_satuan where kode_lokasi='$kode_lokasi'; ";
    //             foreach($satuan as $row){
        
    //                 $sql_satuan .= "insert into brg_satuan(kode_satuan,kode_lokasi,nama) values ('".$row->kode_satuan."','".$kode_lokasi."','".$row->nama."'); ";
    //             }
                
    //         }

            
    //         $inssatuan = DB::connection($this->sql)->insert($begin.$sql_satuan.$commit);

    //         //BONUS
    //         $sql_bonus = "";
    //         $bonus = DB::connection($this->sql2)->select("select kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai from brg_bonus where kode_lokasi='$kode_lokasi' ");
    //         $jum_bonus = count($bonus);
    //         if($jum_bonus > 0){

    //             $sql_bonus .= "delete from brg_bonus where kode_lokasi='$kode_lokasi'; ";

    //             foreach($bonus as $row){

    //                 $sql_bonus .= "insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values ('".$row->kode_barang."','".$row->keterangan."','".$kode_lokasi."',".floatval($row->ref_qty).",".floatval($row->bonus_qty).",'".$row->tgl_mulai."','".$row->tgl_selesai."'); ";
    //             }
               
    //         }
            
    //         $insbonus = DB::connection($this->sql)->insert($begin.$sql_bonus.$commit);

    //         $sql_his = "insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANG',getdate(),'$nik',$jum_barang);
    //                     insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','GUDANG',getdate(),'$nik',$jum_gudang);
    //                     insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANGKLP',getdate(),'$nik',$jum_klp);
    //                     insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','VENDOR',getdate(),'$nik',$jum_vendor);
    //                     insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','SATUAN',getdate(),'$nik',$jum_satuan); 
    //                     insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BONUS',getdate(),'$nik',$jum_bonus); ";
    //         $insert_his = DB::connection($this->sql)->insert($begin.$sql_his.$commit);

    //         DB::connection($this->sql)->commit();
    //         $success['status'] = true;
    //         $success['message'] = "Synchronize Data Successfully. ";
    //         return response()->json($success, $this->successStatus);     
    //     } catch (\Throwable $e) {
    //         DB::connection($this->sql)->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Synchronize Data Failed. ".$e;
    //         return response()->json($success, $this->successStatus); 
    //     }				
        
        
    // }

    public function loadSyncMaster(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            $sql_vendor = "";

            $vendor = DB::connection($this->sql2)->select("select kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang from vendor where kode_lokasi='$kode_lokasi' ");
            $jum_vendor = count($vendor);
            if($jum_vendor > 0){
                $sql_vendor .= " delete from vendor where kode_lokasi='$kode_lokasi'; ";
                foreach($vendor as $row){
                    $sql_vendor .= " insert into vendor(kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang) values ('".$row->kode_vendor."','".$kode_lokasi."','".$row->nama."','".$row->alamat."','".$row->no_tel."','".$row->email."','".$row->npwp."','".$row->pic."','".$row->alamat2."','".$row->bank."','".$row->cabang."','".$row->no_rek."','".$row->nama_rek."','".$row->no_fax."','".$row->no_pictel."','-','-','-','-','".$row->akun_hutang."'); ";
                }
            }
            
            
            //BARANG
            
            $sql_barang = "";
            $barang = DB::connection($this->sql2)->select("select kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli from brg_barang where kode_lokasi='$kode_lokasi' ");
            $jum_barang = count($barang);
            if($jum_barang > 0){
                $sql_barang .= " delete from brg_barang where kode_lokasi='$kode_lokasi'; ";
                foreach($barang as $row){                    
                    $sql_barang .= "insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli) values ('".$row->kode_barang."','".$row->nama."','".$kode_lokasi."','".$row->sat_kecil."','$row->sat_besar',$row->jml_sat,".floatval($row->hna).",'".$row->pabrik."','$row->flag_gen','$row->flag_aktif',".floatval($row->ss).",".floatval($row->sm1).",".floatval($row->sm2).",".floatval($row->mm1).",".floatval($row->mm2).",".floatval($row->fm1).",".floatval($row->fm2).",'".$row->kode_klp."','".$row->file_gambar."','".$row->barcode."',".floatval($row->hrg_satuan).",".floatval($row->ppn).",".floatval($row->profit).",".floatval($row->nilai_beli)."); ";
                }
            }
            
            

            $sql_gudang = "";
            $gudang = DB::connection($this->sql2)->select("select kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp from brg_gudang where kode_lokasi='$kode_lokasi' ");
            $jum_gudang = count($gudang);
            if($jum_gudang > 0){
                $sql_gudang .= " delete from brg_gudang where kode_lokasi='$kode_lokasi'; ";
                foreach($gudang as $row){
        
                    $sql_gudang .= "insert into brg_gudang(kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp) values ('".$row->kode_gudang."','".$kode_lokasi."','".$row->nama."','".$row->pic."','".$row->telp."','".$row->alamat."','".$row->kode_pp."'); ";
                }
                
            }

            //BARANG KLP
            $sql_klp = "";
            $klp = DB::connection($this->sql2)->select("select kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp from brg_barangklp where kode_lokasi='$kode_lokasi' ");
            $jum_klp = count($klp);
            if($jum_klp > 0){
                $sql_klp .= "delete from brg_barangklp where kode_lokasi='$kode_lokasi';";

                foreach($klp as $row){
                    $sql_klp .= "insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp) values ('".$row->kode_klp."','".$kode_lokasi."','".$row->nama."','".$row->akun_pers."','".$row->akun_pdpt."','".$row->akun_hpp."'); ";
                }
                
            }

            //SATUAN
            $sql_satuan = "";
            $satuan = DB::connection($this->sql2)->select("select kode_satuan,kode_lokasi,nama from brg_satuan where kode_lokasi='$kode_lokasi' ");
            $jum_satuan = count($satuan);
            if($jum_satuan > 0){
                
                $sql_satuan .= "delete from brg_satuan where kode_lokasi='$kode_lokasi'; ";
                foreach($satuan as $row){
        
                    $sql_satuan .= "insert into brg_satuan(kode_satuan,kode_lokasi,nama) values ('".$row->kode_satuan."','".$kode_lokasi."','".$row->nama."'); ";
                }
                
            }

            //BONUS
            $sql_bonus = "";
            $bonus = DB::connection($this->sql2)->select("select kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai from brg_bonus where kode_lokasi='$kode_lokasi' ");
            $jum_bonus = count($bonus);
            if($jum_bonus > 0){

                $sql_bonus .= "delete from brg_bonus where kode_lokasi='$kode_lokasi'; ";

                foreach($bonus as $row){

                    $sql_bonus .= "insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values ('".$row->kode_barang."','".$row->keterangan."','".$kode_lokasi."',".floatval($row->ref_qty).",".floatval($row->bonus_qty).",'".$row->tgl_mulai."','".$row->tgl_selesai."'); ";
                }
               
            }

            $success['vendor'] = $begin.$sql_vendor.$commit;
            $success['barang'] = $begin.$sql_barang.$commit;
            $success['klp'] = $begin.$sql_klp.$commit;
            $success['gudang'] = $begin.$sql_gudang.$commit;
            $success['satuan'] = $begin.$sql_satuan.$commit;
            $success['bonus'] = $begin.$sql_bonus.$commit;

            $sql_his = "insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANG',getdate(),'$nik',$jum_barang);
                        insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','GUDANG',getdate(),'$nik',$jum_gudang);
                        insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANGKLP',getdate(),'$nik',$jum_klp);
                        insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','VENDOR',getdate(),'$nik',$jum_vendor);
                        insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','SATUAN',getdate(),'$nik',$jum_satuan); 
                        insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BONUS',getdate(),'$nik',$jum_bonus); ";

            $success['histori'] = $begin.$sql_his.$commit;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    // public function syncMaster(Request $request)
    // {
    //     DB::connection($this->sql)->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

    //         if(isset($request->nik) && $request->nik != ""){
    //             $nik= $request->nik;
    //         }

    //         if($request->vendor != "" ){
    //             $insvendor = DB::connection($this->sql)->insert($request->vendor);
    //         }
    //         if($request->barang != "" ){
    //             $insbarang = DB::connection($this->sql)->insert($request->barang);
    //         }
    //         if($request->klp != "" ){
    //             $insklp = DB::connection($this->sql)->insert($request->klp);
    //         }
    //         if($request->satuan != ""){
    //             $inssatuan = DB::connection($this->sql)->insert($request->satuan);
    //         }
    //         if($request->bonus != ""){
    //             $insbonus = DB::connection($this->sql)->insert($request->bonus);
    //         }
    //         if($request->gudang != ""){
    //             $insgudang = DB::connection($this->sql)->insert($request->gudang);
    //         }
    //         if($request->histori != ""){
    //             $inshistori = DB::connection($this->sql)->insert($request->histori);
    //         }
            
    //         DB::connection($this->sql)->commit();
    //         $success['status'] = true;
    //         $success['message'] = "Synchronize Data Successfully. ";
    //         return response()->json($success, $this->successStatus);     
    //     } catch (\Throwable $e) {
    //         DB::connection($this->sql)->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Synchronize Data Failed. ".$e;
    //         return response()->json($success, $this->successStatus); 
    //     }				
        
        
    // }

    function postSql($url,$token,$sql){
        try{

            $client = new Client(['verify' => false]);
            $response = $client->request('POST',  $url,[
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
                'form_param' => [
                    'sql' => $sql
                ]
            ]);
            
            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
                return $data; 
            }
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $result['message'] = $res;
            $result['status'] = false;
            return $result;
        } 
    }

    function getToken($url,$param){
        try{

            $client = new Client(['verify' => false]);
            $response = $client->request('POST',  $url,[
                'form_params' => $param
            ]);
            
            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
                return $data; 
            }
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $result['message'] = $res;
            $result['status'] = false;
            return $result;
        } 
    }

    public function syncMaster(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $url = "https://api.simkug.com/api/";
            $param = array(
                'nik' => 'kasir',
                'password' => 'saisai'
            );
            $res = $this->getToken($url."ginas/login",$param);
            if(isset($res['message']) && $res['message'] == 'success'){
                $token = $res['token'];

                $sql = "";
                $begin = "SET NOCOUNT on;
                BEGIN tran;
                ";
                $commit = "commit tran;";
                $sql_vendor = "";
    
                $vendor = DB::connection($this->sql)->select("select kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang from vendor where kode_lokasi='$kode_lokasi' ");
                $jum_vendor = count($vendor);
                $i=1;
                $sts_loop = true;
                $msg_loop = "";
                $c = 1;
                $total = 0;
                $x=0;
                if($jum_vendor > 0){
                    $sql_vendor .= " delete from vendor where kode_lokasi='04x'; ";
                    foreach($vendor as $row){
                        $sql_vendor .= " insert into vendor(kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang) values ('".$row->kode_vendor."','04x','".$row->nama."','".$row->alamat."','".$row->no_tel."','".$row->email."','".$row->npwp."','".$row->pic."','".$row->alamat2."','".$row->bank."','".$row->cabang."','".$row->no_rek."','".$row->nama_rek."','".$row->no_fax."','".$row->no_pictel."','-','-','-','-','".$row->akun_hutang."'); ";
                        $x++;
                        if($i % 1000 == 0){
                            $sql_vendor = $begin.$sql_vendor.$commit;
                            $curl = $this->postSql($url."ginas/exec-sql",$token,$sql_vendor);
                            $success['curl'][] = $curl;
                            if(!$curl['status']){
                                $sts_loop = false;
                                $msg_loop .= "gagal di looping 1000 ke ".$c;
                            }else{
                                $total +=1000;
                            }
                            $sql_vendor = "";
                            $x = 0;
                            $c++;
                        }
                        if($i == count($vendor) && ($i % 1000 != 0) ){
                            $sql_vendor = $begin.$sql_vendor.$commit;
                            $curl = $this->postSql($url."ginas/exec-sql",$token,$sql_vendor);
                            $success['curl'][] = $curl;
                            if(!$curl['status']){
                                $sts_loop = false;
                                $msg_loop .= "gagal di looping 1000 ke ".$c;
                            }else{
                                $total +=$x;
                            }
                            $sql_vendor = "";
                            $x = 0;
                            $c++;
                        }
                        $i++;
                    }
                }
                
                
                // //BARANG
                
                // $sql_barang = "";
                // $barang = DB::connection($this->sql2)->select("select kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli from brg_barang where kode_lokasi='$kode_lokasi' ");
                // $jum_barang = count($barang);
                // if($jum_barang > 0){
                //     $sql_barang .= " delete from brg_barang where kode_lokasi='$kode_lokasi'; ";
                //     foreach($barang as $row){                    
                //         $sql_barang .= "insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit,nilai_beli) values ('".$row->kode_barang."','".$row->nama."','".$kode_lokasi."','".$row->sat_kecil."','$row->sat_besar',$row->jml_sat,".floatval($row->hna).",'".$row->pabrik."','$row->flag_gen','$row->flag_aktif',".floatval($row->ss).",".floatval($row->sm1).",".floatval($row->sm2).",".floatval($row->mm1).",".floatval($row->mm2).",".floatval($row->fm1).",".floatval($row->fm2).",'".$row->kode_klp."','".$row->file_gambar."','".$row->barcode."',".floatval($row->hrg_satuan).",".floatval($row->ppn).",".floatval($row->profit).",".floatval($row->nilai_beli)."); ";
                //     }
                // }
                
                
    
                // $sql_gudang = "";
                // $gudang = DB::connection($this->sql2)->select("select kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp from brg_gudang where kode_lokasi='$kode_lokasi' ");
                // $jum_gudang = count($gudang);
                // if($jum_gudang > 0){
                //     $sql_gudang .= " delete from brg_gudang where kode_lokasi='$kode_lokasi'; ";
                //     foreach($gudang as $row){
            
                //         $sql_gudang .= "insert into brg_gudang(kode_gudang,kode_lokasi,nama,pic,telp,alamat,kode_pp) values ('".$row->kode_gudang."','".$kode_lokasi."','".$row->nama."','".$row->pic."','".$row->telp."','".$row->alamat."','".$row->kode_pp."'); ";
                //     }
                    
                // }
    
                // //BARANG KLP
                // $sql_klp = "";
                // $klp = DB::connection($this->sql2)->select("select kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp from brg_barangklp where kode_lokasi='$kode_lokasi' ");
                // $jum_klp = count($klp);
                // if($jum_klp > 0){
                //     $sql_klp .= "delete from brg_barangklp where kode_lokasi='$kode_lokasi';";
    
                //     foreach($klp as $row){
                //         $sql_klp .= "insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp) values ('".$row->kode_klp."','".$kode_lokasi."','".$row->nama."','".$row->akun_pers."','".$row->akun_pdpt."','".$row->akun_hpp."'); ";
                //     }
                    
                // }
    
                // //SATUAN
                // $sql_satuan = "";
                // $satuan = DB::connection($this->sql2)->select("select kode_satuan,kode_lokasi,nama from brg_satuan where kode_lokasi='$kode_lokasi' ");
                // $jum_satuan = count($satuan);
                // if($jum_satuan > 0){
                    
                //     $sql_satuan .= "delete from brg_satuan where kode_lokasi='$kode_lokasi'; ";
                //     foreach($satuan as $row){
            
                //         $sql_satuan .= "insert into brg_satuan(kode_satuan,kode_lokasi,nama) values ('".$row->kode_satuan."','".$kode_lokasi."','".$row->nama."'); ";
                //     }
                    
                // }
    
                // //BONUS
                // $sql_bonus = "";
                // $bonus = DB::connection($this->sql2)->select("select kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai from brg_bonus where kode_lokasi='$kode_lokasi' ");
                // $jum_bonus = count($bonus);
                // if($jum_bonus > 0){
    
                //     $sql_bonus .= "delete from brg_bonus where kode_lokasi='$kode_lokasi'; ";
    
                //     foreach($bonus as $row){
    
                //         $sql_bonus .= "insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values ('".$row->kode_barang."','".$row->keterangan."','".$kode_lokasi."',".floatval($row->ref_qty).",".floatval($row->bonus_qty).",'".$row->tgl_mulai."','".$row->tgl_selesai."'); ";
                //     }
                   
                // }
    
                // $success['vendor'] = $begin.$sql_vendor.$commit;
                // $success['barang'] = $begin.$sql_barang.$commit;
                // $success['klp'] = $begin.$sql_klp.$commit;
                // $success['gudang'] = $begin.$sql_gudang.$commit;
                // $success['satuan'] = $begin.$sql_satuan.$commit;
                // $success['bonus'] = $begin.$sql_bonus.$commit;
    
                // $sql_his = "insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANG',getdate(),'$nik',$jum_barang);
                //             insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','GUDANG',getdate(),'$nik',$jum_gudang);
                //             insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BARANGKLP',getdate(),'$nik',$jum_klp);
                //             insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','VENDOR',getdate(),'$nik',$jum_vendor);
                //             insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','SATUAN',getdate(),'$nik',$jum_satuan); 
                //             insert into sync_master (kode_lokasi,jenis_master,tgl_sync,nik_user,total_rows) values ('$kode_lokasi','BONUS',getdate(),'$nik',$jum_bonus); ";
    
                // $success['histori'] = $begin.$sql_his.$commit;
                $msg = "sukses. Total seluruh data: ".count($vendor).". error: ".$msg_loop.". Total berhasil: ".$total;
                $success['status'] = true;
                $success['message'] = $msg;
            }else{
                $success['status'] = false;
                $success['message'] = "Error, Unauthorized!";
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function getSyncMaster(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->jenis_master)){
                if($request->jenis_master == "all"){
                    $filter = "";
                }else{
                    $filter = " and jenis_master='$request->jenis_master' ";
                }
                $sql= "select id,kode_lokasi,jenis_master,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_master where kode_lokasi='$kode_lokasi'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,jenis_master,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_master where kode_lokasi='$kode_lokasi' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadSyncPnj(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            //TRANSM
            $sql_transm = "";

            $transm = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3 from trans_m where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and form='CLOSING' ");
            $jum_transm = count($transm);
            if($jum_transm > 0){
                foreach($transm as $row){

                    $sql_transm .= " insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                    ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->modul."','".$row->form."','".$row->posted."','".$row->prog_seb."','".$row->progress."','".$row->kode_pp."','".$row->tanggal."','".$row->no_dokumen."','".$row->keterangan."','".$row->kode_curr."',".floatval($row->kurs).",".floatval($row->nilai1).",".floatval($row->nilai2).",".floatval($row->nilai3).",'".$row->nik1."','".$row->nik2."','".$row->nik3."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."','".$row->param1."','".$row->param2."','".$row->param3."'); ";
                }   
            }
            //TRANSJ
            $sql_transj = "";

            $transj = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3 from trans_j where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and modul='BRGJUAL' ");
            $jum_transj = count($transj);
            if($jum_transj > 0){
                
                foreach($transj as $row){
                    
                    $sql_transj .= "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                    ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->no_dokumen."','".$row->tanggal."',".$row->nu.",'".$row->kode_akun."','".$row->dc."',".floatval($row->nilai).",".floatval($row->nilai_curr).",'".$row->keterangan."','".$row->modul."','".$row->jenis."','".$row->kode_curr."',".floatval($row->kurs).",'".$row->kode_pp."','".$row->kode_drk."','".$row->kode_cust."','".$row->kode_vendor."','".$row->no_fa."','".$row->no_selesai."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."');";
                }
            }

            //BRGJUAL
            $sql_brgjual = "";

            $brgJual = DB::connection($this->sql)->select("select no_jual,kode_lokasi,tanggal,keterangan,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp,diskon,kode_gudang,no_ba,tobyr,no_open,no_close from brg_jualpiu_dloc where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and isnull(no_close,'-') <> '-' ");
            $jum_brgjual = count($brgJual);
            if($jum_brgjual > 0){
                foreach($brgJual as $row){
                    
                    $sql_brgjual .= "insert into brg_jualpiu_d (no_jual,kode_lokasi,tanggal,keterangan,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp,diskon,kode_gudang,no_ba,tobyr,no_open,no_close) values ('".$row->no_jual."','".$row->kode_lokasi."','".$row->tanggal."','".$row->keterangan."','".$row->kode_cust."','".$row->kode_curr."',".floatval($row->kurs).",'".$row->kode_pp."',".floatval($row->nilai).",'".$row->periode."','".$row->nik_user."','".$row->tgl_input."','".$row->akun_piutang."',".floatval($row->nilai_ppn).",".floatval($row->nilai_pph).",'".$row->no_fp."',".floatval($row->diskon).",'".$row->kode_gudang."','".$row->no_ba."',".floatval($row->tobyr).",'".$row->no_open."','".$row->no_close."'); ";
                }
            }
            

            //BRGTRANS
            $sql_brgtrans = "";

            $brgTrans = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total from brg_trans_dloc where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and isnull(no_close,'-') <> '-' ");
            $jum_brgtrans = count($brgTrans);
            if($jum_brgtrans > 0){
                foreach($brgTrans as $row){
                   
                    $sql_brgtrans .= "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                        ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->periode."','".$row->modul."','".$row->form."',".$row->nu.",'".$row->kode_gudang."','".$row->kode_barang."','".$row->no_batch."','".$row->tgl_ed."','".$row->satuan."','".$row->dc."',".floatval($row->stok).",".floatval($row->jumlah).",".floatval($row->bonus).",".floatval($row->harga).",".floatval($row->hpp).",".floatval($row->p_disk).",".floatval($row->diskon).",".floatval($row->tot_diskon).",".floatval($row->total)."); ";
                }
            }

            $total = $jum_transm+$jum_transj+$jum_brgjual+$jum_brgtrans;
            $id =  $this->generateKode("sync_pnj", "id", $kode_lokasi.'SC'.date('Y'), "00001");
            $sql_his = "insert into sync_pnj (id,kode_lokasi,keterangan,tgl_sync,nik_user,total_rows) 
            values ('$id','$kode_lokasi','DATA PENJUALAN DAN JURNAL',getdate(),'$nik',$total) ;
            insert into sync_pnj_d (kode_lokasi,keterangan,total_rows,id) 
            values ('$kode_lokasi','TRANS M',$jum_transm,'$id');
            insert into sync_pnj_d (kode_lokasi,keterangan,total_rows,id) 
            values ('$kode_lokasi','TRANS J',$jum_transj,'$id');
            insert into sync_pnj_d (kode_lokasi,keterangan,total_rows,id) 
            values ('$kode_lokasi','BRG JUALPIU',$jum_brgjual,'$id');
            insert into sync_pnj_d (kode_lokasi,keterangan,total_rows,id) 
            values ('$kode_lokasi','BRG TRANSD',$jum_brgtrans,'$id');
            update trans_m set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi'  and form='CLOSING';
            update trans_j set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi'  and modul='BRGJUAL';
            update brg_jualpiu_dloc set id_sync='$id' where isnull(id_sync,'-') = '-' and isnull(no_close,'-') <> '-'  and kode_lokasi='$kode_lokasi';
            update brg_trans_dloc set id_sync='$id' where isnull(id_sync,'-') = '-' and isnull(no_close,'-') <> '-'  and kode_lokasi='$kode_lokasi'; ";

            $success['transm']= ($sql_transm != "" ? $begin.$sql_transm.$commit : "");
            $success['transj'] = ($sql_transj != "" ? $begin.$sql_transj.$commit : "");
            $success['brgjual'] = ($sql_brgjual != "" ? $begin.$sql_brgjual.$commit : "");
            $success['brgtrans'] = ($sql_brgtrans != "" ? $begin.$sql_brgtrans.$commit : "");
            $success['histori'] = ($sql_his != "" ? $begin.$sql_his.$commit : "");
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            DB::connection($this->sql2)->rollback();
            $success['status'] = false;
            $success['message'] = "Error. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    
    }

    public function syncPnj(Request $request)
    {
        DB::connection($this->sql2)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->transm != "" ){
                $instransm = DB::connection($this->sql2)->insert($request->transm);
            }
            if($request->transj != "" ){
                $instransj = DB::connection($this->sql2)->insert($request->transj);
            }
            if($request->brgjual != "" ){
                $insbrgjual = DB::connection($this->sql2)->insert($request->brgjual);
            }
            if($request->brgtrans != ""){
                $insbrgtrans = DB::connection($this->sql2)->insert($request->brgtrans);
            }
            if($request->histori != ""){
                $inshistori = DB::connection($this->sql)->insert($request->histori);
            }
            
            DB::connection($this->sql2)->commit();
            $success['status'] = true;
            $success['req'] = $request->all();
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql2)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    public function getSyncPnj(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pnj where kode_lokasi='$kode_lokasi'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pnj where kode_lokasi='$kode_lokasi' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSyncPnjDetail(Request $request)
    {
        $this->validate($request,[
            'id' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pnj where kode_lokasi='$kode_lokasi' and id='$request->id'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pnj where kode_lokasi='$kode_lokasi' and id='$request->id' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $res2 = DB::connection($this->sql)->select("select id,keterangan,total_rows from sync_pnj_d where kode_lokasi='$kode_lokasi' and id='$request->id' ");
                $res2 = json_decode(json_encode($res2),true);
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadSyncPmb(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            //TRANSM
            $sql_transm = "";

            $transm = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3 from trans_m where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and form='BRGBELI'");
            $jum_transm = count($transm);
            if($jum_transm > 0){
                foreach($transm as $row){
                    $sql_transm .= "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                    ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->modul."','".$row->form."','".$row->posted."','".$row->prog_seb."','".$row->progress."','".$row->kode_pp."','".$row->tanggal."','".$row->no_dokumen."','".$row->keterangan."','".$row->kode_curr."',".floatval($row->kurs).",".floatval($row->nilai1).",".floatval($row->nilai2).",".floatval($row->nilai3).",'".$row->nik1."','".$row->nik2."','".$row->nik3."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."','".$row->param1."','".$row->param2."','".$row->param3."');";
                }
            }

            //TRANSJ
            $sql_transj = "";

            $transj = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3 from trans_j where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi' and modul='BRGBELI' and jenis not in ('BRGRETBELI') ");
            $jum_transj = count($transj);
            if($jum_transj > 0){
                foreach($transj as $row){
                    
                    $sql_transj .= "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                    ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->no_dokumen."','".$row->tanggal."',".$row->nu.",'".$row->kode_akun."','".$row->dc."',".floatval($row->nilai).",".floatval($row->nilai_curr).",'".$row->keterangan."','".$row->modul."','".$row->jenis."','".$row->kode_curr."',".floatval($row->kurs).",'".$row->kode_pp."','".$row->kode_drk."','".$row->kode_cust."','".$row->kode_vendor."','".$row->no_fa."','".$row->no_selesai."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."');";
                }
            }

            //BRGBELI HUT
            $sql_brgbeli = "";

            $brgbeli = DB::connection($this->sql)->select("select no_beli, kode_lokasi, tanggal, keterangan, kode_vendor, kode_curr, kurs, kode_pp, nilai, periode, nik_user, tgl_input, akun_hutang, nilai_ppn, no_fp, due_date, nilai_pph, diskon, modul, kode_gudang
            from brg_belihut_d where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-'  and modul='BELI' ");
            $jum_brgbeli = count($brgbeli);
            if($jum_brgbeli > 0){
                foreach($brgbeli as $row){
                    
                    $sql_brgbeli .= "insert into brg_belihut_d (no_beli, kode_lokasi, tanggal, keterangan, kode_vendor, kode_curr, kurs, kode_pp, nilai, periode, nik_user, tgl_input, akun_hutang, nilai_ppn, no_fp, due_date, nilai_pph, diskon, modul, kode_gudang
                    ) values ('".$row->no_beli."','".$row->kode_lokasi."','".$row->tanggal."','".$row->keterangan."','".$row->kode_vendor."','".$row->kode_curr."',".floatval($row->kurs).",'".$row->kode_pp."',".floatval($row->nilai).",'".$row->periode."','".$row->nik_user."','".$row->tgl_input."','".$row->akun_hutang."',".floatval($row->nilai_ppn).",'".$row->no_fp."','".$row->due_date."',".floatval($row->nilai_pph).",".floatval($row->diskon).",'".$row->modul."','".$row->kode_gudang."'); ";
                }
            }

            //BRGTRANS
            $sql_brgtrans = "";

            $brgtrans = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total
            from brg_trans_d where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-'  and modul='BRGBELI' ");
            $jum_brgtrans = count($brgtrans);
            if($jum_brgtrans > 0){
                foreach($brgtrans as $row){
                   
                    $sql_brgtrans .= "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                        ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->periode."','".$row->modul."','".$row->form."',".$row->nu.",'".$row->kode_gudang."','".$row->kode_barang."','".$row->no_batch."','".$row->tgl_ed."','".$row->satuan."','".$row->dc."',".floatval($row->stok).",".floatval($row->jumlah).",".floatval($row->bonus).",".floatval($row->harga).",".floatval($row->hpp).",".floatval($row->p_disk).",".floatval($row->diskon).",".floatval($row->tot_diskon).",".floatval($row->total)."); ";
                }
            }

            $success['transm']= ($sql_transm != "" ? $begin.$sql_transm.$commit : "");
            $success['transj'] = ($sql_transj != "" ? $begin.$sql_transj.$commit : "");
            $success['brgbeli'] = ($sql_brgbeli != "" ? $begin.$sql_brgbeli.$commit : "");
            $success['brgtrans'] = ($sql_brgtrans != "" ? $begin.$sql_brgtrans.$commit : "");

            $total = $jum_transm+$jum_transj+$jum_brgbeli+$jum_brgtrans;
            $id =  $this->generateKode("sync_pmb", "id", $kode_lokasi.'SC'.date('Y'), "00001");
            $sql_his = "insert into sync_pmb (id,kode_lokasi,keterangan,tgl_sync,nik_user,total_rows) 
                    values ('$id','$kode_lokasi','DATA PEMBELIAN DAN JURNAL',getdate(),'$nik',$total);
                    insert into sync_pmb_d (kode_lokasi,keterangan,total_rows,id) values ('$kode_lokasi','TRANS M',$jum_transm,'$id');
                    insert into sync_pmb_d (kode_lokasi,keterangan,total_rows,id) values ('$kode_lokasi','TRANS J',$jum_transj,'$id');
                    insert into sync_pmb_d (kode_lokasi,keterangan,total_rows,id) values ('$kode_lokasi','BRG HUT',$jum_brgbeli,'$id');
                    insert into sync_pmb_d (kode_lokasi,keterangan,total_rows,id) values ('$kode_lokasi','BRG TRANSD',$jum_brgtrans,'$id');
                    update trans_m set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi' and form='BRGBELI';
                    update trans_j set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi' and modul='BRGBELI' and jenis not in ('BRGRETBELI');
                    update brg_belihut_d set id_sync='$id' where isnull(id_sync,'-') = '-' and kode_lokasi='$kode_lokasi' and modul='BELI';update brg_trans_d set id_sync='$id' where isnull(id_sync,'-') = '-' and kode_lokasi='$kode_lokasi' and modul='BRGBELI'; ";

            $success['histori'] = ($sql_his != "" ? $begin.$sql_his.$commit : "");
            $success['status'] = true;
            $success['message'] = "Sukses!";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
          
    }

    public function syncPmb(Request $request)
    {
        DB::connection($this->sql2)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->transm != "" ){
                $instransm = DB::connection($this->sql2)->insert($request->transm);
            }
            if($request->transj != "" ){
                $instransj = DB::connection($this->sql2)->insert($request->transj);
            }
            if($request->brgbeli != "" ){
                $insbrgbeli = DB::connection($this->sql2)->insert($request->brgbeli);
            }
            if($request->brgtrans != ""){
                $insbrgtrans = DB::connection($this->sql2)->insert($request->brgtrans);
            }
            if($request->histori != ""){
                $inshistori = DB::connection($this->sql)->insert($request->histori);
            }
            
            DB::connection($this->sql2)->commit();
            $success['status'] = true;
            $success['req'] = $request->all();
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql2)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    public function getSyncPmb(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pmb where kode_lokasi='$kode_lokasi'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pmb where kode_lokasi='$kode_lokasi' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSyncPmbDetail(Request $request)
    {
        $this->validate($request,[
            'id' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pmb where kode_lokasi='$kode_lokasi' and id='$request->id'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_pmb where kode_lokasi='$kode_lokasi' and id='$request->id' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $res2 = DB::connection($this->sql)->select("select id,keterangan,total_rows from sync_pmb_d where kode_lokasi='$kode_lokasi' and id='$request->id' ");
                $res2 = json_decode(json_encode($res2),true);
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadSyncReturBeli(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            //TRANSM
            $sql_transm = "";

            $transm = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3 from trans_m where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and form='BRGRETBELI' ");
            $jum_transm = count($transm);
            if($jum_transm > 0){
                foreach($transm as $row){
                     
                     $sql_transm .= "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values 
                     ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->modul."','".$row->form."','".$row->posted."','".$row->prog_seb."','".$row->progress."','".$row->kode_pp."','".$row->tanggal."','".$row->no_dokumen."','".$row->keterangan."','".$row->kode_curr."',".floatval($row->kurs).",".floatval($row->nilai1).",".floatval($row->nilai2).",".floatval($row->nilai3).",'".$row->nik1."','".$row->nik2."','".$row->nik3."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."','".$row->param1."','".$row->param2."','".$row->param3."');";
                 }
             }
 
             //TRANSJ
             
             $sql_transj = "";
             $transj = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3 from trans_j where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and modul='BRGBELI' and jenis ='BRGRETBELI'");
             $jum_transj = count($transj);
             if($jum_transj > 0){
                 foreach($transj as $row){
                     
                     $sql_transj .= "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values 
                     ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->tgl_input."','".$row->nik_user."','".$row->periode."','".$row->no_dokumen."','".$row->tanggal."',".$row->nu.",'".$row->kode_akun."','".$row->dc."',".floatval($row->nilai).",".floatval($row->nilai_curr).",'".$row->keterangan."','".$row->modul."','".$row->jenis."','".$row->kode_curr."',".floatval($row->kurs).",'".$row->kode_pp."','".$row->kode_drk."','".$row->kode_cust."','".$row->kode_vendor."','".$row->no_fa."','".$row->no_selesai."','".$row->no_ref1."','".$row->no_ref2."','".$row->no_ref3."');";
                 }
             }
 
             //BRGBELI BAYAR

             $sql_brgbeli = "";
             $brgbeli = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,no_beli,kode_vendor,periode,dc,modul,nilai,nik_user,tgl_input from brg_belibayar_d where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-' and modul='KBBELICCL' ");
             $jum_brgbeli = count($brgbeli);
             if($jum_brgbeli > 0){
                 foreach($brgbeli as $row){
                     
                     $sql_brgbeli .= "insert into brg_belibayar_d(no_bukti,kode_lokasi,no_beli,kode_vendor,periode,dc,modul,nilai,nik_user,tgl_input) 
                     values ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->no_beli."','".$row->kode_vendor."', '".$row->periode."','".$row->dc."','".$row->modul."',".floatval($row->nilai).",'".$row->nik_user."','".$row->tgl_input."');";
                 }
             }
 
             //BRGTRANS
            //BRGTRANS
            $sql_brgtrans = "";

            $brgtrans = DB::connection($this->sql)->select("select no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total
            from brg_trans_d where kode_lokasi='$kode_lokasi' and isnull(id_sync,'-')='-'  and modul='BRGRETBELI'");
            $jum_brgtrans = count($brgtrans);
            if($jum_brgtrans > 0){
                foreach($brgtrans as $row){
                   
                    $sql_brgtrans .= "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                        ('".$row->no_bukti."','".$row->kode_lokasi."','".$row->periode."','".$row->modul."','".$row->form."',".$row->nu.",'".$row->kode_gudang."','".$row->kode_barang."','".$row->no_batch."','".$row->tgl_ed."','".$row->satuan."','".$row->dc."',".floatval($row->stok).",".floatval($row->jumlah).",".floatval($row->bonus).",".floatval($row->harga).",".floatval($row->hpp).",".floatval($row->p_disk).",".floatval($row->diskon).",".floatval($row->tot_diskon).",".floatval($row->total)."); ";
                }
            }

            $success['transm']= ($sql_transm != "" ? $begin.$sql_transm.$commit : "");
            $success['transj'] = ($sql_transj != "" ? $begin.$sql_transj.$commit : "");
            $success['brgbeli'] = ($sql_brgbeli != "" ? $begin.$sql_brgbeli.$commit : "");
            $success['brgtrans'] = ($sql_brgtrans != "" ? $begin.$sql_brgtrans.$commit : "");

            $total = $jum_transm+$jum_transj+$jum_brgbeli+$jum_brgtrans;
            $id =  $this->generateKode("sync_retbeli", "id", $kode_lokasi.'SC'.date('Y'), "00001");
            $sql_his = "insert into sync_retbeli (id,kode_lokasi,keterangan,tgl_sync,nik_user,total_rows) 
                values ('$id','$kode_lokasi','DATA RETUR BELI DAN JURNAL',getdate(),'$nik',$total);insert into sync_retbeli_d (kode_lokasi,keterangan,total_rows,id) 
                values ('$kode_lokasi','TRANS M',$jum_transm,'$id');
                insert into sync_retbeli_d (kode_lokasi,keterangan,total_rows,id) 
                values ('$kode_lokasi','TRANS J',$jum_transj,'$id');
                insert into sync_retbeli_d (kode_lokasi,keterangan,total_rows,id) 
                values ('$kode_lokasi','BRG BAYAR',$jum_brgbeli,'$id');
                insert into sync_retbeli_d (kode_lokasi,keterangan,total_rows,id) 
                values ('$kode_lokasi','BRG TRANSD',$jum_brgtrans,'$id');
                update trans_m set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi' and form='BRGRETBELI';
                update trans_j set id_sync='$id' where isnull(id_sync,'-') = '-'  and kode_lokasi='$kode_lokasi' and modul='BRGBELI' and jenis='BRGRETBELI';
                update brg_belibayar_d set id_sync='$id' where isnull(id_sync,'-') = '-' and kode_lokasi='$kode_lokasi' and modul='KBBELICCL';
                update brg_trans_d set id_sync='$id' where isnull(id_sync,'-') = '-' and kode_lokasi='$kode_lokasi' and modul='BRGRETBELI'; ";

            $success['histori'] = ($sql_his != "" ? $begin.$sql_his.$commit : "");
            $success['status'] = true;
            $success['message'] = "Sukses!";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error. ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function syncReturBeli(Request $request)
    {
        DB::connection($this->sql2)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->transm != "" ){
                $instransm = DB::connection($this->sql2)->insert($request->transm);
            }
            if($request->transj != "" ){
                $instransj = DB::connection($this->sql2)->insert($request->transj);
            }
            if($request->brgbeli != "" ){
                $insbrgbeli = DB::connection($this->sql2)->insert($request->brgbeli);
            }
            if($request->brgtrans != ""){
                $insbrgtrans = DB::connection($this->sql2)->insert($request->brgtrans);
            }
            if($request->histori != ""){
                $inshistori = DB::connection($this->sql)->insert($request->histori);
            }
            
            DB::connection($this->sql2)->commit();
            $success['status'] = true;
            $success['req'] = $request->all();
            $success['message'] = "Synchronize Data Successfully. ";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql2)->rollback();
            $success['status'] = false;
            $success['message'] = "Synchronize Data Failed. ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    public function getSyncReturBeli(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_retbeli where kode_lokasi='$kode_lokasi'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_retbeli where kode_lokasi='$kode_lokasi' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSyncReturBeliDetail(Request $request)
    {
        $this->validate($request,[
            'id' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik_user)){
                if($request->nik_user == "all"){
                    $filter = "";
                }else{
                    $filter = " and nik_user='$request->nik_user' ";
                }
                $sql= "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_retbeli where kode_lokasi='$kode_lokasi' and id='$request->id'
                $filter ";
            }else{
                $sql = "select id,kode_lokasi,keterangan,tgl_sync as tgl_input,nik_user,total_rows,case when datediff(minute,tgl_sync,getdate()) <= 10 then 'baru' else 'lama' end as status from sync_retbeli where kode_lokasi='$kode_lokasi' and id='$request->id' ";
            }
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $res2 = DB::connection($this->sql)->select("select id,keterangan,total_rows from sync_retbeli_d where kode_lokasi='$kode_lokasi' and id='$request->id' ");
                $res2 = json_decode(json_encode($res2),true);
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
