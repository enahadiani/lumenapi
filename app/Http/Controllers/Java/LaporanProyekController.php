<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanProyekController extends Controller {

    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getSaldoProyek(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('no_proyek', 'kode_cust', 'status');
            $db_col_name = array('a.no_proyek', 'a.kode_cust', 'd.status');
            $where = "where a.kode_lokasi='$kode_lokasi'";
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

            $sql = "select a.no_proyek, a.no_kontrak, convert(varchar,tgl_mulai,103) as tgl_mulai, 
            convert(varchar,tgl_selesai,103) as tgl_selesai, a.keterangan,b.nama as nama_cust,isnull(c.nilai,0) as rab,
            isnull(d.nilai,0) as beban, isnull(e.nilai,0) as tagihan,isnull(f.nilai,0) as bayar, a.nilai as nilai_proyek
            from java_proyek a
            inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            left join (select b.no_proyek,b.kode_lokasi,sum(a.jumlah*a.harga) as nilai
            from java_rab_d a
            inner join java_rab_m b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='11'
            group by b.no_proyek,b.kode_lokasi
            )c on a.no_proyek=c.no_proyek and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai, a.status
            from java_beban  a
            where a.kode_lokasi='11'
            group by a.no_proyek,a.kode_lokasi,a.status
            )d on a.no_proyek=d.no_proyek and a.kode_lokasi=d.kode_lokasi
            left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
            from java_tagihan  a
            where a.kode_lokasi='11'
            group by a.no_proyek,a.kode_lokasi
            )e on a.no_proyek=e.no_proyek and a.kode_lokasi=e.kode_lokasi
            left join (select b.no_proyek,a.kode_lokasi,sum(a.nilai_bayar) as nilai
            from java_bayar_detail a
            inner join java_tagihan b on a.no_tagihan=b.no_tagihan and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='11'
            group by b.no_proyek,a.kode_lokasi
            )f on a.no_proyek=f.no_proyek and a.kode_lokasi=f.kode_lokasi
            $where";

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

    public function getKartuProyek(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('no_proyek', 'kode_cust');
            $db_col_name = array('a.no_proyek', 'b.kode_cust');
            $where = "where a.kode_lokasi='$kode_lokasi'";
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

            $proyek = "select a.no_proyek, a.no_kontrak, convert(varchar,tgl_mulai,103) as tgl_mulai, 
            convert(varchar,tgl_selesai,103) as tgl_selesai, a.keterangan,b.nama as nama_cust, a.nilai
            from java_proyek a
            inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            $where";

            $rs1 = DB::connection($this->sql)->select($proyek);
            $res1 = json_decode(json_encode($rs1),true);

            if(count($res1) > 0) {
                $no_proyek = "";
                $resdata = array();
                $i=0;
                foreach($rs1 as $row){

                    $resdata[]=(array)$row;
                    if($i == 0){
                        $no_proyek .= "'$row->no_proyek'";
                    }else{

                        $no_proyek .= ","."'$row->no_proyek'";
                    }
                    $i++;
                }

                $rab = "select a.no_proyek, b.jumlah, b.satuan, b.harga, b.keterangan
                from  java_rab_m a
                inner join java_rab_d b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi = '".$kode_lokasi."' and a.no_proyek in ($no_proyek)
                order by b.no";

                $res2 = DB::connection($this->sql)->select($rab);
                $res2 = json_decode(json_encode($res2),true);

                $beban = "select b.no_proyek, b.no_bukti, b.no_dokumen, convert(varchar,tanggal,103) as tgl, b.keterangan, 
                a.nama as nama_vendor, b.nilai,b.status
                from java_vendor a
                inner join java_beban b on a.kode_vendor=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
                where b.kode_lokasi = '".$kode_lokasi."' and b.no_proyek in ($no_proyek)";

                $res3 = DB::connection($this->sql)->select($beban);
                $res3 = json_decode(json_encode($res3),true);
            }

            $result = array(
                'data_rab' => $res2,
                'data_beban' => $res3
            );

            $sql = array(
                'data_rab' => $rab,
                'data_beban' => $beban
            );

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $result;
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

    function convertBilangan($nilai) {
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
			$temp = $this->convertBilangan($nilai/1000) . "Ribu" . $this->convertBilangan($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->convertBilangan($nilai/1000000) . " Juta" . $this->convertBilangan($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000) . " Milyar" . $this->convertBilangan(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000000) . " Trilyun" . $this->convertBilangan(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    
    function bilanganAngka($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim($this->convertBilangan($nilai));
		} else {
			$hasil = trim($this->convertBilangan($nilai));
		}     		
		return $hasil." "."Rupiah";
	}

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
    }
}
?>