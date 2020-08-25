<div id='canvasPreview'>
    <div>
        <style>
        .info-table thead{
            // background:#e9ecef;
        }
        .table-bordered td{
            border: 1px solid #e9ecef !important;
        }
        .no-border td{
            border:0 !important;
        }
        .bold {
            font-weight:bold;
        }
        </style>
        <table class='table table-bordered info-table' style="border-collapse:collapse">
            <thead>
                <tr>
                    <td width='30' rowspan='2'  class='header_laporan' align='center'>No</td>
                    <td width='70' rowspan='2' class='header_laporan' align='center'>Kode Akun</td>
                    <td width='300' rowspan='2' class='header_laporan' align='center'>Nama Akun</td>
                    <td height='25' colspan='2' class='header_laporan' align='center'>Saldo Awal </td>
                    <td colspan='2' class='header_laporan' align='center'>Mutasi</td>
                    <td colspan='2' class='header_laporan' align='center'>Saldo Akhir </td>
                </tr>
                <tr> 
                    <td width='90' height='25' class='header_laporan' align='center'>Debet</td>
                    <td width='90' class='header_laporan' align='center'>Kredit</td>
                    <td width='90' class='header_laporan' align='center'>Debet</td>
                    <td width='90' class='header_laporan' align='center'>Kredit</td>
                    <td width='90' class='header_laporan' align='center'>Debet</td>
                    <td width='90' class='header_laporan' align='center'>Kredit</td>
                </tr>
            </thead>
            <tbody>
                @php
                   $so_awal_debet=0;
                   $so_awal_kredit=0;
                   $debet=0;
                   $kredit=0;
                   $so_akhir_debet=0;
                   $so_akhir_kredit=0;
                   $no=1;
                @endphp
                    @for ($i=0; $i < count($data_array) ; $i++)
                    
                        @php 
                            $line  = $data_array[$i]; 
                            $so_awal_debet+=+floatval($line['so_awal_debet']);
                            $so_awal_kredit+=+floatval($line['so_awal_kredit']);
                            $debet+=+floatval($line['debet']);
                            $kredit+=+floatval($line['kredit']);
                            $so_akhir_debet+=+floatval($line['so_akhir_debet']);
                            $so_akhir_kredit+=+floatval($line['so_akhir_kredit']);
                        @endphp
                        <tr>
                            <td class='isi_laporan' align='center'>{{ $no }}</td>
                            <td class='isi_laporan'>{{ $line['kode_akun'] }}</td>
                            <td height='20' class='isi_laporan'>{{ $line['nama'] }}
                            </td>
                            <td class='isi_laporan' align='right'>{{ $line['so_awal_debet'] }}</td>
                            <td class='isi_laporan' align='right'>{{ $line['so_awal_kredit'] }}</td>
                            <td class='isi_laporan' align='right'>{{ $line['debet'] }}</td>
                            <td class='isi_laporan' align='right'>{{ $line['kredit'] }}</td>
                            <td class='isi_laporan' align='right'>{{ $line['so_akhir_debet'] }}</td>
                            <td class='isi_laporan' align='right'>{{ $line['so_akhir_kredit'] }}</td>
                        </tr>
                    @endfor
                <tr>
                    <td height='20' colspan='3' class='sum_laporan' align='right'>Total</td>
                    <td class='sum_laporan' align='right'>{{ number_format($so_awal_debet,0,".",",") }}</td>
                    <td class='sum_laporan' align='right'>{{ number_format($so_awal_kredit,0,".",",") }}</td>
                    <td class='sum_laporan' align='right'>{{ number_format($debet,0,".",",") }}</td>
                    <td class='sum_laporan' align='right'>{{ number_format($kredit,0,".",",") }}</td>
                    <td class='sum_laporan' align='right'>{{ number_format($so_akhir_debet,0,".",",") }}</td>
                    <td class='sum_laporan' align='right'>{{ number_format($so_akhir_kredit,0,".",",") }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
   