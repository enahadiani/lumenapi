<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KepegawaianV2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi){
        
        $auth = DB::connection($this->db)->select("SELECT nik FROM hr_karyawan WHERE nik ='".$isi."' AND kode_lokasi = '".$kode_lokasi."'");
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
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT nik, nama, alamat FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT 
            a.nik, a.no_ktp, a.nama, a.jk, a.kode_agama, a.no_telp, a.no_hp, a.tempat, convert(varchar(10), a.tgl_lahir, 101) as tgl_lahir, a.alamat,
            a.provinsi, a.kota, a.kecamatan, a.kelurahan, a.kode_pos, ISNULL(a.t_badan, 0) AS t_badan, ISNULL(a.b_badan, 0) AS b_badan, a.gol_darah, a.no_kk, a.status_nikah,
            convert(varchar(10), a.tgl_nikah, 101) as tgl_nikah, a.kode_gol, a.kode_sdm, a.kode_unit, a.kode_pp, a.kode_loker, convert(varchar(10), a.tgl_masuk, 101) as tgl_masuk, a.npwp, a.no_bpjs, a.no_bpjs_kerja,
            a.kode_profesi, a.bank, a.cabang, a.no_rek, a.nama_rek, a.client, a.fungsi, a.skill, a.no_kontrak, convert(varchar(10), a.tgl_kontrak, 101) as tgl_kontrak,
            convert(varchar(10), a.tgl_kontrak_akhir, 101) as tgl_kontrak_akhir, a.area, a.kota_area, a.fm, a.bm, a.loker_client, a.jabatan_client, a.atasan_langsung, a.atasan_t_langsung, b.nama as nama_pp,
            c.nama as nama_sdm, d.nama as nama_gol, e.nama as nama_loker, f.nama as nama_unit, g.nama as nama_profesi, h.nama as nama_agama
            FROM hr_karyawan a
            LEFT JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_sdm c ON a.kode_sdm=c.kode_sdm AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN hr_gol d ON a.kode_gol=d.kode_gol AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_loker e ON a.kode_loker=e.kode_loker AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_unit f ON a.kode_unit=f.kode_unit AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_profesi g ON a.kode_profesi=g.kode_profesi AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_agama h ON a.kode_agama=h.kode_agama AND a.kode_lokasi=h.kode_lokasi
            WHERE a.nik = '".$request->nik."' AND a.kode_lokasi = '".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "SELECT nu, jenis, dokumen, sts_dokumen FROM hr_karyawan_doc WHERE kode_lokasi = '".$kode_lokasi."'
            AND nik = '".$request->query('nik')."' ORDER BY nu";

            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'no_ktp' => 'required',
            'nama' => 'required',
            'jk' => 'required',
            'kode_agama' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'alamat' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'kode_pos' => 'required',
            't_badan' => 'required',
            'b_badan' => 'required',
            'gol_darah' => 'required',
            'no_kk' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'kode_gol' => 'required',
            'kode_sdm' => 'required',
            'kode_unit' => 'required',
            'kode_pp' => 'required',
            'kode_loker' => 'required',
            'tgl_masuk' => 'required',
            'npwp' => 'required',
            'no_bpjs' => 'required',
            'no_bpjs_kerja' => 'required',
            'kode_profesi' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'client' => 'required',
            'fungsi' => 'required',
            'skill' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak' => 'required',
            'tgl_kontrak_akhir' => 'required',
            'area' => 'required',
            'kota_area' => 'required',
            'fm' => 'required',
            'bm' => 'required',
            'loker_client' => 'required',
            'jabatan_client' => 'required',
            'atasan_langsung' => 'required',
            'atasan_t_langsung' => 'required',
        ]);
        // 49 field
        
        DB::connection($this->db)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->input('nik'), $kode_lokasi)) {
                //  50 column
                $insert_kar = "INSERT INTO hr_karyawan(
                nik, no_ktp, nama, jk, kode_agama, no_telp, no_hp, tempat, tgl_lahir, alamat, 
                provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
                tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_pp, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja,
                kode_profesi, bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak,
                tgl_kontrak_akhir, area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung, kode_lokasi) 
                VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                DB::connection($this->db)->insert($insert_kar, [
                    $request->input('nik'),
                    $request->input('no_ktp'),
                    $request->input('nama'),
                    $request->input('jk'),
                    $request->input('kode_agama'),
                    $request->input('no_telp'),
                    $request->input('no_hp'),
                    $request->input('tempat'),
                    $request->input('tgl_lahir'),
                    $request->input('alamat'),
                    // 10
                    $request->input('provinsi'),
                    $request->input('kota'),
                    $request->input('kecamatan'),
                    $request->input('kelurahan'),
                    $request->input('kode_pos'),
                    $request->input('t_badan'),
                    $request->input('b_badan'),
                    $request->input('gol_darah'),
                    $request->input('no_kk'),
                    $request->input('status_nikah'),
                    // 10
                    $request->input('tgl_nikah'),
                    $request->input('kode_gol'),
                    $request->input('kode_sdm'),
                    $request->input('kode_unit'),
                    $request->input('kode_pp'),
                    $request->input('kode_loker'),
                    $request->input('tgl_masuk'),
                    $request->input('npwp'),
                    $request->input('no_bpjs'),
                    $request->input('no_bpjs_kerja'),
                    // 10
                    $request->input('kode_profesi'),
                    $request->input('bank'),
                    $request->input('cabang'),
                    $request->input('no_rek'),
                    $request->input('nama_rek'),
                    $request->input('client'),
                    $request->input('fungsi'),
                    $request->input('skill'),
                    $request->input('no_kontrak'),
                    $request->input('tgl_kontrak'),
                    // 10
                    $request->input('tgl_kontrak_akhir'),
                    $request->input('area'),
                    $request->input('kota_area'),
                    $request->input('fm'),
                    $request->input('bm'),
                    $request->input('loker_client'),
                    $request->input('jabatan_client'),
                    $request->input('atasan_langsung'),
                    $request->input('atasan_t_langsung'),
                    $kode_lokasi
                    // 10
                ]);

                if(count($request->input('nu')) > 0) {
                    if(!empty($request->file('file'))) { 
                        if(count($request->file('file')) > 0) {
                            for($j=0;$j<count($request->file('file'));$j++) {
                                $file = $request->file('file')[$j];
                                $nama_foto = $request->input('nik')."_".$file->getClientOriginalName();
                                        
                                if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                                    Storage::disk('s3')->delete('sdm/'.$nama_foto);
                                }
                                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                            }
                        }
                    }
                    // 6 column
                    $insert_doc = "INSERT INTO hr_karyawan_doc(
                        nik, nu, kode_lokasi, jenis, dokumen, sts_dokumen
                    ) VALUES (?, ?, ?, ?, ?, ?)";

                    for($i=0;$i<count($request->input('nu'));$i++) {
                        $nu = $request->input('nu'); 
                        $jenis = $request->input('jenis'); 
                        $fileName = $request->input('fileName');
                        $filePrevName = $request->input('filePrevName');
                        $isUpload = $request->input('isUpload');
                        $sts_dokumen = $request->input('sts_dokumen');
                        
                        if($isUpload[$i] == "false") {
                            DB::connection($this->db)->insert($insert_doc, [
                                $request->input('nik'),
                                $nu[$i],
                                $kode_lokasi,
                                $jenis[$i],
                                $filePrevName[$i],
                                $sts_dokumen[$i]
                            ]);
                        } else {
                            DB::connection($this->db)->insert($insert_doc, [
                                $request->input('nik'),
                                $nu[$i],
                                $kode_lokasi,
                                $jenis[$i],
                                $fileName[$i],
                                $sts_dokumen[$i]
                            ]);
                        }
                    }   
                }
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK karyawan sudah ada di database!";
            }
            $success['kode'] = $request->nik;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
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
            'nik' => 'required',
            'no_ktp' => 'required',
            'nama' => 'required',
            'jk' => 'required',
            'kode_agama' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'alamat' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'kode_pos' => 'required',
            't_badan' => 'required',
            'b_badan' => 'required',
            'gol_darah' => 'required',
            'no_kk' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'kode_gol' => 'required',
            'kode_sdm' => 'required',
            'kode_unit' => 'required',
            'kode_pp' => 'required',
            'kode_loker' => 'required',
            'tgl_masuk' => 'required',
            'npwp' => 'required',
            'no_bpjs' => 'required',
            'no_bpjs_kerja' => 'required',
            'kode_profesi' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'client' => 'required',
            'fungsi' => 'required',
            'skill' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak' => 'required',
            'tgl_kontrak_akhir' => 'required',
            'area' => 'required',
            'kota_area' => 'required',
            'fm' => 'required',
            'bm' => 'required',
            'loker_client' => 'required',
            'jabatan_client' => 'required',
            'atasan_langsung' => 'required',
            'atasan_t_langsung' => 'required',
        ]);
        // 49 field
        DB::connection($this->db)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_karyawan')
            ->where('nik', $request->input('nik'))
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->table('hr_karyawan_doc')
            ->where('nik', $request->input('nik'))
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            //  50 column
            $insert_kar = "INSERT INTO hr_karyawan(
            nik, no_ktp, nama, jk, kode_agama, no_telp, no_hp, tempat, tgl_lahir, alamat, 
            provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah,
            tgl_nikah, kode_gol, kode_sdm, kode_unit, kode_pp, kode_loker, tgl_masuk, npwp, no_bpjs, no_bpjs_kerja,
            kode_profesi, bank, cabang, no_rek, nama_rek, client, fungsi, skill, no_kontrak, tgl_kontrak,
            tgl_kontrak_akhir, area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung, kode_lokasi) 
            VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            DB::connection($this->db)->insert($insert_kar, [
                $request->input('nik'),
                $request->input('no_ktp'),
                $request->input('nama'),
                $request->input('jk'),
                $request->input('kode_agama'),
                $request->input('no_telp'),
                $request->input('no_hp'),
                $request->input('tempat'),
                $request->input('tgl_lahir'),
                $request->input('alamat'),
                // 10
                $request->input('provinsi'),
                $request->input('kota'),
                $request->input('kecamatan'),
                $request->input('kelurahan'),
                $request->input('kode_pos'),
                $request->input('t_badan'),
                $request->input('b_badan'),
                $request->input('gol_darah'),
                $request->input('no_kk'),
                $request->input('status_nikah'),
                // 10
                $request->input('tgl_nikah'),
                $request->input('kode_gol'),
                $request->input('kode_sdm'),
                $request->input('kode_unit'),
                $request->input('kode_pp'),
                $request->input('kode_loker'),
                $request->input('tgl_masuk'),
                $request->input('npwp'),
                $request->input('no_bpjs'),
                $request->input('no_bpjs_kerja'),
                // 10
                $request->input('kode_profesi'),
                $request->input('bank'),
                $request->input('cabang'),
                $request->input('no_rek'),
                $request->input('nama_rek'),
                $request->input('client'),
                $request->input('fungsi'),
                $request->input('skill'),
                $request->input('no_kontrak'),
                $request->input('tgl_kontrak'),
                // 10
                $request->input('tgl_kontrak_akhir'),
                $request->input('area'),
                $request->input('kota_area'),
                $request->input('fm'),
                $request->input('bm'),
                $request->input('loker_client'),
                $request->input('jabatan_client'),
                $request->input('atasan_langsung'),
                $request->input('atasan_t_langsung'),
                $kode_lokasi
                // 10
            ]);

            if(count($request->input('nu')) > 0) {
                if(!empty($request->file('file'))) { 
                    if(count($request->file('file')) > 0) {
                        for($j=0;$j<count($request->file('file'));$j++) {
                            $file = $request->file('file')[$j];
                            $nama_foto = $request->input('nik')."_".$file->getClientOriginalName();
                                        
                            if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                                Storage::disk('s3')->delete('sdm/'.$nama_foto);
                            }
                            Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                        }
                    }
                }
                // 6 column
                $insert_doc = "INSERT INTO hr_karyawan_doc(
                nik, nu, kode_lokasi, jenis, dokumen, sts_dokumen
                ) VALUES (?, ?, ?, ?, ?, ?)";

                for($i=0;$i<count($request->input('nu'));$i++) {
                    $nu = $request->input('nu'); 
                    $jenis = $request->input('jenis'); 
                    $fileName = $request->input('fileName');
                    $filePrevName = $request->input('filePrevName');
                    $isUpload = $request->input('isUpload');
                    $sts_dokumen = $request->input('sts_dokumen');
                        
                    if($isUpload[$i] == "false") {
                        DB::connection($this->db)->insert($insert_doc, [
                            $request->input('nik'),
                            $nu[$i],
                            $kode_lokasi,
                            $jenis[$i],
                            $filePrevName[$i],
                            $sts_dokumen[$i]
                        ]);
                    } else {
                        DB::connection($this->db)->insert($insert_doc, [
                            $request->input('nik'),
                            $nu[$i],
                            $kode_lokasi,
                            $jenis[$i],
                            $fileName[$i],
                            $sts_dokumen[$i]
                        ]);
                    }
                }   
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data karyawan berhasil diubah";
            $success['kode'] = $request->nik;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data karyawan gagal diubah ".$e;
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
            'nik' => 'required'
        ]);
        
        DB::connection($this->db)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_karyawan_doc')
            ->where('nik', $request->input('nik'))
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            DB::connection($this->db)->table('hr_karyawan')
            ->where('nik', $request->input('nik'))
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPP(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_pp, nama FROM pp WHERE kode_lokasi = '".$kode_lokasi."' ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
