<?php
namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MutasiController extends Controller {
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getDataBarangMutasiKirim(Request $request) {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql1 = "select b.kode_barang,b.nama,a.satuan,a.jumlah as stok
                from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '$no_bukti' and a.kode_lokasi= '$kode_lokasi' order by a.nu";
            $res = DB::connection($this->sql)->select($sql1);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMutasiDetail(Request $request) {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "select tanggal, substring(no_bukti,1,2) as jenis, no_bukti, no_dokumen, keterangan, param1, param2 
            from trans_m where no_bukti = '$no_bukti' and kode_lokasi = '$kode_lokasi'";
            $res = DB::connection($this->sql)->select($sql1);
            $res = json_decode(json_encode($res),true);
            
            if($res[0]['jenis'] == "MK") {
                $sql2 = "select a.kode_barang,b.nama,a.satuan,a.jumlah,c.stok+a.jumlah as stok
                    from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    inner join brg_stok c on a.kode_barang=c.kode_barang and a.kode_gudang=c.kode_gudang and a.kode_lokasi=c.kode_lokasi and c.nik_user='$nik'
                    where a.no_bukti='$no_bukti' and a.kode_lokasi='$kode_lokasi'";
            } else {
                $sql2 = "select b.kode_barang,b.nama,a.satuan,c.jumlah as stok, a.jumlah
                    from brg_trans_d a
                    inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
                    inner join brg_trans_d c on c.no_bukti='$no_bukti'
                    and a.kode_lokasi=c.kode_lokasi and a.kode_barang=c.kode_barang
                    where a.no_bukti = '$no_bukti' and a.kode_lokasi='$kode_lokasi' order by a.nu";
            }
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $th) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataMutasiTerima() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,a.keterangan
                from trans_m a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='esaku'
                where a.kode_lokasi= '$kode_lokasi' and no_ref1='-' and form='BRGTERIMA'";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataMutasiKirim() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,a.keterangan
                from trans_m a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='esaku'
                where a.kode_lokasi= '$kode_lokasi' and no_ref1='-' and form='BRGKIRIM'";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function destroy(Request $request)
    {
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            
            DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            DB::connection($this->sql)->table('brg_trans_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Mutasi Barang berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mutasi gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function update(Request $request) {
        $this->validate($request, [
            'mutasi' => 'required|array',
            'mutasi.*.tanggal' => 'required',
            'mutasi.*.jenis' => 'required',
            'mutasi.*.no_bukti' => 'required',
            'mutasi.*.no_dokumen' => 'required',
            'mutasi.*.keterangan' => 'required',
            'mutasi.*.gudang_asal' => 'required',
            'mutasi.*.gudang_tujuan' => 'required',
            'mutasi.*.detail' => 'required|array',
            'mutasi.*.detail.*.kode_barang' => 'required',
            'mutasi.*.detail.*.satuan' => 'required',
            'mutasi.*.detail.*.stok' => 'required',
            'mutasi.*.detail.*.jumlah' => 'required'
        ]);

        try {
            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
            }

            $res = DB::connection($this->sql)
                    ->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'");
            $res = json_decode(json_encode($res),true);

            DB::connection($this->sql)->beginTransaction();
            
            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('mutasi');
            $periode = substr($data[0]['tanggal'],0,4).substr($data[0]['tanggal'],5,2);
            $no_bukti = $data[0]['no_bukti'];
            $sql1 = "exec sp_brg_stok '$periode', '$kode_lokasi', '$nik'";
            DB::connection($this->sql)->update($sql1);

            DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();
            DB::connection($this->sql)->table('brg_trans_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();

            if($data[0]['jenis'] == "KRM") {
                $sql2 = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,
                    posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,
                    nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync) 
                    values ('".$data[0]['no_bukti']."', '$kode_lokasi', getdate(), '$nik', '$periode', 'IV', 
                    'BRGKIRIM', 'X', '0', '0', '$kode_pp','".$data[0]['tanggal']."', '".$data[0]['no_dokumen']."', 
                    '".$data[0]['keterangan']."', 'IDR', '1', '0', '0', '0', '-', '-', '-', '-', '-', '-', 
                    '".$data[0]['gudang_asal']."', '".$data[0]['gudang_tujuan']."', '-', null, null, null)";
                
                DB::connection($this->sql)->insert($sql2);

                $data2 = $data[0]['detail'];
                if(count($data2) > 0) {
                    for($i=0;$i<count($data2);$i++) {
                        $stok = floatval($data2[$i]['stok']);
                        $jumlah = floatval($data2[$i]['jumlah']);
                        $sql3 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,
                            kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,
                            diskon,tot_diskon,total) values ('".$data[0]['no_bukti']."', '$kode_lokasi', '$periode', 'BRGKIRIM',
                            'BRGKIRIM', '$i', '".$data[0]['gudang_asal']."', '".$data2[$i]['kode_barang']."', '-', getdate(), 
                            '".$data2[$i]['satuan']."', 'C', '$stok', '$jumlah', '0','0','0','0','0','0','0')";
                        DB::connection($this->sql)->insert($sql3);
                    }
                }
            } else {
                $sql2 = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,
                    posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,
                    nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync) 
                    values ('".$data[0]['no_bukti']."', '$kode_lokasi', getdate(), '$nik', '$periode', 'IV', 
                    'BRGTERIMA', 'X', '0', '0', '$kode_pp','".$data[0]['tanggal']."', '".$data[0]['no_dokumen']."', 
                    '".$data[0]['keterangan']."', 'IDR', '1', '0', '0', '0', '-', '-', '-', '-', '-', '-', 
                    '".$data[0]['gudang_asal']."', '".$data[0]['gudang_tujuan']."', '-', null, null, null)";
                
                DB::connection($this->sql)->insert($sql2);

                $data2 = $data[0]['detail'];
                if(count($data2) > 0) {
                    for($i=0;$i<count($data2);$i++) {
                        $stok = floatval($data2[$i]['stok']);
                        $jumlah = floatval($data2[$i]['jumlah']);
                        $sql3 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,
                            kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,
                            diskon,tot_diskon,total) values ('".$data[0]['no_bukti']."', '$kode_lokasi', '$periode', 'BRGTERIMA',
                            'BRGTERIMA', '$i', '".$data[0]['gudang_asal']."', '".$data2[$i]['kode_barang']."', '-', getdate(), 
                            '".$data2[$i]['satuan']."', 'C', '$stok', '$jumlah', '0','0','0','0','0','0','0')";
                        DB::connection($this->sql)->insert($sql3);
                    }
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Mutasi Barang berhasil disimpan ";
            return response()->json(['success'=>$success], $this->successStatus);
            
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mutasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus);
        }
    }

    public function store(Request $request) {
        $this->validate($request, [
            'mutasi' => 'required|array',
            'mutasi.*.tanggal' => 'required',
            'mutasi.*.jenis' => 'required',
            'mutasi.*.no_bukti' => 'required',
            'mutasi.*.no_dokumen' => 'required',
            'mutasi.*.keterangan' => 'required',
            'mutasi.*.gudang_asal' => 'required',
            'mutasi.*.gudang_tujuan' => 'required',
            'mutasi.*.detail' => 'required|array',
            'mutasi.*.detail.*.kode_barang' => 'required',
            'mutasi.*.detail.*.satuan' => 'required',
            'mutasi.*.detail.*.stok' => 'required',
            'mutasi.*.detail.*.jumlah' => 'required'
        ]);

        try {
            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
            }

            $res = DB::connection($this->sql)
                    ->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'");
            $res = json_decode(json_encode($res),true);

            DB::connection($this->sql)->beginTransaction();
            
            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('mutasi');
            $periode = substr($data[0]['tanggal'],0,4).substr($data[0]['tanggal'],5,2);
            $no_bukti = $data[0]['no_bukti'];
            $sql1 = "exec sp_brg_stok '$periode', '$kode_lokasi', '$nik'";
            DB::connection($this->sql)->update($sql1);

            DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();
            DB::connection($this->sql)->table('brg_trans_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();

            if($data[0]['jenis'] == "KRM") {
                $sql2 = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,
                    posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,
                    nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync) 
                    values ('".$data[0]['no_bukti']."', '$kode_lokasi', getdate(), '$nik', '$periode', 'IV', 
                    'BRGKIRIM', 'X', '0', '0', '$kode_pp','".$data[0]['tanggal']."', '".$data[0]['no_dokumen']."', 
                    '".$data[0]['keterangan']."', 'IDR', '1', '0', '0', '0', '-', '-', '-', '-', '-', '-', 
                    '".$data[0]['gudang_asal']."', '".$data[0]['gudang_tujuan']."', '-', null, null, null)";
                
                DB::connection($this->sql)->insert($sql2);

                $data2 = $data[0]['detail'];
                if(count($data2) > 0) {
                    for($i=0;$i<count($data2);$i++) {
                        $stok = floatval($data2[$i]['stok']);
                        $jumlah = floatval($data2[$i]['jumlah']);
                        $sql3 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,
                            kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,
                            diskon,tot_diskon,total) values ('".$data[0]['no_bukti']."', '$kode_lokasi', '$periode', 'BRGKIRIM',
                            'BRGKIRIM', '$i', '".$data[0]['gudang_asal']."', '".$data2[$i]['kode_barang']."', '-', getdate(), 
                            '".$data2[$i]['satuan']."', 'C', '$stok', '$jumlah', '0','0','0','0','0','0','0')";
                        DB::connection($this->sql)->insert($sql3);
                    }
                }
            } else {
                $sql2 = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,
                    posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,
                    nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync) 
                    values ('".$data[0]['no_bukti']."', '$kode_lokasi', getdate(), '$nik', '$periode', 'IV', 
                    'BRGTERIMA', 'X', '0', '0', '$kode_pp','".$data[0]['tanggal']."', '".$data[0]['no_dokumen']."', 
                    '".$data[0]['keterangan']."', 'IDR', '1', '0', '0', '0', '-', '-', '-', '-', '-', '-', 
                    '".$data[0]['gudang_asal']."', '".$data[0]['gudang_tujuan']."', '-', null, null, null)";
                
                DB::connection($this->sql)->insert($sql2);

                $data2 = $data[0]['detail'];
                if(count($data2) > 0) {
                    for($i=0;$i<count($data2);$i++) {
                        $stok = floatval($data2[$i]['stok']);
                        $jumlah = floatval($data2[$i]['jumlah']);
                        $sql3 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,
                            kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,
                            diskon,tot_diskon,total) values ('".$data[0]['no_bukti']."', '$kode_lokasi', '$periode', 'BRGTERIMA',
                            'BRGTERIMA', '$i', '".$data[0]['gudang_asal']."', '".$data2[$i]['kode_barang']."', '-', getdate(), 
                            '".$data2[$i]['satuan']."', 'C', '$stok', '$jumlah', '0','0','0','0','0','0','0')";
                        DB::connection($this->sql)->insert($sql3);
                    }
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Mutasi Barang berhasil disimpan ";
            return response()->json(['success'=>$success], $this->successStatus);
            
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mutasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus);
        }

    }

    public function getDetailBarangMutasi(Request $request) {
        $this->validate($request, [            
            'kode_barang' => 'required',                             
            'kode_gudang' => 'required',                             
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_barang = $request->kode_barang;
            $kode_gudang = $request->kode_gudang;

            $sql = "select distinct a.nama,a.sat_kecil,b.stok
                from brg_barang a inner join brg_stok b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
                and b.kode_gudang='$kode_gudang'
                where a.kode_barang='$kode_barang' and a.kode_lokasi='$kode_lokasi' and b.nik_user='$nik'";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
                
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, 200);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function handleNoBukti(Request $request) {
        $this->validate($request, [            
            'tanggal' => 'required',                                    
            'jenis' => 'required',                                    
        ]);
        $tanggal = $request->tanggal;
        $jenis = $request->jenis;
        $kodeAcuan = "";
        if($jenis == 'KRM') {
            $kodeAcuan = "MK";
        } elseif($jenis == 'TRM') {
            $kodeAcuan = "MT";
        }
        $explode = explode("-", $tanggal);
        $lastTahun = substr($explode[0], -2, 2);
        $periode = "$lastTahun$explode[1]"; 

        $no_bukti = $this->generateKode("trans_m", "no_bukti", "$kodeAcuan/$periode/", "0001");

        $success['status'] = true;
        $success['kode'] = $no_bukti;
        
        return response()->json($success, 200);
    }

}

?>