<?php
namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PembayaranPembelianController extends Controller 
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("SELECT no_bukti, convert(varchar,tanggal,103) as tgl, param2 as kode_vendor ,nilai1 as nilai
            from trans_m where kode_lokasi=? and form = ? ",[$kode_lokasi, 'BYRBELI']);
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

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function GenerateBukti(Request $request) {
        $this->validate($request, [            
            'tanggal' => 'required'                     
        ]);
        $tanggal = $request->tanggal;

        $explode = explode("-", $tanggal);
        $lastTahun = substr($explode[0], -2, 2);
        $periode = "$lastTahun$explode[1]"; 

        $no_bukti = $this->generateKode("trans_m", "no_bukti", "PT/$periode/", "0001");

        $success['status'] = true;
        $success['kode'] = $no_bukti;
        
        return response()->json($success, 200);
    }

    function getVendor(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection($this->sql)->select("SELECT kode_vendor, nama as nama_vendor from vendor where kode_lokasi=?",[$kode_lokasi]);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailPembelian(Request $request) {
        $this->validate($request, [            
            'kode_vendor' => 'required',                             
            'periode' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_vendor = $request->kode_vendor;
            $periode = $request->periode;


            $sql = "SELECT 'OPEN' as status_bayar,a.no_beli,a.tanggal,a.nilai+a.nilai_ppn-isnull(b.bayar_tot,0) as saldo
            from brg_belihut_d a 
            left join (select no_beli,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar_tot
                      from brg_belibayar_d 
                      where kode_lokasi =?
                      group by no_beli,kode_lokasi) b on a.no_beli=b.no_beli and a.kode_lokasi=b.kode_lokasi
            where  a.kode_lokasi =? and a.periode <= ? and a.nilai+a.nilai_ppn-isnull(b.bayar_tot,0)  >0 and a.kode_vendor=?
            order by a.no_beli";

            $par_array = [$kode_lokasi, $kode_lokasi, $periode, $kode_vendor];

            $res = DB::connection($this->sql)->select($sql,$par_array);
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

    public function store(Request $request) {
        $this->validate($request, [
            'bayarbeli' => 'required|array',
            'bayarbeli.*.tanggal' => 'required',
            'bayarbeli.*.no_bukti' => 'required',
            'bayarbeli.*.vendor' => 'required',
            'bayarbeli.*.keterangan' => 'required',
            'bayarbeli.*.no_dokumen' => 'required',
            'bayarbeli.*.total_pembayaran' => 'required',
            'bayarbeli.*.detail' => 'required|array',
            'bayarbeli.*.detail.*.no_beli' => 'required',
            'bayarbeli.*.detail.*.status_bayar' => 'required',
            'bayarbeli.*.detail.*.tgl' => 'required',
            'bayarbeli.*.detail.*.saldo' => 'required'
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
            
            $data = $request->input('bayarbeli');

            $tanggal = $data[0]['tanggal'];
            $explode = explode("-", $tanggal);
            $lastTahun = substr($explode[0], -2, 2);
            $kode_periode = "$lastTahun$explode[1]"; 
            $no_bukti = $this->generateKode("trans_m", "no_bukti", "PT/$kode_periode/", "0001");

            $periode = substr($data[0]['tanggal'],0,4).substr($data[0]['tanggal'],5,2);
            // $kode_pp = $res[0]['kode_pp'];
            $kode_pp = '-';
            $total_pembayaran = floatval($data[0]['total_pembayaran']);

            // DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();
            // DB::connection($this->sql)->table('brg_trans_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $data[0]['no_bukti'])->delete();

            $sql2 = "INSERT INTO trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,
                    posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,
                    nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync) 
                    values (?,?,getdate(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,null,null,null)";
                
                DB::connection($this->sql)->insert($sql2, [
                    $no_bukti,
                    $kode_lokasi, 
                    $nik, 
                    $periode, 
                    'KB', 
                    'BYRBELI',

                    'F', 
                    '-', 
                    '-', 
                    $kode_pp,
                    $data[0]['tanggal'], 
                    $data[0]['no_dokumen'],  
                    $data[0]['keterangan'], 
                    'IDR', 
                    1, 
                    $total_pembayaran, 
                    0, 
                    0, 
                    '-', 
                    '-', 
                    '-',
                    '-', 
                    '-', 
                    '-', 
                    '-',
                    '-', 
                    '-'
                ]);

                $data2 = $data[0]['detail'];
                if(count($data2) > 0) {
                    for($i=0;$i<count($data2);$i++) {
                        if($data2[$i]['status_bayar'] == 'BAYAR'){
                            $saldo = floatval($data2[$i]['saldo']);
                            $sql3 = "INSERT INTO brg_belibayar_d (no_bukti,kode_lokasi,no_beli,kode_vendor,periode,dc,modul,nilai,nik_user,tgl_input,tanggal) 
                            VALUES (?,?,?,?,?,?,?,?,?,getdate(),?)";
                            DB::connection($this->sql)->insert($sql3, [
                                $no_bukti, 
                                $kode_lokasi,
                                $data2[$i]['no_beli'],
                                $data[0]['vendor'],
                                $periode, 
                                'D', 
                                'BYRBELI',
                                $saldo, 
                                $nik,
                                $data2[$i]['tgl']
                            ]);
                        }
                    }
                }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Pembayaran Pembelian berhasil disimpan ";
            return response()->json(['success'=>$success], $this->successStatus);
            
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran Pembelian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus);
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
            
            DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('form', 'BYRBELI')->where('no_bukti', $no_bukti)->delete();
            DB::connection($this->sql)->table('brg_belibayar_d')->where('kode_lokasi', $kode_lokasi)->where('modul', 'BYRBELI')->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran Pembelian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran Pembelian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }


}