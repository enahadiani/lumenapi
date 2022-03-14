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
             
        <p>{{ $judul }}</p>   
        <table width='800' border='1' cellspacing='0' cellpadding='0' class='kotak'>
            <tr>
                <td colspan='2'><table width='800' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td width='146'><img src='https://app.simkug.com/img/gratika2.jpg' width='200' height='80' /></td>
                    <td width='640' align='center' class='istyle17'><h4>SURAT PERINTAH BAYAR</h4></td>
                </tr>
                <tr>
                    <td colspan='2' align='center'>DIREKTORAT KEUANGAN</td>
                    </tr>
                </table></td>
            </tr>
            <tr>
                <td align='center'><table width='350' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td width='158'>No. PO </td>
                    <td width='182'>: {{ $data[0]['no_po'] }}</td>
                </tr>
                <tr>
                    <td>Tgl. PO </td>
                    <td>: {{ $data[0]['tgl_po'] }}</td>
                </tr>
                <tr>
                    <td>No./Tgl BA/Log TR </td>
                    <td>: {{ $data[0]['no_ba'] }} / {{ $data[0]['tgl_ba'] }}</td>
                </tr>
                <tr>
                    <td>No Dokumen </td>
                    <td>: {{ $data[0]['no_dok'] }}</td>
                </tr>
                <tr>
                    <td>No. Ref. Dokumen </td>
                    <td>: {{ $data[0]['no_ref'] }}</td>
                </tr>
                <tr>
                    <td>Tgl. Dok </td>
                    <td>: {{ $data[0]['tgl_dok'] }}</td>
                </tr>
                <tr>
                    <td>Kode Perkiraan </td>
                    <td>: -</td>
                </tr>
                <tr>
                    <td>Kode Lokasi </td>
                    <td>: -</td>
                </tr>
                <tr>
                    <td>Cost Centre </td>
                    <td>: -</td>
                </tr>
                </table></td>
                <td align='center'><table width='350' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td>No. SPB </td>
                    <td width='182'>: {{ $data[0]['no_spb'] }}</td>
                </tr>
                <tr>
                    <td>Tgl. SPB </td>
                    <td>: {{ $data[0]['tgl'] }}</td>
                </tr>
                <tr>
                    <td>No./Tgl. PRPK </td>
                    <td>: -</td>
                </tr>
                <tr>
                    <td>No. DRK/TRIW </td>
                    <td>: -</td>
                </tr>
                <tr>
                    <td>Keg. Menurut DRK </td>
                    <td>: -</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>:</td>
                </tr>
                <tr>
                    <td>Beban Angg Thn </td>
                    <td>: {{ $data[0]['tahun'] }}</td>
                </tr>
                <tr>
                    <td>Rekening </td>
                    <td>:</td>
                </tr>
                <tr>
                    <td>Jenis Transaksi </td>
                    <td>:</td>
                </tr>
                </table></td>
            </tr>
            <tr align='left'>
                <td colspan='2'><table width='400' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td width='23'>&nbsp;</td>
                    <td width='367'>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Jakarta, {{ substr($data[0]['tanggal'],8,2) }} {{ \app\Helper\SaiHelpers::getNamaBulan(substr($data[0]['tanggal'],5,2)) }} {{ substr($data[0]['tanggal'],0,4) }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Dokumen Penagihan disahkan oleh</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Mgr. Finanace/GM Fin. &amp; Acc.</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td height='60' valign='bottom'>{{ $data[0]['nama_bdh'] }}</td>
                </tr>
                </table></td>
            </tr>
            <tr>
                <td colspan='2'><table width='750' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td width='25'>&nbsp;</td>
                    <td width='178'>Harap dibayarkan :<br></td>
                    <td width='220'>&nbsp;</td>
                    <td width='309'>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Sebesar </td>
                    <td colspan='2'>: {{ number_format($data[0]['nilai'],0,",",".") }}</td>
                    </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Terbilang </td>
                    <td colspan='2'>: {{ \app\Helper\SaiHelpers::terbilang($data[0]['nilai']) }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Kepada </td>
                    <td colspan='2'>: {{ $data[0]['nama'] }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Alamat </td>
                    <td colspan='2'>: {{ $data[0]['alamat'] }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Bank </td>
                    <td colspan='2'>: {{ $data[0]['bank'] }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>No. Rekening </td>
                    <td colspan='2'>: {{ $data[0]['norek'] }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Alamat Bank </td>
                    <td colspan='2'>: {{ $data[0]['alamat'] }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>Untuk Pembayaran </td>
                    <td colspan='2'>: {{ $data[0]['keterangan'] }}</td>
                    </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>Jakarta, {{ substr($data[0]['tanggal'],8,2) }} {{ \app\Helper\SaiHelpers::getNamaBulan(substr($data[0]['tanggal'],5,2)) }} {{ substr($data[0]['tanggal'],0,4) }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>GM Fin. &amp; Acc. / Dir. Adm. &amp; Keu.</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td height='60' valign='bottom'>{{ $data[0]['nama_ver'] }}</td>
                </tr>
                </table></td>
            </tr>
            <tr>
                <td align='center'><table width='350' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td width='158'>Catatan Pembayaran: </td>
                    <td width='182'>:</td>
                </tr>
                <tr>
                    <td>JUMLAH TAGIHAN </td>
                    <td>: {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['tagihan'],0,",",".") }} </td>
                </tr>
                <tr>
                    <td>PPN</td>
                    <td>: {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['ppn'],0,",",".") }} </td>
                </tr>
                <tr>
                    <td>PPh </td>
                    <td>: {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['pph'],0,",",".") }}</td>
                </tr>
                <tr>
                    <td>SubTotal (a) </td>
                    <td>: {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['nilai'],0,",",".") }}</td>
                </tr>
                <tr>
                    <td>Potongan lain: </td>
                    <td>:</td>
                </tr>
                <tr>
                    <td>Jumlah Potongan lain (b) </td>
                    <td>: {{ $data[0]['kode_curr'] }} 0</td>
                </tr>
                <tr>
                    <td>Jumlah dibayarkan (a-b) </td>
                    <td>: {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['nilai'],0,",",".") }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                </tr>
                </table></td>
                <td align='center' valign='top'><table width='350' border='0' cellspacing='2' cellpadding='1'>
                <tr>
                    <td colspan='2'>Catatan Pembayaran </td>
                    </tr>
                <tr>
                    <td colspan='2'>Telah dibayar uang sejumlah : {{ $data[0]['kode_curr'] }} {{ number_format($data[0]['nilai'],0,",",".") }} </td>
                    </tr>
                <tr>
                    <td width='68' valign='top'>Terbilang :</td>
                    <td width='272' valign='top'>{{ \app\Helper\SaiHelpers::terbilang($data[0]['nilai']) }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Jakarta, {{ substr($data[0]['tanggal'],8,2) }} {{ \app\Helper\SaiHelpers::getNamaBulan(substr($data[0]['tanggal'],5,2)) }} {{ substr($data[0]['tanggal'],0,4) }}</td>
                </tr>
                <tr>
                    <td height='60'>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                </table></td>
            </tr>
            <tr>
                <td height='60' valign='top'>&nbsp;&nbsp; &nbsp;Catatan Perpajakan :  &nbsp;{{ $data[0]['cat_pajak'] }}</td>
                <td valign='top'>&nbsp;&nbsp;&nbsp;&nbsp;Catatan Perbendaharaan :  &nbsp;{{ $data[0]['cat_bdh'] }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding:4px 8px">
                    <div class="col-12">
                        <p style='font-weight:bold'># <u>LAMPIRAN</u></p>
                    </div>
                    <div class="col-12">
                    <table id="table-penutup" style="border: 1px solid black;border-collapse: collapse;margin-bottom: 20px;" class="" width="100%" border="1">
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
                    @php
                    $no=1;
                    @endphp
                    @for($i=0; $i < count($histori); $i++)
                       <tr>
                        <td>{{ $histori[$i]['ket'] }} </td>
                        <td>{{ $histori[$i]['nama_kar'] }} /{{ $histori[$i]['nik'] }} </td>
                        <td>{{ $histori[$i]['nama_jab'] }} </td>
                        <td>{{ $histori[$i]['tanggal'] }} </td>
                        <td>{{ $histori[$i]['no_app'] }} </td>
                        <td>{{ $histori[$i]['status'] }} </td>
                        <td>&nbsp;</td>
                        </tr>
                        @php
                        $no++;
                        @endphp
                    @endfor
                    </tbody>
                    </table>
                    </div>
                </td>
            </tr>
            </table> 
            <p>Klik <a href="{{ config('services.api.redirect_email_url_siaga') }}">link ini</a> untuk melihat detail pengajuan.</p>
    </div>
</body>
</html>