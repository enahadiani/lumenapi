<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JurSesuaiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    
    public function getBuktiDetail(Request $request) {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);

        try {
           
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select * from ju_m where no_ju='".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."'");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("select b.kode_akun,b.nama as nama_akun, a.dc, a.keterangan, a.nilai,  c.kode_pp,c.nama as nama_pp, d.kode_fs, d.nama as nama_fs
                                                       from gldt a 
                                                       inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                                       inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                                                       inner join fs d on a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi 
                                                       where a.no_bukti='".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by no_urut");						
            $res2= json_decode(json_encode($res2),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['arrjurnal'] = $res2;  
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['arrjurnal'] = [];  
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }
   
    public function index(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_bukti)){
                if($request->no_bukti == "all"){
                    $filter = "";
                }else{
                    $filter = " and no_ju='".$request->no_bukti."' ";
                }
                $sql= "select no_ju as no_bukti, tanggal, no_dokumen, keterangan, nilai from ju_m where modul = 'SESUAI' and kode_lokasi='".$kode_lokasi."' ".$filter;
            }
            else {
                $sql= "select no_ju as no_bukti, tanggal, no_dokumen, keterangan, nilai from ju_m where modul = 'SESUAI' and kode_lokasi='".$kode_lokasi."' ";
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

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function getNoBukti(Request $request) {
        $this->validate($request, [    
            'tanggal' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = substr($request->tanggal,2,2).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("ju_m", "no_ju", $kode_lokasi."-JS".$periode.".", "0001");

            $res = $no_bukti;
            
            $success['status'] = true;
            $success['data'] = $res;
            $success['message'] = "Success!";
            return response()->json(['success'=>$success], $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function store(Request $request)
    {
        $this->validate($request, [            
            'tanggal' => 'required',
            'no_dokumen' => 'required|max:50',
            'keterangan' => 'required|max:200',                               
            'kode_pp' => 'required',
            'nilai' => 'required|numeric',

            'arrjurnal'=>'required|array',
            'arrjurnal.*.no_urut' => 'required', 
            'arrjurnal.*.kode_akun' => 'required',                                    
            'arrjurnal.*.dc' => 'required',
            'arrjurnal.*.keterangan' => 'required',
            'arrjurnal.*.nilai' => 'required|numeric',  
            'arrjurnal.*.kode_pp' => 'required',
            'arrjurnal.*.kode_fs' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                                  
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("ju_m", "no_ju", $kode_lokasi."-JS".$periode.".", "0001");

            $ins = DB::connection($this->sql)->insert("insert into ju_m(no_ju, kode_lokasi, no_dokumen, tanggal, keterangan, kode_pp, modul, jenis, periode, kode_curr, kurs, nilai, nik_buat, nik_setuju, tgl_input, nik_user, posted, no_del, no_link, ref1) values 
                                                      ('".$no_bukti."', '".$kode_lokasi."', '".$request->no_dokumen."', '".$request->tanggal."', '".$request->keterangan."', '".$request->kode_pp."', 'SESUAI', 'JS', '".$periode."', 'IDR', 1, ".floatval($request->nilai).", '".$nik."', '".$nik."', getdate(), '".$nik."', 'T', '-', '-', '-')");

            $arrjurnal = $request->arrjurnal;
            if (count($arrjurnal) > 0){
                for ($i=0;$i <count($arrjurnal);$i++){                
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into gldt(no_bukti, no_urut, kode_lokasi, no_dokumen, tanggal, kode_akun, dc, nilai, keterangan, kode_pp, kode_drk, kode_cust, kode_proyek, kode_task, kode_vendor, kode_lokarea, nik, modul, jenis, periode, kode_curr, kurs, nilai_curr, tgl_input, nik_user, kode_fs) values  
                                                                   ('".$no_bukti."', ".floatval($arrjurnal[$i]['no_urut']).", '".$kode_lokasi."', '".$request->no_dokumen."', '".$request->tanggal."', '".$arrjurnal[$i]['kode_akun']."', '".$arrjurnal[$i]['dc']."', '".floatval($arrjurnal[$i]['nilai'])."', '".$arrjurnal[$i]['keterangan']."', '".$arrjurnal[$i]['kode_pp']."', '-', '-', '-', '-', '-', '-', '-', 'JS', 'JS', '".$periode."', 'IDR', 1, ".floatval($arrjurnal[$i]['nilai']).", getdate(), '".$nik."', '".$arrjurnal[$i]['kode_fs']."')");                    
                }						
            }	
                                                                    
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				 
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [    
            'no_bukti' => 'required',
            'periode' => 'required',        
            'tanggal' => 'required',
            'no_dokumen' => 'required|max:50',
            'keterangan' => 'required|max:200',
            'kode_pp' => 'required',
            'nilai' => 'required|numeric',

            'arrjurnal'=>'required|array',
            'arrjurnal.*.no_urut' => 'required', 
            'arrjurnal.*.kode_akun' => 'required',                                    
            'arrjurnal.*.dc' => 'required',
            'arrjurnal.*.keterangan' => 'required',
            'arrjurnal.*.nilai' => 'required|numeric',  
            'arrjurnal.*.kode_pp' => 'required',
            'arrjurnal.*.kode_fs' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ju_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ju', $request->no_bukti)
            ->delete();

            $del2 = DB::connection($this->sql)->table('gldt')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into ju_m(no_ju, kode_lokasi, no_dokumen, tanggal, keterangan, kode_pp, modul, jenis, periode, kode_curr, kurs, nilai, nik_buat, nik_setuju, tgl_input, nik_user, posted, no_del, no_link, ref1) values 
                                                      ('".$request->no_bukti."', '".$kode_lokasi."', '".$request->no_dokumen."', '".$request->tanggal."', '".$request->keterangan."', '".$request->kode_pp."', 'SESUAI', 'JS', '".$request->periode."', 'IDR', 1, ".floatval($request->nilai).", '".$nik."', '".$nik."', getdate(), '".$nik."', 'T', '-', '-', '-')");

            $arrjurnal = $request->arrjurnal;
            if (count($arrjurnal) > 0){
                for ($i=0;$i <count($arrjurnal);$i++){                
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into gldt(no_bukti, no_urut, kode_lokasi, no_dokumen, tanggal, kode_akun, dc, nilai, keterangan, kode_pp, kode_drk, kode_cust, kode_proyek, kode_task, kode_vendor, kode_lokarea, nik, modul, jenis, periode, kode_curr, kurs, nilai_curr, tgl_input, nik_user, kode_fs) values  
                                                                   ('".$request->no_bukti."', ".floatval($arrjurnal[$i]['no_urut']).", '".$kode_lokasi."', '".$request->no_dokumen."', '".$request->tanggal."', '".$arrjurnal[$i]['kode_akun']."', '".$arrjurnal[$i]['dc']."', '".floatval($arrjurnal[$i]['nilai'])."', '".$arrjurnal[$i]['keterangan']."', '".$arrjurnal[$i]['kode_pp']."', '-', '-', '-', '-', '-', '-', '-', 'JS', 'JS', '".$request->periode."', 'IDR', 1, ".floatval($arrjurnal[$i]['nilai']).", getdate(), '".$nik."', '".$arrjurnal[$i]['kode_fs']."')");                    
                }						
            }		

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ju_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ju', $request->no_bukti)
            ->delete();

            $del2 = DB::connection($this->sql)->table('gldt')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kunjungan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
    
}
