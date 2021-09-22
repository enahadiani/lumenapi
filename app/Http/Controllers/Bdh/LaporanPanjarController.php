<?php
namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class LaporanPanjarController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    private function convertBilangan($nilai) {
		$nilai = abs($nilai);
		$huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = $this->convertBilangan($nilai - 10). " Belas";
		} else if ($nilai < 100) {
			$temp = $this->convertBilangan($nilai/10)." Puluh". $this->convertBilangan($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " seratus" . $this->convertBilangan($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = $this->convertBilangan($nilai/100) . " Ratus" . $this->convertBilangan($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " seribu" . $this->convertBilangan($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = $this->convertBilangan($nilai/1000) . " Ribu" . $this->convertBilangan($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->convertBilangan($nilai/1000000) . " Juta" . $this->convertBilangan($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000) . " Milyar" . $this->convertBilangan(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000000) . " Trilyun" . $this->convertBilangan(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    
    private function bilanganAngka($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim($this->convertBilangan($nilai));
		} else {
			$hasil = trim($this->convertBilangan($nilai));
		}     		
		return $hasil." "."Rupiah";
	}

    public function DataSaldoPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_kas');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_pj, a.no_kas, CONVERT(varchar,a.tanggal,103) AS tgl,a.keterangan,a.nilai,
            a.kode_pp, c.nama AS nama_pp, a.nik_pengaju, d.nama,
            b.no_ptg, b.no_kas AS kas_ptg, ISNULL(b.nilai,0) + ISNULL(b.nilai_kas,0) AS nilai_ptg
            FROM panjar_m a
            LEFT JOIN ptg_m b ON a.no_pj=b.no_pj AND a.kode_lokasi=b.kode_lokasi
            INNER JOIN pp c ON a.kode_pp=c.kode_pp AND a.kode_lokasi=c.kode_lokasi
            INNER JOIN karyawan d ON a.nik_pengaju=d.nik AND a.kode_lokasi=d.kode_lokasi
            $where
            ORDER BY a.no_pj";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);
            
            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
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

    public function DataPosisiTanggungPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_pb');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, a.posted, a.nilai, 
            d.no_dokumen AS no_dpc,
            CASE a.progress WHEN '0' THEN 'Pengajuan PB' 
            WHEN 'D' THEN 'Ver Dok'
            WHEN '1' THEN 'Ver Akun'
            WHEN 'C' THEN 'Return Ver'
            WHEN '2' THEN 'SPB'
            WHEN '3' THEN 'Dibayar'
            END AS progress, a.kode_pp,b.nama AS nama_pp,
            a.no_ver, CONVERT(varchar,c.tanggal,103) AS tgl_ver,
            a.no_verdok, CONVERT(varchar,e.tanggal,103) AS tgl_verdok,
            f.no_kas, CONVERT(varchar,g.tanggal,103) AS tgl_kas,
            a.no_spb, CONVERT(varchar,d.tanggal,103) AS tgl_spb,
            a.no_fisik, CONVERT(varchar,h.tanggal,103) AS tgl_fisik,
            a.no_pajak, CONVERT(varchar,i.tanggal,103) AS tgl_pajak
            FROM pbh_pb_m a 
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN pbh_ver_m c ON a.no_ver=c.no_ver AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN spb_m d ON a.no_spb=d.no_spb AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN pbh_ver_m e ON a.no_verdok=e.no_ver AND a.kode_lokasi=e.kode_lokasi
            INNER JOIN ptg_m f on a.no_pb=f.no_ptg and a.kode_lokasi=f.kode_lokasi
            LEFT JOIN kas_m g ON f.no_kas=g.no_kas AND f.kode_lokasi=g.kode_lokasi
            LEFT JOIN pbh_ver_m h ON a.no_fisik=h.no_ver AND a.kode_lokasi=h.kode_lokasi
            LEFT JOIN pbh_ver_m i ON a.no_pajak=i.no_ver AND a.kode_lokasi=i.kode_lokasi
            $where AND a.modul='PJPTG'
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);
            
            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
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

    public function DataTanggungPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_pb');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, a.nilai, a.nik_user, 
            a.tanggal, h.logo, h.alamat, h.kota, a.nik_user, a.nik_app, f.nama AS nama_user, g.nama AS nama_app, 
            SUBSTRING(a.periode,1,4) AS tahun, b.no_pj, c.nilai AS nilai_pj, c.nilai-a.nilai AS sisa
            FROM pbh_pb_m a 
            LEFT JOIN karyawan f ON a.nik_user=f.nik AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan g ON a.nik_app=g.nik AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN lokasi h ON a.kode_lokasi=h.kode_lokasi
            LEFT JOIN ptg_m b ON a.no_pb=b.no_ptg AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN panjar_m c ON b.no_pj=c.no_pj AND b.kode_lokasi=c.kode_lokasi
            $where AND a.modul='PJPTG'
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0) { 
                $bilanganAngka = array();
                $no_pb = "";
                $i=0;
                foreach($res1 as $row) { 
                    array_push($bilanganAngka, $this->bilanganAngka(floatval($row['nilai'])));
                    if($i == 0) {
                        $no_pb = "'".$row['no_pb']."'";
                    } else {
                        $no_pb .= ", '".$row['no_pb']."'";
                    }
                    $i++;
                }

                $select2 = "SELECT no_bukti, no_rek, nama_rek, bank, 
                nilai + ISNULL(pajak,0) AS nilai, ISNULL(pajak,0) AS pajak, nilai AS netto 
                FROM pbh_rek
                WHERE no_bukti IN ($no_pb) AND kode_lokasi='".$kode_lokasi."' 
                ORDER BY no_rek";

                $res2 = DB::connection($this->db)->select($select2);
                $res2 = json_decode(json_encode($res2),true);

                $select3 = "SELECT a.no_ptg, a.kode_akun, b.nama, a.keterangan, a.kode_pp, a.kode_drk, a.nilai  
                FROM ptg_j a
                INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
                WHERE a.no_ptg IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."' AND a.dc='D'
                UNION ALL
                SELECT a.no_ptg, a.kode_akun, b.nama, a.keterangan, a.kode_pp, a.kode_drk, a.nilai*-1 AS nilai  
                FROM ptg_j a
                INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
                WHERE a.no_ptg IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."' AND a.dc='C' AND a.jenis='PAJAK'
                ORDER BY a.kode_akun";

                $res3 = DB::connection($this->db)->select($select3);
                $res3 = json_decode(json_encode($res3),true);

                $select4 = "SELECT a.no_ptg, a.kode_akun, b.nama, SUM(a.nilai) AS nilai  
                FROM ptg_j a
                INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
                WHERE a.no_ptg IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."' AND a.dc='D'
                GROUP BY a.kode_akun, b.nama, a.no_ptg
                UNION ALL
                SELECT a.no_ptg, a.kode_akun, b.nama, SUM(a.nilai)*-1 AS nilai  
                FROM ptg_j a
                INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
                WHERE a.no_ptg IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."' AND a.dc='C' AND a.jenis='PAJAK'
                GROUP BY a.kode_akun, b.nama, a.no_ptg";

                $res4 = DB::connection($this->db)->select($select4);
                $res4 = json_decode(json_encode($res4),true);
                
            }
            
            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_rek'] = $res2;
                $success['data_detail'] = $res3;
                $success['data_lain'] = $res4;
                $success['bilangan_angka'] = $bilanganAngka;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_akun'] = [];
                $success['data_lain'] = [];
                $success['bilangan_angka'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function DataPosisiAjuPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_pb');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) AS tgl, a.keterangan, a.posted, a.nilai, 
            d.no_dokumen AS no_dpc, ISNULL(j.jum_dok,0) AS jum_dok, 
            CASE a.progress WHEN '0' THEN 'Pengajuan PB'
            WHEN 'D' THEN 'Ver Dok'
            WHEN '1' THEN 'Ver Akun'
            WHEN 'C' THEN 'Return Ver Dok'
            WHEN 'V' THEN 'Return Ver Akun'
            WHEN '2' THEN 'SPB'
            WHEN '3' THEN 'Dibayar'
            END AS progress, a.kode_pp,b.nama AS nama_pp, a.no_ver, CONVERT(varchar,c.tanggal,103) AS tgl_ver, 
            a.no_verdok, CONVERT(varchar,e.tanggal,103) AS tgl_verdok, 
            a.no_kas, CONVERT(varchar,g.tanggal,103) AS tgl_kas, a.no_spb, CONVERT(varchar,d.tanggal,103) AS tgl_spb, 
            a.no_fisik, CONVERT(varchar,h.tanggal,103) AS tgl_fisik, 
            a.no_pajak, CONVERT(varchar,i.tanggal,103) AS tgl_pajak
            FROM pbh_pb_m a 
            INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN pbh_ver_m c ON a.no_ver=c.no_ver AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN spb_m d ON a.no_spb=d.no_spb AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN pbh_ver_m e ON a.no_verdok=e.no_ver AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN kas_m g ON a.no_kas=g.no_kas AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN pbh_ver_m h ON a.no_fisik=h.no_ver AND a.kode_lokasi=h.kode_lokasi
            LEFT JOIN pbh_ver_m i ON a.no_pajak=i.no_ver AND a.kode_lokasi=i.kode_lokasi
            LEFT JOIN (
                SELECT b.no_bukti, b.kode_lokasi, COUNT(b.no_gambar) AS jum_dok 
                FROM pbh_pb_m a
                INNER JOIN pbh_dok b ON a.no_pb=b.no_bukti AND a.kode_lokasi=b.kode_lokasi
                $where AND a.modul='PJPTG'
                GROUP BY b.no_bukti, b.kode_lokasi
            ) j ON a.no_pb=j.no_bukti AND a.kode_lokasi=j.kode_lokasi
            $where AND a.modul='PJPTG'
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
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

    public function DataPencairanPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_kas');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_kas, a.no_dokumen, a.periode, a.tanggal, CONVERT(varchar,a.tanggal,103) as tanggal1, 
            a.keterangan, a.nik_buat, b.nama as nama_buat, b.jabatan as jabatan_buat, a.nik_app,c.nama as nama_setuju, 
            c.jabatan as jabatan_setuju, d.kota, a.nilai, d.logo, d.alamat
            FROM kas_m a 
            INNER JOIN lokasi d ON a.kode_lokasi=d.kode_lokasi
            LEFT JOIN karyawan b ON a.nik_buat=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan c ON a.nik_app=c.nik AND a.kode_lokasi=c.kode_lokasi
            $where AND a.modul='KBPJCAIR'";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0) { 
                $bilanganAngka = array();
                $no_kas = "";
                $i=0;
                foreach($res1 as $row) { 
                    array_push($bilanganAngka, $this->bilanganAngka(floatval($row['nilai']))); 
                    if($i == 0) {
                        $no_kas = "'".$row['no_kas']."'";
                    } else {
                        $no_kas .= ", '".$row['no_kas']."'";
                    }
                    $i++;
                }

                $select2 = "SELECT a.no_kas, a.kode_akun, b.nama, a.keterangan, a.kode_pp, a.kode_drk, a.kode_cf, 
                CASE dc WHEN 'D' THEN nilai ELSE 0 END AS debet,
                CASE dc WHEN 'C' THEN nilai ELSE 0 END AS kredit
                FROM kas_j a
                INNER JOIN masakun b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
                WHERE a.no_kas IN ($no_kas) AND a.kode_lokasi='".$kode_lokasi."'
                ORDER BY a.no_urut";

                $res2 = DB::connection($this->db)->select($select2);
                $res2 = json_decode(json_encode($res2),true);
            }

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $res2;
                $success['bilangan_angka'] = $bilanganAngka;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['bilangan_angka'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function DataPanjar(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_pb');
            $where = "where a.kode_lokasi='".$kode_lokasi."'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($r->input($col_array[$i])[0])){
                    if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                    }else if($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                    }else if($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                        $tmp = explode(",",$r->input($col_array[$i])[1]);
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

            $select1 = "SELECT a.no_pb, CONVERT(varchar,a.tanggal,103) as tgl, a.keterangan, a.nilai, a.nik_user, a.tanggal, 
            h.logo, h.alamat, h.kota, a.nik_user, a.nik_app, f.nama as nama_user, g.nama as nama_app, 
            SUBSTRING(a.periode,1,4) as tahun
            FROM pbh_pb_m a 
            INNER JOIN panjar_m b ON a.no_pb=b.no_pj AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN karyawan f ON a.nik_user=f.nik AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN karyawan g ON a.nik_app=g.nik AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN lokasi h ON a.kode_lokasi=h.kode_lokasi
            $where
            ORDER BY a.no_pb";

            $res1 = DB::connection($this->db)->select($select1);
            $res1 = json_decode(json_encode($res1),true);

            if(count($res1) > 0) {
                $bilanganAngka = array(); 
                $no_pb = "";
                $tahun = "";
                $i=0;
                foreach($res1 as $row) { 
                    array_push($bilanganAngka, $this->bilanganAngka(floatval($row['nilai']))); 
                    if($i == 0) {
                        $no_pb = "'".$row['no_pb']."'";
                        $tahun = "'".$row['tahun']."'";
                    } else {
                        $no_pb .= ", '".$row['no_pb']."'";
                        $tahun .= ", '".$row['tahun']."'";
                    }
                    $i++;
                }

                $select2 = "SELECT a.no_pj, a.kode_akun, a.kode_lokasi, a.kode_drk, a.kode_pp,
                b.nama AS nama_pp, c.nama AS nama_akun, d.nama AS nama_drk, ISNULL(a.nilai,0) AS nilai
                FROM (
                    SELECT a.no_pj, a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk, SUM(a.nilai) as nilai
                    FROM panjar_j a
                    WHERE a.no_pj IN ($no_pb) AND a.kode_lokasi='".$kode_lokasi."'
                    GROUP BY a.kode_akun, a.kode_lokasi, a.kode_pp, a.kode_drk, a.no_pj
                ) a
                INNER JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
                INNER JOIN masakun c ON a.kode_akun=c.kode_akun AND a.kode_lokasi=c.kode_lokasi
                LEFT JOIN drk d ON a.kode_drk=d.kode_drk AND a.kode_lokasi=d.kode_lokasi AND d.tahun IN ($tahun) 
                ORDER BY a.kode_akun";

                $res2 = DB::connection($this->db)->select($select2);
                $res2 = json_decode(json_encode($res2),true);

                if(count($res2) > 0) { 
                    $no_pb = "";
                    $i=0;
                    foreach($res2 as $row) { 
                        if($i == 0) {
                            $no_pb = "'".$row['no_pj']."'";
                        } else {
                            $no_pb .= ", '".$row['no_pj']."'";
                        }
                        $i++;
                    }

                    $select3 = "SELECT no_bukti, no_rek, nama_rek, bank, nilai + ISNULL(pajak,0) AS nilai, 
                    ISNULL(pajak,0) AS pajak, nilai AS netto 
                    FROM pbh_rek
                    WHERE no_bukti IN ($no_pb) AND kode_lokasi='".$kode_lokasi."' 
                    ORDER BY no_rek";

                    $res3 = DB::connection($this->db)->select($select3);
                    $res3 = json_decode(json_encode($res3),true);
                }
            }   

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $res2;
                $success['data_sub_detail'] = $res3;
                $success['bilangan_angka'] = $bilanganAngka;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;  
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_sub_detail'] = [];
                $success['bilangan_angka'] = [];
                $success['status'] = true;
            }
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>