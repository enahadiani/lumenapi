<?php
namespace App\Http\Controllers\Yakes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helper\SaiHelpers;

class AnperController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yakes';
    public $db = 'dbsapkug';

    function getLabaRugi(Request $request,$anper){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            switch($anper){
                case 'tm':
                    $dbanper = "dbtm1";
                    $kode_lokasi = "51";
                break;
                default :
                    $dbanper = "";
                    $kode_lokasi = "";
                break;
            }

            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $where = SaiHelpers::filterRpt($request,$col_array,$db_col_name,$where,"");

            $nik_user = (isset($request->nik_user) ? $request->nik_user : $nik);
            $periode = $request->input('periode');
            $kode_fs = (isset($request->kode_fs) ? $request->input('kode_fs') : 'FS1');
            $level = (isset($request->level) ? $request->input('level') : 1);
            $format = (isset($request->kode_fs) ? $request->input('kode_fs') : 'Saldo Akhir');
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            // SAVE LOG TO DB
            $log = print_r($request->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_anper_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'Laba Rugi',$dbanper));
            // END SAVE

            $dbh = DB::connection($this->db)->getPdo();
            $sth = $dbh->prepare("SET NOCOUNT ON; EXEC dbtm1.dbo.sp_neraca_dw '$kode_fs','L','S','$level','$periode','$kode_lokasi','$nik_user'; ");
            $sth->execute();

            $sql2="select max(periode) as periode from dbtm1.dbo.periode where kode_lokasi='$kode_lokasi'";
            $row = DB::connection($this->db)->select($sql2);
            $periode_aktif = $row[0]->periode;
            $nama_periode= SaiHelpers::getNamaBulan($periode);

            if($format == "Mutasi"){
                $sql3 = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                case jenis_akun when  'Pendapatan' then -(n2-n3) else n2-n3 end as n4
                from dbtm1.dbo.neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex ";
                
            }else if($format == "DC"){
                $sql3 = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                    case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
                    case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2,
                    case jenis_akun when  'Pendapatan' then -n3 else n3 end as n3,
                    case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from dbtm1.dbo.neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex";
            }
            else{
                $sql3 = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
                case jenis_akun when  'Pendapatan' then -n4 else n4 end as n4
                from dbtm1.dbo.neraca_tmp 
                where modul='L' and nik_user='$nik_user' 
                order by rowindex";
            }
           
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $success["nama_periode"] = $nama_periode;
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                $success["auth_status"] = 1;   
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            $success["auth_status"] = 2;   
            return response()->json($success, $this->successStatus);
        }
    }
    
}
?>