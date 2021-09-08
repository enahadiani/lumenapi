<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use App\Imports\SiswaImport;
use App\Exports\SiswaExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Log; 

class UploadSiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    public function convertDateExcel($date) {
       $date =  is_int($date) ? Date::excelToDateTimeObject($date)->format('Y-m-d') : $date;
       return $date;
    }

    public function convertDate($date, $separator = '/') {
        $explode = explode($separator, $date);
        return "$explode[2]"."-"."$explode[1]"."-"."$explode[0]";
    }

    public function store(Request $request) {
        $this->validate($request, [ 
            'nik_user' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select = "SELECT nis FROM sis_siswa_tmp WHERE nik_user = '".$request->input('nik_user')."'
            AND kode_lokasi = '".$kode_lokasi."'";
            $res1 = DB::connection($this->db)->select($select);
            $res1 = json_decode(json_encode($res1),true);
            
            $niks = array();
            for($i=0;$i<count($res1);$i++) {
                array_push($niks, $res1[$i]['nis']);
            }
            
            DB::connection($this->db)
            ->table('sis_siswa')
            ->where('kode_lokasi', $kode_lokasi)
            ->whereIn('nis', $niks)
            ->delete();

            $insert = "INSERT INTO sis_siswa (nis, flag_aktif, kode_kelas, kode_akt, nama, tmp_lahir, tgl_lahir, jk, agama, hp_siswa,
            email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali, gol_darah, id_bank, nis2, kode_pp,
            kode_lokasi) SELECT nis, flag_aktif, kode_kelas, kode_akt, nama, tmp_lahir, tgl_lahir, jk, agama, hp_siswa,
            email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali, gol_darah, id_bank, nis2, kode_pp,
            kode_lokasi FROM sis_siswa_tmp 
            WHERE kode_lokasi = '".$kode_lokasi."' AND nik_user = '".$request->input('nik_user')."'";
            DB::connection($this->db)->insert($insert);
            
            DB::connection($this->db)
            ->table('sis_siswa_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $request->input('nik_user'))
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Siswa berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function dataTMP(Request $request) {
        $this->validate($request, [
            'nik_user' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select = "SELECT nis, flag_aktif, kode_kelas, kode_akt, nama, tmp_lahir, tgl_lahir, jk, agama, hp_siswa,
            email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali, gol_darah, id_bank, nis2, nu 
            FROM sis_siswa_tmp
            WHERE nik_user = '".$request->query('nik_user')."' AND kode_lokasi = '".$kode_lokasi."'";

            $res = DB::connection($this->db)->select($select);
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function importXLS(Request $request) {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->db)
            ->table('sis_siswa_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $request->input('nik_user'))
            ->delete();

            $file = $request->file('file');
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));

            $dt = Excel::toArray(new SiswaImport(), $nama_file);
            $excel = $dt[0];

            $x = array();
            $status_validate = true;
            $no=1;

            foreach($excel as $row){
                if($row[0] != "") {
                    // tanggal lahir
                    if($row[6] == "") {
                        $row[6] = date('Y-m-d');
                    } else {
                        $row[6] = $this->convertDateExcel($row[6]);
                    }

                    $sts = 1;
                    $insert = "INSERT INTO sis_siswa_tmp (
                    nis, flag_aktif, kode_kelas, kode_akt, nama, tmp_lahir, tgl_lahir, jk, agama, hp_siswa,
                    email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali, gol_darah, id_bank, nis2, kode_pp,
                    kode_lokasi, nik_user, nu, sts_upload, ket_upload) 
                    VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?)";
                    DB::connection($this->db)->insert($insert, [
                        $row[0],
                        $row[1],
                        $row[2],
                        $row[3],
                        $row[4],
                        $row[5],
                        $row[6],
                        $row[7],
                        $row[8],
                        $row[9],
                        $row[10],
                        $row[11],
                        $row[12],
                        $row[13],
                        $row[14],
                        $row[15],
                        $row[16],
                        $row[17],
                        $row[18],
                        $row[19],
                        $row[20],
                        $kode_lokasi,
                        $request->input('nik_user'),
                        $no,
                        $sts,
                        "-"
                    ]);
                    $no++;
                }
            }

            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function exportXLS(Request $request) {
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'nik' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $request->nik_user;
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        if(isset($request->type) && $request->type == "template") { 
            return Excel::download(new SiswaExport($nik_user,$kode_lokasi,$request->type), 'Siswa_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new SiswaExport($nik_user,$kode_lokasi,$request->type,$request->periode), 'Siswa_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

}
