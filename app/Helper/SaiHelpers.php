<?php
namespace App\Helper;

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


}
