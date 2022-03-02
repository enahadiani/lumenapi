<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="px-3">
        <style>
        .info-table thead{
            background:#4286f5;
            color:white;
        }
        .bold {
            font-weight:bold;
        }
        </style> 
        @php
        function terbilang($int) {
        $angka = [
            "",
            "satu",
            "dua",
            "tiga",
            "empat",
            "lima",
            "enam",
            "tujuh",
            "delapan",
            "sembilan",
            "sepuluh",
            "sebelas",
        ];
        if ($int < 12) return " " .$angka[$int];
        else if ($int < 20) return terbilang($int - 10) ." belas ";
        else if ($int < 100)
            return terbilang($int / 10) ." puluh " .terbilang($int % 10);
        else if ($int < 200) return "seratus" .terbilang($int - 100);
        else if ($int < 1000)
            return terbilang($int / 100) ." ratus " .terbilang($int % 100);
        else if ($int < 2000) return "seribu" .terbilang($int - 1000);
        else if ($int < 1000000)
            return terbilang($int / 1000) ." ribu " .terbilang($int % 1000);
        else if ($int < 1000000000)
            return terbilang($int / 1000000) ." juta " .terbilang($int % 1000000);
        else if ($int < 1000000000000)
            return (
                terbilang($int / 1000000) ." milyar " .terbilang($int % 1000000000)
            );
        else if ($int >= 1000000000000)
            return (
                terbilang($int / 1000000).
                " trilyun ".
                terbilang($int % 1000000000000)
            );
    }
    
    function getNamaBulan($no_bulan) {
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
    
        return $bulan;
    }
        @endphp       
            <p>{{ $judul }}</p>    
            <div class="row px-4">
                <div class="col-12" style="border-bottom:3px solid black;text-align:center">
                    <h4 style="margin-bottom:0px !important">JUSTIFIKASI</h4>
                    <h4>KEBUTUHAN BARANG ATAU JASA</h4>
                </div>
                <div class="col-12 my-2" style="text-align:center">
                    <h4>Nomor : {{ $data[0]['no_pb'] }}</h4>
                </div>
                <div class="col-12">
                    <table class="table table-condensed table-bordered" width="100%"  id="table-m">
                        <tbody>
                            <tr>
                                <td width="5%">1</td>
                                <td width="25">UNIT KERJA</td>
                                <td width="70%" id="print-unit">{{ $data[0]['nama_pp'] }}</td>
                            </tr>
                            <tr>
                                <td width="5%">2</td>
                                <td width="25">JENIS ANGGARAN</td>
                                <td width="70%" id="print-unit">{{ $data[0]['jenis'] }}</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>TOTAL NILAI</td>
                                <td id="print-kegiatan2">{{ number_format($data[0]['nilai'],0,",",".") }}</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>TERBILANG</td>
                                <td id="print-kegiatan2">{{ terbilang($data[0]['nilai']) }} Rupiah</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>KEBUTUHAN</td>
                                <td id="print-pic">{{ $data[0]['keterangan'] }}</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>SAAT PENGGUNAAN</td>
                                <td id="print-waktu">{{ substr($data[0]['tanggal'],8,2) }} {{ getNamaBulan(substr($data[0]['tanggal'],5,2)) }} {{ substr($data[0]['tanggal'],0,4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>KEBUTUHAN</u></p>
                </div>
                <div class="col-12">
                    <table class="table table-condensed table-bordered" style='border:1px solid black;border-collapse:collapse' border="1" width="100%" id="table-d">
                        <thead>
                            <tr>
                                <td style="width:3%">No</td>
                                <td style="width:15%">Nama Barang</td>
                                <td style="width:30%">Satuan</td>
                                <td style="width:10%">Harga</td>
                                <td style="width:6%">Qty</td>
                                <td style="width:15%">Jumlah Harga</td>
                            </tr>
                        </thead>
                        <tbody>
                        @php $total =0; $no=0; @endphp
                        @for($i=0; $i < count($detail); $i++)
                            @php
                                $line = $detail[$i];
                                $total+=floatval($line['harga'])*floatval($line['jumlah']);
                                $no++;
                            @endphp
                            <tr>
                                <td>{{ $no }}</td>
                                <td>{{ $line['nama_brg'] }}</td>
                                <td>{{ $line['satuan'] }}</td>
                                <td style="text-align:right">{{ number_format($line['jumlah'],0,",",".") }}</td>
                                <td style="text-align:right">{{ number_format($line['harga'],0,",",".") }}</td>
                                <td style="text-align:right">{{ number_format($total,0,",",".") }}</td>
                                </tr>
                        @endfor
                        <tr>
                            <td colspan="5">Total</td>
                            <td style="text-align:right">{{ number_format($total,0,",",".") }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>LATAR BELAKANG</u></p>
                    <p>{{ $data[0]['latar'] }}</p>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>ASPEK STRATEGIS</u></p>
                    <p>{{ $data[0]['strategis'] }}</p>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>ASPEK BISNIS</u></p>
                    <p>{{ $data[0]['bisnis'] }}</p>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>SPESIFIKASI TEKNIS</u></p>
                    <p>{{ $data[0]['teknis'] }}</p>
                </div>
                <div class="col-12">
                    <p style="font-weight:bold"># <u>ASPEK LAIN</u></p>
                    <p>{{ $data[0]['lain'] }}</p>
                </div>
                    <div class="col-12">
                        <p style="font-weight:bold"># <u>LAMPIRAN</u></p>
                    </div>
                    <div class="col-12">
                    <table class="table table-condensed table-bordered" style='border:1px solid black;border-collapse:collapse' border="1" width="100%" id="table-penutup">
                        <thead class="text-center">
                        <tr>
                        <td width="10%"></td>
                        <td width="25">NAMA/NIK</td>
                        <td width="15%">JABATAN</td>
                        <td width="10%">TANGGAL</td>
                        <td width="15%">NO APPROVAL</td>
                        <td width="10%">STATUS</td>
                        <td width="15%">TTD</td>
                        </tr>
                    </thead>
                    <tbody>
                    @php $total =0; $no=0; @endphp
                    @for($i=0; $i < count($histori); $i++)
                        @php
                            $line2 = $histori[$i];
                            $no++;
                        @endphp
                        <tr>
                        <td>{{ $line2['ket'] }} </td>
                        <td>{{ $line2['nama_kar'] }} / {{ $line2['nik'] }} </td>
                        <td>{{ $line2['nama_jab'] }} </td>
                        <td>{{ $line2['tanggal'] }} </td>
                        <td>{{ $line2['no_app'] }} </td>
                        <td>{{ $line2['status'] }} </td>
                        <td>&nbsp;</td>
                        </tr>
                    @endfor
                    </tbody>
                    </table>
                    </div>
                </div>
                <p>Klik <a href="{{ config('services.api.redirect_email_url_siaga') }}">link ini</a> untuk melihat detail pengajuan.</p>
        </div>
</body>
</html>