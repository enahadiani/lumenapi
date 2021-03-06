<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql="select no_bukti,nik_user,tanggal,param2 as kode_vendor,nilai1 as total from trans_m where kode_lokasi='".$kode_lokasi."' and nik_user='".$nik."' and form = 'BRGBELI' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }    

    public function getNota(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
  
            $success["nik"]=$nik;
            $success["no_bukti"] = $request->no_bukti;

            $sql="select * from trans_m where no_bukti='$request->no_bukti' and kode_lokasi='$kode_lokasi' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $success["totpemb"]=$get[0]['nilai1']-$get[0]['nilai2']+$get[0]['nilai3'];
                $success["totdisk"]=$get[0]['nilai2'];
                $success["tottrans"]=$get[0]['nilai1'];
                $success["totppn"]=$get[0]['nilai3'];
                $success["tgl"] = $get[0]['tanggal'];
            }else{
                $success["totpemb"]=0;
                $success["totdisk"]=0;
                $success["tottrans"]=0;
                $success["totppn"]=0;
                $success["tgl"] =null;
            }

            $sql="select a.kode_barang,a.harga,a.jumlah,a.diskon,b.nama,b.sat_kecil from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi where a.no_bukti='$request->no_bukti' and a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getBarang(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            
            $periode = date('Ym');
            $tahun = date('Y');

            $sql="select a.kode_barang,a.nama,a.hna as harga,a.barcode,a.sat_kecil as satuan,x.akun_pers as kode_akun,isnull(a.nilai_beli,0) as harga_seb,isnull(d.sakhir,0) as saldo 
            from ( select a.kode_barang,a.nama,a.hna,a.barcode,a.sat_kecil,a.nilai_beli,b.kode_gudang,a.kode_klp,a.kode_lokasi
			from brg_barang a cross join brg_gudang b
			where a.kode_lokasi='$kode_lokasi' and b.kode_lokasi='$kode_lokasi' and b.kode_gudang='G01'
			) a
            inner join brg_barangklp x on a.kode_klp=x.kode_klp and a.kode_lokasi=x.kode_lokasi 
            left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah) as sawal from brg_sawal 
                        where periode='$tahun-01' and kode_lokasi='$kode_lokasi' 
                        group by kode_lokasi,kode_barang,kode_gudang 
            ) b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.kode_gudang 
            
            left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah+bonus) as beli 
                        from brg_trans_d 
                        where modul='BRGBELI' and periode like '$tahun%' and periode <= '$periode' and kode_lokasi='$kode_lokasi' 
                        group by kode_lokasi,kode_barang,kode_gudang 
            ) c on a.kode_barang=c.kode_barang and a.kode_lokasi=c.kode_lokasi and a.kode_gudang=c.kode_gudang    	   	 
            
            left join (select kode_barang,kode_gudang,kode_lokasi,sum(stok) as sakhir 
                        from brg_stok where kode_lokasi='$kode_lokasi' and nik_user ='$nik' 
                        group by kode_lokasi,kode_barang,kode_gudang 
            ) d on a.kode_barang=d.kode_barang and a.kode_lokasi=d.kode_lokasi and a.kode_gudang=d.kode_gudang 								 
            where a.kode_lokasi='$kode_lokasi'";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            $no_bukti = $request->no_bukti;

            $sql = "select no_bukti,nik_user,nilai1 as total,nilai2 as ppn,nilai3 as diskon,param2 as kode_vendor,no_dokumen,convert(varchar,tanggal,103) as tanggal from trans_m where form='BRGBELI' and kode_lokasi='$kode_lokasi' and nik_user='$nik' and no_bukti='$no_bukti' 
            ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2="select a.nu, a.kode_barang,isnull(b.nilai_beli,0) as hrg_seb,a.satuan,a.jumlah,a.harga,a.diskon,a.total as subtotal,b.nama,a.stok, isnull(b.hna,0) as harga_jual 
            from brg_trans_d  a 
            left join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where  a.form='BRGBELI' and a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
   
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_vendor' => 'required',
            'no_faktur' => 'required',
            'kode_pp' => 'required',
            'total_trans' => 'required',
            'total_diskon' => 'required',
            'total_ppn' => 'required',
            'kode_akun' => 'required|array',
            'kode_barang' => 'required|array',
            'qty_barang' => 'required|array',
            'harga_barang' => 'required|array',
            'satuan_barang' => 'required|array',
            'disc_barang' => 'required|array',
            'sub_barang' => 'required|array',
            'harga_jual' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix="PO/".substr($periode,2,4)."/".$kode_lokasi."/";
            $sql="select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";

            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $sql="select kode_spro,flag from spro where kode_spro in ('PPNM','BELIDIS') and kode_lokasi = '".$kode_lokasi."'";
            $spro = DB::connection($this->sql)->select($sql);
            if(count($spro) > 0){
                foreach ($spro as $row){
                    if ($row->kode_spro == "PPNM") $akunPPN=$row->flag;
                    if ($row->kode_spro == "BELIDIS") $akunDiskon=$row->flag;
                }
            }

            $sql3 = "select akun_hutang from vendor where kode_vendor ='".$request->kode_vendor."' and kode_lokasi = '".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql3);
            if (count($res) > 0)
            {
                $akunHutang = $res[0]->akun_hutang;									
            }	

            $sqlg="select top 1 a.kode_gudang from brg_gudang a where a.kode_lokasi='$kode_lokasi' ";
            $rsg = DB::connection($this->sql)->select($sqlg);
            if(count($rsg) > 0){
                $kodeGudang=$rsg[0]->kode_gudang;
            }else{
                $kodeGudang="-";
            }

            $exec = array();

            $sqlm = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','IV','BRGBELI','F','-','-','".$request->kode_pp."',getdate(),'".$request->no_faktur."','Pembelian Persediaan','IDR',1,$request->total_trans,$request->total_ppn,$request->total_diskon,'-','-','-','-','-','-','-','".$request->kode_vendor."','".$akunHutang."')");
					
            $sqlb = DB::connection($this->sql)->insert("insert into brg_belihut_d(no_beli,kode_lokasi,tanggal,keterangan,kode_vendor,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_hutang,nilai_ppn,no_fp,due_date, nilai_pph, diskon, modul,kode_gudang) values ('".$id."','".$kode_lokasi."',getdate(), 'Pembelian Persediaan','".$request->kode_vendor."','IDR',1,'".$request->kode_pp."',$request->total_trans,'".$periode."','".$nik."',getdate(),'".$akunHutang."',$request->total_ppn,'-',getdate(),0,$request->total_diskon, 'BELI','$kodeGudang')");
                        
            $series = array();
            $series2 = array();
            $group = array();
            $nilai = 0;
            $diskItem = 0;
            $total=0;
            for($b=0; $b<count($request->kode_barang);$b++){
                $nilai = $request->sub_barang[$b];
                $isAda = false;
                $idx = 0;
                
                $akun = $request->kode_akun[$b];						
                for ($c=0;$c <= $b;$c++){
                    if(isset($request->kode_akun[$c-1])){

                        if ($akun == $request->kode_akun[$c-1]) {
                            $isAda = true;
                            $idx = $c;
                            break;
                        }
                    }
                }
                if (!$isAda) {							
                    array_push($series,$request->kode_akun[$b]);
                    
                    $series2[$request->kode_akun[$b]]=$nilai;
                } 
                else { 
                    $total = $series2[$request->kode_akun[$b]];
                    $total = $total + $nilai;
                    $series2[$request->kode_akun[$b]]=$total;
                }		
                    
                $diskItem+=$request->disc_barang[$b];
            }
            
            for($x=0; $x<count($series);$x++){
                
                $sqlj=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$request->kode_akun[$x]."','D',".$series2[$series[$x]].",".$series2[$series[$x]].",'Persediaan Barang','BRGBELI','BRGBELI','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                    
            }
            
            $totDiskon = $request->total_diskon +$diskItem;
            if ($request->total_ppn > 0) {
                $x=$x+1;
                $sql6=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunPPN."','D',".$request->total_ppn.",".$request->total_ppn.",'PPN Masukan','BRGBELI','PPNM','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                    
            }

            if ($request->total_trans > 0) {
                $x=$x+1;
                $sql7= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunHutang."','C',".$request->total_trans.",".$request->total_trans.",'Hutang Vendor Pembelian','BRGBELI','BELIDISC','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
            }
            
            if ($request->total_diskon > 0) {
                $x=$x+1;
                $sql8=  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunDiskon."','C',".$totDiskon.",".$totDiskon.",'Diskon Pembelian','BRGBELI','BELIDISC','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
            }
            
            for($a=0; $a<count($request->kode_barang);$a++){

                $sql9 = DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                ('".$id."','".$kode_lokasi."','".$periode."','BRGBELI','BRGBELI',".$a.",'$kodeGudang','".$request->kode_barang[$a]."','-',getdate(),'".$request->satuan_barang[$a]."','D',0,".$request->qty_barang[$a].",0,".$request->harga_barang[$a].",0,0,".$diskItem.",".$request->disc_barang[$a].",".$request->sub_barang[$a].")");

                $update = DB::connection($this->sql)->table('brg_barang')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_barang', $request->kode_barang[$a])->update(['nilai_beli'=>$request->harga_barang[$a],'hna'=>$request->harga_jual[$a]]);
            }	

            $tmp="Data Pembelian berhasil disimpan";
            $sts=true;
            DB::connection($this->sql)->commit();

            $success['status'] = $sts;
            $success['message'] = $tmp;
            $success['no_bukti'] = $id;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembelian gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti'=>'required',
            'kode_vendor' => 'required',
            'no_faktur' => 'required',
            'kode_pp' => 'required',
            'total_trans' => 'required',
            'total_diskon' => 'required',
            'total_ppn' => 'required',
            'kode_akun' => 'required|array',
            'kode_barang' => 'required|array',
            'qty_barang' => 'required|array',
            'harga_barang' => 'required|array',
            'satuan_barang' => 'required|array',
            'disc_barang' => 'required|array',
            'sub_barang' => 'required|array',
            'harga_jual' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
            $periode=date('Y').date('m');

            $id = $request->no_bukti;
            $sql="select * from trans_m where isnull(id_sync,'-') ='-' and no_bukti='$id' and kode_lokasi='$kode_lokasi'";

            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){

                $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('nik_user', $nik)
                ->where('form', 'BRGBELI')
                ->delete();

                $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('nik_user', $nik)
                ->where('modul', 'BRGBELI')
                ->delete();

                $del3 = DB::connection($this->sql)->table('brg_belihut_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_beli', $request->no_bukti)
                ->where('nik_user', $nik)
                ->where('modul', 'BELI')
                ->delete();

                $del4 = DB::connection($this->sql)->table('brg_trans_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->where('form', 'BRGBELI')
                ->delete();

               
                $sql="select kode_spro,flag from spro where kode_spro in ('PPNM','BELIDIS') and kode_lokasi = '".$kode_lokasi."'";
                $spro = DB::connection($this->sql)->select($sql);
                if(count($spro) > 0){
                    foreach ($spro as $row){
                        if ($row->kode_spro == "PPNM") $akunPPN=$row->flag;
                        if ($row->kode_spro == "BELIDIS") $akunDiskon=$row->flag;
                    }
                }
    
                $sql3 = "select akun_hutang from vendor where kode_vendor ='".$request->kode_vendor."' and kode_lokasi = '".$kode_lokasi."'";
                $res = DB::connection($this->sql)->select($sql3);
                if (count($res) > 0){
                    $akunHutang = $res[0]->akun_hutang;									
                }	
    
                $sqlg="select top 1 a.kode_gudang from brg_gudang a where a.kode_lokasi='$kode_lokasi' ";
                $rsg = DB::connection($this->sql)->select($sqlg);
                if(count($rsg) > 0){
                    $kodeGudang=$rsg[0]->kode_gudang;
                }else{
                    $kodeGudang="-";
                }
    
                $exec = array();
    
                $sqlm = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','IV','BRGBELI','F','-','-','".$request->kode_pp."',getdate(),'".$request->no_faktur."','Pembelian Persediaan','IDR',1,$request->total_trans,$request->total_ppn,$request->total_diskon,'-','-','-','-','-','-','-','".$request->kode_vendor."','".$akunHutang."')");
                        
                $sqlb = DB::connection($this->sql)->insert("insert into brg_belihut_d(no_beli,kode_lokasi,tanggal,keterangan,kode_vendor,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_hutang,nilai_ppn,no_fp,due_date, nilai_pph, diskon, modul,kode_gudang) values ('".$id."','".$kode_lokasi."',getdate(), 'Pembelian Persediaan','".$request->kode_vendor."','IDR',1,'".$request->kode_pp."',$request->total_trans,'".$periode."','".$nik."',getdate(),'".$akunHutang."',$request->total_ppn,'-',getdate(),0,$request->total_diskon, 'BELI','$kodeGudang')");
                            
                $series = array();
                $series2 = array();
                $group = array();
                $nilai = 0;
                $diskItem = 0;
                $total=0;
                for($b=0; $b<count($request->kode_barang);$b++){
                    $nilai = $request->sub_barang[$b];
                    $isAda = false;
                    $idx = 0;
                    
                    $akun = $request->kode_akun[$b];						
                    for ($c=0;$c <= $b;$c++){
                        if(isset($request->kode_akun[$c-1])){
                            if ($akun == $request->kode_akun[$c-1]) {
                                $isAda = true;
                                $idx = $c;
                                break;
                            }
                        }
                    }
                    if (!$isAda) {							
                        array_push($series,$request->kode_akun[$b]);
                        
                        $series2[$request->kode_akun[$b]]=$nilai;
                    } 
                    else { 
                        $total = $series2[$request->kode_akun[$b]];
                        $total = $total + $nilai;
                        $series2[$request->kode_akun[$b]]=$total;
                    }		
                        
                    $diskItem+=$request->disc_barang[$b];
                }
                
                for($x=0; $x<count($series);$x++){
                    
                    $sqlj=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$request->kode_akun[$x]."','D',".$series2[$series[$x]].",".$series2[$series[$x]].",'Persediaan Barang','BRGBELI','BRGBELI','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                        
                }
                
                $totDiskon = $request->total_diskon +$diskItem;
                if ($request->total_ppn > 0) {
                    $x=$x+1;
                    $sql6=DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunPPN."','D',".$request->total_ppn.",".$request->total_ppn.",'PPN Masukan','BRGBELI','PPNM','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                        
                }
    
                if ($request->total_trans > 0) {
                    $x=$x+1;
                    $sql7= DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunHutang."','C',".$request->total_trans.",".$request->total_trans.",'Hutang Vendor Pembelian','BRGBELI','BELIDISC','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                }
                
                if ($request->total_diskon > 0) {
                    $x=$x+1;
                    $sql8=  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunDiskon."','C',".$totDiskon.",".$totDiskon.",'Diskon Pembelian','BRGBELI','BELIDISC','IDR',1,'$request->kode_pp','-','-','-','-','-','-','-','-')");
                }
                
                for($a=0; $a<count($request->kode_barang);$a++){
    
                    $sql9 = DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
                    ('".$id."','".$kode_lokasi."','".$periode."','BRGBELI','BRGBELI',".$a.",'$kodeGudang','".$request->kode_barang[$a]."','-',getdate(),'".$request->satuan_barang[$a]."','D',0,".$request->qty_barang[$a].",0,".$request->harga_barang[$a].",0,0,".$diskItem.",".$request->disc_barang[$a].",".$request->sub_barang[$a].")");
    
                    $update = DB::connection($this->sql)->table('brg_barang')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('kode_barang', $request->kode_barang[$a])->update(['nilai_beli'=>$request->harga_barang[$a],'hna'=>$request->harga_jual[$a]]);
                }	
    
                DB::connection($this->sql)->commit();
                $tmp = "Data Pembelian berhasil diubah";
                $sts = true;
            }else{
                $tmp = "No Pembelian = ".$id." tidak dapat diedit karena sudah disyncronize ";
                $sts = false;
            }
            
            $success['status'] = $sts;
            $success['message'] = $tmp;
            $success['no_bukti']= $id;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembelian gagal diubah ".$e;
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

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $del = DB::connection($this->sql)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('nik_user', $nik)
            ->where('form', 'BRGBELI')
            ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('nik_user', $nik)
            ->where('modul', 'BRGBELI')
            ->delete();

            $del3 = DB::connection($this->sql)->table('brg_belihut_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_beli', $request->no_bukti)
            ->where('nik_user', $nik)
            ->where('modul', 'BELI')
            ->delete();

            $del4 = DB::connection($this->sql)->table('brg_trans_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('form', 'BRGBELI')
            ->delete();


            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembelian berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembelian gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
