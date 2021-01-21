<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AbsenKelasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }   
    

    public function show(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_kelas)){
                $filter .= " and a.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti
            from sis_set_absen a
            where a.kode_lokasi='".$kode_lokasi."' $filter ");
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){
                $res2 = DB::connection($this->db)->select("select a.nis,a.nis2,a.nama,a.no_urut 
                from sis_siswa a 
                where a.kode_lokasi = '".$kode_lokasi."' $filter and a.flag_aktif=1
                order by a.no_urut ");
            }else{
                $res2 = DB::connection($this->db)->select("select a.nis,a.nis2,a.nama,a.no_urut 
                from sis_siswa a 
                where a.kode_lokasi = '".$kode_lokasi."' $filter and a.flag_aktif=1
                order by a.nama ");
            }
            $res2 = json_decode(json_encode($res2),true);   

            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data_detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }

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
            'kode_pp' => 'required',
            'kode_kelas' => 'required',
            'nis'=>'required|array',
            'no_urut'=>'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            date_default_timezone_set("Asia/Bangkok");
            $per = date('ym');
            $no_bukti = $this->generateKode("sis_set_absen", "no_bukti", $kode_lokasi."-ABS".$per.".", "00001");
            if(count($request->nis) > 0){     
                
                $ins = DB::connection($this->db)->insert("insert into sis_set_absen(no_bukti,kode_lokasi,kode_kelas,kode_pp,nik_user,tgl_input) values ('$no_bukti','$kode_lokasi','$request->kode_kelas','$request->kode_pp','$nik',getdate())");
                
                $sql = "";
                $begin = "SET NOCOUNT on;
                BEGIN tran;
                ";
                $commit = "commit tran;";
                for($i=0;$i<count($request->nis);$i++){

                    $sql.=" update sis_siswa set no_urut ='".$request->no_urut[$i]."' where kode_pp = '$request->kode_pp' and kode_kelas='$request->kode_kelas' and kode_lokasi='$kode_lokasi' and nis='".$request->nis[$i]."'; ";
                }  
                $upd = DB::connection($this->db)->update($sql);   
                DB::connection($this->db)->commit();

                $sts = true;
                $msg = "Data Absensi berhasil disimpan.";
            }else{
                $sts = true;
                $no_bukti = "-";
                $msg = "Data Absensi gagal disimpan. Detail Absen tidak valid";
            }
            $success['no_bukti'] = $no_bukti;
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penilaian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    

}
