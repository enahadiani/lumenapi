<?php

namespace App\Http\Controllers\Simkug\Setting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exports\AktivitasUserExport;
use Maatwebsite\Excel\Facades\Excel; 
use PhpOffice\PhpSpreadsheet\Shared\Date; 

class HistoriAksesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsimkug';
    public $guard = 'simkug';

    function filterReq($request,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($request->input($col_array[$i])[0])){
                if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                    $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }else{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public function getAktivitasUser(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $col_array = array('tanggal');
            $db_col_name = array('convert(date,a.timeacc)');
            $this_in = "";
            $where = $this->filterReq($request,$col_array,$db_col_name,"",$this_in);
            $where = $where == "" ? "" : "where ".substr($where,4);
            $sql= "
            select a.uid as nik,a.timeacc as tanggal,a.form as page,e.nama as nama_form,a.userloc,a.id,a.session
            from userformacces_esaku a
            inner join m_form b on REPLACE(a.form,'app_simkug_','app_')=b.form
            left join hakakses d on a.uid=d.nik 
            left join menu e on d.kode_menu_lab=e.kode_klp and b.kode_form=e.kode_form
            left join karyawan c on a.uid=c.nik
            $where
            order by timeacc desc
            ";
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

    public function exportAktivitas(Request $request) 
    {
        $this->validate($request, [
            'kode_lokasi' => 'required',
            'nik' => 'required'
        ]);

        date_default_timezone_set("Asia/Jakarta");
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        return Excel::download(new AktivitasUserExport($kode_lokasi,$request), 'AktivitasUser_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        
    }
}
