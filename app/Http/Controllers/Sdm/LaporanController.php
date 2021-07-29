<?php
namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller {

    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getKaryawan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_sdm', 'kode_gol', 'kode_jab', 'kode_loker', 'nik');
            $db_col_name = array('a.kode_sdm', 'a.kode_gol', 'a.kode_jab', 'a.kode_loker', 'a.nik');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sql = "SELECT a.nik,a.kode_lokasi,a.nama,a.no_telp,a.no_hp,a.email,a.alamat,b.nama AS nama_pp,c.nama AS nama_gol,
            d.nama AS nama_jab,e.nama AS nama_sdm,f.nama AS nama_loker,a.kode_pajak,a.kode_gol, 
            convert(varchar,a.tgl_masuk,103) AS tgl_masuk
            FROM hr_karyawan a 
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi  
            INNER JOIN hr_gol c ON a.kode_gol=c.kode_gol AND a.kode_lokasi=c.kode_lokasi 
            INNER JOIN hr_jab d ON a.kode_jab=d.kode_jab AND a.kode_lokasi=d.kode_lokasi 
            INNER JOIN hr_sdm e ON a.kode_sdm=e.kode_sdm AND a.kode_lokasi=e.kode_lokasi 
            INNER JOIN hr_loker f ON a.kode_loker=f.kode_loker AND a.kode_lokasi=f.kode_lokasi 
            $where
            order by a.tgl_masuk";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_rab'] = [];
                $success['data_beban'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getCVKaryawan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_sdm', 'kode_gol', 'kode_jab', 'kode_loker', 'nik');
            $db_col_name = array('a.kode_sdm', 'a.kode_gol', 'a.kode_jab', 'a.kode_loker', 'a.nik');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sql = "SELECT a.nik, a.kode_lokasi, a.nama, a.alamat, a.no_telp, a.email, a.kode_pp, a.npwp, a.bank,
            a.cabang, a.no_rek, a.nama_rek, a.grade, a.kota, a.kode_pos, a.no_hp, a.flag_aktif, a.foto, 
            g.nama AS nama_agama,h.nama AS nama_unit,i.nama AS nama_profesi,kode_pajak, b.nama AS nama_pp,
            c.nama AS nama_gol,d.nama AS nama_jab,e.nama AS nama_sdm,f.nama AS nama_loker,
			a.tempat, convert(varchar,a.tgl_lahir,103) AS tgl_lahir, a.tahun_masuk, a.npwp, a.bank, a.cabang,
            a.no_rek, a.nama_rek,
			CASE WHEN a.jk='L' THEN 'Laki-Laki' ELSE 'Perempuan' END AS jk,
			a.no_sk, convert(varchar,a.tgl_sk,103) AS tgl_sk, a.gelar_depan, a.gelar_belakang,
			convert(varchar,a.tgl_nikah,103) AS tgl_nikah, a.gol_darah, a.no_kk, a.kelurahan, a.kecamatan, a.ibu_kandung,
			a.tempat,
			CASE WHEN a.status_nikah='0' THEN 'Tidak' ELSE 'Ya' END AS status_nikah
		    from hr_karyawan a 
			inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi  
			inner join hr_gol c on a.kode_gol=c.kode_gol and a.kode_lokasi=c.kode_lokasi 
			inner join hr_jab d on a.kode_jab=d.kode_jab and a.kode_lokasi=d.kode_lokasi 
			inner join hr_sdm e on a.kode_sdm=e.kode_sdm and a.kode_lokasi=e.kode_lokasi 
			inner join hr_loker f on a.kode_loker=f.kode_loker and a.kode_lokasi=f.kode_lokasi 
			inner join hr_agama g on a.kode_agama=g.kode_agama and a.kode_lokasi=g.kode_lokasi
			inner join hr_unit h on a.kode_unit=h.kode_unit and a.kode_lokasi=h.kode_lokasi
			inner join hr_profesi i on a.kode_profesi=i.kode_profesi and a.kode_lokasi=i.kode_lokasi  
            $where
            ORDER BY a.nik";

            $cv = DB::connection($this->sql)->select($sql);
            $cv = json_decode(json_encode($cv),true);

           if(count($cv) > 0) {
                $data_keluarga = array();
                $data_dinas = array();
                $data_pendidikan = array();
                $data_pelatihan = array();
                $data_penghargaan = array();
                $data_sanksi = array();

                for($i=0;$i<count($cv);$i++) {
                    $keluarga = "SELECT nik, kode_lokasi, nu, jenis, nama, jk, tempat,
                    convert(varchar,tgl_lahir,103) as tgl_lahir, status_kes
                    from hr_keluarga 
                    where nik = '".$cv[$i]['nik']."' and kode_lokasi='$kode_lokasi'";

                    $resKeluarga = DB::connection($this->sql)->select($keluarga);
                    $resKeluarga = json_decode(json_encode($resKeluarga),true);

                    if(count($resKeluarga) > 0) {
                        array_push($data_keluarga, $resKeluarga);
                    } else {
                        array_push($data_keluarga, array());
                    }

                    $dinas = "SELECT no_sk,nama, convert(varchar,tgl_sk,103) AS tgl_sk 
                    FROM hr_sk
			        WHERE nik = '".$cv[$i]['nik']."' AND kode_lokasi='".$kode_lokasi."' ORDER BY tgl_sk DESC";

                    $resDinas = DB::connection($this->sql)->select($dinas);
                    $resDinas = json_decode(json_encode($resDinas),true);

                    if(count($resDinas) > 0) {
                        array_push($data_dinas, $resDinas);
                    } else {
                        array_push($data_dinas, array());
                    }

                    $pendidikan = "SELECT a.nama, a.tahun, a.kode_jurusan, a.kode_strata,b.nama AS nama_jur,
                    c.nama AS nama_strata
                    FROM hr_pendidikan a
                    INNER JOIN hr_jur b ON a.kode_jurusan=b.kode_jur AND a.kode_lokasi=b.kode_lokasi
                    INNER JOIN hr_strata c ON a.kode_strata=c.kode_strata AND a.kode_lokasi=c.kode_lokasi
                    where a.nik = '".$cv[$i]['nik']."' AND a.kode_lokasi='".$kode_lokasi."' ORDER BY tahun DESC";

                    $resPendidikan = DB::connection($this->sql)->select($pendidikan);
                    $resPendidikan = json_decode(json_encode($resPendidikan),true);

                    if(count($resPendidikan) > 0) {
                        array_push($data_pendidikan, $resPendidikan);
                    } else {
                        array_push($data_pendidikan, array());
                    }

                    $pelatihan = "SELECT nama, panitia, convert(varchar,tgl_mulai,103) AS tgl_mulai, 
                    convert(varchar,tgl_selesai,103) AS tgl_selesai
                    FROM hr_pelatihan
                    WHERE nik = '".$cv[$i]['nik']."' AND kode_lokasi='".$kode_lokasi."' ORDER BY tgl_mulai DESC";

                    $resPelatihan = DB::connection($this->sql)->select($pelatihan);
                    $resPelatihan = json_decode(json_encode($resPelatihan),true);

                    if(count($resPelatihan) > 0) {
                        array_push($data_pelatihan, $resPelatihan);
                    } else {
                        array_push($data_pelatihan, array());
                    }

                    $penghargaan = "SELECT nama, convert(varchar,tanggal,103) AS tanggal
                    FROM hr_penghargaan
                    WHERE nik = '".$cv[$i]['nik']."' AND kode_lokasi='".$kode_lokasi."' ORDER BY tanggal DESC";

                    $resPenghargaan = DB::connection($this->sql)->select($penghargaan);
                    $resPenghargaan = json_decode(json_encode($resPenghargaan),true);

                    if(count($resPenghargaan) > 0) {
                        array_push($data_penghargaan, $resPenghargaan);
                    } else {
                        array_push($data_penghargaan, array());
                    }

                    $sanksi = "SELECT nama, jenis, convert(varchar,tanggal,103) AS tanggal
                    FROM hr_sanksi
                    WHERE nik = '".$cv[$i]['nik']."' AND kode_lokasi='".$kode_lokasi."' ORDER BY tanggal DESC";

                    $resSanksi = DB::connection($this->sql)->select($sanksi);
                    $resSanksi = json_decode(json_encode($resSanksi),true);

                    if(count($resSanksi) > 0) {
                        array_push($data_sanksi, $resSanksi);
                    } else {
                        array_push($data_sanksi, array());
                    }

                }
            }

            if(count($cv) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $cv;
                $success['data_keluarga'] = $data_keluarga;
                $success['data_dinas'] = $data_dinas;
                $success['data_pendidikan'] = $data_pendidikan;
                $success['data_pelatihan'] = $data_pelatihan;
                $success['data_penghargaan'] = $data_penghargaan;
                $success['data_sanksi'] = $data_sanksi;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_keluarga'] = [];
                $success['data_dinas'] = [];
                $success['data_pendidikan'] = [];
                $success['data_pelatihan'] = [];
                $success['data_penghargaan'] = [];
                $success['data_sanksi'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
?>