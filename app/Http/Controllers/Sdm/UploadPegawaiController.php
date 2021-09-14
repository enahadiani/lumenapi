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

    public function convertDate($date, $separator = '/') {
        $explode = explode($separator, $date);
        return "$explode[2]"."-"."$explode[1]"."-"."$explode[0]";
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

    // public function getKodeLoker($nama, $kode_lokasi) {
    //     $select = "SELECT kode_loker FROM hr_loker WHERE LOWER(nama) = '".strtolower($nama)."'
    //     AND kode_lokasi = '".$kode_lokasi."'";
    //     $res1 = DB::connection($this->db)->select($select);
    //     $res1 = json_decode(json_encode($res1),true);
        
    //     return $res1[0]['kode_loker'];
    // }

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

            $insert = "INSERT INTO hr_karyawan (nik, nama, no_ktp, jk, kode_agama, no_telp, no_hp, tempat, tgl_lahir, alamat,
            provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
            tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja,
            kode_profesi, bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak,
            tgl_kontrak_akhir, area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung,
            kode_lokasi) SELECT nik, nama, no_ktp, jk, kode_agama, no_telp, no_hp, tempat, tgl_lahir, alamat,
            provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
            tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja,
            kode_profesi, bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak,
            tgl_kontrak_akhir, area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung,
            kode_lokasi FROM hr_karyawan_tmp 
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

            $select = "SELECT nik, nama, no_ktp, jk, kode_agama, no_telp, no_hp, tempat, convert(varchar(10), tgl_lahir, 101) as tgl_lahir, alamat,
            provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
            tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja,
            kode_profesi, bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak,
            tgl_kontrak_akhir, area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung,nu 
            FROM hr_karyawan_tmp
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
                    // tanggal lahir
                    if($row[8] == "") {
                        $row[8] = date('Y-m-d');
                    } else {
                        $row[8] = $this->convertDate($row[8]);
                    }

                    // tanggal nikah
                    if($row[20] == "") {
                        $row[20] = date('Y-m-d');
                    } else {
                        $row[20] = $this->convertDate($row[20]);
                    }

                    // tanggal masuk
                    if($row[25] == "") {
                        $row[25] = date('Y-m-d');
                    } else {
                        $row[25] = $this->convertDate($row[25]);
                    }

                    // tanggal kontrak
                    if($row[38] == "") {
                        $row[38] = date('Y-m-d');
                    } else {
                        $row[38] = $this->convertDate($row[38]);
                    }

                    // tanggal kontrak akhir
                    if($row[39] == "") {
                        $row[39] = date('Y-m-d');
                    } else {
                        $row[39] = $this->convertDate($row[39]);
                    }

                    $sts = 1;
                    $insert = "INSERT INTO hr_karyawan_tmp (
                    nik, no_ktp, nama, jk, kode_agama, no_telp, no_hp, tempat, tgl_lahir, alamat,
                    provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
                    tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja, kode_profesi, 
                    bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak, tgl_kontrak_akhir, 
                    area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung,
                    kode_lokasi, nik_user, nu, sts_upload, ket_upload) 
                    VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
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
                        $this->joinNum($row[15]),
                        $this->joinNum($row[16]),
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
                        $row[35],
                        $row[36],
                        $row[37],
                        $row[38],
                        $row[39],
                        $row[40],
                        $row[41],
                        $row[42],
                        $row[43],
                        $row[44],
                        $row[45],
                        $row[46],
                        $row[47],
                        $kode_lokasi,
                        $request->input('nik_user'),
                        $no,
                        $sts,
                        ""
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