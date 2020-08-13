<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'admin';
    public $sql = 'sqlsrv2';

    function convertBilangan($nilai) {
		$nilai = abs($nilai);
		$huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = $this->convertBilangan($nilai - 10). " belas";
		} else if ($nilai < 100) {
			$temp = $this->convertBilangan($nilai/10)." puluh". $this->convertBilangan($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " seratus" . $this->convertBilangan($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = $this->convertBilangan($nilai/100) . " ratus" . $this->convertBilangan($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " seribu" . $this->convertBilangan($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = $this->convertBilangan($nilai/1000) . "ribu" . $this->convertBilangan($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->convertBilangan($nilai/1000000) . " juta" . $this->convertBilangan($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000) . " milyar" . $this->convertBilangan(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->convertBilangan($nilai/1000000000000) . " trilyun" . $this->convertBilangan(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    
    function bilanganAngka($nilai) {
		if($nilai<0) {
			$hasil = "minus ". trim($this->convertBilangan($nilai));
		} else {
			$hasil = trim($this->convertBilangan($nilai));
		}     		
		return $hasil;
	}

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
    }

    function getReportTagihanDetail(Request $request) {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_dokumen = $request->input('no_dokumen');
            $customer   = $request->input('kode_cust');
            
            $sqlTagihan = "select a.tanggal, a.no_bill, c.nama, c.alamat, c.provinsi, c.jabatan_pic, d.no_dokumen, d.tgl_sepakat, d.keterangan, d.nilai, d.nilai_ppn, d.nilai + d.nilai_ppn as 'nilai_akhir', d.due_date 
            from sai_bill_m a
            inner join sai_bill_d b on a.kode_lokasi=b.kode_lokasi and a.no_bill=b.no_bill
            inner join sai_cust c on b.kode_lokasi=c.kode_lokasi and b.kode_cust=c.kode_cust
            inner join sai_kontrak d on c.kode_cust=d.kode_cust and a.kode_lokasi=d.kode_lokasi
            where b.no_dokumen = '$no_dokumen' and a.kode_lokasi = '$kode_lokasi' and c.kode_cust = '$customer'";

            $rs1 = DB::connection($this->sql)->select($sqlTagihan);
            $res1 = json_decode(json_encode($rs1),true);
            $totalNilai = floatval($res1[0]['nilai_akhir']);

            $sqlSaiBank = "select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a where a.kode_lokasi='$kode_lokasi'";

            $rs2 = DB::connection($this->sql)->select($sqlSaiBank);
            $res2 = json_decode(json_encode($rs2),true);

            $sqlLampiran = "select b.nama from sai_cust_d a inner join sai_lampiran b 
            on a.kode_lokasi=b.kode_lokasi and a.kode_lampiran=b.kode_lampiran
            where a.kode_lokasi = '$kode_lokasi' and  a.kode_cust = '$customer'";

            $rs3 = DB::connection($this->sql)->select($sqlLampiran);
            $res3 = json_decode(json_encode($rs3),true);

            $convertDate = date('m',strtotime($res1[0]['tanggal']));
            $convertFloat = floatval($convertDate);

            $success['status'] = true;
            $success['terbilang'] = $this->bilanganAngka($totalNilai);
            $success['bulan'] = $this->getNamaBulan($convertFloat);
            $success['data'] = $res1;
            $success['data_bank'] = $res2;
            $success['data_lampiran'] = $res3;
            $success['message'] = "Success!";
            $success["auth_status"] = 1;        

            return response()->json($success, $this->successStatus);


        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }   

    function getReportTagihan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','periode');
            $db_col_name = array('b.kode_cust','b.periode');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select b.no_dokumen,b.kode_cust+' - '+c.nama as cust,e.keterangan as keterangan_kontrak,e.nilai as nilai_kontrak,e.nilai_ppn as nilai_ppn_kontrak
            from sai_bill_m a
            inner join sai_bill_d b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi
            left join sai_cust c on b.kode_cust=c.kode_cust and b.kode_lokasi=c.kode_lokasi
            left join sai_kontrak e on b.no_kontrak=e.no_kontrak and a.kode_lokasi=e.kode_lokasi
            $filter and b.status = '1' ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $kode_cust = "";
            $no_kontrak = "";
            $resdata = array();
            $i=0;
            foreach($rs as $row){

                $resdata[]=(array)$row;
                if($i == 0){
                    $nb .= "'$row->no_dokumen'";
                    $kode_cust .= "'$row->cust'";
                    // $no_kontrak .= "'$row->no_kontrak'";
                }else{

                    $nb .= ","."'$row->no_dokumen'";
                    $kode_cust .= ","."'$row->cust'";
                    // $no_kontrak .= ","."'$row->no_kontrak'";
                }
                $i++;
            }

            // $sql2="select a.no_bill,a.nu,a.item,a.harga,a.jumlah,a.nilai,a.nilai_ppn 
            // from sai_bill_d a
            // where a.no_bill in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_cust in ($kode_cust) and a.no_kontrak in ($no_kontrak) ";
            // $res2 = DB::connection($this->sql)->select($sql2);
            // $res2 = json_decode(json_encode($res2),true);

            // $sql3="select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a
            // where a.kode_lokasi='$kode_lokasi' ";
            // $res3 = DB::connection($this->sql)->select($sql3);
            // $res3 = json_decode(json_encode($res3),true);

            // $sql4="select a.kode_lampiran,b.nama 
            // from sai_cust_d a
            // inner join sai_lampiran b on a.kode_lampiran=b.kode_lampiran and a.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and a.kode_cust in ($kode_cust) ";
            // $res4 = DB::connection($this->sql)->select($sql4);
            // $res4 = json_decode(json_encode($res4),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                // $success['data_detail'] = $res2;
                // $success['data_bank'] = $res3;
                // $success['data_lampiran'] = $res4;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                // $success['data_detail'] = [];
                // $success['data_bank'] = [];
                // $success['data_lampiran'] = [];
                // $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportKuitansi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_cust','no_bayar','periode');
            $db_col_name = array('a.kode_cust','a.no_bayar','substring(convert(varchar,tanggal,112),1,6)');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for($i = 0; $i<count($col_array); $i++){
                if($request->input($col_array[$i]) !=""){
                    $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
                }
            }

            $sql="select a.no_bayar,a.keterangan,convert(varchar,a.tanggal,103) as tanggal,a.kode_cust,b.nama as nama_cust,isnull(c.nilai,0) as nilai
            from sai_bayar_m a
            inner join sai_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_bayar,a.kode_lokasi, sum(a.nilai) as nilai
                        from sai_bayar_d a
                        group by a.no_bayar,a.kode_lokasi) c on a.no_bayar=c.no_bayar and a.kode_lokasi=c.kode_lokasi
            $filter ";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $sql3="select a.bank,a.cabang,a.no_rek,a.nama_rek from sai_bank a
            where a.kode_lokasi='$kode_lokasi' ";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_bank'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_bank'] = [];
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
    

}
