<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KepegawaianV3Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi)
    {

        $auth = DB::connection($this->db)->select("SELECT nik FROM hr_sdm_pribadi WHERE nik ='" . $isi . "' AND kode_lokasi = '" . $kode_lokasi . "'");
        $auth = json_decode(json_encode($auth), true);
        if (count($auth) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT nik, nama, alamat, ISNULL(nomor_ktp, '-') AS no_ktp FROM hr_sdm_pribadi WHERE kode_lokasi = '" . $kode_lokasi . "' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) {
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            // data pribadi
            $sql1 = "SELECT a.nik, a.nama, a.nomor_ktp, a.jenis_kelamin, a.kode_agama,b.nama as nama_agama, a.no_telp, a.no_hp, a.tempat_lahir,
            convert(varchar(10), a.tgl_lahir, 101) as tgl_lahir,a.alamat,
            a.provinsi,a.kota,a.kecamatan,a.kelurahan,a.kode_pos,a.tinggi_badan,a.berat_badan,
            a.golongan_darah,a.nomor_kk,a.status_nikah,
            convert(varchar(10), tgl_nikah, 101) as tgl_nikah
            FROM hr_sdm_pribadi a
            LEFT JOIN hr_agama b ON a.kode_agama=b.kode_agama AND a.kode_lokasi=b.kode_lokasi
            WHERE a.nik = '" . $request->nik . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
            $res = DB::connection($this->db)->select($sql1);
            $res = json_decode(json_encode($res), true);

            // Data Kepegawaian
            $sql2 = "SELECT a.kode_golongan,h.nama as nama_golongan,a.kode_sdm,b.nama as nama_sdm, a.kode_area,c.nama as nama_area,a.kode_fm,d.nama as nama_fm,
            a.kode_bm,e.nama as nama_bm, a.kode_loker,f.nama as nama_loker, a.no_npwp, a.no_bpjs,
            a.tgl_masuk, a.no_bpjs_naker, a.kode_profesi,g.nama as nama_profesi
            FROM hr_sdm_kepegawaian a
            LEFT JOIN hr_sdm b ON a.kode_sdm=b.kode_sdm AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_area c ON a.kode_area=c.kode_area AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN hr_fm d ON a.kode_fm=d.kode_fm AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_bm e ON a.kode_bm=e.kode_bm AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_loker f ON a.kode_loker=f.kode_loker AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_profesi g ON a.kode_profesi=g.kode_profesi AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_gol h ON a.kode_golongan=h.kode_gol AND a.kode_lokasi=g.kode_lokasi
            WHERE a.nik = '" . $request->nik . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            // DATA BANK
            $sql3 = "SELECT a.kode_bank,b.nama as nama_bank,a.cabang, a.no_rek,a.nama_rek
            FROM hr_sdm_bank a
            LEFT JOIN hr_bank b ON a.kode_bank=b.kode_bank WHERE a.nik = '" . $request->nik . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3), true);

            // DATA GAJI PARAM
            $sql4 = "SELECT kode_param,nama_param,nilai,nu FROM hr_sdm_gaji WHERE nik = '" . $request->nik . "' AND kode_lokasi = '" . $kode_lokasi . "' ORDER BY nu ";
            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4), true);

            // DATA CLIENT
            $sql5 = "SELECT * FROM hr_sdm_client WHERE nik = '" . $request->nik . "' AND kode_lokasi = '" . $kode_lokasi . "' ";
            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5), true);

            $sql6 = "SELECT nu, jenis, dokumen, sts_dokumen FROM hr_sdm_doc WHERE kode_lokasi = '" . $kode_lokasi . "'
            AND nik = '" . $request->query('nik') . "' ORDER BY nu";

            $res6 = DB::connection($this->db)->select($sql3);
            $res6 = json_decode(json_encode($res6), true);

            if (count($res) > 0) {
                $success['data_pribadi'] = $res;
                $success['data_kepeg'] = $res2;
                $success['data_bank'] = $res3;
                $success['data_gaji'] = $res4;
                $success['data_client'] = $res5;
                $success['data_doc'] = $res6;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data_pribadi'] = [];
                $success['data_kepeg'] = [];
                $success['data_bank'] = [];
                $success['data_gaji'] = [];
                $success['data_client'] = [];
                $success['data_doc'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
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
            'nomor_ktp' => 'required',
            'nama' => 'required',
            'jenis_kelamin' => 'required',
            'kode_agama' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required',
            'alamat' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'kode_pos' => 'required',
            'tinggi_badan' => 'required',
            'berat_badan' => 'required',
            'golongan_darah' => 'required',
            'nomor_kk' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'kode_golongan' => 'required',
            'kode_sdm' => 'required',
            'kode_loker' => 'required',
            'tgl_masuk' => 'required',
            'no_npwp' => 'required',
            'no_bpjs' => 'required',
            'no_bpjs_naker' => 'required',
            'kode_profesi' => 'required',
            'kode_bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'skill' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak_awal' => 'required',
            'tgl_kontrak_akhir' => 'required',
            'kode_area' => 'required',
            'kode_fm' => 'required',
            'kode_bm' => 'required',
            'nama_client' => 'required',
            'atasan_langsung' => 'required',
            'atasan_tidak_langsung' => 'required',
        ]);
        // 49 field

        DB::connection($this->db)->beginTransaction();
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('nik'), $kode_lokasi)) {
                //  50 column
                $insert_kar = "INSERT INTO hr_sdm_pribadi(
                    nik,kode_lokasi,
                    nama, nomor_ktp,
                    jenis_kelamin, kode_agama,
                    no_telp, no_hp, tempat_lahir, tgl_lahir,
                    alamat, provinsi, kota, kecamatan, kelurahan, kode_pos,
                    tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah, tgl_nikah)
                    VALUES(
                        ?,?,
                        ?,?,
                        ?,?,
                        ?,?,?,?,
                        ?,?,?,?,?,?,
                        ?,?,?,?,?,?
                    )";

                DB::connection($this->db)->insert($insert_kar, [
                    $request->input('nik'),
                    $kode_lokasi,
                    //2
                    $request->input('nama'),
                    $request->input('nomor_ktp'),
                    //2
                    $request->input('jenis_kelamin'),
                    $request->input('kode_agama'),
                    // 2
                    $request->input('no_telp'),
                    $request->input('no_hp'),
                    $request->input('tempat_lahir'),
                    $request->input('tgl_lahir'),
                    // 4
                    $request->input('alamat'),
                    $request->input('provinsi'),
                    $request->input('kota'),
                    $request->input('kecamatan'),
                    $request->input('kelurahan'),
                    $request->input('kode_pos'),
                    // 6
                    $request->input('tinggi_badan'),
                    $request->input('berat_badan'),
                    $request->input('golongan_darah'),
                    $request->input('nomor_kk'),
                    $request->input('status_nikah'),
                    $request->input('tgl_nikah')
                    // 6
                ]);

                $insert_kepeg = "INSERT INTO hr_sdm_kepegawaian(
                        nik,kode_golongan,
                        kode_sdm,kode_area,kode_fm,
                        kode_bm,kode_loker,no_npwp,no_bpjs,
                        tgl_masuk,no_bpjs_naker,kode_profesi,kode_lokasi
                    ) VALUES(
                        ?,?,
                        ?,?,?,
                        ?,?,?,?,
                        ?,?,?,?
                    )";
                DB::connection($this->db)->insert($insert_kepeg, [
                    $request->input('nik'),
                    $request->input('kode_golongan'),
                    // 2
                    $request->input('kode_sdm'),
                    $request->input('kode_area'),
                    $request->input('kode_fm'),
                    // 3
                    $request->input('kode_bm'),
                    $request->input('kode_loker'),
                    $request->input('no_npwp'),
                    $request->input('no_bpjs'),

                    // 4
                    $request->input('tgl_masuk'),
                    $request->input('no_bpjs_naker'),
                    $request->input('kode_profesi'),
                    $kode_lokasi
                ]);

                $insert_bank = "INSERT INTO hr_sdm_bank(
                        nik,kode_lokasi,
                        kode_bank,cabang,no_rek,nama_rek
                    ) VALUES(
                        ?,?,
                        ?,?,?,?
                    )";
                DB::connection($this->db)->insert($insert_bank, [
                    $request->input('nik'),
                    $kode_lokasi,
                    $request->input('kode_bank'),
                    $request->input('cabang'),
                    $request->input('no_rek'),
                    $request->input('nama_rek'),
                ]);

                $insert_client = "INSERT INTO hr_sdm_client(
                    nik,nama_client,skill,
                    no_kontrak,tgl_kontrak_awal,tgl_kontrak_akhir,
                    atasan_langsung,atasan_tidak_langsung,kode_lokasi
                    ) VALUES(
                        ?,?,?,
                        ?,?,?,
                        ?,?,?
                    )";

                DB::connection($this->db)->insert($insert_client, [
                    $request->input('nik'),
                    $request->input('nama_client'),
                    $request->input('skill'),
                    $request->input('no_kontrak'),
                    $request->input('tgl_kontrak_awal'),
                    $request->input('tgl_kontrak_akhir'),
                    $request->input('atasan_langsung'),
                    $request->input('atasan_tidak_langsung'),
                    $kode_lokasi
                ]);

                if (count($request->input('kode_param')) > 0) {
                    $nik = $request->input('nik');
                    $insert_param = "INSERT INTO hr_sdm_gaji(nik,kode_param,nama_param,nilai,kode_lokasi,nu) VALUES(?,?,?,?,?,?)";
                    for ($y = 0; $y < count($request->input('kode_param')); $y++) {
                        $kode_param = $request->input('kode_param');
                        $nama_param = $request->input('nama_param');
                        $nilai = $request->input('nilai');
                        $nu_param = $request->input('nu_param');
                        DB::connection($this->db)->insert($insert_param, [
                            $nik,
                            $kode_param[$y],
                            $nama_param[$y],
                            $nilai[$y],
                            $kode_lokasi,
                            $nu_param[$y],
                        ]);
                    }
                }

                if (count($request->input('nu')) > 0) {
                    if (!empty($request->file('file'))) {
                        if (count($request->file('file')) > 0) {
                            for ($j = 0; $j < count($request->file('file')); $j++) {
                                $file = $request->file('file')[$j];
                                $nama_foto = $request->input('nik') . "_" . $file->getClientOriginalName();

                                if (Storage::disk('s3')->exists('sdm/' . $nama_foto)) {
                                    Storage::disk('s3')->delete('sdm/' . $nama_foto);
                                }
                                Storage::disk('s3')->put('sdm/' . $nama_foto, file_get_contents($file));
                            }
                        }
                    }
                    // 6 column
                    $insert_doc = "INSERT INTO hr_sdm_doc(
                        nik, nu, kode_lokasi, jenis, dokumen, sts_dokumen
                    ) VALUES (?, ?, ?, ?, ?, ?)";

                    for ($i = 0; $i < count($request->input('nu')); $i++) {
                        $nu = $request->input('nu');
                        $jenis = $request->input('jenis');
                        $fileName = $request->input('fileName');
                        $filePrevName = $request->input('filePrevName');
                        $isUpload = $request->input('isUpload');
                        $sts_dokumen = $request->input('sts_dokumen');

                        if ($isUpload[$i] == "false") {
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
            } else {
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK karyawan sudah ada di database!";
            }
            $success['kode'] = $request->nik;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data karyawan gagal disimpan " . $e;
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
            'nomor_ktp' => 'required',
            'nama' => 'required',
            'jenis_kelamin' => 'required',
            'kode_agama' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required',
            'alamat' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'kode_pos' => 'required',
            'tinggi_badan' => 'required',
            'berat_badan' => 'required',
            'golongan_darah' => 'required',
            'nomor_kk' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'kode_golongan' => 'required',
            'kode_sdm' => 'required',
            'kode_loker' => 'required',
            'tgl_masuk' => 'required',
            'no_npwp' => 'required',
            'no_bpjs' => 'required',
            'no_bpjs_naker' => 'required',
            'kode_profesi' => 'required',
            'kode_bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'skill' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak_awal' => 'required',
            'tgl_kontrak_akhir' => 'required',
            'kode_area' => 'required',
            'kode_fm' => 'required',
            'kode_bm' => 'required',
            'nama_client' => 'required',
            'atasan_langsung' => 'required',
            'atasan_tidak_langsung' => 'required',
        ]);
        // 49 field
        DB::connection($this->db)->beginTransaction();

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_sdm_pribadi')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            DB::connection($this->db)->table('hr_sdm_kepegawaian')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_bank')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_client')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_gaji')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_doc')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            $insert_kar = "INSERT INTO hr_sdm_pribadi(
                    nik,kode_lokasi,
                    nama, nomor_ktp,
                    jenis_kelamin, kode_agama,
                    no_telp, no_hp, tempat_lahir, tgl_lahir,
                    alamat, provinsi, kota, kecamatan, kelurahan, kode_pos,
                    tinggi_badan, berat_badan, golongan_darah, nomor_kk, status_nikah, tgl_nikah)
                    VALUES(
                        ?,?,
                        ?,?,
                        ?,?,
                        ?,?,?,?,
                        ?,?,?,?,?,?,
                        ?,?,?,?,?,?
                    )";

            DB::connection($this->db)->insert($insert_kar, [
                $request->input('nik'),
                $kode_lokasi,
                //2
                $request->input('nama'),
                $request->input('nomor_ktp'),
                //2
                $request->input('jenis_kelamin'),
                $request->input('kode_agama'),
                // 2
                $request->input('no_telp'),
                $request->input('no_hp'),
                $request->input('tempat_lahir'),
                $request->input('tgl_lahir'),
                // 4
                $request->input('alamat'),
                $request->input('provinsi'),
                $request->input('kota'),
                $request->input('kecamatan'),
                $request->input('kelurahan'),
                $request->input('kode_pos'),
                // 6
                $request->input('tinggi_badan'),
                $request->input('berat_badan'),
                $request->input('golongan_darah'),
                $request->input('nomor_kk'),
                $request->input('status_nikah'),
                $request->input('tgl_nikah')
                // 6
            ]);

            $insert_kepeg = "INSERT INTO hr_sdm_kepegawaian(
                        nik,kode_golongan,
                        kode_sdm,kode_area,kode_fm,
                        kode_bm,kode_loker,no_npwp,no_bpjs,
                        tgl_masuk,no_bpjs_naker,kode_profesi,kode_lokasi
                    ) VALUES(
                        ?,?,
                        ?,?,?,
                        ?,?,?,?,
                        ?,?,?,?
                    )";
            DB::connection($this->db)->insert($insert_kepeg, [
                $request->input('nik'),
                $request->input('kode_golongan'),
                // 2
                $request->input('kode_sdm'),
                $request->input('kode_area'),
                $request->input('kode_fm'),
                // 3
                $request->input('kode_bm'),
                $request->input('kode_loker'),
                $request->input('no_npwp'),
                $request->input('no_bpjs'),

                // 4
                $request->input('tgl_masuk'),
                $request->input('no_bpjs_naker'),
                $request->input('kode_profesi'),
                $kode_lokasi
            ]);

            $insert_bank = "INSERT INTO hr_sdm_bank(
                        nik,kode_lokasi,
                        kode_bank,cabang,no_rek,nama_rek
                    ) VALUES(
                        ?,?,
                        ?,?,?,?
                    )";
            DB::connection($this->db)->insert($insert_bank, [
                $request->input('nik'),
                $kode_lokasi,
                $request->input('kode_bank'),
                $request->input('cabang'),
                $request->input('no_rek'),
                $request->input('nama_rek'),
            ]);

            $insert_client = "INSERT INTO hr_sdm_client(
                    nik,nama_client,skill,
                    no_kontrak,tgl_kontrak_awal,tgl_kontrak_akhir,
                    atasan_langsung,atasan_tidak_langsung,kode_lokasi
                    ) VALUES(
                        ?,?,?,
                        ?,?,?,
                        ?,?,?
                    )";

            DB::connection($this->db)->insert($insert_client, [
                $request->input('nik'),
                $request->input('nama_client'),
                $request->input('skill'),
                $request->input('no_kontrak'),
                $request->input('tgl_kontrak_awal'),
                $request->input('tgl_kontrak_akhir'),
                $request->input('atasan_langsung'),
                $request->input('atasan_tidak_langsung'),
                $kode_lokasi
            ]);

            if (count($request->input('kode_param')) > 0) {
                $nik = $request->input('nik');
                $insert_param = "INSERT INTO hr_sdm_gaji(nik,kode_param,nama_param,nilai,kode_lokasi,nu) VALUES(?,?,?,?,?,?)";
                for ($y = 0; $y < count($request->input('kode_param')); $y++) {
                    $kode_param = $request->input('kode_param');
                    $nama_param = $request->input('nama_param');
                    $nilai = $request->input('nilai');
                    $nu_param = $request->input('nu_param');
                    DB::connection($this->db)->insert($insert_param, [
                        $nik,
                        $kode_param[$y],
                        $nama_param[$y],
                        $nilai[$y],
                        $kode_lokasi,
                        $nu_param[$y],
                    ]);
                }
            }

            if (count($request->input('nu')) > 0) {
                if (!empty($request->file('file'))) {
                    if (count($request->file('file')) > 0) {
                        for ($j = 0; $j < count($request->file('file')); $j++) {
                            $file = $request->file('file')[$j];
                            $nama_foto = $request->input('nik') . "_" . $file->getClientOriginalName();

                            if (Storage::disk('s3')->exists('sdm/' . $nama_foto)) {
                                Storage::disk('s3')->delete('sdm/' . $nama_foto);
                            }
                            Storage::disk('s3')->put('sdm/' . $nama_foto, file_get_contents($file));
                        }
                    }
                }
                // 6 column
                $insert_doc = "INSERT INTO hr_sdm_doc(
                        nik, nu, kode_lokasi, jenis, dokumen, sts_dokumen
                    ) VALUES (?, ?, ?, ?, ?, ?)";

                for ($i = 0; $i < count($request->input('nu')); $i++) {
                    $nu = $request->input('nu');
                    $jenis = $request->input('jenis');
                    $fileName = $request->input('fileName');
                    $filePrevName = $request->input('filePrevName');
                    $isUpload = $request->input('isUpload');
                    $sts_dokumen = $request->input('sts_dokumen');

                    if ($isUpload[$i] == "false") {
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
            $success['message'] = "Data karyawan gagal diubah " . $e;
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
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_sdm_pribadi')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            DB::connection($this->db)->table('hr_sdm_kepegawaian')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_bank')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_client')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_gaji')
                ->where('nik', $request->input('nik'))
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();
            DB::connection($this->db)->table('hr_sdm_doc')
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
            $success['message'] = "Data karyawan gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }

    public function getPP(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT kode_pp, nama FROM pp WHERE kode_lokasi = '" . $kode_lokasi . "' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) {
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }
}
