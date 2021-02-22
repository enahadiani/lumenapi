<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_cust from java_cust where kode_cust ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function checkCustomer(Request $request){
        
        $auth = DB::connection($this->sql)->select("select kode_cust from java_cust where kode_cust ='".$request->query('kode')."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $success['status'] = false;   
        }else{
            $success['status'] = true;
        }
        return response()->json($success, $this->successStatus);
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_vendor)){
                if($request->kode_vendor == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_vendor='$request->kode_vendor' ";
                }
                $sql= "select a.kode_vendor,a.nama,a.alamat,a.no_telp,a.kode_pos,a.email,a.npwp,a.alamat2,a.pic,a.akun_hutang,a.bank,a.cabang,a.no_rek,a.nama_rek,a.no_pictel,b.nama as nama_akun 
                from vendor a left join masakun b on a.akun_hutang=b.kode_akun and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select kode_cust,nama,alamat,no_telp, tgl_input,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_cust
                where kode_lokasi= '$kode_lokasi'";
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
            'kode_customer' => 'required',
            'nama' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'alamat' => 'required',
            'kode_pos' => 'required',
            'kecamatan' => 'required',
            'kota' => 'required',
            'negara' => 'required',
            'pic' => 'required',
            'no_telp_pic' => 'required',
            'email_pic' => 'required',
            'akun_piutang' => 'required',
            'no_rek' => 'required|array',
            'nama_rek' => 'required|array',
            'bank' => 'required|array',
            'cabang' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_vendor,$kode_lokasi)){
                $insertCust = "insert into java_cust(kode_cust, nama, no_telp, email, alamat, kode_pos, kecamatan, 
                kota, negara, pic, no_telp_pic, email_pic, akun_piutang, tgl_input, kode_lokasi)
                values('$request->kode_customer', '$request->nama', '$request->no_telp', '$request->email', '$request->alamat',
                '$request->kode_pos', '$request->kecamatan', '$request->kota', '$request->negara', '$request->pic', '$request->no_telp_pic',
                '$request->email_pic', '$request->akun_piutang', getdate(), '$kode_lokasi')";
                
                DB::connection($this->sql)->insert($insertCust);
                
                $no_rek = $request->input('no_rek');
                $nama_rek = $request->input('nama_rek');
                $bank = $request->input('bank');
                $cabang = $request->input('cabang');

                for($i=0;$i<count($request->no_rek);$i++) {
                    $insertDetail = "insert into java_cust_detail(kode_cust, nama_rekening, bank, cabang, kode_lokasi, no_rek) 
                    values ('$request->kode_customer', '".$nama_rek[$i]."', '".$bank[$i]."', '".$cabang[$i]."', '$kode_lokasi', '".$no_rek[$i]."')";
                    DB::connection($this->sql)->insert($insertDetail);
                }
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_customer;
                $success['message'] = "Data Customer berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Customer sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Vendor gagal disimpan ".$e;
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
            'kode_vendor' => 'required|max:10',
            'nama' => 'required|max:50',
            'alamat' => 'required|max:200',
            'no_tel' => 'required|max:50',
            'email' => 'required|max:50',
            'npwp' => 'required|max:50',
            'pic' => 'required|max:50',
            'alamat2' => 'required|max:200',
            'bank' => 'required|max:50',
            'cabang' => 'required|max:50',
            'no_rek' => 'required|max:50',
            'nama_rek' => 'required|max:50',
            'no_fax' => 'required|max:50',
            'no_pictel' => 'required|max:50',
            'spek' => 'required|max:200',
            'kode_klpvendor' => 'required|max:10',
            'penilaian' => 'required|max:50',
            'bank_trans' => 'required|max:50',
            'akun_hutang' => 'required|max:20'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('vendor')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_vendor', $request->kode_vendor)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into vendor(kode_vendor,kode_lokasi,nama,alamat,no_tel,email,npwp,pic,alamat2,bank,cabang,no_rek,nama_rek,no_fax,no_pictel,spek,kode_klpvendor,penilaian,bank_trans,akun_hutang,tgl_input) values ('$request->kode_vendor','$kode_lokasi','$request->nama','$request->alamat',' $request->no_tel',' $request->email','$request->npwp','$request->pic','$request->alamat2','$request->bank','$request->cabang','$request->no_rek','$request->nama_rek','$request->no_fax','$request->no_pictel','$request->spek','$request->kode_klpvendor','$request->penilaian','$request->bank_trans','$request->akun_hutang',getdate()) ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_vendor;
            $success['message'] = "Data Vendor berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Data Vendor gagal diubah ".$e;
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
            'kode_vendor' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('vendor')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_vendor', $request->kode_vendor)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Vendor berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Vendor gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi where b.kode_flag = '003' and a.kode_lokasi = '$kode_lokasi' ";

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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
