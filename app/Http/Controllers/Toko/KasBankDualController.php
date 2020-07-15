<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KasBankDualController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $len = strlen($str_format)+1;
        $query =DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%' and LEN($kolom_acuan) = $len ");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index(Request $request)
    {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->jenis)){
                if($request->jenis == 'PENGELUARAN'){
                    $jenis="BK";
                }else if($request->jenis == 'PEMASUKAN'){
                    $jenis="BM";
                }else{
                    $jenis="PIN";
                }

                $filter .= " and a.param3 ='$jenis' ";
                
            }else{
                $filter .= "";
            }

            $sql = "select distinct a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,a.keterangan,a.nilai1,a.tanggal 
            from trans_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.form = 'KBDUAL' and a.posted ='F' $filter
            order by a.tanggal";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_jenis' => 'required|in:PENGELUARAN,PEMASUKAN,PINDAH BUKU',
            'kode_ref' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->kode_jenis == 'PENGELUARAN'){
                $jenis="BK";
            }else if($request->kode_jenis == 'PEMASUKAN'){
                $jenis="BM";
            }else{
                $jenis="PIN";
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-".$jenis.$per.".";
            $query = DB::connection($this->sql)->select("select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' ");
            $query = json_decode(json_encode($query),true);
            
            $id = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            $sql="select a.nama,a.akun_debet,a.akun_kredit,a.kode_pp
            from trans_ref a 
            inner join masakun b on a.akun_debet=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join masakun c on a.akun_kredit=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
            where a.kode_ref='".$request->kode_ref."' and a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $akunDebet=$res[0]['akun_debet'];
            $akunKredit=$res[0]['akun_kredit'];

            // if($request->kode_jenis == 'Keluar'){
            //     $akunKredit = $request->kode_akun;
            // }else{
            //     $akunDebet = $request->kode_akun;
            // }

            $ins = DB::connection($this->sql)->insert('insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBDUAL','F','-','-',$request->kode_pp,date('Y-m-d H:i:s'),'-',$request->keterangan,'IDR','1',$request->nilai,0,0,$nik,'-','-','-','-','-',$request->kode_ref,'TUNAI',$jenis]);

            $ins2 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunDebet,'D',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            $ins3 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),1,$akunKredit,'C',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            // $ins4 = DB::connection($this->sql)->update("insert into gldt (no_bukti,no_urut,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,kurs,nilai_curr,tgl_input,nik_user,kode_cust,kode_proyek,kode_task,kode_vendor,kode_lokarea,nik) select no_bukti,nu,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,1,nilai,tgl_input,nik_user,'-','-','-','-','-','-' from trans_j 
            // where kode_lokasi='".$kode_lokasi."' and no_bukti='".$id."' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kas Bank Dual berhasil disimpan. No Bukti: ".$id;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kas Bank Dual gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $akun = DB::connection($this->sql)->select("select a.no_bukti,a.param1 as kode_ref,case a.param3 when 'BM' then 'PEMASUKAN' when 'BK' then 'PENGELUARAN' when 'PIN' then 'PINDAH BUKU' end as kode_jenis,a.kode_pp,a.keterangan,a.nilai1 from trans_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.form = 'KBDUAL' and a.posted ='F' $filter 
            ");

            $akun = json_decode(json_encode($akun),true);

            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] =[];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'kode_pp' => 'required',
            'kode_jenis' => 'required|in:PENGELUARAN,PEMASUKAN,PINDAH BUKU',
            'kode_ref' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->kode_jenis == 'PENGELUARAN'){
                $jenis="BK";
            }else if($request->kode_jenis == 'PEMASUKAN'){
                $jenis="BM";
            }else{
                $jenis="PIN";
            }

            $del = DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();
            $del3 = DB::connection($this->sql)->table('gldt')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();

           
            $id = $request->no_bukti;

            $sql="select a.nama,a.akun_debet,a.akun_kredit,a.kode_pp
            from trans_ref a 
            inner join masakun b on a.akun_debet=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join masakun c on a.akun_kredit=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
            where a.kode_ref='".$request->kode_ref."' and a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $akunDebet=$res[0]['akun_debet'];
            $akunKredit=$res[0]['akun_kredit'];

            // if($request->kode_jenis == 'Keluar'){
            //     $akunKredit = $request->kode_akun;
            // }else{
            //     $akunDebet = $request->kode_akun;
            // }

            $ins = DB::connection($this->sql)->insert('insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBDUAL','F','-','-',$request->kode_pp,date('Y-m-d H:i:s'),'-',$request->keterangan,'IDR','1',$request->nilai,0,0,$nik,'-','-','-','-','-',$request->kode_ref,'TUNAI',$jenis]);

            $ins2 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunDebet,'D',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            $ins3 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),1,$akunKredit,'C',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            // $ins4 = DB::connection($this->sql)->update("insert into gldt (no_bukti,no_urut,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,kurs,nilai_curr,tgl_input,nik_user,kode_cust,kode_proyek,kode_task,kode_vendor,kode_lokarea,nik) select no_bukti,nu,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,1,nilai,tgl_input,nik_user,'-','-','-','-','-','-' from trans_j 
            // where kode_lokasi='".$kode_lokasi."' and no_bukti='".$id."' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kas Bank Dual berhasil diubah. No Bukti: ".$id;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kas Bank Dual gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();
            $del3 = DB::connection($this->sql)->table('gldt')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kas Bank Dual berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kas Bank Dual gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
