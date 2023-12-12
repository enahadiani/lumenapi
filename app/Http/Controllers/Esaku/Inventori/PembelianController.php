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
    public $db = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $sql="select no_bukti,nik_user,tanggal,param2 as kode_vendor,(nilai1+nilai2-nilai3) as total from trans_m where kode_lokasi='".$kode_lokasi."' and nik_user='".$nik."' and form = 'BRGBELI' ";
            $res = DB::connection($this->db)->select($sql);
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

    public function getNota(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }
  
            $success["nik"]=$nik;
            $success["no_bukti"] = $r->no_bukti;

            $sql="select * from trans_m where no_bukti='$r->no_bukti' and kode_lokasi='$kode_lokasi' ";
            $get = DB::connection($this->db)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $success["totpemb"]=$get[0]['nilai1'];
                $success["totdisk"]=$get[0]['nilai3'];
                $success["tottrans"]=$get[0]['nilai1']-$get[0]['nilai3'];
                $success["totppn"]=$get[0]['nilai2'];
                $success["tgl"] = $get[0]['tanggal'];
            }else{
                $success["totpemb"]=0;
                $success["totdisk"]=0;
                $success["tottrans"]=0;
                $success["totppn"]=0;
                $success["tgl"] =null;
            }

            $sql="select a.kode_barang,a.harga,a.jumlah,a.diskon,b.nama,b.sat_kecil from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi where a.no_bukti='$r->no_bukti' and a.kode_lokasi='$kode_lokasi' and b.pabrik='$pabrik' ";
            $res = DB::connection($this->db)->select($sql);
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

    public function getBarang(Request $r)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }
            
            $periode = date('Ym');
            $tahun = date('Y');

            $kodeGudang = $pabrik;

            $sql="select a.kode_barang,a.nama,a.hna as harga,a.barcode,a.sat_kecil as satuan,x.akun_pers as kode_akun,isnull(a.nilai_beli,0) as harga_seb,isnull(d.sakhir,0) as saldo,a.flag_ppn
            from ( select a.kode_barang,a.nama,a.hna,a.barcode,a.sat_kecil,a.nilai_beli,b.kode_gudang,a.kode_klp,a.kode_lokasi,isnull(a.flag_ppn, 0) as flag_ppn
			from brg_barang a 
            inner join brg_gudang b on a.pabrik=b.kode_gudang and a.kode_lokasi=b.kode_lokasi			
			where a.kode_lokasi='$kode_lokasi' and b.kode_gudang='$kodeGudang' and a.flag_aktif='1'
			) a
            inner join brg_barangklp x on a.kode_klp=x.kode_klp and a.kode_lokasi=x.kode_lokasi 
            left join (select kode_barang,kode_gudang,kode_lokasi,sum(jumlah) as sawal 
                        from brg_sawal 
                        where periode='".$tahun."01' and kode_lokasi='$kode_lokasi' 
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
            
            $res = DB::connection($this->db)->select($sql);
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
            $success['message'] = $sql;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function show(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }
            $no_bukti = $r->no_bukti;

            $sql = "select no_bukti,nik_user,nilai1 as total,nilai2 as ppn,nilai3 as diskon,param2 as kode_vendor,no_dokumen,convert(varchar,tanggal,103) as tanggal,keterangan from trans_m where form='BRGBELI' and kode_lokasi='$kode_lokasi' and nik_user='$nik' and no_bukti='$no_bukti' 
            ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2="select a.nu, a.kode_barang,isnull(b.nilai_beli,0) as hrg_seb,a.satuan,a.jumlah,a.harga,a.diskon,a.total as subtotal,b.nama,a.stok, isnull(b.hna,0) as harga_jual 
            from brg_trans_d  a 
            left join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where  a.form='BRGBELI' and a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' and b.pabrik='$pabrik' ";

            $res2 = DB::connection($this->db)->select($sql2);
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

    public function showTmp(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            $nik_user = $r->nik_user;
            
            $sql2="select ROW_NUMBER() OVER(ORDER BY a.nu ASC) AS no,a.nu as no_urut,a.kode_barang,a.harga_jual,a.harga_seb,a.satuan as satuan_barang,a.jumlah as qty_barang,a.harga as harga_barang,a.diskon as disc_barang,a.total as sub_barang,b.nama as nama_barang,a.stok as stok_barang,a.kode_akun
            from brg_trans_d_tmp a 
            left join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.kode_gudang=b.pabrik
            where a.form='BRGBELI' and a.kode_lokasi='$kode_lokasi' and a.nik_user='$nik_user' and a.kode_gudang='$pabrik' ";

            $res = DB::connection($this->db)->select($sql2);
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
   
    public function store(Request $r)
    {
        $this->validate($r, [
            'kode_vendor' => 'required',
            'no_faktur' => 'required',
            'keterangan' => 'required',
            'kode_pp' => 'required',
            'total_trans' => 'required',
            'total_diskon' => 'required',
            'total_ppn' => 'required',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix="PO/".substr($periode,2,4)."/".$kode_lokasi."/";
            $sql="select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";

            $get = DB::connection($this->db)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $sql="select kode_spro,flag from spro where kode_spro in ('PPNM','BELIDIS') and kode_lokasi = '".$kode_lokasi."'";
            $spro = DB::connection($this->db)->select($sql);
            if(count($spro) > 0){
                foreach ($spro as $row){
                    if ($row->kode_spro == "PPNM") $akunPPN=$row->flag;
                    if ($row->kode_spro == "BELIDIS") $akunDiskon=$row->flag;
                }
            }

            $sql3 = "select akun_hutang from vendor where kode_vendor ='".$r->kode_vendor."' and kode_lokasi = '".$kode_lokasi."'";
            $res = DB::connection($this->db)->select($sql3);
            if (count($res) > 0)
            {
                $akunHutang = $res[0]->akun_hutang;									
            }	

            $spro3 = DB::connection($this->db)->select(" select kode_spro,flag  from spro where kode_lokasi='$kode_lokasi' and kode_spro='CUSTINV'");
            $spro3 = json_decode(json_encode($spro3),true);
            if(count($spro3)>0){
                $akunpiu=$spro3[0]["flag"];
            }else{
                $akunpiu = "-";
            }

            $kodeGudang=$pabrik;

            $exec = array();

            $insertM = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) 
            values (?,?,getdate(),?,?,?,?,?,?,?,?,getdate(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            DB::connection($this->db)->insert($insertM, [
                $id,
                $kode_lokasi,
                $nik,
                $periode,
                'IV',
                'BRGBELI',
                'F',
                '-',
                '-',
                $r->kode_pp,
                $r->no_faktur,
                $r->keterangan,
                'IDR',
                1,
                $r->total_trans,
                $r->total_ppn,
                $r->total_diskon,
                '-',
                '-',
                '-',
                '-',
                '-',
                '-',
                '-',
                $r->kode_vendor,
                $akunHutang
            ]);
			
            $insertB = "insert into brg_belihut_d(no_beli,kode_lokasi,tanggal,keterangan,kode_vendor,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_hutang,nilai_ppn,no_fp,due_date, nilai_pph, diskon, modul,kode_gudang) 
            values (?,?,getdate(),?,?,?,?,?,?,?,?,getdate(),?,?,?,getdate(),?,?,?,?)";
            DB::connection($this->db)->insert($insertB, [
               $id,
               $kode_lokasi,
               'Pembelian Persediaan',
               $r->kode_vendor,
               'IDR',
               1,
               $r->kode_pp,
               $r->total_trans,
               $periode,
               $nik,
               $akunHutang,
               $r->total_ppn,
               '-',
               0,
               $r->total_diskon, 
               'BELI',
               $kodeGudang 
            ]);
                        
            $diskItem = 0;
            $x=0;
            $gt = DB::connection($this->db)->select("select kode_akun,sum(total) as total,sum(diskon)as total_diskon  
            from brg_trans_d_tmp 
            where nik_user=? and form='BRGBELI'
            group by kode_akun",[$r->input('nik_user')]);
            if(count($gt) > 0){
                for($x=0; $x<count($gt);$x++){
                    $insertJ = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                    values (?,?,getdate(),?,?,?,getdate(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    DB::connection($this->db)->insert($insertJ, [
                        $id,
                        $kode_lokasi,
                        $nik,
                        $periode,
                        '-',
                        $x,
                        $gt[$x]->kode_akun,
                        'D',
                        $gt[$x]->total,
                        $gt[$x]->total,
                        $r->keterangan,
                        'BRGBELI',
                        'BRGBELI',
                        'IDR',
                        1,
                        $r->kode_pp,
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-'   
                    ]);  
                    $diskItem += floatval($gt[$x]->total_diskon);
                }
            }
            
            $totDiskon = $r->total_diskon +$diskItem;
            if ($r->total_ppn > 0) {
                $x=$x+1;
                $insert6 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                DB::connection($this->db)->insert($insert6, [
                    $id,
                    $kode_lokasi,
                    date('Y-m-d H:i:s'),
                    $nik,
                    $periode,
                    '-',
                    date('Y-m-d H:i:s'),
                    $x,
                    $akunPPN,
                    'D',
                    $r->total_ppn,
                    $r->total_ppn,
                    'PPN Masukan',
                    'BRGBELI',
                    'PPNM',
                    'IDR',
                    1,
                    $r->kode_pp,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-'
                ]);

                $insert6d = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                DB::connection($this->db)->insert($insert6, [
                    $id,
                    $kode_lokasi,
                    date('Y-m-d H:i:s'),
                    $nik,
                    $periode,
                    '-',
                    date('Y-m-d H:i:s'),
                    $x,
                    $akunPPN,
                    'C',
                    $r->total_ppn,
                    $r->total_ppn,
                    'PPN Masukan',
                    'BRGBELI',
                    'PPNM',
                    'IDR',
                    1,
                    $r->kode_pp,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-'
                ]);
                    
            }

            if ($r->total_trans > 0) {
                $x=$x+1;
                $hut = floatval($r->total_trans)-floatval($totDiskon);
                $insert7 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                DB::connection($this->db)->insert($insert7, [
                    $id,
                    $kode_lokasi,
                    date('Y-m-d H:i:s'),
                    $nik,
                    $periode,
                    '-',
                    date('Y-m-d H:i:s'),
                    $x,
                    $akunHutang,
                    'C',
                    $hut,
                    $hut,
                    'Hutang Vendor Pembelian',
                    'BRGBELI',
                    'HUTBELI',
                    'IDR',
                    1,
                    $r->kode_pp,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-'
                ]);
            }
            
            if ($r->total_diskon > 0) {
                $x=$x+1;
                $insert8 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
                values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                DB::connection($this->db)->insert($insert8, [
                    $id,
                    $kode_lokasi,
                    date('Y-m-d H:i:s'),
                    $nik,
                    $periode,
                    '-',
                    date('Y-m-d H:i:s'),
                    $x,
                    $akunDiskon,
                    'C',
                    $totDiskon,
                    $totDiskon,
                    'Diskon Pembelian',
                    'BRGBELI',
                    'BELIDISC',
                    'IDR',
                    1,
                    $r->kode_pp,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-'
                ]);
            }

            $insert9 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) 
            select ?,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,?,tot_diskon,total 
            from brg_trans_d_tmp 
            where nik_user=? and form='BRGBELI' ";
            DB::connection($this->db)->insert($insert9, [
                $id,
                $diskItem,
                $r->nik_user
            ]);

            $update = DB::connection($this->db)->update("update brg_barang
            set nilai_beli= b.harga, hna=b.harga_jual 
            from brg_barang a
            inner join brg_trans_d_tmp b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi and a.pabrik=b.kode_gudang 
            where b.nik_user=? and a.kode_lokasi=? and b.form='BRGBELI'
            ",[$r->nik_user,$kode_lokasi]);

            
            $get = DB::connection($this->db)->select("select sum(case dc when 'D' then nilai else -nilai end) as sls  
            from trans_j where no_bukti ='".$id."' and kode_lokasi='$kode_lokasi'
            ");
            
            if(count($get) > 0){
                $sls = $get[0]->sls;
            }else{
                $sls = 0;
            }
            
            if($sls < 0){
                $dc = "D";
            }else{
                $dc = "C";
            }
            
            $insls = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3,id_sync) values (?, ?, getdate(), ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($id,$kode_lokasi,$nik,$periode,'-',999,$akunpiu,$dc,abs($sls),abs($sls),'Selisih Koma','BRGBELI','SLS','IDR',1,$r->kode_pp,'-','-','-','-','-','-','-','-',NULL));

            $exec = DB::connection($this->db)->update("exec sp_brg_hpp ?,?,?,? ", array($id,$periode,$kode_lokasi,$nik));
            // $exec2 = DB::connection($this->db)->update("exec sp_brg_saldo_harian ?,? ", array($id,$kode_lokasi));
            $del = DB::connection($this->db)->table('brg_trans_d_tmp')
            ->where('nik_user', $r->nik_user)
            ->where('form', 'BRGBELI')
            ->delete();

            $tmp="Data Pembelian berhasil disimpan";
            $sts=true;
            DB::connection($this->db)->commit();

            $success['status'] = $sts;
            $success['message'] = $tmp;
            $success['no_bukti'] = $id;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembelian gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function storeDetail(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required',
            'kode_akun' => 'required',
            'kode_barang' => 'required',
            'harga_jual' => 'required',
            'harga_seb' => 'required',
            'stok_barang' => 'required',
            'qty_barang' => 'required',
            'harga_barang' => 'required',
            'satuan_barang' => 'required',
            'disc_barang' => 'required',
            'sub_barang' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix="PO/".substr($periode,2,4)."/".$kode_lokasi."/";
            $sql="select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";

            $get = DB::connection($this->db)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $kodeGudang=$pabrik;
            $diskItem=$r->input('disc_barang');

            $sql="select isnull(max(nu),0)+1 as no_urut from brg_trans_d_tmp where nik_user='$r->nik_user' ";
            $get = DB::connection($this->db)->select($sql);
            $no_urut = 0;
            if(count($get) > 0){
                $no_urut = $get[0]->no_urut;
            }

            $sql="select nu as no_urut, jumlah as qty, harga from brg_trans_d_tmp where nik_user='$r->nik_user' and kode_barang='$r->kode_barang' ";
            $get = DB::connection($this->db)->select($sql);
            if(count($get) > 0){
                $no_urut = $get[0]->no_urut;
                $qty = intval($get[0]->qty) + $r->input('qty_barang');
                $sub = floatval($get[0]->harga)*$qty;
                DB::connection($this->db)->update("update brg_trans_d_tmp
                set jumlah=?, total=? 
                where nik_user=? and nu=? and kode_barang=?",[$qty,$sub,$r->input('nik_user'),$no_urut,$r->input('kode_barang')]);
            }else{

                $insert9 = "insert into brg_trans_d_tmp (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total,harga_jual,harga_seb,kode_akun,nik_user,tgl_input) 
                values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,getdate())";
                DB::connection($this->db)->insert($insert9, [
                    $id,
                    $kode_lokasi,
                    $periode,
                    'BRGBELI',
                    'BRGBELI',
                    $no_urut,
                    $kodeGudang,
                    $r->input('kode_barang'),
                    '-',
                    date('Y-m-d H:i:s'),
                    $r->input('satuan_barang'),
                    'D',
                    $r->input('stok_barang'),
                    $r->input('qty_barang'),
                    0,
                    $r->input('harga_barang'),
                    0,
                    0,
                    $diskItem,
                    $r->input('disc_barang'),
                    $r->input('sub_barang'),
                    $r->input('harga_jual'),
                    $r->input('harga_seb'),
                    $r->input('kode_akun'),
                    $r->input('nik_user')
                ]);
            }
            

            $tmp="Data Detail Pembelian berhasil disimpan";
            $sts=true;
            DB::connection($this->db)->commit();

            $success['status'] = $sts;
            $success['message'] = $tmp;
            $success['no_bukti'] = $id;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Detail Pembelian gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function updateDetail(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required',
            'no_urut' => 'required',
            'kode_barang' => 'required',
            'harga_jual' => 'required',
            'harga_seb' => 'required',
            'qty_barang' => 'required',
            'harga_barang' => 'required',
            'disc_barang' => 'required',
            'sub_barang' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $pabrik= $data->pabrik;
            }
            
            DB::connection($this->db)->update("update brg_trans_d_tmp
            set harga_jual=?, harga_seb=?, jumlah=?, harga=?, diskon=?, total=? 
            where nik_user=? and nu=? and kode_barang=?",[$r->input('harga_jual'),$r->input('harga_seb'),$r->input('qty_barang'),$r->input('harga_barang'),$r->input('disc_barang'),$r->input('sub_barang'),$r->input('nik_user'),$r->input('no_urut'),$r->input('kode_barang')]);

            $tmp="Data Detail Pembelian berhasil diubah";
            $sts=true;
            DB::connection($this->db)->commit();

            $success['status'] = $sts;
            $success['message'] = $tmp;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Detail Pembelian gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    // public function update(Request $r)
    // {
    //     $this->validate($r, [
    //         'no_bukti'=>'required',
    //         'kode_vendor' => 'required',
    //         'no_faktur' => 'required',
    //         'kode_pp' => 'required',
    //         'total_trans' => 'required',
    //         'total_diskon' => 'required',
    //         'total_ppn' => 'required',
    //         'keterangan' => 'required',
    //         'kode_akun' => 'required|array',
    //         'kode_barang' => 'required|array',
    //         'qty_barang' => 'required|array',
    //         'harga_barang' => 'required|array',
    //         'satuan_barang' => 'required|array',
    //         'disc_barang' => 'required|array',
    //         'sub_barang' => 'required|array',
    //         'harga_jual' => 'required|array'
    //     ]);

    //     DB::connection($this->db)->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

    //         if(isset($r->nik) && $r->nik != ""){
    //             $nik= $r->nik;
    //         }
    //         $periode=date('Y').date('m');

    //         $id = $r->no_bukti;
    //         $sql="select * from trans_m where isnull(id_sync,'-') ='-' and no_bukti='$id' and kode_lokasi='$kode_lokasi'";

    //         $get = DB::connection($this->db)->select($sql);
    //         $get = json_decode(json_encode($get),true);
    //         if(count($get) > 0){

    //             $del = DB::connection($this->db)->table('trans_m')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_bukti', $r->no_bukti)
    //             ->where('nik_user', $nik)
    //             ->where('form', 'BRGBELI')
    //             ->delete();

    //             $del2 = DB::connection($this->db)->table('trans_j')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_bukti', $r->no_bukti)
    //             ->where('nik_user', $nik)
    //             ->where('modul', 'BRGBELI')
    //             ->delete();

    //             $del3 = DB::connection($this->db)->table('brg_belihut_d')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_beli', $r->no_bukti)
    //             ->where('nik_user', $nik)
    //             ->where('modul', 'BELI')
    //             ->delete();

    //             $del4 = DB::connection($this->db)->table('brg_trans_d')
    //             ->where('kode_lokasi', $kode_lokasi)
    //             ->where('no_bukti', $r->no_bukti)
    //             ->where('form', 'BRGBELI')
    //             ->delete();

               
    //             $sql="select kode_spro,flag from spro where kode_spro in ('PPNM','BELIDIS') and kode_lokasi = '".$kode_lokasi."'";
    //             $spro = DB::connection($this->db)->select($sql);
    //             if(count($spro) > 0){
    //                 foreach ($spro as $row){
    //                     if ($row->kode_spro == "PPNM") $akunPPN=$row->flag;
    //                     if ($row->kode_spro == "BELIDIS") $akunDiskon=$row->flag;
    //                 }
    //             }
    
    //             $sql3 = "select akun_hutang from vendor where kode_vendor ='".$r->kode_vendor."' and kode_lokasi = '".$kode_lokasi."'";
    //             $res = DB::connection($this->db)->select($sql3);
    //             if (count($res) > 0){
    //                 $akunHutang = $res[0]->akun_hutang;									
    //             }	
    
    //             $sqlg="select top 1 a.kode_gudang from brg_gudang a where a.kode_lokasi='$kode_lokasi' ";
    //             $rsg = DB::connection($this->db)->select($sqlg);
    //             if(count($rsg) > 0){
    //                 $kodeGudang=$rsg[0]->kode_gudang;
    //             }else{
    //                 $kodeGudang="-";
    //             }
    
    //             $exec = array();

    //             $insertM = "insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) 
    //             values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //             DB::connection($this->db)->insert($insertM, [
    //                 $id,
    //                 $kode_lokasi,
    //                 date('Y-m-d H:i:s'),
    //                 $nik,
    //                 $periode,
    //                 'IV',
    //                 'BRGBELI',
    //                 'F',
    //                 '-',
    //                 '-',
    //                 $r->kode_pp,
    //                 date('Y-m-d H:i:s'),
    //                 $r->no_faktur,
    //                 $r->keterangan,
    //                 'IDR',
    //                 1,
    //                 $r->total_trans,
    //                 $r->total_ppn,
    //                 $r->total_diskon,
    //                 '-',
    //                 '-',
    //                 '-',
    //                 '-',
    //                 '-',
    //                 '-',
    //                 '-',
    //                 $r->kode_vendor,
    //                 $akunHutang
    //             ]);
    
    //             // $sqlm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','IV','BRGBELI','F','-','-','".$r->kode_pp."',getdate(),'".$r->no_faktur."','Pembelian Persediaan','IDR',1,$r->total_trans,$r->total_ppn,$r->total_diskon,'-','-','-','-','-','-','-','".$r->kode_vendor."','".$akunHutang."')");
                
    //             $insertB = "insert into brg_belihut_d(no_beli,kode_lokasi,tanggal,keterangan,kode_vendor,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_hutang,nilai_ppn,no_fp,due_date, nilai_pph, diskon, modul,kode_gudang) 
    //             values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //             DB::connection($this->db)->insert($insertB, [
    //                 $id,
    //                 $kode_lokasi,
    //                 date('Y-m-d H:i:s'), 
    //                 'Pembelian Persediaan',
    //                 $r->kode_vendor,
    //                 'IDR',
    //                 1,
    //                 $r->kode_pp,
    //                 $r->total_trans,
    //                 $periode,
    //                 $nik,
    //                 date('Y-m-d H:i:s'),
    //                 $akunHutang,
    //                 $r->total_ppn,
    //                 '-',
    //                 date('Y-m-d H:i:s'),
    //                 0,
    //                 $r->total_diskon, 
    //                 'BELI',
    //                 $kodeGudang 
    //             ]);
    //             // $sqlb = DB::connection($this->db)->insert("insert into brg_belihut_d(no_beli,kode_lokasi,tanggal,keterangan,kode_vendor,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_hutang,nilai_ppn,no_fp,due_date, nilai_pph, diskon, modul,kode_gudang) values ('".$id."','".$kode_lokasi."',getdate(), 'Pembelian Persediaan','".$r->kode_vendor."','IDR',1,'".$r->kode_pp."',$r->total_trans,'".$periode."','".$nik."',getdate(),'".$akunHutang."',$r->total_ppn,'-',getdate(),0,$r->total_diskon, 'BELI','$kodeGudang')");
                            
    //             $series = array();
    //             $series2 = array();
    //             $group = array();
    //             $nilai = 0;
    //             $diskItem = 0;
    //             $total=0;
    //             for($b=0; $b<count($r->kode_barang);$b++){
    //                 $nilai = $r->sub_barang[$b];
    //                 $isAda = false;
    //                 $idx = 0;
                    
    //                 $akun = $r->kode_akun[$b];						
    //                 for ($c=0;$c <= $b;$c++){
    //                     if(isset($r->kode_akun[$c-1])){
    //                         if ($akun == $r->kode_akun[$c-1]) {
    //                             $isAda = true;
    //                             $idx = $c;
    //                             break;
    //                         }
    //                     }
    //                 }
    //                 if (!$isAda) {							
    //                     array_push($series,$r->kode_akun[$b]);
                        
    //                     $series2[$r->kode_akun[$b]]=$nilai;
    //                 } 
    //                 else { 
    //                     $total = $series2[$r->kode_akun[$b]];
    //                     $total = $total + $nilai;
    //                     $series2[$r->kode_akun[$b]]=$total;
    //                 }		
                        
    //                 $diskItem+=$r->disc_barang[$b];
    //             }
                
    //             for($x=0; $x<count($series);$x++){
                    
    //                 // $sqlj=DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$r->kode_akun[$x]."','D',".$series2[$series[$x]].",".$series2[$series[$x]].",'Persediaan Barang','BRGBELI','BRGBELI','IDR',1,'$r->kode_pp','-','-','-','-','-','-','-','-')");
    //                 $insertJ = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
    //                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //                 DB::connection($this->db)->insert($insertJ, [
    //                     $id,
    //                     $kode_lokasi,
    //                     date('Y-m-d H:i:s'),
    //                     $nik,
    //                     $periode,
    //                     '-',
    //                     date('Y-m-d H:i:s'),
    //                     $x,
    //                     $r->kode_akun[$x],
    //                     'D',
    //                     $series2[$series[$x]],
    //                     $series2[$series[$x]],
    //                     $r->keterangan,
    //                     'BRGBELI',
    //                     'BRGBELI',
    //                     'IDR',
    //                     1,
    //                     $r->kode_pp,
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-'   
    //                 ]);
                        
    //             }
                
    //             $totDiskon = $r->total_diskon +$diskItem;
    //             if ($r->total_ppn > 0) {
    //                 $x=$x+1;
    //                 $insert6 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
    //                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //                 DB::connection($this->db)->insert($insert6, [
    //                     $id,
    //                     $kode_lokasi,
    //                     date('Y-m-d H:i:s'),
    //                     $nik,
    //                     $periode,
    //                     '-',
    //                     date('Y-m-d H:i:s'),
    //                     $x,
    //                     $akunPPN,
    //                     'D',
    //                     $r->total_ppn,
    //                     $r->total_ppn,
    //                     'PPN Masukan',
    //                     'BRGBELI',
    //                     'PPNM',
    //                     'IDR',
    //                     1,
    //                     $r->kode_pp,
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-'
    //                 ]);
    //                 // $sql6=DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunPPN."','D',".$r->total_ppn.",".$r->total_ppn.",'PPN Masukan','BRGBELI','PPNM','IDR',1,'$r->kode_pp','-','-','-','-','-','-','-','-')");
                        
    //             }
    
    //             if ($r->total_trans > 0) {
    //                 $x=$x+1;
    //                 $hut = floatval($r->total_trans)+floatval($r->total_ppn)-floatval($totDiskon);
    //                 $insert7 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
    //                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //                 DB::connection($this->db)->insert($insert7, [
    //                     $id,
    //                     $kode_lokasi,
    //                     date('Y-m-d H:i:s'),
    //                     $nik,
    //                     $periode,
    //                     '-',
    //                     date('Y-m-d H:i:s'),
    //                     $x,
    //                     $akunHutang,
    //                     'C',
    //                     $hut,
    //                     $hut,
    //                     'Hutang Vendor Pembelian',
    //                     'BRGBELI',
    //                     'BELIDISC',
    //                     'IDR',
    //                     1,
    //                     $r->kode_pp,
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-'
    //                 ]);
    //                 // $sql7= DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunHutang."','C',".$r->total_trans.",".$r->total_trans.",'Hutang Vendor Pembelian','BRGBELI','BELIDISC','IDR',1,'$r->kode_pp','-','-','-','-','-','-','-','-')");
    //             }
                
    //             if ($r->total_diskon > 0) {
    //                 $x=$x+1;
    //                 $insert8 = "insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) 
    //                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //                 DB::connection($this->db)->insert($insert8, [
    //                     $id,
    //                     $kode_lokasi,
    //                     date('Y-m-d H:i:s'),
    //                     $nik,
    //                     $periode,
    //                     '-',
    //                     date('Y-m-d H:i:s'),
    //                     $x,
    //                     $akunDiskon,
    //                     'C',
    //                     $totDiskon,
    //                     $totDiskon,
    //                     'Diskon Pembelian',
    //                     'BRGBELI',
    //                     'BELIDISC',
    //                     'IDR',
    //                     1,
    //                     $r->kode_pp,
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-',
    //                     '-'
    //                 ]);
    //                 // $sql8=  DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$id."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-',getdate(),".$x.",'".$akunDiskon."','C',".$totDiskon.",".$totDiskon.",'Diskon Pembelian','BRGBELI','BELIDISC','IDR',1,'$r->kode_pp','-','-','-','-','-','-','-','-')");
    //             }
                
    //             for($a=0; $a<count($r->kode_barang);$a++){
                    
    //                 $insert9 = "insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) 
    //                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    //                 DB::connection($this->db)->insert($insert9, [
    //                     $id,
    //                     $kode_lokasi,
    //                     $periode,
    //                     'BRGBELI',
    //                     'BRGBELI',
    //                     $a,
    //                     $kodeGudang,
    //                     $r->kode_barang[$a],
    //                     '-',
    //                     date('Y-m-d H:i:s'),
    //                     $r->satuan_barang[$a],
    //                     'D',
    //                     0,
    //                     $r->qty_barang[$a],
    //                     0,
    //                     $r->harga_barang[$a],
    //                     0,
    //                     0,
    //                     $diskItem,
    //                     $r->disc_barang[$a],
    //                     $r->sub_barang[$a]
    //                 ]);
    //                 // $sql9 = DB::connection($this->db)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values 
    //                 // ('".$id."','".$kode_lokasi."','".$periode."','BRGBELI','BRGBELI',".$a.",'$kodeGudang','".$r->kode_barang[$a]."','-',getdate(),'".$r->satuan_barang[$a]."','D',0,".$r->qty_barang[$a].",0,".$r->harga_barang[$a].",0,0,".$diskItem.",".$r->disc_barang[$a].",".$r->sub_barang[$a].")");
    
    //                 $update = DB::connection($this->db)->table('brg_barang')
    //                 ->where('kode_lokasi', $kode_lokasi)
    //                 ->where('kode_barang', $r->kode_barang[$a])->update(['nilai_beli'=>$r->harga_barang[$a],'hna'=>$r->harga_jual[$a]]);
    //             }
                
    //             $exec = DB::connection($this->db)->update("exec sp_brg_hpp ?,?,?,? ", array($id,$periode,$kode_lokasi,$nik));
    //             // $exec2 = DB::connection($this->db)->update("exec sp_brg_saldo_harian ?,? ", array($id,$kode_lokasi));
    
    //             DB::connection($this->db)->commit();
    //             $tmp = "Data Pembelian berhasil diubah";
    //             $sts = true;
    //         }else{
    //             $tmp = "No Pembelian = ".$id." tidak dapat diedit karena sudah disyncronize ";
    //             $sts = false;
    //         }
            
    //         $success['status'] = $sts;
    //         $success['message'] = $tmp;
    //         $success['no_bukti']= $id;
            
    //         return response()->json($success, $this->successStatus);     
    //     } catch (\Throwable $e) {
    //         DB::connection($this->db)->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Data Pembelian gagal diubah ".$e;
    //         return response()->json($success, $this->successStatus); 
    //     }				
        
        
    // }

    public function destroy(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $del = DB::connection($this->db)->table('trans_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $r->no_bukti)
            ->where('nik_user', $nik)
            ->where('form', 'BRGBELI')
            ->delete();

            $del2 = DB::connection($this->db)->table('trans_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $r->no_bukti)
            ->where('nik_user', $nik)
            ->where('modul', 'BRGBELI')
            ->delete();

            $del3 = DB::connection($this->db)->table('brg_belihut_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_beli', $r->no_bukti)
            ->where('nik_user', $nik)
            ->where('modul', 'BELI')
            ->delete();

            $del4 = DB::connection($this->db)->table('brg_trans_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $r->no_bukti)
            ->where('form', 'BRGBELI')
            ->delete();


            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembelian berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembelian gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function clearTmp(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $del = DB::connection($this->db)->table('brg_trans_d_tmp')
            ->where('nik_user', $r->nik_user)
            ->where('form', 'BRGBELI')
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Detail Pembelian berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Detail Pembelian gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroyDetail(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required',
            'no_urut' => 'required',
            'kode_barang' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->nik) && $r->nik != ""){
                $nik= $r->nik;
            }

            $del = DB::connection($this->db)->table('brg_trans_d_tmp')
            ->where('nik_user', $r->nik_user)
            ->where('nu', $r->no_urut)
            ->where('kode_barang', $r->kode_barang)
            ->where('form', 'BRGBELI')
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Detail Pembelian berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Detail Pembelian gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
