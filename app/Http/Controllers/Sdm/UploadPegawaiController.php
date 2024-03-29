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

    public function convertDateExcel($date)
    {
        $date =  is_int($date) ? Date::excelToDateTimeObject($date)->format('Y-m-d') : $date;
        return $date;
    }

    public function convertDate($date, $separator = '/')
    {
        $explode = explode($separator, $date);
        return "$explode[2]" . "-" . "$explode[1]" . "-" . "$explode[0]";
    }

    public function joinNum($num)
    {
        // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
        if ($num == "" || $num == "-" || $num == NULL) {
            $num = 0;
        } else {
            $num = str_replace(",", "", $num);
        }
        return $num;
    }

    public function getKodeLoker($nama, $kode_lokasi)
    {
        $select = "SELECT kode_loker FROM hr_loker WHERE LOWER(nama) = '" . strtolower($nama) . "'
        AND kode_lokasi = '" . $kode_lokasi . "'";
        $res1 = DB::connection($this->db)->select($select);
        $res1 = json_decode(json_encode($res1), true);

        return $res1[0]['kode_loker'];
    }

    public function getKodeProfesi($nama, $kode_lokasi)
    {
        $select = "SELECT kode_profesi FROM hr_profesi WHERE LOWER(nama) = '" . strtolower($nama) . "'
        AND kode_lokasi = '" . $kode_lokasi . "'";
        $res1 = DB::connection($this->db)->select($select);
        $res1 = json_decode(json_encode($res1), true);
        return $res1[0]['kode_profesi'];
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'nik_user' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $select = "SELECT nik FROM hr_sdm_tmp WHERE nik_user = '" . $request->input('nik_user') . "'
            AND kode_lokasi = '" . $kode_lokasi . "'";
            $res1 = DB::connection($this->db)->select($select);
            $res1 = json_decode(json_encode($res1), true);

            $niks = array();
            for ($i = 0; $i < count($res1); $i++) {
                array_push($niks, $res1[$i]['nik']);
            }

            DB::connection($this->db)
                ->table('hr_sdm_pribadi')
                ->where('kode_lokasi', $kode_lokasi)
                ->whereIn('nik', $niks);
            DB::connection($this->db)
                ->table('hr_sdm_bank')
                ->where('kode_lokasi', $kode_lokasi)
                ->whereIn('nik', $niks);
                DB::connection($this->db)
                ->table('hr_sdm_kepegawaian')
                ->where('kode_lokasi', $kode_lokasi)
                ->whereIn('nik', $niks);

            $insmd = DB::connection($this->db)->insert("INSERT INTO hr_sdm_pribadi (nik, nama, nomor_ktp, jenis_kelamin, kode_agama, no_telp, no_hp, tempat_lahir, tgl_lahir, alamat,provinsi, kota, kecamatan, kelurahan, kode_pos, tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah,tgl_nikah,kode_lokasi)
            SELECT nik, nama, nomor_ktp, jenis_kelamin, kode_agama, no_telp, no_hp, tempat_lahir, tgl_lahir, alamat,
            provinsi, kota, kecamatan, kelurahan, kode_pos, tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah,
            tgl_nikah,kode_lokasi FROM hr_sdm_tmp WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'");

            $insmd = DB::connection($this->db)->insert("INSERT INTO hr_sdm_bank (nik, kode_bank, cabang, no_rek, nama_rek,kode_lokasi)
            SELECT nik,kode_bank,cabang,no_rek,nama_rek,kode_lokasi FROM hr_sdm_tmp
            WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'");

            $insmd = DB::connection($this->db)->insert("INSERT INTO hr_sdm_kepegawaian (kode,nik,kode_lokasi,kode_status)
            SELECT kode,nik, kode_lokasi,kode_status FROM hr_sdm_tmp
            WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'");

            // $insert = "INSERT INTO hr_sdm_pribadi (nik, nama, nomor_ktp, jenis_kelamin, kode_agama, no_telp, no_hp, tempat_lahir, tgl_lahir, alamat,provinsi, kota, kecamatan, kelurahan, kode_pos, tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah,tgl_nikah,kode_lokasi)
            // SELECT nik, nama, nomor_ktp, jenis_kelamin, kode_agama, no_telp, no_hp, tempat_lahir, tgl_lahir, alamat,
            // provinsi, kota, kecamatan, kelurahan, kode_pos, tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah,
            // tgl_nikah,kode_lokasi FROM hr_sdm_tmp
            // WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'";
            // DB::connection($this->db)->insert($insert);

            // $insert2 = "INSERT INTO hr_sdm_bank (nik, kode_bank, cabang, no_rek, nama_rek,kode_lokasi)
            // SELECT nik,kode_bank,cabang,no_rek,nama_rek,kode_lokasi FROM hr_sdm_tmp
            // WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'";
            // DB::connection($this->db)->insert($insert2);

            // $insert3 = "INSERT INTO hr_sdm_kepegawaian (kode,nik,kode_lokasi,kode_status)
            // SELECT kode,nik, kode_lokasi,kode_status FROM hr_sdm_tmp
            // WHERE kode_lokasi = '" . $kode_lokasi . "' AND nik_user = '" . $request->input('nik_user') . "'";
            // DB::connection($this->db)->insert($insert3);


            DB::connection($this->db)
                ->table('hr_sdm_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('nik_user', $request->input('nik_user'));

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil disimpan";
            return response()->json(['success' => $success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error" . $e;
            Log::error($e);
            return response()->json(['success' => $success], $this->successStatus);
        }
    }

    public function dataTMP(Request $request)
    {
        $this->validate($request, [
            'nik_user' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }


            $select = "SELECT nik, nama, nomor_ktp, jenis_kelamin, kode_agama, no_telp, no_hp, tempat_lahir, convert(varchar(10), tgl_lahir, 101) as tgl_lahir, alamat,
            provinsi, kota, kecamatan, kelurahan, kode_pos, tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah,
            convert(varchar(10), tgl_nikah, 101) as tgl_nikah, kode_bank, cabang, no_rek, nama_rek,nu,kode_status,kode,jabatan
			FROM hr_sdm_tmp
            WHERE nik_user = '" . $request->query('nik_user') . "' AND kode_lokasi = '" . $kode_lokasi . "'";

            $res = DB::connection($this->db)->select($select);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success' => $success], $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['nik'] = $request->query('nik_user');
                $success['status'] = false;
                return response()->json(['success' => $success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error" . $e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function importXLS(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        set_time_limit(300);
        ini_set('max_execution_time', 300);
        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->db)
                ->table('hr_sdm_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('nik_user', $request->input('nik_user'))
                ->delete();
            
            // menangkap file excel
            $file = $request->file('file');
            
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();
            
            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new SDMKaryawanImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            
            $sql = ""; $sql2 = ""; $sql3 = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            
            $i=1;$no=1;
            set_time_limit(300);
            ini_set('max_execution_time', 300); 
            $ins = array(); 
            $periode = date('Ym');
			$nilai = 0; $pph = 0;
			$kode_biaya = "";
            foreach ($excel as $row) {
                if ($row[0] != "") {
                    $sts = 1;
                    $ket = "-";
                    // $tgl_masuk = (is_int($row['tgl_masuk']) ? Date::excelToDateTimeObject($row['tgl_masuk'])->format('Y-m-d') : $row['tgl_masuk']);
                    // // $tgl_keluar = (is_int($row['tgl_keluar']) ? Date::excelToDateTimeObject($row['tgl_keluar'])->format('Y-m-d') : $row['tgl_keluar']);
                    // $tgl_masuk = $this->transformDate($row['tgl_masuk']);
                    // $tgl_keluar = $this->transformDate($row['tgl_keluar']);


                    $sts = 1;
                    $insert = "INSERT INTO hr_sdm_tmp (
                    nik, nama, nomor_ktp,jenis_kelamin,kode_agama, no_telp, no_hp,tempat_lahir, tgl_lahir,alamat,
                    provinsi, kota, kecamatan,kelurahan,kode_pos,tinggi_badan,berat_badan,golongan_darah,nomor_kk,
                    status_nikah,tgl_nikah,kode_bank,cabang, no_rek,nama_rek,
                    kode_lokasi, nik_user, nu, sts_upload, ket_upload,kode_status,kode,jabatan)
                    VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
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
                        $kode_lokasi,
                        $request->input('nik_user'),
                        $no,
                        $sts,
                        "Upload Data Karyawan" . $nik,
                        $row[24],
                        $row[25],
                        $row[26],
                    ]);

                    if($i % 100 == 0){
                        $sql = $begin.$sql.$commit;
                        $ins[] = DB::connection($this->db)->update($sql);
                        $sql = "";
                    }
                    if($i == count($excel) && ($i % 100 != 0) ){
                        $sql = $begin.$sql.$commit;
                        $ins[] = DB::connection($this->db)->update($sql);
                        $sql = "";
                    }
                    $i++;
                    
                   
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
            
            $success['excel'] = count($excel);
            $success['i'] = $i;
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function exportXLS(Request $request)
    {
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
        if (isset($request->type) && $request->type == "template") {
            return Excel::download(new SDMKaryawanExport($nik_user, $kode_lokasi, $request->type), 'Karyawan_' . $nik . '_' . $kode_lokasi . '_' . date('dmy') . '_' . date('Hi') . '.xlsx');
        } else {
            return Excel::download(new SDMKaryawanExport($nik_user, $kode_lokasi, $request->type, $request->periode), 'Karyawan_' . $nik . '_' . $kode_lokasi . '_' . date('dmy') . '_' . date('Hi') . '.xlsx');
        }
    }
}
