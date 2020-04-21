<?php

namespace App\Http\Controllers\Gl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
    $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
    $query = json_decode(json_encode($query),true);
    $kode = $query[0]['id'];
    $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
    return $id;
}


class PostingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

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
            'detail.*.status' => 'required',
            'detail.*.no_bukti' => 'required',
            'detail.*.form' => 'required'
        ]);

        try {

            if($rs =  Auth::guard('admin')->user()){
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
                    if (strtoupper($line['status']) == "POSTING"){
                        $arr_nobukti[] = $line['no_bukti'];
                        $arr_nobukti2 .= ",'".$line['no_bukti']."'"; 
                        $isAda = true;
                    }
                }
            
                if($isAda){

                    $strSQL = "select no_bukti+' - '+periode as bukper from ( 
                                select a.no_bukti,a.periode,sum(case a.dc when 'D' then a.nilai else -a.nilai end) as total 
                                from trans_j a inner join trans_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and b.posted='F' 
                                where a.no_bukti in (".$arr_nobukti2.") and a.kode_lokasi='".$kode_lokasi."' group by a.no_bukti,a.periode ) x where round(x.total,4) <> 0 ";						 
				
                    // $cek = DB::connection('sqlsrv2')->select($strSQL);
                    $msg = $strSQL;
                    // if (count($cek) > 0){			
                    //     for ($i=0; $i <count($cek);$i++){																		
                    //         $msg+= "Data Bukti Tidak Balance.(Bukti - Periode : ".$cek[$i]['bukper'].")\n";
                    //     }
                    // }	
                    // if ($msg != "") {
                    //     $tmp = "Posting tidak valid. Terdapat Bukti Jurnal tidak Balanace Lihat Pesan Error. ".$msg;
                    //     $sts = false;
                    // }else{

                    //     $arr_nobukti = substr($arr_nobukti,1);
                    //     DB::connection('sqlsrv2')->beginTransaction();
            
                    //     // $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
                    //     // $no_bukti = generateKode("posting_m", "no_post", $kode_lokasi."-PT".substr($periode,2,4).".", "0001");
            
                    //     // $del = DB::connection('sqlsrv2')->table('gldt')->whereIn('no_bukti',$arr_nobukti)->where('kode_lokasi', $kode_lokasi)->delete();
                        
                    //     // $ins = DB::connection('sqlsrv2')->insert("insert into posting_m(no_post,kode_lokasi,periode,tanggal,modul,keterangan,nik_buat,nik_app,no_del,tgl_input,nik_user,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",[$no_bukti,$kode_lokasi,$periode,$request->tanggal,'-',$request->deskripsi,$nik,$nik,'-',getdate(),$nik,0]);
        
                    //     // for ($i=0;$i < count($det);$i++){
                    //     //     $line = $det[$i];
                    //     //     if (strtoupper($line['status']) == "POSTING"){
                    //     //         $arr_nobukti[] = $line['no_bukti'];
                    //     //         $ins2[$i] = DB::connection('sqlsrv2')->insert("insert into posting_d(no_post,modul,no_bukti,status,catatan,no_del,kode_lokasi,periode) values () ",[$no_bukti,strtoupper($line['form']),$line['no_bukti'],strtoupper($line['status']),'-','-',$kode_lokasi,$periode]);
        
                    //     //         $call[$i] = DB::connection('sqlsrv2')->select("exec sp_post_bukti (?, ?) ", array($kode_lokasi,$line['no_bukti']));
                             
                    //     //     }
                    //     // }
                        
                    //     // $call2 = DB::connection('sqlsrv2')->select("exec sp_exs_proses (?, ?, ?) ", array($kode_lokasi,$periode,'FS1'));
                        $sts = true;
                    //     $msg = "Posting data berhasil disimpan ";
                    // }

                }else{
                    $sts = false;
                    $msg = "Transaksi tidak valid. Tidak ada transaksi dengan status POSTING ";
                }
            }


            if($sts){
                DB::connection('sqlsrv2')->commit();
                $success['status'] = $sts;
                $success['message'] = "Data Posting berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection('sqlsrv2')->rollback();
                $success['status'] = $sts;
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Posting gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function getModul()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
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

            $res = DB::connection('sqlsrv2')->select($strSQL);						
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

            if($data =  Auth::guard('admin')->user()){
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
                                where a.modul = '".$res[$i]['modul']."' and a.posted='F' and a.periode between '".$res[$i]['periode_awal']."' and '".$res[$i]['periode_akhir']."' and a.kode_lokasi='".$kode_lokasi."' ";								
                    
                }		
            }
            
            $strSQL = substr($strSQL,9);
            $result = DB::connection('sqlsrv2')->select($strSQL);						
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
