<?php
namespace App\Helper;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; 
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SaiHelpers
{
    public static function terbilang($bilangan, $curr = " Rupiah")
    {
        $angka = array('0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0');
        $kata = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan');
        $tingkat = array('', 'Ribu', 'Juta', 'Milyar', 'Triliun');
        $koma = "";
        if (strpos($bilangan, ".")) {
            $koma = explode(".", $bilangan);
            $bilangan = $koma[0];
            $koma = SaiHelpers::terbilang($koma[1], "");
            $koma = " koma " . $koma;
        }

        $panjang_bilangan = strlen($bilangan);

        /* pengujian panjang bilangan */
        if ($panjang_bilangan > 15) {
            $kalimat = "Diluar Batas";
            return $kalimat;
        }

        /* mengambil angka-angka yang ada dalam bilangan,
        dimasukkan ke dalam array */
        for ($i = 1; $i <= $panjang_bilangan; $i++) {
            $angka[$i] = substr($bilangan, -($i), 1);
        }

        $i = 1;
        $j = 0;
        $kalimat = "";

        /* mulai proses iterasi terhadap array angka */
        while ($i <= $panjang_bilangan) {

            $subkalimat = "";
            $kata1 = "";
            $kata2 = "";
            $kata3 = "";

            /* untuk ratusan */
            if ($angka[$i + 2] != "0") {
                if ($angka[$i + 2] == "1") {
                    $kata1 = "Seratus";
                } else {
                    $kata1 = $kata[$angka[$i + 2]] . " Ratus";
                }
            }

            /* untuk puluhan atau belasan */
            if ($angka[$i + 1] != "0") {
                if ($angka[$i + 1] == "1") {
                    if ($angka[$i] == "0") {
                        $kata2 = "Sepuluh";
                    } elseif ($angka[$i] == "1") {
                        $kata2 = "Sebelas";
                    } else {
                        $kata2 = $kata[$angka[$i]] . " Belas";
                    }
                } else {
                    $kata2 = $kata[$angka[$i + 1]] . " Puluh";
                }
            }

            /* untuk satuan */
            if ($angka[$i] != "0") {
                if ($angka[$i + 1] != "1") {
                    $kata3 = $kata[$angka[$i]];
                }
            }

            /* pengujian angka apakah tidak nol semua,
            lalu ditambahkan tingkat */
            if (($angka[$i] != "0") or ($angka[$i + 1] != "0") or
                ($angka[$i + 2] != "0")) {
                $subkalimat = "$kata1 $kata2 $kata3 " . $tingkat[$j] . " ";
            }

            /* gabungkan variabe sub kalimat (untuk satu blok 3 angka)
            ke variabel kalimat */
            $kalimat = $subkalimat . $kalimat;
            $i = $i + 3;
            $j = $j + 1;

        }

        /* mengganti satu ribu jadi seribu jika diperlukan */
        if (($angka[5] == "0") and ($angka[6] == "0")) {
            $kalimat = str_replace("Satu Ribu", "Seribu", $kalimat);
        }
        //$kalimat=$kalimat. " ".$koma. $curr;
        $kalimat = $kalimat . " " . $curr;
        return trim($kalimat);

    }

    public static function getNamaBulan($no_bulan) {
        $no_bulan = strval($no_bulan);
        if(strlen($no_bulan) == 6){
            $tahun = substr($no_bulan,0,4);
            $no_bulan = substr($no_bulan,4,2);
        }else{
            $tahun = "";
        }
        switch ($no_bulan) {
            case 1:
            case "1":
            case "01":
                $bulan = "Januari";
                break;
            case 2:
            case "2":
            case "02":
                $bulan = "Februari";
                break;
            case 3:
            case "3":
            case "03":
                $bulan = "Maret";
                break;
            case 4:
            case "4":
            case "04":
                $bulan = "April";
                break;
            case 5:
            case "5":
            case "05":
                $bulan = "Mei";
                break;
            case 6:
            case "6":
            case "06":
                $bulan = "Juni";
                break;
            case 7:
            case "7":
            case "07":
                $bulan = "Juli";
                break;
            case 8:
            case "8":
            case "08":
                $bulan = "Agustus";
                break;
            case 9:
            case "9":
            case "09":
                $bulan = "September";
                break;
            case 10:
            case "10":
            case "10":
                $bulan = "Oktober";
                break;
            case 11:
            case "11":
            case "11":
                $bulan = "November";
                break;
            case 12:
            case "12":
            case "12":
                $bulan = "Desember";
                break;
            default:
                $bulan = null;
        }
    
        if($tahun != ""){
            return $bulan." ".$tahun;
        }else{
            return $bulan;
        }
    }

    public static function lastOfMonth($year, $month) {
        return date("d", strtotime('-1 second', strtotime('+1 month',strtotime($month . '/01/' . $year. ' 00:00:00'))));
    }

    public static function joinNum($num){
        // menggabungkan angka yang di-separate(10.000,75) menjadi 10000.00
        $num = str_replace(".", "", $num);
        $num = str_replace(",", ".", $num);
        return $num;
    }

    // tahun bulan tanggal
    public static function reverseDate($ymd_or_dmy_date, $org_sep = '/', $new_sep = '-') {
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2] . $new_sep . $arr[1] . $new_sep . $arr[0];
    }

    public static function getNamaHari($tanggal) {
        $date = date_create($tanggal);
        $name = date_format($date,"l");
        switch ($name) {
            case 'Sunday':
                $hari = "Minggu";
                break;
            case 'Monday':
                $hari = "Senin";
                break;
            case 'Tuesday':
                    $hari = "Selasa";
                    break;
            case 'Wednesday':
                    $hari = "Rabu";
                    break;
            case 'Thursday':
                $hari = "Kamis";
                break;
            case 'Friday':
                $hari = "Jumat";
                break;
            case 'Saturday':
                $hari = "Sabtu";
            default:
                $hari = null;
        }
        return $hari;
    }


    public static function getPeriodeAktif($db,$kode_lokasi){
        $query = DB::connection($db)->select("select max(periode) as periode from periode where kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
    }

    public static function namaPeriode($periode){
        $bulan = substr($periode,4,2);
        $tahun = substr($periode,0,4);
        switch ($bulan){
            case 1 : case '1' : case '01': $bulan = "Januari"; break;
            case 2 : case '2' : case '02': $bulan = "Februari"; break;
            case 3 : case '3' : case '03': $bulan = "Maret"; break;
            case 4 : case '4' : case '04': $bulan = "April"; break;
            case 5 : case '5' : case '05': $bulan = "Mei"; break;
            case 6 : case '6' : case '06': $bulan = "Juni"; break;
            case 7 : case '7' : case '07': $bulan = "Juli"; break;
            case 8 : case '8' : case '08': $bulan = "Agustus"; break;
            case 9 : case '9' : case '09': $bulan = "September"; break;
            case 10 : case '10' : case '10': $bulan = "Oktober"; break;
            case 11 : case '11' : case '11': $bulan = "November"; break;
            case 12 : case '12' : case '12': $bulan = "Desember"; break;
            default: $bulan = null;
        }
    
        return $bulan.' '.$tahun;
    }

    public static function doCekPeriode($db,$kode_lokasi,$periode) {
        try{
            
            $perValid = false;
            $periode_aktif = SaiHelpers::getPeriodeAktif($db,$kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[$periode_aktif]";
                }else{
                    $perNext = SaiHelpers::nextNPeriode($periode,1);
                    if($perNext == "1"){
                        $perValid = true;
                        $msg = "ok";
                    }else{
                        $perValid = false;
                        $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh melebihi periode aktif sistem.[$periode_aktif]";
                    }
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    public static function doCekPeriodeKurang($db,$kode_lokasi,$periode) {
        try{
            
            $perValid = false;
            $periode_aktif = SaiHelpers::getPeriodeAktif($db,$kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[$periode_aktif]";
                }else{
                    $perValid = true;
                    $msg = "ok";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    public static function doCekPeriodeLebih($db,$kode_lokasi,$periode) {
        try{
            
            $perValid = false;
            $periode_aktif = SaiHelpers::getPeriodeAktif($db,$kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = true;
                    $msg = "ok";
                }else{
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh lebih dari periode aktif sistem.[$periode_aktif]";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    public static function nextNPeriode($periode, $n) 
    {
        $bln = floatval(substr($periode,4,2));
        $thn = floatval(substr($periode,0,4));
        for ($i = 1; $i <= $n;$i++){
            if ($bln < 12) $bln++;
            else {
                $bln = 1;
                $thn++;
            }
        }
        if ($bln < 10) $bln = "0".$bln;
        return $thn."".$bln;
    }

    public static function filterRpt($request,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($request->input($col_array[$i])[0])){
                if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                    $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }else{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public static function tglToPeriode($tgl,$db,$kode_lokasi){
        $periode_aktif = SaiHelpers::getPeriodeAktif($db,$kode_lokasi);
        $m = intval(substr($tgl,5,2));
        $y = substr($tgl,0,4);

        if ($m < 10) $m = "0".$m;			
		if (intval(substr($periode_aktif,4,2)) <= 12) $periode = $y."".$m;
		else {
			if ($m == 12) $periode = $periode_aktif;
			else $periode = $y."".$m;
		}
        return $periode;
    }

    public static function generateKode($db,$tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public static function removeKutip($str){
        $string = str_replace("'","",$str);
        return $string;
    }

    public static function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return Carbon::instance(Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }

}
