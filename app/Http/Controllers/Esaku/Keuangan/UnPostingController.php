<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class UnPostingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $sql = 'tokoaws';
    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
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
            'deskripsi' => 'required',
            'tanggal' => 'required',
            'detail.*.no_bukti' => 'required',
            'detail.*.form' => 'required'
        ]);

        try {
            
            date_default_timezone_set('Asia/Jakarta');
            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $det = $request->input('detail');
            $arr_nobukti = array();
            $arr_nobukti2 = "";
            if(count($det) > 0){
                $isAda = false;
                for ($i=0;$i < count($det);$i++){
                    $line = $det[$i];
                    $arr_nobukti[] = $line['no_bukti'];
                    $arr_nobukti2 .= ",'".$line['no_bukti']."'"; 
                    $isAda = true;
                }
            
                if($isAda){

                    $arr_nobukti2 = substr($arr_nobukti2,1);
                    $strSQL = "select no_bukti+' - '+periode as bukper from ( 
                                select a.no_bukti,a.periode,sum(case a.dc when 'D' then a.nilai else -a.nilai end) as total 
                                from trans_j a inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and b.posted='T' 
                                where a.no_bukti in (".$arr_nobukti2.") and a.kode_lokasi='".$kode_lokasi."' group by a.no_bukti,a.periode ) x where round(x.total,4) <> 0 ";						 
				
                    $cek = DB::connection($this->sql)->select($strSQL);
                    $msg = "";
                    if (count($cek) > 0){			
                        for ($i=0; $i <count($cek);$i++){																		
                            $msg+= "Data Bukti Tidak Balance.(Bukti - Periode : ".$cek[$i]['bukper'].")\n";
                        }
                    }	
                    if ($msg != "") {
                        $tmp = "UnPosting tidak valid. Terdapat Bukti Jurnal tidak Balanace Lihat Pesan Error. ".$msg;
                        $sts = false;
                    }else{

                        DB::connection($this->sql)->beginTransaction();
            
                        $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                        $no_bukti = $this->generateKode("unposting_m", "no_unpost", $kode_lokasi."-UP".substr($periode,2,4).".", "0001");
            
                        $ins = DB::connection($this->sql)->insert("insert into unposting_m(no_unpost,kode_lokasi,periode,tanggal,modul,keterangan,nik_buat,nik_app,no_del,tgl_input,nik_user,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$periode,$request->tanggal,'-',$request->deskripsi,$nik,$nik,'-',date('Y-m-d H:i:s'),$nik,0));
        
                        for ($i=0;$i < count($det);$i++){
                            DB::connection($this->sql)->getPdo()->exec("EXEC sp_unpost_bukti '$kode_lokasi','".$det[$i]['no_bukti']."','".$no_bukti."' ");    
                        }

                        DB::connection($this->sql)->getPdo()->exec("EXEC sp_exs_proses '$kode_lokasi','$periode','FS1' ");
                        $sts = true;
                        $msg = "UnPosting data berhasil disimpan ";
                    }

                }else{
                    $sts = false;
                    $msg = "Transaksi tidak valid. Tidak ada transaksi dengan status UNPOSTING ";
                }
            }

            $success['no_bukti'] = $no_bukti;
            if($sts){
                DB::connection($this->sql)->commit();
                $success['status'] = $sts;
                $success['message'] = "Data UnPosting berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->sql)->rollback();
                $success['status'] = $sts;
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Posting gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function getModul()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            if ($status_admin == "U") {
				$strSQL = "select modul,keterangan,per_awal1 as per1,per_akhir1 as per2 from periode_aktif where kode_lokasi='".$kode_lokasi."' order by modul";							
				$batasPeriode = " between b.per_awal1 and b.per_akhir1 ";	
			}
			else {
				$strSQL = "select modul,keterangan,per_awal2 as per1,per_akhir2 as per2 from periode_aktif where kode_lokasi='".$kode_lokasi."' order by modul";				
				$batasPeriode = " between b.per_awal2 and b.per_akhir2 ";
			}

            $res = DB::connection($this->sql)->select($strSQL);						
            $res= json_decode(json_encode($res),true);
            
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function loadData(Request $request)
    {
        try {
            $this->validate($request, [
                'data_modul.*.modul' => 'required',
                'data_modul.*.periode_awal' => 'required',
                'data_modul.*.periode_akhir' => 'required'
            ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $strSQL = "";
            $res = $request->input('data_modul');
            if(count($res) > 0){

                for ($i=0;$i < count($res);$i++){	
                  
                    $strSQL .= "union all 
                                select 'INPROG' as status,a.no_bukti as no_bukti,a.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.form
                                from trans_m a  
                                where a.modul = '".$res[$i]['modul']."' and a.posted='T' and a.periode between '".$res[$i]['periode_awal']."' and '".$res[$i]['periode_akhir']."' and a.kode_lokasi='".$kode_lokasi."' ";								
                    
                }		
            }
            
            $strSQL = substr($strSQL,9);
            $result = DB::connection($this->sql)->select($strSQL);						
            $result= json_decode(json_encode($result),true);
            
           
            if(count($result) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $result;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json(['success'=>$success], $this->successStatus);
        }
        
    }

}
