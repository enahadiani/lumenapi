<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $sql = 'tokoaws';

    public function convertDate($date, $from = '-', $to = '/')
    {
        $explode = explode($from, $date);
        return "$explode[2]" . "$to" . "$explode[1]" . "$to" . "$explode[0]";
    }

    public function sendMail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required'
        ]);

        $email = $request->email;
        try {
            $rs = $this->getNrcLajur($request);
            $res = json_decode(json_encode($rs), true);
            $data_array = $res["original"]["data"];
            $res = Mail::to($email)->send(new LaporanNrcLajur($data_array));

            return response()->json(array('status' => true, 'message' => 'Sent successfully', 'res' => $res), $this->successStatus);
        } catch (Exception $ex) {
            return response()->json(array('status' => false, 'message' => 'Something went wrong, please try later.'), $this->successStatus);
        }
    }

    function getReportRekapPenjualan(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'tanggal', 'nik_kasir');
            $db_col_name = array('a.periode', 'a.tanggal', 'a.nik_user');

            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.nik_user 
            from brg_jualpiu_dloc a 
            $where
            group by a.nik_user";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $kasir = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $kasir .= "'$row->nik_user'";
                } else {
                    $kasir .= "," . "'$row->nik_user'";
                }
                $i++;
            }

            if (count($res) > 0) {
                $tgl_filter = null;
                if ($request->input($col_array[1])[0] == "=" && isset($request->input($col_array[1])[1])) {
                    $tgl_filter = "and a.tanggal = '" . $request->input($col_array[1])[1] . "'";
                } elseif ($request->input($col_array[1])[0] == "range" && isset($request->input($col_array[1])[1]) && isset($request->input($col_array[1])[2])) {
                    $tgl_filter = "and a.tanggal between '" . $request->input($col_array[1])[1] . "' and '" . $request->input($col_array[1])[2] . "'";
                }

                $sql3 = "select a.tanggal 
                from brg_jualpiu_dloc a 
                where a.kode_lokasi = '" . $kode_lokasi . "' and  a.nik_user in ($kasir) and a.periode = '" . $request->input($col_array[0])[1] . "'
                $tgl_filter
                group by a.tanggal";
                $rs3 = DB::connection($this->sql)->select($sql3);
                $res3 = json_decode(json_encode($rs3), true);

                $tgl = "";
                $resdata = array();
                $i = 0;
                foreach ($rs3 as $row) {
                    $resdata[] = (array)$row;
                    if ($i == 0) {
                        $tgl .= "'$row->tanggal'";
                    } else {
                        $tgl .= "," . "'$row->tanggal'";
                    }
                    $i++;
                }
            }

            if (count($res3) > 0) {
                $sql2 = "select CONVERT(varchar,a.tanggal,103) as tanggal, a.tanggal as tgl, a.nik_user as kasir,
                sum(c.total) as total, sum(c.ppn) as ppn, sum(c.hpp) as hpp, count(a.no_jual) as struk,
                (sum(c.total) - sum(c.ppn)) as bersih, isnull(sum(d.brg_pajak),0) as brg_pajak, 
                isnull(sum(e.brg_non_pajak),0) as brg_non_pajak, '0' as margin, '0' as rata, '0' as persen
                from brg_jualpiu_dloc a
                left join (
                select no_bukti, kode_lokasi, isnull(sum(total),0) as total, 
                isnull(sum(ppn),0) as ppn, isnull(sum(hpp),0) as hpp
                from brg_trans_d
                where kode_lokasi = '" . $kode_lokasi . "' and form='BRGJUAL'
                group by no_bukti, kode_lokasi
                ) c on a.kode_lokasi=c.kode_lokasi and a.no_jual=c.no_bukti
                left join (
                    select no_bukti, kode_lokasi, isnull(sum(ppn),0) as brg_pajak
                    from brg_trans_d
                    where kode_lokasi = '" . $kode_lokasi . "' and form='BRGJUAL' and ppn > 0
                    group by no_bukti, kode_lokasi
                ) d on a.kode_lokasi=d.kode_lokasi and a.no_jual=d.no_bukti
                left join (
                    select no_bukti, kode_lokasi, isnull(sum(ppn),0) as brg_non_pajak
                    from brg_trans_d
                    where kode_lokasi = '" . $kode_lokasi . "' and form='BRGJUAL' and ppn = 0
                    group by no_bukti, kode_lokasi
                ) e on a.kode_lokasi=e.kode_lokasi and a.no_jual=e.no_bukti
                where a.kode_lokasi = '" . $kode_lokasi . "' and a.tanggal in ($tgl) and a.nik_user in ($kasir)
                group by a.tanggal, a.nik_user
                order by a.tanggal asc";

                $res2 = DB::connection($this->sql)->select($sql2);
                $res2 = json_decode(json_encode($res2), true);
            }

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportBarang(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('no_bukti');
            $db_col_name = array('a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.kode_barang,a.nama,a.sat_kecil as satuan,a.hna as harga,a.hrg_satuan,a.ppn,a.profit,a.barcode,a.kode_klp
            from brg_barang a $where ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportClosing(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'tanggal', 'nik_kasir', 'no_bukti');
            $db_col_name = array("substring(convert(varchar(10),a.tgl_input,121),1,4)+''+substring(convert(varchar(10),a.tgl_input,121),6,2)", 'convert(date,a.tgl_input)', 'a.nik_user', 'a.no_close');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.no_close as no_bukti_close, b.no_open as no_bukti_open, 
            convert(varchar,a.tgl_input,103) as tanggal_close, convert(varchar,b.tgl_input,103) as tanggal_open,
            convert(char(5), a.tgl_input, 108) as jam_close, convert(char(5), b.tgl_input, 108) as jam_open, 
            b.saldo_awal, a.total_pnj, a.nik_user, convert(varchar(10),a.tgl_input,121) as tanggal
            from kasir_close a
            inner join kasir_open b on a.no_close=b.no_close and a.kode_lokasi=b.kode_lokasi 
            $where";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);
            $nb = "";
            $tanggal = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $tanggal .= "'$row->tanggal'";
                    $nb .= "'$row->no_bukti_close'";
                } else {
                    $tanggal .= ", '$row->tanggal'";
                    $nb .= "," . "'$row->no_bukti_close'";
                }
                $i++;
            }

            // $sql2="select no_jual,tanggal,keterangan,periode,nilai,diskon,no_close as no_bukti from brg_jualpiu_dloc
            // where kode_lokasi = '".$kode_lokasi."' and no_close in ($nb) and tanggal in ($tanggal)" ;
            $sql2 = "select no_jual,tanggal,keterangan,periode,case when isnull(kode_jenis,'-') in ('JB01','-') then nilai else 0 end as cash,case when kode_jenis='JB02' then nilai else 0 end as qris,case when kode_jenis='JB03' then nilai else 0 end as link_aja,diskon,no_close as no_bukti from brg_jualpiu_dloc
            where kode_lokasi = '" . $kode_lokasi . "' and no_close in ($nb)";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLapSaldoStok(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_gudang', 'kode_klp', 'kode_barang');
            $db_col_name = array('substring(convert(varchar(10),a.tgl_input,112),1,6)', 'c.kode_gudang', 'b.kode_klp', 'a.kode_barang');
            $where = "where a.kode_lokasi='$kode_lokasi'";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode')[1];
            if ($periode == "") {
                $periode = date('Ym');
            }

            $sql1 = "exec sp_brg_stok_mutasi '$periode','$kode_lokasi','$nik_user' ";
            // $sql2 = "exec sp_brg_hpp '$periode','$kode_lokasi','$nik_user' ";
            DB::connection($this->sql)->update($sql1);
            // DB::connection($this->sql)->update($sql2);

            // $sql3 = "SELECT DISTINCT a.kode_barang, a.kode_gudang, a.stok, a.kode_lokasi, ISNULL(a.so_awal, 0) AS so_awal, 
            // ISNULL(a.debet, 0) AS debet, ISNULL(a.kredit, 0) AS kredit, d.h_avg, d.h_avg*a.stok AS nilai, b.sat_kecil, 
            // b.nama AS nama_barang,c.nama AS nama_gudang
            // FROM brg_stok a
            // INNER JOIN brg_barang b ON a.kode_barang=b.kode_barang AND a.kode_lokasi=b.kode_lokasi 
            // INNER JOIN brg_gudang c ON a.kode_gudang=c.kode_gudang AND a.kode_lokasi=c.kode_lokasi 
            // INNER JOIN brg_hpp d ON a.kode_lokasi=d.kode_lokasi AND a.kode_barang=d.kode_barang AND a.nik_user=d.nik_user
            // $where
            // ORDER BY a.kode_barang,a.kode_gudang";
            $sql3 = "SELECT DISTINCT a.kode_barang, a.kode_gudang, a.stok, a.kode_lokasi, ISNULL(a.so_awal, 0) AS so_awal, 
            ISNULL(a.debet, 0) AS debet, ISNULL(a.kredit, 0) AS kredit, d.h_avg, d.h_avg*a.stok AS nilai, b.sat_kecil, 
            b.nama AS nama_barang,c.nama AS nama_gudang
            FROM brg_stok a
            INNER JOIN brg_barang b ON a.kode_barang=b.kode_barang AND a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            INNER JOIN brg_gudang c ON a.kode_gudang=c.kode_gudang AND a.kode_lokasi=c.kode_lokasi 
            INNER JOIN brg_hpp d ON a.kode_lokasi=d.kode_lokasi AND a.kode_barang=d.kode_barang
            $where
            ORDER BY a.kode_barang,a.kode_gudang";

            $rs = DB::connection($this->sql)->select($sql3);
            $res = json_decode(json_encode($rs), true);
            // $nb = "";
            // $nb2 = "";
            // $resdata = array();
            // $i=0;

            // foreach($rs as $row){

            //     $resdata[]=(array)$row;
            //     if($i == 0){
            //         $nb .= "'$row->kode_barang'";
            //         $nb2 .= "'$row->kode_gudang'";
            //     }else{
            //         $nb .= ","."'$row->kode_barang'";
            //         $nb2 .= ","."'$row->kode_gudang'";
            //     }
            //     $i++;
            // }

            // $sql4="select * from (select convert(varchar(20),a.tgl_ed,103) as tgl , a.no_bukti, b.keterangan, a.modul, a.stok,a.harga,b.param2,
            // case when a.dc='D' then a.jumlah else 0 end as debet,	      
            // case when a.dc='C' then a.jumlah else 0 end as kredit, a.tgl_ed
            // from brg_trans_d a
            // inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
            // inner join brg_barang c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi 
            // where a.kode_barang in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_gudang in ($nb2) and a.periode = '$periode'
            // union all
            // select convert(varchar(20),a.tgl_ed,103) as tgl , b.no_bukti, b.keterangan, a.modul, a.stok,a.harga,b.param2,
            // case when a.dc='D' then a.jumlah else 0 end as debet,	      
            // case when a.dc='C' then a.jumlah else 0 end as kredit, a.tgl_ed
            // from brg_trans_d a
            // inner join brg_jualpiu_d d on a.no_bukti=d.no_jual and a.kode_lokasi=d.kode_lokasi
            // inner join trans_m b on d.no_close=b.no_bukti and d.kode_lokasi=b.kode_lokasi 
            // inner join brg_barang c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi 
            // where a.kode_barang in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_gudang in ($nb2) and a.periode = '$periode' 
            // )a order by a.tgl_ed";
            // $res2 = DB::connection($this->sql)->select($sql4);
            // $res2 = json_decode(json_encode($res2),true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                // $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                // $success['data_detail'] = [];
                // $success['sql'] = $sql4;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getKartuStok(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('kode_gudang', 'kode_klp', 'kode_barang');
            $db_col_name = array('c.kode_gudang', 'b.kode_klp', 'a.kode_barang');
            $where = "where a.kode_lokasi='$kode_lokasi'";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode')[1];
            if ($periode == "") {
                $periode = date('Ym');
            }

            // $sql1 = "exec sp_brg_stok '$periode','$kode_lokasi','$nik_user' ";
            $sql2 = "exec sp_brg_stok_mutasi '$periode','$kode_lokasi','$nik_user' ";
            // DB::connection($this->sql)->update($sql1);
            DB::connection($this->sql)->update($sql2);
            $success['exec'] = $sql2;

            // $sql3 = "select distinct a.kode_barang,a.kode_gudang,a.stok,a.kode_lokasi,a.so_awal,a.debet,a.kredit,d.h_avg,d.h_avg*a.stok as nilai,b.sat_kecil, 
            //     b.nama as nama_barang,c.nama as nama_gudang
            //     from brg_stok a
            //     inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
            //     inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi 
            //     inner join brg_hpp d on a.kode_lokasi=d.kode_lokasi and a.kode_barang=d.kode_barang and a.nik_user=d.nik_user
            //     $where
            //     order by a.kode_barang,a.kode_gudang";
            // $sql3 = "select distinct a.kode_barang,a.kode_gudang,a.stok,a.kode_lokasi,a.so_awal,a.debet,a.kredit,d.h_avg,d.h_avg*a.stok as nilai,b.sat_kecil, 
            //     b.nama as nama_barang,c.nama as nama_gudang
            //     from brg_stok a
            //     inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
            //     inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi 
            //     inner join brg_hpp d on a.kode_lokasi=d.kode_lokasi and a.kode_barang=d.kode_barang
            //     $where
            //     order by a.kode_barang,a.kode_gudang";
            $sql3 = "select a.kode_barang,b.nama as nama_barang,a.stok,a.kode_gudang,c.nama as nama_gudang,b.kode_klp,isnull(d.h_avg,0) as harga, a.stok*isnull(d.h_avg,0) as total
            from brg_stok a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi
            left join brg_hpp d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.nik_user=d.nik_user
            $where and a.nik_user='$nik_user'
            order by b.kode_klp,a.kode_barang";

            $rs = DB::connection($this->sql)->select($sql3);
            $res = json_decode(json_encode($rs), true);
            $nb = "";
            $nb2 = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $nb .= "'$row->kode_barang'";
                    $nb2 .= "'$row->kode_gudang'";
                } else {
                    $nb .= "," . "'$row->kode_barang'";
                    $nb2 .= "," . "'$row->kode_gudang'";
                }
                $i++;
            }

            $sql4 = "select distinct * from (select a.kode_barang,convert(varchar(20),a.tgl_ed,103) as tgl , a.no_bukti, b.keterangan, a.modul, a.stok,a.harga,b.param2,
                case when a.dc='D' then a.jumlah else 0 end as debet,	      
                case when a.dc='C' then a.jumlah else 0 end as kredit, a.tgl_ed
                from brg_trans_d a
                inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi 
                inner join brg_barang c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.pabrik
                where a.kode_barang in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_gudang in ($nb2) and a.periode = '$periode'
                union all
                select a.kode_barang,convert(varchar(20),a.tgl_ed,103) as tgl , b.no_bukti, b.keterangan, a.modul, a.stok,a.harga,b.param2,
                case when a.dc='D' then a.jumlah else 0 end as debet,	      
                case when a.dc='C' then a.jumlah else 0 end as kredit, a.tgl_ed
                from brg_trans_d a
                inner join brg_jualpiu_d d on a.no_bukti=d.no_jual and a.kode_lokasi=d.kode_lokasi
                inner join trans_m b on d.no_close=b.no_bukti and d.kode_lokasi=b.kode_lokasi 
                inner join brg_barang c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.pabrik
                where a.kode_barang in ($nb) and a.kode_lokasi='$kode_lokasi' and a.kode_gudang in ($nb2) and a.periode = '$periode' 
                )a order by a.tgl_ed";
            $res2 = DB::connection($this->sql)->select($sql4);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_detail'] = [];
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getStok(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('kode_gudang', 'kode_klp', 'kode_barang');
            $db_col_name = array('a.pabrik', 'a.kode_klp', 'a.kode_barang');
            $where = "where a.kode_lokasi='$kode_lokasi'";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $col_array = array('periode');
            $db_col_name = array('periode');
            $where2 = "where kode_lokasi='$kode_lokasi'";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where2 .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where2 .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where2 .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode')[1];
            if ($periode == "") {
                $periode = date('Ym');
            }

            //  $sql1 = "exec sp_brg_stok '$periode','$kode_lokasi','$nik_user' ";
            // $sql2 = "exec sp_brg_stok_mutasi '$periode','$kode_lokasi','$nik_user' ";
            //  DB::connection($this->sql)->update($sql1);
            // DB::connection($this->sql)->update($sql2);
            // $success['exec'] = $sql2;

            // $sql3 = "select distinct a.kode_barang,a.kode_gudang,a.stok,a.kode_lokasi,a.so_awal,a.debet,a.kredit,d.h_avg,d.h_avg*a.stok as nilai,b.sat_kecil, 
            //     b.nama as nama_barang,c.nama as nama_gudang
            //     from brg_stok a
            //     inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
            //     inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi 
            //     inner join brg_hpp d on a.kode_lokasi=d.kode_lokasi and a.kode_barang=d.kode_barang and a.nik_user=d.nik_user
            //     $where
            //     order by a.kode_barang,a.kode_gudang";
            // $sql3 = "select distinct a.kode_barang,a.kode_gudang,a.stok,a.kode_lokasi,a.so_awal,a.debet,a.kredit,d.h_avg,d.h_avg*a.stok as nilai,b.sat_kecil, 
            //     b.nama as nama_barang,c.nama as nama_gudang
            //     from brg_stok a
            //     inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
            //     inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi 
            //     inner join brg_hpp d on a.kode_lokasi=d.kode_lokasi and a.kode_barang=d.kode_barang
            //     $where
            //     order by a.kode_barang,a.kode_gudang";
            // $sql3 = "select a.kode_barang,b.nama as nama_barang,a.stok,a.kode_gudang,c.nama as nama_gudang,
            //  b.kode_klp,isnull(d.h_avg,0) as harga, a.stok*isnull(d.h_avg,0) as total,isnull(a.so_awal,0) as so_awal,
            //  isnull(a.debet,0) as debet,isnull(a.kredit,0) as kredit, b.hpp, (a.stok * b.hpp) as saldo_persediaan
            //  from brg_stok a
            //  inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            //  inner join brg_gudang c on a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi
            //  left join brg_hpp d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.nik_user=d.nik_user
            //  $where and a.nik_user='$nik_user'
            //  order by b.kode_klp,a.kode_barang";

            $sql3 = "select a.pabrik,a.kode_barang,a.nama, a.sat_kecil, 
            isnull(b.jumlah,0) as sawal, 
            isnull(b.harga,0) as hawal, 
            isnull(b.jumlah,0) * isnull(b.harga,0) as nilai_sawal,
            
            isnull(c.masuk,0) as masuk, 
            isnull(c.keluar,0) as keluar, 
            isnull(b.jumlah,0) + isnull(c.masuk,0) - isnull(c.keluar,0) as sakhir 
            
            from brg_barang a 
            left join brg_sawal b on a.kode_barang = b.kode_barang and b.kode_gudang=a.pabrik and a.kode_lokasi=b.kode_lokasi and b.periode ='$periode' 
            left join ( 
                        select kode_barang,kode_gudang,kode_lokasi
                        ,sum(case dc when 'D' then jumlah else 0 end) as masuk 
                        ,sum(case dc when 'C' then jumlah else 0 end) as keluar 
                        from brg_trans_d 
                        $where2
                        group by kode_barang,kode_gudang,kode_lokasi
                    ) c on a.kode_barang=c.kode_barang and a.pabrik=c.kode_gudang and a.kode_lokasi=c.kode_lokasi  
            $where ";

            $rs = DB::connection($this->sql)->select($sql3);
            $res = json_decode(json_encode($rs), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_detail'] = [];
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getPosisiStok(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode')[1];
            $kode_lokasi = isset($request->kode_lokasi) && $request->kode_lokasi[1] != "" ? $request->input('kode_lokasi')[1] : $kode_lokasi;
            $kode_gudang = $request->input('kode_gudang')[1];
            if ($periode == "") {
                $periode = date('Ym');
            }

            $sql1 = "exec sp_brg_stok_gudang_bulan '$kode_gudang', '$periode','$kode_lokasi', '$nik_user';";
            $sql2 = "exec sp_brg_hpp_periodik '$periode','$kode_lokasi','$nik_user','$kode_gudang';";

            $sqlex="SET NOCOUNT ON; ".$sql1;
            $dbh = DB::connection($this->sql)->getPdo();
            $sth = $dbh->prepare($sqlex);
            $sth->execute();

            $sqlex2="SET NOCOUNT ON; ".$sql2;
            $dbh = DB::connection($this->sql)->getPdo();
            $sth = $dbh->prepare($sqlex2);
            $sth->execute();

            $sql3 = "select a.kode_barang,c.nama,a.kode_gudang,d.nama as gudang,a.so_awal,a.debet,a.kredit,a.stok,round(b.h_avg,0) as h_avg,
                round(a.stok * b.h_avg,0) as nilai_stok
            from brg_stok a
            inner join brg_barang c on a.kode_barang=c.kode_barang and a.kode_gudang=c.pabrik
            inner join brg_gudang d on a.kode_gudang=d.kode_gudang
            left join brg_hpp b on a.kode_barang=b.kode_barang and a.kode_gudang='$kode_gudang' and a.kode_lokasi=b.kode_lokasi and b.nik_user='$nik_user'
            where a.nik_user = '$nik_user' and a.kode_gudang='$kode_gudang'";

            $rs = DB::connection($this->sql)->select($sql3);
            $res = json_decode(json_encode($rs), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_detail'] = [];
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportPenjualan(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'tanggal', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.tanggal', 'a.nik_user', 'a.no_jual');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select distinct a.no_jual,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai,b.nilai as nilai2,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir,a.tobyr,a.diskon
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='" . $kode_lokasi . "' and form='BRGJUAL'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $where
            order by a.no_jual";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $nb = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $nb .= "'$row->no_jual'";
                } else {

                    $nb .= "," . "'$row->no_jual'";
                }
                $i++;
            }

            $sql2 = "select distinct a.no_bukti,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,a.jumlah,a.bonus,a.harga,a.diskon,(a.harga)*a.jumlah-a.diskon as total
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            where a.kode_lokasi = '" . $kode_lokasi . "'  and a.no_bukti in ($nb) 
            order by a.no_bukti
            ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportPembelian(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.nik_user', 'a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select DISTINCT a.no_bukti,a.tanggal,e.keterangan,e.param2 as kode_vendor,b.nama as nama_vendor ,d.kode_gudang,c.nama as nama_pp
            ,a.nik_user,f.nama as nama_user,a.kode_lokasi,e.nilai1 as total,e.nilai3 as diskon,e.nilai2 as ppn
            from trans_j a
            left join brg_trans_d d on a.no_bukti=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            inner join brg_gudang c on d.kode_gudang=c.kode_gudang and d.kode_lokasi=c.kode_lokasi
            inner join trans_m e on e.no_bukti=a.no_bukti and e.kode_lokasi=a.kode_lokasi
            left join vendor b on e.param2=b.kode_vendor and e.kode_lokasi=b.kode_lokasi
            inner join karyawan f on a.nik_user=f.nik and a.kode_lokasi=f.kode_lokasi
            $where and e.form='BRGBELI' 
            order by a.no_bukti";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $nb = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $nb .= "'$row->no_bukti'";
                } else {

                    $nb .= "," . "'$row->no_bukti'";
                }
                $i++;
            }

            $sql2 = "select a.no_bukti,a.kode_barang,b.nama as nama_brg,a.satuan,a.jumlah,a.bonus,a.tot_diskon,a.harga,a.total
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            where a.kode_lokasi = '" . $kode_lokasi . "' and a.no_bukti in ($nb) 
            order by a.kode_barang
            ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportPenjualanHarian(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'tanggal', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.tanggal', 'a.nik_user', 'a.no_jual');

            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir,sum(a.nilai) as nilai
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='$kode_lokasi' and (form='BRGJUAL' or form='BRGRETJ')
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $where
            group by a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama,d.nama,e.nama,a.nik_user";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $tgl = "";
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                if ($i == 0) {
                    $tgl .= "'$row->tanggal'";
                } else {

                    $tgl .= "," . "'$row->tanggal'";
                }
                $i++;
            }
            $nik_filter = null;
            if ($request->input($col_array[2])[0] == "=" && isset($request->input($col_array[2])[1])) {
                $nik_filter = "and c.nik_user = '" . $request->input($col_array[2])[1] . "'";
            }

            $sql2 = "select c.tanggal,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,sum(a.jumlah) as jumlah,
            sum(a.bonus) as bonus,a.harga,sum(a.diskon) as diskon,sum((a.harga*a.jumlah)-a.diskon) as total,sum(a.total) as total_ex,
            '0' as hpp,'0' as stok_akhir, '-' as keterangan, c.nik_user,a.dc
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
			inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and c.tanggal in ($tgl) $nik_filter
			group by c.tanggal,a.kode_barang,b.nama,b.sat_kecil,a.harga,c.nik_user,a.dc
            order by c.tanggal";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res2) > 0) {
                for ($i = 0; $i < count($res2); $i++) {
                    if ($res2[$i]['dc'] == 'D') {
                        $res2[$i]['jumlah'] = -1 * $res2[$i]['jumlah'];
                        $res2[$i]['total'] = -1 * $res2[$i]['total'];
                        $res2[$i]['total_ex'] = -1 * $res2[$i]['total_ex'];
                    }
                }
            }

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportPenjualanHarianV2(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'tanggal', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.tanggal', 'a.nik_user', 'a.no_jual');

            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir,sum(a.nilai) as nilai,a.no_close
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='$kode_lokasi' and form='BRGJUAL'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $where
            group by a.tanggal,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama,d.nama,e.nama,a.nik_user,a.no_close";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $tgl = "";
            $kasir = array();
            $resdata = array();
            $i = 0;
            foreach ($rs as $row) {

                $resdata[] = (array)$row;
                array_push($kasir, $row->nik_kasir);
                if ($i == 0) {
                    $tgl .= "'$row->tanggal'";
                } else {

                    $tgl .= "," . "'$row->tanggal'";
                }
                $i++;
            }
            $nik_filter = null;
            if ($request->input($col_array[2])[0] == "=" && isset($request->input($col_array[2])[1])) {
                $nik_filter = "and c.nik_user = '" . $request->input($col_array[2])[1] . "'";
            }

            // $sql2="select a.no_bukti,b.sat_kecil as satuan,c.tanggal,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,sum(a.jumlah) as jumlah,
            // sum(a.bonus) as bonus,a.harga,sum(a.diskon) as diskon,sum((a.harga*a.jumlah)-a.diskon) as total,sum(a.total) as total_ex, sum(a.hpp) as hpp,
            // '0' as stok_akhir, '-' as keterangan
            // from brg_trans_d a
            // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            // inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
            // where a.kode_lokasi='$kode_lokasi' and c.tanggal in ($tgl) $nik_filter
            // group by c.tanggal,a.kode_barang,b.nama,b.sat_kecil,a.harga,a.no_bukti,b.sat_kecil
            // order by c.tanggal";
            // $sql2 = "select b.sat_kecil as satuan,c.tanggal,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,sum(a.jumlah) as jumlah,
            // sum(a.bonus) as bonus,a.harga,sum(a.diskon) as diskon,sum((a.harga*a.jumlah)-a.diskon) as total,sum(a.total) as total_ex, sum(a.hpp) as hpp,
            // isnull(d.so_akhir, 0) as stok_akhir, '-' as keterangan,c.nik_user
            // from brg_trans_d a
            // inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            // inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
            // left join brg_lap_harian d on a.kode_lokasi=d.kode_lokasi and a.no_close=d.no_bukti and a.kode_barang=d.kode_barang
            // where a.kode_lokasi='$kode_lokasi' and c.tanggal in ($tgl) $nik_filter
            // group by c.tanggal,a.kode_barang,b.nama,b.sat_kecil,a.harga,b.sat_kecil,c.nik_user,d.so_akhir
            // order by c.tanggal";
            $sql2 = "select e.no_bukti,b.sat_kecil as satuan,c.tanggal,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,sum(a.jumlah) as jumlah,
            sum(a.bonus) as bonus,a.harga,sum(a.diskon) as diskon,sum((a.harga*a.jumlah)-a.diskon) as total,sum(a.total) as total_ex, sum(a.hpp) as hpp,
            e.stok_akhir, '-' as keterangan,c.nik_user
            from brg_trans_d a
            inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            inner join brg_jualpiu_dloc c on a.no_bukti=c.no_jual and a.kode_lokasi=c.kode_lokasi
			left join (select a.no_bukti, a.kode_barang, isnull(sum(a.so_akhir),0) as stok_akhir, a.kode_lokasi from brg_lap_harian a
			where a.kode_lokasi = '" . $kode_lokasi . "'
			group by a.no_bukti, a.kode_barang,a.kode_lokasi) e on a.kode_lokasi=e.kode_lokasi and a.no_close=e.no_bukti and a.kode_barang=e.kode_barang
            where a.kode_lokasi='" . $kode_lokasi . "' and c.tanggal in ($tgl) $nik_filter
            group by e.stok_akhir,c.tanggal,a.kode_barang,b.nama,b.sat_kecil,a.harga,b.sat_kecil,c.nik_user,e.no_bukti
            order by c.tanggal";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['tanggal'] = $this->convertDate(date('Y-m-d'));
                $success['jam'] = date('H:i');
                $success['kasir'] = $kasir;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportReturBeli(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.nik_user', 'a.no_bukti');

            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.no_bukti,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai1,b.nilai as nilai2,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir
            from trans_m a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then -total else total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='$kode_lokasi' and form='BRGRETBELI'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp 
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $where and a.form='BRGRETBELI'
            order by a.no_bukti";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            $res2 = [];
            if (count($res) > 0) {
                $nb = "";
                $resdata = array();
                $i = 0;
                foreach ($rs as $row) {

                    $resdata[] = (array)$row;
                    if ($i == 0) {
                        $nb .= "'$row->no_bukti'";
                    } else {

                        $nb .= "," . "'$row->no_bukti'";
                    }
                    $i++;
                }

                $sql2 = "select distinct a.no_bukti,a.kode_barang,b.nama as nama_brg,b.sat_kecil as satuan,a.jumlah,a.bonus,a.harga,a.diskon,(a.harga)*a.jumlah-a.diskon as total,a.stok
                from brg_trans_d a
                inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
                where a.kode_lokasi='$kode_lokasi'  and a.form='BRGRETBELI' and a.no_bukti in ($nb)
                order by a.no_bukti
                ";
                $res2 = DB::connection($this->sql)->select($sql2);
                $res2 = json_decode(json_encode($res2), true);
            }

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportReturJual(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'nik_kasir', 'no_bukti');
            $db_col_name = array('a.periode', 'a.nik_user', 'a.no_jual');

            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "select a.no_jual,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.nilai,b.nilai as nilai2,b.kode_gudang,a.periode,a.nik_user,a.kode_pp,c.nama as nama_pp,d.nama as nama_user,e.nama as nama_gudang,a.nik_user as nik_kasir
            from brg_jualpiu_dloc a 
            left join ( select no_bukti,kode_gudang,kode_lokasi,sum(case when dc='D' then total else -total end) as nilai
                        from brg_trans_d 
                        where kode_lokasi='$kode_lokasi' and form='BRGRETJUAL'
                        group by no_bukti,kode_gudang,kode_lokasi
                        ) b on a.no_jual=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            left join karyawan d on a.nik_user=d.nik and a.kode_lokasi=d.kode_lokasi
            left join brg_gudang e on b.kode_gudang=e.kode_gudang and b.kode_lokasi=e.kode_lokasi
            $where
            order by a.no_jual";
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs), true);

            if (count($res) > 0) {
                $nb = "";
                $resdata = array();
                $i = 0;
                foreach ($rs as $row) {

                    $resdata[] = (array)$row;
                    if ($i == 0) {
                        $nb .= "'$row->no_jual'";
                    } else {

                        $nb .= "," . "'$row->no_jual'";
                    }
                    $i++;
                }
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]['data_detail'] = [];

                    $sql2 = "select distinct a.no_bukti,
                    a.kode_barang, b.nama as nama_brg, b.sat_kecil as satuan, 
                    -a.jumlah as jumlah, a.bonus, a.harga, a.diskon, a.stok,
                    (a.harga*a.jumlah)-a.diskon as total 
                    from brg_trans_d a
                    inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
                    where a.kode_lokasi='$kode_lokasi'  and a.form='BRGRETJ' and a.no_bukti = '" . $res[$i]['no_jual'] . "'
                    order by a.no_bukti";
                    $res2 = DB::connection($this->sql)->select($sql2);
                    $res2 = json_decode(json_encode($res2), true);

                    $res[$i]['data_detail'] = $res2;
                }
            }

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportBukuBesar(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_akun', 'kode_pp');
            $db_col_name = array('a.periode', 'a.kode_akun', 'a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for ($i = 0; $i < count($col_array); $i++) {
                if ($request->input($col_array[$i]) != "") {
                    $filter .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i]) . "' ";
                }
            }

            $get = DB::connection($this->sql)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if (count($get) > 0) {
                $kode_pp = $get[0]->kode_pp;
            } else {
                $kode_pp = "-";
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode');
            if ($periode == "") {
                $periode = date('Ym');
            }

            $sql = "exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);

            $tmp = "";
            if (isset($request->jenis) && $request->jenis == "Tidak") {
                $tmp = " and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }

            $sql = "select a.kode_lokasi,a.kode_akun,a.kode_pp,a.nama,a.so_awal,a.periode,b.nama as nama_pp
            from glma_pp_tmp a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.nik_user='$nik_user' and a.kode_lokasi='$kode_lokasi'  $tmp
            order by a.kode_akun
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if ($request->input('tgl_awal') != "" && $request->input('tgl_akhir') != "") {
                $filter .= " and a.tanggal between '" . $request->input('tgl_awal') . "' and '" . $request->input('tgl_akhir') . "' ";
            }

            $sql2 = "select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $filter order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['sql'] = $sql;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportNeracaLajur(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_akun', 'kode_pp');
            $db_col_name = array('a.periode', 'a.kode_akun', 'a.kode_pp');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for ($i = 0; $i < count($col_array); $i++) {
                if ($request->input($col_array[$i]) != "") {
                    $filter .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i]) . "' ";
                }
            }

            $get = DB::connection($this->sql)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
            if (count($get) > 0) {
                $kode_pp = $get[0]->kode_pp;
            } else {
                $kode_pp = "-";
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode');
            if ($periode == "") {
                $periode = date('Ym');
            }

            $sql = "exec sp_trans_pp_tmp '$kode_lokasi','$kode_pp','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);

            $mutasi = "";
            if ($request->input('jenis') != "") {

                if ($request->input('jenis') == "Tidak") {
                    $mutasi = "and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql = "select a.kode_akun,a.nama,a.kode_pp,b.nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from glma_pp_tmp a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            $filter and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
            if ($request->input('trail') != "") {

                if ($request->input('trail') == "1") {
                    $sql = "select a.kode_akun,a.nama,a.kode_lokasi,a.kode_pp,c.nama as nama_pp,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
                if ($request->input('trail') == "2") {
                    $sql = "select a.kode_akun,a.nama,a.kode_pp,c nama as nama_pp,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                    case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                    case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                    case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                    case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                    from glma_pp_tmp a
                    inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    $filter and a.nik_user='$nik_user' $mutasi
                    order by a.kode_akun";
                }
            }
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlReportLabaRugi(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_fs');
            $db_col_name = array('a.periode', 'a.kode_fs');
            $filter = "where a.kode_lokasi='$kode_lokasi'";
            for ($i = 0; $i < count($col_array); $i++) {
                if ($request->input($col_array[$i]) != "") {
                    $filter .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i]) . "' ";
                }
            }

            $nik_user = $nik . "_" . uniqid();
            $periode = $request->input('periode');
            $kode_fs = $request->input('kode_fs');

            $sql = "exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);
            $success['sql'] = $sql;
            $sql = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                        case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
            $success['sql2'] = $sql;
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBuktiJurnal(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'modul', 'no_bukti');
            $db_col_name = array('a.periode', 'a.modul', 'a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            if ($request->input('tgl_awal') != "" && $request->input('tgl_akhir') != "") {
                $filter .= " and a.tanggal between '" . $request->input('tgl_awal') . "' and '" . $request->input('tgl_akhir') . "' ";
            }


            $sql = "select a.no_bukti,a.keterangan,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,
                        a.nik1,a.nik2,b.nama as nama1,c.nama as nama2
                from trans_m a 
                left join karyawan b on a.nik1=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik1=c.nik and a.kode_lokasi=c.kode_lokasi
                $where order by a.no_bukti ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            $sql = "select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit,c.nama as nama_pp
                from trans_j a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql);
            $res2 = json_decode(json_encode($res2), true);

            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJurnal(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'modul', 'no_bukti');
            $db_col_name = array('a.periode', 'a.modul', 'a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            if ($request->input('tgl_awal') != "" && $request->input('tgl_akhir') != "") {
                $filter .= " and a.tanggal between '" . $request->input('tgl_awal') . "' and '" . $request->input('tgl_akhir') . "' ";
            }

            $sql = "select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBukuBesar(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_akun');
            $db_col_name = array('a.periode', 'a.kode_akun');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];

            $sqlex = "exec sp_glma_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sqlex);

            $tmp = "";
            if (isset($request->mutasi[1]) && $request->mutasi[1] == "Tidak") {
                $tmp = " and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
            }

            $sql = "select a.kode_lokasi,a.kode_akun,b.nama,a.so_awal,a.periode
                from glma_tmp a
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where and a.nik_user='$nik_user' $tmp
                order by a.kode_akun ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if ($request->input('tgl_awal') != "" && $request->input('tgl_akhir') != "") {
                $where .= " and a.tanggal between '" . $request->input('tgl_awal') . "' and '" . $request->input('tgl_akhir') . "' ";
            }

            $sql2 = "select a.kode_akun,a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.kode_pp,a.kode_akun,b.nama as nama_akun,a.no_dokumen,a.modul, 
                case when a.dc='D' then a.nilai else 0 end as debet,
                case when a.dc='C' then a.nilai else 0 end as kredit 
                from gldt a 
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                $where order by a.no_bukti ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2), true);

            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;
            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                $success['data'] = [];
                $success['data_detail'] = [];
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNrcLajur(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_akun', 'kode_neraca', 'kode_fs');
            $db_col_name = array('a.periode', 'a.kode_akun', 'b.kode_neraca', 'b.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            // for($i = 0; $i<count($col_array); $i++){
            //     if($request->input($col_array[$i]) !=""){
            //         $filter .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])."' ";
            //     }
            // }
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }


            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];

            $sqlex = "exec sp_glma_tmp '$kode_lokasi','$periode','$nik_user' ";
            $res = DB::connection($this->sql)->update($sqlex);

            $mutasi = "";
            if ($request->input('jenis') != "") {

                if ($request->input('jenis') == "Tidak") {
                    $mutasi = "and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) ";
                }
            }

            $sql = "select a.kode_akun,b.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
            case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
            case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
            case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
            case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
            from glma_tmp a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where and a.nik_user='$nik_user'  $mutasi
            order by a.kode_akun ";
            if (isset($request->trail[1])) {
                if ($request->input('trail')[1] != "") {

                    if ($request->input('trail')[1] == "1") {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from glma_tmp a
                        inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where and a.nik_user='$nik_user' $mutasi
                        order by a.kode_akun";
                    }
                    if ($request->input('trail')[1] == "2") {
                        $sql = "select a.kode_akun,c.nama,a.kode_lokasi,a.debet,a.kredit,a.so_awal,so_akhir, 
                        case when a.so_awal>0 then so_awal else 0 end as so_awal_debet,
                        case when a.so_awal<0 then -so_awal else 0 end as so_awal_kredit, 
                        case when a.so_akhir>0 then so_akhir else 0 end as so_akhir_debet,
                        case when a.so_akhir<0 then -so_akhir else 0 end as so_akhir_kredit
                        from glma_tmp a
                        inner join konsol_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
                        $where and a.nik_user='$nik_user' $mutasi
                        order by a.kode_akun";
                    }
                }
            }
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;
            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNeraca(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_fs');
            $db_col_name = array('a.periode', 'a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }
            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];
            $kode_fs = $request->input('kode_fs')[1];
            $level = $request->input('level')[1];
            $format = $request->input('format')[1];

            $sql = "exec sp_neraca_dw '$kode_fs','A','K','$level','$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->getPdo()->exec($sql);

            $sql2 = "select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->sql)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode = "";
            if ($periode > $periode_aktif) {
                $nama_periode = "<br>(UnClosing)";
            }

            $sql3 = "select '$kode_lokasi' as kode_lokasi,kode_neraca1,kode_neraca2,nama1,tipe1,nilai1,level_spasi1,nama2,tipe2,nilai2,level_spasi2 
				from neraca_skontro 
				where nik_user='$nik_user' order by rowindex ";
            $nama = "";
            if ($format == "Mutasi") {
                $sql3 = "select '$kode_lokasi' as kode_lokasi,kode_neraca1,kode_neraca2,nama1,tipe1,nilai3 as nilai1,level_spasi1,nama2,tipe2,nilai4 as nilai2,level_spasi2 
                    from neraca_skontro 
                    where nik_user='$nik_user' order by rowindex ";
                $nama = "(MUTASI)";
            }

            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3), true);

            $success["nama_periode"] = $nama_periode;
            $success["nama"] = $nama;
            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;
            if (count($res3) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['res'] = $res;
                $success['sql3'] = $sql3;
                $success['sql'] = $sql;
                $success['status'] = true;

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugi(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'kode_fs');
            $db_col_name = array('a.periode', 'a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }
            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];
            $kode_fs = $request->input('kode_fs')[1];

            $sql = "exec sp_neraca_dw '$kode_fs','L','S',5,'$periode','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->update($sql);
            $success['sql'] = $sql;
            $sql = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                        case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
            $success['sql2'] = $sql;
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;
            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getNotaPnj(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode', 'no_bukti');
            $db_col_name = array('a.periode', 'a.no_jual');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }
            $nik_user = $request->nik_user;
            $sql = "select a.*,b.nama,b.alamat from brg_jualpiu_dloc a 
            left join brg_gudang b on a.kode_gudang=b.kode_gudang and a.kode_lokasi=b.kode_lokasi
            $where ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            $reslok = DB::connection($this->sql)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='" . $kode_lokasi . "'");
            $reslok = json_decode(json_encode($reslok), true);
            $success['lokasi'] = $reslok;
            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                for ($i = 0; $i < count($res); $i++) {

                    $sql = "select a.kode_barang,a.harga,a.jumlah,a.diskon*-1 as diskon,b.nama,b.sat_kecil,a.total from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik where a.no_bukti='" . $res[$i]['no_jual'] . "' and a.kode_lokasi='$kode_lokasi' ";
                    $res2 = DB::connection($this->sql)->select($sql);
                    $res[$i]['detail'] = json_decode(json_encode($res2), true);
                }
                $success['data'] = $res;

                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }
}
