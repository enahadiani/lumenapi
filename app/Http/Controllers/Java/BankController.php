<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_bank)){
                if($request->kode_bank == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_bank='$request->kode_bank' ";
                }
                $sql= "select kode_bank, nama, isnull(no_rek, '-') as no_rek
                from java_bank where kode_lokasi='".$kode_lokasi."' $filter ";

            }else{
                $sql = "select kode_bank,nama,isnull(no_rek, '-') as no_rek,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_bank
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_bank' => 'required',
            'nama' => 'required',
            'no_rek' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $insert= "insert into java_bank(kode_bank, nama, no_rek, kode_lokasi, tgl_input)
            values('$request->kode_bank', '$request->nama', '$request->no_rek', '$kode_lokasi', getdate())";
                
            DB::connection($this->sql)->insert($insert);
                
            $success['status'] = true;
            $success['kode'] = $request->kode_bank;
            $success['message'] = "Data Bank berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Bank gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_bank' => 'required',
            'nama' => 'required',
            'no_rek' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->sql)->table('java_bank')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_bank', $request->kode_bank)
            ->delete();

            $insert= "insert into java_bank(kode_bank, nama, no_rek, kode_lokasi, tgl_input)
            values('$request->kode_bank', '$request->nama', '$request->no_rek', '$kode_lokasi', getdate())";
                
            DB::connection($this->sql)->insert($insert);
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_bank;
            $success['message'] = "Data Bank berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bank gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_bank' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->sql)->table('java_bank')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_bank', $request->kode_bank)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bank berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bank gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}

?>