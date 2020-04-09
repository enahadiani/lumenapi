<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body{
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            color:#333;
            text-align:left;
            font-size:10px;
            margin:0;
        }
        .container{
            /* margin:0 auto; */
            margin-top:25px;
            padding:10px;
            /* width:750px; */
            height:auto;
            background-color:#fff;
        }
        table{
            border:1px solid #333;
            border-collapse:collapse;
            /* margin:0 auto; */
            /* width:740px; */
        }
        td, tr, th{
            padding:4px;
            border:1px solid #333;
        }
        th{
            background-color: #f0f0f0;
        }
        h4, p{
            margin:0px;
        }
    </style>
</head>
<body>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Kode Klp Menu</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Status Admin</th>
                    <th>Kode Lokasi</th>
                    <th>Klp Akses</th>
                    <th>Menu Mobile</th>
                    <th>Path View</th>
                    <th>Kode Menu Lab</th>
                </tr>
            </thead>
            <tbody>
                
                @foreach ($user as $row)
                <tr>
                    <td>{{ $row->kode_klp_menu }}</td>
                    <td>{{ $row->nik }}</td>
                    <td>{{ $row->nama }}</td>
                    <td>{{ $row->status_admin }}</td>
                    <td>{{ $row->kode_lokasi }}</td>
                    <td>{{ $row->klp_akses }}</td>
                    <td>{{ $row->menu_mobile }}</td>
                    <td>{{ $row->path_view }}</td>
                    <td>{{ $row->kode_klp_menu }}</td>
                </tr>
                @endforeach
               
            </tbody>
        </table>
    </div>
</body>
</html>

