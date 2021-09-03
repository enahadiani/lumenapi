<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use App\Imports\SDMKaryawanImport;
use App\Exports\SDMKaryawanExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Log; 

class UploadPegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function convertDateExcel($date) {
       $date =  is_int($date) ? Date::excelToDateTimeObject($date)->format('Y-m-d') : $date;
       return $date;
    }

    public function joinNum($num){
        // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
        if($num == "" || $num == "-" || $num == NULL) {
            $num = 0;
        } else {
            $num = str_replace(",", "", $num);
        }
        return $num;
    }

    public function getKodeLoker($nama, $kode_lokasi) {
        $select = "SELECT kode_loker FROM hr_loker WHERE LOWER(nama) = '".strtolower($nama)."'
        AND kode_lokasi = '".$kode_lokasi."'";
        $res1 = DB::connection($this->db)->select($select);
        $res1 = json_decode(json_encode($res1),true);
        
        return $res1[0]['kode_loker'];
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

            $select = "SELECT nik FROM hr_karyawan_tmp WHERE nik_user = '".$request->input('nik_user')."'
            AND kode_lokasi = '".$kode_lokasi."'";
            $res1 = DB::connection($this->db)->select($select);
            $res1 = json_decode(json_encode($res1),true);
            
            $niks = array();
            for($i=0;$i<count($res1);$i++) {
                array_push($niks, $res1[$i]['nik']);
            }
            
            DB::connection($this->db)
            ->table('hr_karyawan')
            ->where('kode_lokasi', $kode_lokasi)
            ->whereIn('nik', $niks)
            ->delete();

            $insert = "INSERT INTO hr_karyawan (nik, nama, status_nikah, tempat, tgl_lahir, jk, 
            t_badan, b_badan, gol_darah, kode_agama, alamat, kelurahan, kecamatan, kota, provinsi, kode_pos, 
            no_ktp, no_telp, email, kode_strata, client, area, kota_area, fm, bm, loker, jabatan, skill, fungsi,
            no_kontrak, tgl_kontrak, tgl_kontrak_akhir, bank, no_rek, nama_rek, gaji_pokok, 
            tunj_jabatan, tunj_penampilan, tunj_gondola, tunj_taman, tunj_kompetensi, tunj_skill, tunj_patroli,
            tunj_lembur, tunj_masakerja, no_bpjs, no_bpjs_kerja, kode_lokasi, kode_loker) SELECT nik, nama, status_nikah, tempat, tgl_lahir, jk, 
            t_badan, b_badan, gol_darah, kode_agama, alamat, kelurahan, kecamatan, kota, provinsi, kode_pos, 
            no_ktp, no_telp, email, kode_strata, client, area, kota_area, fm, bm, loker, jabatan, skill, fungsi,
            no_kontrak, tgl_kontrak, tgl_kontrak_akhir, bank, no_rek, nama_rek, gaji_pokok, 
            tunj_jabatan, tunj_penampilan, tunj_gondola, tunj_taman, tunj_kompetensi, tunj_skill, tunj_patroli,
            tunj_lembur, tunj_masakerja, no_bpjs, no_bpjs_kerja, kode_lokasi, kode_loker FROM hr_karyawan_tmp 
            WHERE kode_lokasi = '".$kode_lokasi."' AND nik_user = '".$request->input('nik_user')."'";
            DB::connection($this->db)->insert($insert);
            
            DB::connection($this->db)
            ->table('hr_karyawan_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $request->input('nik_user'))
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan awal berhasil disimpan";
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

            $select = "SELECT nu,nik, nama, status_nikah, tempat, tgl_lahir, jk, 
            t_badan, b_badan, gol_darah, kode_agama, alamat, kelurahan, kecamatan, kota, provinsi, kode_pos, 
            no_ktp, no_telp, email, kode_strata, client, area, kota_area, fm, bm, loker, jabatan, skill, fungsi,
            no_kontrak, tgl_kontrak, tgl_kontrak_akhir, bank, no_rek, nama_rek, gaji_pokok, 
            tunj_jabatan, tunj_penampilan, tunj_gondola, tunj_taman, tunj_kompetensi, tunj_skill, tunj_patroli,
            tunj_lembur, tunj_masakerja, no_bpjs, no_bpjs_kerja FROM hr_karyawan_tmp
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
            ->table('hr_karyawan_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user', $request->input('nik_user'))
            ->delete();

            $file = $request->file('file');
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));

            $dt = Excel::toArray(new SDMKaryawanImport(), $nama_file);
            $excel = $dt[0];

            $x = array();
            $status_validate = true;
            $no=1;

            foreach($excel as $row){
                if($row[0] != "") {
                    if($row[6] == "") {
                        $row[6] = 0;
                    }
                    if($row[7] == "") {
                        $row[7] = 0;
                    }

                    if($row[30] == "") {
                        $row[30] = date('Y-m-d');
                    } else {
                        $row[30] = $this->convertDateExcel($row[30]);
                    }

                    if($row[31] == "") {
                        $row[31] = date('Y-m-d');
                    } else {
                        $row[31] = $this->convertDateExcel($row[31]);
                    }
                    
                    $kode_loker = $this->getKodeLoker($row[25], $kode_lokasi);
                    $sts = 1;
                    $insert = "INSERT INTO hr_karyawan_tmp (nik, nama, status_nikah, tempat, tgl_lahir, jk, 
                    t_badan, b_badan, gol_darah, kode_agama, alamat, kelurahan, kecamatan, kota, provinsi, kode_pos, 
                    no_ktp, no_telp, email, kode_strata, client, area, kota_area, fm, bm, loker, jabatan, skill, fungsi,
                    no_kontrak, tgl_kontrak, tgl_kontrak_akhir, bank, no_rek, nama_rek, gaji_pokok, 
                    tunj_jabatan, tunj_penampilan, tunj_gondola, tunj_taman, tunj_kompetensi, tunj_skill, tunj_patroli,
                    tunj_lembur, tunj_masakerja, no_bpjs, no_bpjs_kerja, kode_lokasi, sts_upload, 
                    ket_upload, nu, nik_user, kode_loker) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    DB::connection($this->db)->insert($insert, [
                        $row[0],
                        $row[1],
                        $row[2],
                        $row[3],
                        $this->convertDateExcel($row[4]),
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
                        $row[21],
                        $row[22],
                        $row[23],
                        $row[24],
                        $row[25],
                        $row[26],
                        $row[27],
                        $row[28],
                        $row[29],
                        $row[30],
                        $row[31],
                        $row[32],
                        $row[33],
                        $row[34],
                        $this->joinNum($row[35]),
                        $this->joinNum($row[36]),
                        $this->joinNum($row[37]),
                        $this->joinNum($row[38]),
                        $this->joinNum($row[39]),
                        $this->joinNum($row[40]),
                        $this->joinNum($row[41]),
                        $this->joinNum($row[42]),
                        $this->joinNum($row[43]),
                        $this->joinNum($row[44]),
                        $row[45],
                        $row[46],
                        $kode_lokasi,
                        $sts,
                        "",
                        $no,
                        $request->input('nik_user'),
                        $kode_loker
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
            return Excel::download(new SDMKaryawanExport($nik_user,$kode_lokasi,$request->type), 'Karyawan_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new SDMKaryawanExport($nik_user,$kode_lokasi,$request->type,$request->periode), 'Karyawan_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }
    
}
