<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KepegawaianController extends Controller
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

            $sql = "SELECT a.nik, a.nama, a.alamat, a.no_telp, a.email, a.npwp, a.bank, a.cabang, a.no_rek, a.nama_rek,
            a.grade, a.kota, a.kode_pos, a.no_hp, a.flag_aktif, a.foto, a.nip, a.jk, a.tempat, a.tgl_lahir, a.tahun_masuk,
            a.gelar_depan, a.gelar_belakang, a.ibu_kandung, a.status_nikah, a.tgl_nikah, a.gol_darah, a.kelurahan, a.kecamatan,
            a.no_kk, a.no_sk, a.tgl_sk, a.tgl_masuk, a.no_bpjs, a.no_ktp, a.ijht, a.bpjs, a.jp, a.no_kontrak, a.tgl_kontrak,
            a.mk_gol, a.mk_ytb, a.kode_pp, b.nama AS nama_pp, a.kode_sdm, c.nama AS nama_sdm, a.kode_gol, d.nama as nama_golongan,
            a.kode_jab, e.nama AS nama_jabatan, a.kode_loker, f.nama AS nama_loker, a.kode_pajak, g.nama AS nama_pajak,
            a.kode_unit, h.nama AS nama_unit, a.kode_profesi, i.nama AS nama_profesi, a.kode_agama, j.nama AS nama_agama,
            a.kode_strata, k.nama AS nama_strata  
            FROM hr_karyawan a
            LEFT JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_sdm c ON a.kode_sdm=c.kode_sdm AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN hr_gol d ON a.kode_gol=d.kode_gol AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_jab e ON a.kode_jab=e.kode_jab AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_loker f ON a.kode_loker=f.kode_loker AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_pajak g ON a.kode_pajak=g.kode_pajak AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_unit h ON a.kode_unit=h.kode_unit AND a.kode_lokasi=h.kode_lokasi
            LEFT JOIN hr_profesi i ON a.kode_profesi=i.kode_profesi AND a.kode_lokasi=i.kode_lokasi
            LEFT JOIN hr_agama j ON a.kode_agama=j.kode_agama AND a.kode_lokasi=j.kode_lokasi
            LEFT JOIN hr_strata k ON a.kode_strata=k.kode_strata AND a.kode_lokasi=k.kode_lokasi
            WHERE a.nik = '".$request->nik."' AND a.kode_lokasi = '".$kode_lokasi."'";
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
            'nama' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'kode_pp' => 'required',
            'npwp' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'grade' => 'required',
            'kota' => 'required',
            'kode_pos' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'kode_sdm' => 'required',
            'kode_gol' => 'required',
            'kode_jab' => 'required',
            'kode_loker' => 'required',
            'kode_pajak' => 'required',
            'nip' => 'required',
            'kode_unit' => 'required',
            'kode_profesi' => 'required',
            'jk' => 'required',
            'kode_agama' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'tahun_masuk' => 'required',
            'gelar_depan' => 'required',
            'gelar_belakang' => 'required',
            'ibu_kandung' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'gol_darah' => 'required',
            'kelurahan' => 'required',
            'kecamatan' => 'required',
            'no_kk' => 'required',
            'no_sk' => 'required',
            'tgl_sk' => 'required',
            'tgl_masuk' => 'required',
            'no_bpjs' => 'required',
            'no_ktp' => 'required',
            'kode_strata' => 'required',
            'ijht' => 'required',
            'bpjs' => 'required',
            'jp' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak' => 'required',
            'mk_gol' => 'required',
            'mk_ytb' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->input('nik'), $kode_lokasi)) {
                $foto = NULL;
                if($request->hasFile('file')) {
                    $file = $request->file('file');
                    $nama_foto = "_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                        Storage::disk('s3')->delete('sdm/'.$nama_foto);
                    }
                     Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
                }

                $insert = "INSERT INTO hr_karyawan(nik, kode_lokasi, nama, alamat, no_telp, email, kode_pp, npwp, bank,
                cabang, no_rek, nama_rek, kota, kode_pos, no_hp, flag_aktif, foto, kode_sdm, kode_gol, kode_jab, kode_loker,
                kode_pajak, nip, kode_unit, kode_profesi, jk, kode_agama, tempat, tgl_lahir, tahun_masuk, gelar_depan, 
                gelar_belakang, ibu_kandung, status_nikah, tgl_nikah, gol_darah, kelurahan, kecamatan, no_kk, no_sk, tgl_sk,
                tgl_masuk, no_bpjs, no_ktp, kode_strata, ijht, bpjs, jp, no_kontrak, tgl_kontrak, mk_gol, mk_ytb, grade) 
                VALUES ('".$request->input('nik')."', '".$kode_lokasi."', 
                '".$request->input('nama')."', '".$request->input('alamat')."', '".$request->input('no_telp')."', 
                '".$request->input('email')."', '".$request->input('kode_pp')."', '".$request->input('npwp')."',
                '".$request->input('bank')."', '".$request->input('cabang')."', '".$request->input('no_rek')."',
                '".$request->input('nama_rek')."', '".$request->input('kota')."', '".$request->input('kode_pos')."',
                '".$request->input('no_hp')."', '".$request->input('flag_aktif')."', '".$foto."', 
                '".$request->input('kode_sdm')."', '".$request->input('kode_gol')."', '".$request->input('kode_jab')."',
                '".$request->input('kode_loker')."', '".$request->input('kode_pajak')."', '".$request->input('nip')."',
                '".$request->input('kode_unit')."', '".$request->input('kode_profesi')."', '".$request->input('jk')."',
                '".$request->input('kode_agama')."', '".$request->input('tempat')."', '".$request->input('tgl_lahir')."',
                '".$request->input('tahun_masuk')."', '".$request->input('gelar_depan')."', '".$request->input('gelar_belakang')."',
                '".$request->input('ibu_kandung')."', '".$request->input('status_nikah')."', '".$request->input('tgl_nikah')."',
                '".$request->input('gol_darah')."', '".$request->input('kelurahan')."', '".$request->input('kecamatan')."',
                '".$request->input('no_kk')."', '".$request->input('no_sk')."', '".$request->input('tgl_sk')."', 
                '".$request->input('tgl_masuk')."', '".$request->input('no_bpjs')."', '".$request->input('no_ktp')."',
                '".$request->input('kode_strata')."', '".$request->input('ijht')."', '".$request->input('bpjs')."',
                '".$request->input('jp')."', '".$request->input('no_kontrak')."', '".$request->input('tgl_kontrak')."',
                '".$request->input('mk_gol')."', '".$request->input('mk_ytb')."', '".$request->input('grade')."')";

                DB::connection($this->db)->insert($insert);
                
                $success['status'] = true;
                $success['message'] = "Data karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK karyawan sudah ada di database!";
            }
            $success['kode'] = $request->nik;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
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
            'nama' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'kode_pp' => 'required',
            'npwp' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'grade' => 'required',
            'kota' => 'required',
            'kode_pos' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'kode_sdm' => 'required',
            'kode_gol' => 'required',
            'kode_jab' => 'required',
            'kode_loker' => 'required',
            'kode_pajak' => 'required',
            'nip' => 'required',
            'kode_unit' => 'required',
            'kode_profesi' => 'required',
            'jk' => 'required',
            'kode_agama' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'tahun_masuk' => 'required',
            'gelar_depan' => 'required',
            'gelar_belakang' => 'required',
            'ibu_kandung' => 'required',
            'status_nikah' => 'required',
            'tgl_nikah' => 'required',
            'gol_darah' => 'required',
            'kelurahan' => 'required',
            'kecamatan' => 'required',
            'no_kk' => 'required',
            'no_sk' => 'required',
            'tgl_sk' => 'required',
            'tgl_masuk' => 'required',
            'no_bpjs' => 'required',
            'no_ktp' => 'required',
            'kode_strata' => 'required',
            'ijht' => 'required',
            'bpjs' => 'required',
            'jp' => 'required',
            'no_kontrak' => 'required',
            'tgl_kontrak' => 'required',
            'mk_gol' => 'required',
            'mk_ytb' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $foto = $request->input('prevFoto');
            if($request->hasFile('file')) {
                $file = $request->file('file');
                $nama_foto = "_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                    Storage::disk('s3')->delete('sdm/'.$nama_foto);
                }
                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
            }

            $update = "UPDATE hr_karyawan SET nama = '".$request->input('nama')."', alamat = '".$request->input('alamat')."', 
            no_telp = '".$request->input('no_telp')."', email = '".$request->input('email')."', kode_pp = '".$request->input('kode_pp')."',
            npwp = '".$request->input('npwp')."', bank = '".$request->input('bank')."', cabang = '".$request->input('cabang')."',
            no_rek = '".$request->input('no_rek')."', nama_rek = '".$request->input('nama_rek')."', kota = '".$request->input('kota')."',
            kode_pos = '".$request->input('kode_pos')."', no_hp = '".$request->input('no_hp')."', flag_aktif = '".$request->input('flag_aktif')."',
            foto = '".$foto."', kode_sdm = '".$request->input('kode_sdm')."', kode_gol = '".$request->input('kode_gol')."',
            kode_jab = '".$request->input('kode_jab')."', kode_loker = '".$request->input('kode_loker')."',
            kode_pajak = '".$request->input('kode_pajak')."', nip = '".$request->input('nip')."', kode_unit = '".$request->input('kode_unit')."',
            kode_profesi = '".$request->input('kode_profesi')."', jk = '".$request->input('jk')."', kode_agama = '".$request->input('kode_agama')."',
            tempat = '".$request->input('tempat')."', tgl_lahir = '".$request->input('tgl_lahir')."', tahun_masuk = '".$request->input('tahun_masuk')."',
            gelar_depan = '".$request->input('gelar_depan')."', gelar_belakang = '".$request->input('gelar_belakang')."', 
            ibu_kandung = '".$request->input('ibu_kandung')."', status_nikah = '".$request->input('status_nikah')."',
            tgl_nikah = '".$request->input('tgl_nikah')."', gol_darah = '".$request->input('gol_darah')."', kelurahan = '".$request->input('kelurahan')."',
            kecamatan = '".$request->input('kecamatan')."', no_kk = '".$request->input('no_kk')."', no_sk = '".$request->input('no_sk')."',
            tgl_sk = '".$request->input('tgl_sk')."', tgl_masuk = '".$request->input('tgl_masuk')."', no_bpjs = '".$request->input('no_bpjs')."',
            no_ktp = '".$request->input('no_ktp')."', kode_strata = '".$request->input('kode_strata')."', ijht = '".$request->input('ijht')."',
            bpjs = '".$request->input('bpjs')."', jp = '".$request->input('jp')."', no_kontrak = '".$request->input('no_kontrak')."',
            tgl_kontrak = '".$request->input('tgl_kontrak')."', mk_gol = '".$request->input('mk_gol')."', mk_ytb = '".$request->input('mk_ytb')."',
            grade = '".$request->input('grade')."' WHERE nik = '".$request->input('nik')."' AND kode_lokasi = '".$kode_lokasi."'";
            
            DB::connection($this->db)->update($update);
            
            $success['status'] = true;
            $success['message'] = "Data karyawan berhasil diubah";
            $success['kode'] = $request->nik;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
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
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->table('hr_karyawan')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPP(Request $request)
    {
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
