<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MobileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    //GURU

    public function getJadwalSekarang(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_hari' => 'required',
            'kode_ta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp = $request->kode_pp;
            $kode_hari = $request->kode_hari;
            $kode_ta = $request->kode_ta;

            $res = DB::connection('sqlsrvtarbak')->select( "select a.kode_kelas,a.kode_matpel,b.jam1,b.jam2,c.nama as matpel,'-' as status 
            from sis_jadwal a 
            inner join sis_slot b on a.kode_slot=b.kode_slot and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            where a.kode_ta='$kode_ta' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.nik='$nik' and a.kode_hari='$kode_hari' 
            ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAbsenTotal(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_hari' => 'required',
            'kode_ta' => 'required',
            'jam' => 'required',
            'tanggal' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp = $request->kode_pp;
            $kode_hari = $request->kode_hari;
            $kode_ta = $request->kode_ta;
            $jam = $request->jam;
            $tanggal = $request->tanggal;

            $res = DB::connection('sqlsrvtarbak')->select( "select a.kode_kelas,isnull(b.jum,0) as jum_sis,isnull(c.jum_hadir,0) as jum_hadir,isnull(c.jum_sakit,0) as jum_sakit,isnull(c.jum_izin,0) as jum_izin,isnull(c.jum_alpa,0) as jum_alpa
            from sis_jadwal a 
            inner join sis_slot x on a.kode_slot=x.kode_slot and a.kode_lokasi=x.kode_lokasi
            left join (select kode_kelas,kode_pp,kode_lokasi,count(nis) as jum
                        from sis_siswa 
                        where flag_aktif='1'
                        group by kode_kelas,kode_pp,kode_lokasi) b on a.kode_kelas=b.kode_kelas and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join (select kode_kelas,kode_pp,kode_lokasi,kode_matpel,count(case when status='Hadir' then nis end) jum_hadir,
                        count(case when status='Sakit' then nis end) jum_sakit,
                        count(case when status='Alpa' then nis end) jum_alpa,
                        count(case when status='Izin' then nis end) jum_izin
                        from sis_presensi
                        where tanggal = '$tanggal'
                        group by kode_kelas,kode_pp,kode_lokasi,kode_matpel) c on a.kode_kelas=c.kode_kelas and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi and a.kode_matpel=c.kode_matpel
            where a.kode_ta='$kode_ta' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and a.nik='$nik' and a.kode_hari='$kode_hari' and '$jam' between x.jam1 and x.jam2
            ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDaftarSiswa(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp = $request->kode_pp;
            $kode_kelas = $request->kode_kelas;

            $res = DB::connection('sqlsrvtarbak')->select( "select a.nis,a.nama,'HADIR' as sts
            from sis_siswa a
            where a.flag_aktif='1' and a.kode_kelas = '$kode_kelas' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'             
            ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getEditAbsen(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'tanggal' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp = $request->kode_pp;
            $kode_kelas = $request->kode_kelas;
            $kode_matpel = $request->kode_matpel;
            $tanggal = $request->tanggal;

            $res = DB::connection('sqlsrvtarbak')->select( "select a.nis,a.nama,isnull(b.status,'-') as sts
            from sis_siswa a
            left join (select a.nis,kode_pp,kode_lokasi,a.status,a.kode_kelas
                        from sis_presensi a
                        where a.tanggal = '$tanggal' and a.kode_matpel='$kode_matpel'
                        ) b on a.nis=b.nis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.kode_kelas=b.kode_kelas
            where a.flag_aktif='1' and a.kode_kelas = '$kode_kelas' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'           
           ");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function insertAbsen(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_kelas' => 'required',
            'kode_ta' => 'required',
            'kode_matpel' => 'required',
            'tanggal' => 'required',
            'status_simpan' => 'required',
            'detail.*.status' => 'required',
            'detail.*.nis' => 'required'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($res =  Auth::guard('tarbak')->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_kelas = $request->kode_kelas;
            $kode_ta = $request->kode_ta;
            $kode_matpel = $request->kode_matpel;
            $tanggal = $request->tanggal;
            $status_simpan = $request->status_simpan;

            if ($status_simpan == 0) {
                $del = DB::connection('sqlsrvtarbak')->table('sis_presensi')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_kelas', $request->kode_kelas)
                ->where('kode_pp', $request->kode_pp)
                ->where('tanggal', $request->tanggal)
                ->where('kode_ta', $request->kode_ta)
                ->where('kode_matpel', $request->kode_matpel)
                ->where('jenis_absen', 'HARIAN')
                ->delete();
            }

            $data = $request->detail;	
            if(count($data) > 0){

                for ($i=0;$i < count($data);$i++){
                     
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert('insert into sis_presensi(nis,kode_kelas,kode_ta,tgl_input,status,kode_lokasi,kode_pp,keterangan,tanggal,jenis_absen,kode_matpel,nik) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$data[$i]['nis'],$kode_kelas,$kode_ta,date('Y-m-d H:i:s'),$data[$i]['status'],$kode_lokasi,$kode_pp,$tanggal,'HARIAN',$kode_matpel,$nik]);
                }	
            }

            $success['status'] = true;
            $success['message'] = "Data Presensi berhasil disimpan";
            DB::connection('sqlsrvtarbak')->commit();
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Presensi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function getJadwalGuru(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_ta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_pp = $request->kode_pp;
            $kode_ta = $request->kode_ta;

            $res = DB::connection('sqlsrvtarbak')->select( "select a.kode_slot,a.kode_kelas, a.kode_hari, a.kode_matpel,d.nama as nama_matpel, a.nik,e.nama as nama_guru,c.jam1,c.jam2 
            from sis_jadwal a
                        inner join sis_slot c on a.kode_slot=c.kode_slot and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        inner join sis_matpel d on a.kode_matpel=d.kode_matpel and a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
                        inner join karyawan e on a.nik=e.nik and a.kode_pp=e.kode_pp and a.kode_lokasi=e.kode_lokasi
                        where a.nik='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.kode_ta='$kode_ta'
                        order by a.kode_slot,a.kode_hari  ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    //SISWA
    public function getAbsen()
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select a.nis, a.nama , isnull(b.hadir,0) as hadir,isnull(b.alpa,0) as alpha,isnull(b.izin,0) as izin,isnull(b.sakit,0) as sakit 
            from sis_siswa a 
            left join (select a.nis,a.kode_lokasi,count(case when a.status ='hadir' then status end) hadir,
                       count(case when a.status ='alpa' then status end) alpa,
                       count(case when a.status ='izin' then status end) izin,
                       count(case when a.status ='sakit' then status end) sakit  
                        from sis_presensi a
                        inner join sis_ta b on a.kode_ta=b.kode_ta and a.kode_pp=b.kode_pp
                        inner join sis_kelas c on a.kode_kelas=c.kode_kelas and a.kode_pp=c.kode_pp
                        inner join sis_siswa d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
                        where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik' 
            group by a.nis,a.kode_lokasi) b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
           ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select( " select tanggal,convert(varchar(5),tgl_input,108) as jam, status from sis_presensi  where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp' and nis='$nik'
            ");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getJadwalSiswa()
    {
        try {
            
            if($cek =  Auth::guard('siswa')->user()){
                $nik= $cek->nik;
                $kode_lokasi= $cek->kode_lokasi;
                $kode_pp = $cek->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select kode_hari,nama from sis_hari where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp' ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select( "select a.kode_slot, c.nama as nama_slot,a.kode_kelas, a.kode_hari, a.kode_matpel,d.nama as nama_matpel, b.nis,a.nik,e.nama as nama_guru from sis_jadwal a
            inner join sis_siswa b on a.kode_kelas=b.kode_kelas and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_slot c on a.kode_slot=c.kode_slot and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            inner join sis_matpel d on a.kode_matpel=d.kode_matpel and a.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
            inner join karyawan e on a.nik=e.nik and a.kode_pp=e.kode_pp and a.kode_lokasi=e.kode_lokasi
            where b.nis='$nik' and b.kode_lokasi='$kode_lokasi' and b.kode_pp='$kode_pp'  order by kode_slot,kode_hari ");
            $res2 = json_decode(json_encode($res2),true);

            if(count($res2) > 0){
                $data=array();
                for($i=0;$i< count($res2);$i++){
                    $sub_array = array();
                    $tmp = explode(" ",$res2[$i]["nama_slot"]);
                    $jam1 = str_replace("[","",$tmp[2]);
                    $jam2 = str_replace("]","",$tmp[4]);
                    $sub_array =array(
                        'kode_slot'=>$res2[$i]['kode_slot'],
                        'nama_slot'=>$res2[$i]['nama_slot'],
                        'kode_kelas'=>$res2[$i]['kode_kelas'],
                        'kode_hari'=>$res2[$i]['kode_hari'],
                        'kode_matpel'=>$res2[$i]['kode_matpel'],
                        'nama_matpel'=>$res2[$i]['nama_matpel'],
                        'nis'=>$res2[$i]['nis'],
                        'nik'=>$res2[$i]['nik'],
                        'nama_guru'=>$res2[$i]['nama_guru'],
                        'jam1'=>$jam1,
                        'jam2'=>$jam2,
                        'absen'=>"-"
                    );
                    $data[] = $sub_array;
                }

            }
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $data;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = $data;
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKalender()
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select * from sis_kalender_akad a where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getEskul()
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select convert(varchar,a.tgl_mulai,103) as tgl_mulai,convert(varchar,a.tgl_selesai,103) as tgl_selesai,a.keterangan,a.predikat from sis_ekskul a
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPiutang()
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,c.nama as nama_jur,a.kode_akt,a.id_bank,x.nama as nama_pp
			from sis_siswa a
			inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
			inner join sis_jur c on b.kode_jur=c.kode_jur and 
			b.kode_lokasi=c.kode_lokasi and b.kode_pp=c.kode_pp
			inner join pp x on a.kode_pp=x.kode_pp and a.kode_lokasi=x.kode_lokasi
			where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            order by a.kode_kelas,a.nis");
            $res = json_decode(json_encode($res),true);
            
            $sql2 = "select case when sum(debet-kredit) < 0 then 0 else sum(debet-kredit) end as so_akhir
			from (select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			a.nilai as debet,0 as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D'
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' 
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C'
			)a";
            $get = DB::connection('sqlsrvtarbak')->select($sql2);
            $get = json_decode(json_encode($get),true);
            $success['saldo'] = $get[0]['so_akhir'];

            $sql3 = "select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,a.periode,
            b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
            from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,0 as bayar,x.periode 
                    from sis_bill_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param,x.periode
                    )a 
            inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
            union all 
            select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'PDD' as modul, isnull(a.tagihan,0) as tagihan,
                isnull(a.bayar,0) as bayar,a.kode_param
            from (select x.kode_lokasi,x.no_rekon,x.kode_param,x.periode,
                        sum(case when x.modul in ('BTLREKON') then x.nilai else 0 end) as tagihan,
                        sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
                    from sis_rekon_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
                    group by x.modul,x.nilai,x.kode_lokasi,x.no_rekon,x.nis,x.kode_param,x.periode
                )a 
            inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
            union all 
            select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'KB' as modul, 
                isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
            from (select x.kode_lokasi,x.no_rekon,x.kode_param,x.periode,
                        sum(case when x.modul in ('BTLREKON') then x.nilai else 0 end) as tagihan,
                        sum(case when x.modul <>'BTLREKON' then x.nilai else 0 end) as bayar
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.modul,x.nilai,x.kode_lokasi,x.no_rekon,x.nis,x.kode_param ,x.periode
                    )a
                    inner join (select tanggal,keterangan,no_kas,kode_lokasi from kas_m where kode_lokasi='$kode_lokasi' union select tanggal,keterangan,no_ju as no_kas,kode_lokasi from ju_m where kode_lokasi='$kode_lokasi') b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            ";
            $res2 = DB::connection('sqlsrvtarbak')->select($sql3);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPDD()
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $sql = "select a.nis,a.kode_lokasi,a.kode_pp,a.nama,a.kode_kelas,b.nama as nama_kelas,a.kode_lokasi,b.kode_jur,c.nama as nama_jur,a.kode_akt,a.id_bank,x.nama as nama_pp
			from sis_siswa a
			inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
			inner join sis_jur c on b.kode_jur=c.kode_jur and 
			b.kode_lokasi=c.kode_lokasi and b.kode_pp=c.kode_pp
			inner join pp x on a.kode_pp=x.kode_pp and a.kode_lokasi=x.kode_lokasi
			where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
			order by a.kode_kelas,a.nis";
            $res = DB::connection('sqlsrvtarbak')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2 = "select case when sum(debet-kredit) < 0 then 0 else sum(debet-kredit) end as so_akhir
			from (select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			a.nilai as debet,0 as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D'
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' 
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C'
			)a";
            $get = DB::connection('sqlsrvtarbak')->select($sql2);
            $get = json_decode(json_encode($get),true);
            $success['saldo'] = $get[0]['so_akhir'];

            $sql3 = "select a.no_bukti as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,'KB' as modul, 
                'PDD' as kode_param,isnull(a.masuk,0) as debet,isnull(a.keluar,0)  as kredit
            from (select x.kode_lokasi,x.no_bukti,x.kode_param,x.periode,x.modul,
                        sum(case when x.dc='D' then x.nilai else 0 end) as masuk,
                        sum(case when x.dc='C' then x.nilai else 0 end) as keluar
                    from sis_cd_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.modul,x.nilai,x.kode_lokasi,x.no_bukti,x.nis,x.kode_param ,x.periode
                )a
            inner join (select tanggal,keterangan,no_kas,kode_lokasi from kas_m where kode_lokasi='$kode_lokasi' union select tanggal,keterangan,no_ju as no_kas,kode_lokasi from ju_m where kode_lokasi='$kode_lokasi') b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            union all 
            select a.no_bukti as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,a.periode,b.keterangan,a.modul, 
                'PDD' as kode_param ,isnull(a.masuk,0) as debet,isnull(a.keluar,0)  as kredit
            from (select x.kode_lokasi,x.no_bukti,x.kode_param,x.periode,x.modul,
                        sum(case when x.dc='D' then x.nilai else 0 end) as masuk,
                        sum(case when x.dc='C' then x.nilai else 0 end) as keluar
                    from sis_cd_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.modul,x.nilai,x.kode_lokasi,x.no_bukti,x.nis,x.kode_param ,x.periode
                    )a
            inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
            order by tanggal,modul";
            $res2 = DB::connection('sqlsrvtarbak')->select($sql3);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSaldoPiutang(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            $periode = $request->periode;

            $res = DB::connection('sqlsrvtarbak')->select("select a.nis,a.nama,a.kode_lokasi,a.kode_pp,isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total,a.kode_kelas,isnull(e.total,0) as bayar
            from sis_siswa a 
            inner join sis_kelas f on a.kode_kelas=f.kode_kelas and a.kode_lokasi=f.kode_lokasi and a.kode_pp=f.kode_pp
            left join (select y.nis,y.kode_lokasi,  
                        sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi
            left join (select y.nis,y.kode_lokasi,  
                        sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )c on a.nis=c.nis and a.kode_lokasi=c.kode_lokasi
            left join (select y.nis,y.kode_lokasi,  
                        sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode')	and x.kode_pp='$kode_pp'		
                        group by y.nis,y.kode_lokasi 			
                        )d on a.nis=d.nis and a.kode_lokasi=d.kode_lokasi
            left join (select y.nis,y.kode_lokasi, 
                        sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') and x.kode_pp='$kode_pp'			
                        group by y.nis,y.kode_lokasi 			
                        )e on a.nis=e.nis and a.kode_lokasi=e.kode_lokasi
            where(a.kode_lokasi = '$kode_lokasi') and a.kode_pp='$kode_pp'	and a.nis='$nik'
            order by a.kode_kelas,a.nis");
            $res = json_decode(json_encode($res),true);
            
            $success['status'] = true;
            $success['saldo'] = $res[0]['sak_total'];
            $success['message'] = "Success!";     
           
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getSaldoPDD(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            $periode = $request->periode;

            $res = DB::connection('sqlsrvtarbak')->select("select case when sum(debet-kredit) < 0 then 0 else sum(debet-kredit) end as so_akhir
			from (select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			a.nilai as debet,0 as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='D'
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join sis_rekon_m b on a.no_bukti=b.no_rekon and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' 
			union all
			select a.no_bukti,a.nilai,convert(varchar(20),b.tanggal,103) as tgl,b.keterangan,b.modul,b.tanggal,
			0 as debet,case when a.dc='C' then a.nilai else 0 end as kredit,a.dc
			from sis_cd_d a
			inner join kas_m b on a.no_bukti=b.no_kas and a.kode_lokasi=b.kode_lokasi
			where a.nis='$nik' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.dc='C'
			
			)a");
            $res = json_decode(json_encode($res),true);
            
            $success['status'] = true;
            $success['saldo'] = $res[0]['so_akhir'];
            $success['message'] = "Success!";     
           
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRiwayat(Request $request)
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select( "select  top 10 a.* from (
                select a.no_bill as no_bukti,a.kode_lokasi,b.tanggal,convert(varchar(10),b.tanggal,103) as tgl,b.periode,
                b.keterangan,'BILL' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
                from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan,
                        0 as bayar from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                        group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
                inner join sis_bill_m b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,b.periode,b.keterangan,'PDD' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param
                from (select x.kode_lokasi,x.no_rekon,x.kode_param,
                    case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0
                    )a 
                inner join sis_rekon_m b on a.no_rekon=b.no_rekon and a.kode_lokasi=b.kode_lokasi 
                union all 
                select a.no_rekon as no_bukti,a.kode_lokasi,b.tanggal,
                convert(varchar(10),b.tanggal,103) as tgl,b.periode,b.keterangan,'KB' as modul, isnull(a.tagihan,0) as tagihan,isnull(a.bayar,0) as bayar,a.kode_param 
                from (select x.kode_lokasi,x.no_rekon,x.kode_param,
                    case when x.modul in ('BTLREKON') then x.nilai else 0 end as tagihan,case when x.modul <>'BTLREKON' then x.nilai else 0 end as bayar
                    from sis_rekon_d x inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                )a
                inner join kas_m b on a.no_rekon=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            ) a
            order by a.tanggal desc");
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function getDetailPiu(Request $request)
    {
        $this->validate($request, [
            'tahun' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            $tahun = $request->tahun;
            $sql = "select distinct a.periode from sis_bill_d a where(a.kode_lokasi = '$kode_lokasi')and a.kode_pp='$kode_pp' and a.nis='$nik' and a.periode  LIKE '$tahun%' ";
            $res = DB::connection('sqlsrvtarbak')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select a.no_bill,a.periode,a.tanggal,isnull(b.jum,0) as jum_param
            from sis_bill_m a 
             left join (select a.no_bill,a.kode_lokasi,a.kode_pp,a.periode,a.nis,count(a.kode_param) as jum
                       from sis_bill_d a
                        where(a.kode_lokasi = '$kode_lokasi')and a.kode_pp='$kode_pp' and a.periode  LIKE '$tahun%' 
                        group by a.no_bill,a.kode_lokasi,a.kode_pp,a.periode,a.nis
             ) b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
             where(a.kode_lokasi = '$kode_lokasi')and a.kode_pp='$kode_pp' and a.periode  LIKE '$tahun%'  and b.nis ='$nik'";
            $res2 = DB::connection('sqlsrvtarbak')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 = "select a.nis, a.nama, b.kode_param,isnull(b.total,0) as bill, isnull(c.total,0) as bayar , isnull(b.total,0)-isnull(c.total,0) as saldo,b.periode,b.tanggal,b.no_bill   
            from sis_siswa a             
            left join (select x.no_bill,x.periode,z.tanggal,x.kode_param,x.nis,x.kode_lokasi,sum(case when x.dc='D' then x.nilai else -x.nilai end) as total                         
                       from sis_bill_d x    
					   inner join sis_bill_m z on x.no_bill=z.no_bill and x.kode_lokasi=z.kode_lokasi and x.kode_pp=z.kode_pp                   
                       inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp               
                       where(x.kode_lokasi = '$kode_lokasi')and x.kode_pp='$kode_pp'              
                       group by x.no_bill,x.periode,z.tanggal,x.kode_param,x.kode_lokasi,x.nis ) b on a.kode_lokasi=b.kode_lokasi and a.nis=b.nis            
            left join (select x.no_rekon,x.periode_bill,x.kode_param,x.nis,x.kode_lokasi, 
                        sum(case when x.dc='D' then x.nilai else -x.nilai end) as total                       
                        from sis_rekon_d x                       
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp                       
                        where(x.kode_lokasi = '$kode_lokasi')and x.kode_pp='$kode_pp'                        
                        group by x.no_rekon,x.periode_bill,x.kode_param,x.nis,x.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi and a.nis=c.nis 
                        and b.periode=c.periode_bill and b.kode_param=c.kode_param            
            where(a.kode_lokasi = '$kode_lokasi')and a.kode_pp='$kode_pp' and a.nis='$nik' and b.periode  LIKE '$tahun%'            
            order by periode desc";
            $res3 = DB::connection('sqlsrvtarbak')->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['data_detail2'] = $res3;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_detail2'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNilai(Request $request)
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel, b.nilai,c.nama as nama_matpel 
            from sis_nilai_m a
            inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and b.nis='$nik' and a.kode_jenis='ASS'
           ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel, b.nilai,c.nama as nama_matpel 
            from sis_nilai_m a
            inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and b.nis='$nik' and a.kode_jenis='KUI'
            ");
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection('sqlsrvtarbak')->select("select a.kode_ta,a.kode_kelas,a.kode_jenis,a.kode_matpel, b.nilai,c.nama as nama_matpel 
            from sis_nilai_m a
            inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and b.nis='$nik' and a.kode_jenis='NIA'
            ");
            $res3 = json_decode(json_encode($res3),true);
            
            $success['status'] = true;
            $success['ASS'] = $res;
            $success['KUI'] = $res2;
            $success['NIA'] = $res3;
            $success['message'] = "Success!";     
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPrestasi(Request $request)
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select(" select a.no_bukti, convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.tempat,a.jenis from sis_prestasi a
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            ");
            $res = json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }    
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRaport(Request $request)
    {
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $request->kode_pp;
            }

            $res = DB::connection('sqlsrvtarbak')->select("select b.kode_matpel,c.nama, isnull(b.nilai,0) as nilai,isnull(c.kkm,0) as kkm from sis_raport_m a
            inner join sis_raport_d b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on b.kode_matpel=c.kode_matpel and b.kode_lokasi=c.kode_lokasi and b.kode_pp=c.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.nis='$nik'
            ");
            $res = json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }    
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getMatpel(Request $request)
    {
        $this->validate($request,[
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }
            
            $filter = "";
            if(isset($request->kode_matpel)){
                $filter .= "and a.kode_matpel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_matpel,b.nama 
            from sis_guru_matpel_kelas a 
            inner join sis_matpel b on a.kode_matpel=b.kode_matpel and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='$kode_pp' and a.kode_kelas='$request->kode_kelas' $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDetMatpel(Request $request)
    {
        $this->validate($request,[
            'kode_matpel' => 'required',
            'kode_sem' => 'required|in:1,2,All',
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('siswa')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }

            if(isset($request->kode_sem)){
                if($request->kode_sem != "All"){
                    $filter = " and a.kode_sem ='$request->kode_sem' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $res3 = DB::connection('sqlsrvtarbak')->select("
            select kode_ta,nama from sis_ta where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' and flag_aktif='1' ");
            $res3 = json_decode(json_encode($res3),true);

            $kode_ta = $res3[0]['kode_ta'];
            
            $res2 = DB::connection('sqlsrvtarbak')->select("select a.nik,a.kode_matpel,b.nama as nama_guru,c.nama as nama_matpel 
            from sis_guru_matpel_kelas a
            inner join sis_guru b on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            inner join sis_matpel c on a.kode_matpel=c.kode_matpel and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_matpel='$request->kode_matpel' and a.kode_kelas='$request->kode_kelas' and a.kode_ta='$kode_ta' ");
            $res2 = json_decode(json_encode($res2),true);

            $sql = "select a.kode_kd,a.nama_kd,a.tgl_input,a.no_bukti,c.nilai,a.pelaksanaan,'-' as periode,'-' as minggu,isnull(d.file_dok,'-') as file_dok
            from sis_nilai_m a 
            inner join sis_nilai c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            left join sis_nilai_dok d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp and c.nis=d.nis
            where a.kode_pp='$kode_pp' and c.nis='$nik' and a.kode_lokasi='".$kode_lokasi."'  and a.kode_matpel='$request->kode_matpel' and a.kode_ta='$kode_ta' $filter ";
            // $success['sql'] = $sql;
            $res = DB::connection('sqlsrvtarbak')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data_ta'] = $res3;
                $success['data_guru'] = $res2;
                $success['data_kompetensi'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data_ta'] = [];
                $success['data_guru'] = [];
                $success['data_kompetensi'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function updateStatusReadMobile(Request $request)
	{
		if($auth =  Auth::guard('siswa')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $kode_pp = $request->kode_pp;
		}

		$this->validate($request,[
            'id' => 'required|max:300',
            'id_device' => 'required'
		]);

		DB::connection('sqlsrvtarbak')->beginTransaction();
        try{
            
			$upd = DB::connection('sqlsrvtarbak')->insert("update sis_pesan_d set sts_read_mob = '1' where no_bukti='$request->id' and id_device='$request->id_device' and kode_lokasi='$kode_lokasi' ");

			DB::connection('sqlsrvtarbak')->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }
    
    public function getInfo(Request $request){

		if($auth =  Auth::guard('siswa')->user()){
			$nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
            $kode_pp = $auth->kode_pp;
		}
		
        try{

            $get = DB::connection('sqlsrvtarbak')->select("select kode_kelas from sis_siswa where nis='$nik' ");
            if(count($get) > 0){
                $kode_kelas = $get[0]->kode_kelas;
            }else{
                $kode_kelas = "-";
            }
            
			$sql = "select a.nik_user as nik,c.nama,isnull(c.foto,'-') as foto,a.judul,convert(varchar,a.tgl_input,103) as tanggal,a.no_bukti,d.file_dok
            from sis_pesan_m a
            inner join (select nik_user,kode_lokasi,kode_pp,max(tgl_input) as tgl_input
                        from sis_pesan_m
                        group by  nik_user,kode_lokasi,kode_pp) b on a.nik_user=b.nik_user and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.tgl_input=b.tgl_input
            inner join sis_guru c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            inner join sis_pesan_dok d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp and d.no_urut=0
            where a.tipe='info' and a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and (a.nis='$nik' or a.kode_kelas='$kode_kelas')
			";
			$res = DB::connection('sqlsrvtarbak')->select($sql);
			$res = json_decode(json_encode($res),true);
			if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
            }
            $success['status'] = true;
            $success['message'] = "Sukses ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }
    
    public function getDetailInfo(Request $request){
        $this->validate($request,[
            'nik_guru' => 'required'
        ]);

		if($auth =  Auth::guard('siswa')->user()){
			$nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
            $kode_pp = $auth->kode_pp;
		}
		
        try{
            
			$sql = "select a.judul,a.pesan,a.ref1,a.ref2,a.ref3,a.link,isnull(c.file_dok,'-') as file_dok,dbo.fnNamaTanggal(a.tgl_input) as tanggal
            from sis_pesan_m a
            inner join sis_pesan_d b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join sis_pesan_dok c on a.no_bukti=c.no_bukti and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp and c.no_urut=0
            where b.nis='$nik' and a.nik_user='$request->nik_guru'
            order by a.tgl_input desc
			";
			$res = DB::connection('sqlsrvtarbak')->select($sql);
			$res = json_decode(json_encode($res),true);
			if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
            }
            $success['status'] = true;
            $success['message'] = "Sukses ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}


}
