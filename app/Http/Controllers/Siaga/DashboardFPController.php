<?php

namespace App\Http\Controllers\Siaga;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    private function filterReq($r,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($r->input($col_array[$i])[0])){
                if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                    $where .= " AND (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                }elseif($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                }elseif($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                    $tmp = explode(",",$r->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }else{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " AND ".$db_col_name[$i]." in ($this_in) ";
                }elseif($r->input($col_array[$i])[0] == "<=" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <= '".$r->input($col_array[$i])[1]."' ";
                }elseif($r->input($col_array[$i])[0] == "<>" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <> '".$r->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public function getDataBox(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $r->periode;

            $sql = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='41' and kode_fs='FS1' and modul='L'";
            $select = DB::connection($this->db)->select($sql);

            $sql2 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='42' and kode_fs='FS1' and modul='L'";
            $select2 = DB::connection($this->db)->select($sql2);

            $sql3 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='4T' and kode_fs='FS1' and modul='L'";
            $select3 = DB::connection($this->db)->select($sql3);

            $sql4 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='59' and kode_fs='FS1' and modul='L'";
            $select4 = DB::connection($this->db)->select($sql4);

            $sql5 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='74' and kode_fs='FS1' and modul='L'";
            $select5 = DB::connection($this->db)->select($sql5);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'revenue' => [
                    'nilai' => count($select) > 0 ? round($select[0]->nilai,0) : 0,
                    'rka' => count($select) > 0 ? round($select[0]->rka,0) : 0,
                    'yoy' => count($select) > 0 ? round($select[0]->yoy,0) : 0,
                    'capai_rka' => count($select) > 0 ? ($select[0]->rka <> 0 ? round(($select[0]->nilai/$select[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select) > 0 ? ($select[0]->yoy <> 0 ? round((($select[0]->nilai-$select[0]->yoy)/$select[0]->yoy)*100,1) : 0) : 0,
                ],
                'cogs' => [
                    'nilai' => count($select2) > 0 ? round($select2[0]->nilai,0) : 0,
                    'rka' => count($select2) > 0 ? round($select2[0]->rka,0) : 0,
                    'yoy' => count($select2) > 0 ? round($select2[0]->yoy,0) : 0,
                    'capai_rka' => count($select2) > 0 ? ($select2[0]->rka <> 0 ? round(($select2[0]->nilai/$select2[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select2) > 0 ? ($select2[0]->yoy <> 0 ? round((($select2[0]->nilai-$select2[0]->yoy)/$select2[0]->yoy)*100,1) : 0) : 0,
                ],
                'gross_profit' => [
                    'nilai' => count($select3) > 0 ? round($select3[0]->nilai,0) : 0,
                    'rka' => count($select3) > 0 ? round($select3[0]->rka,0) : 0,
                    'yoy' => count($select3) > 0 ? round($select3[0]->yoy,0) : 0,
                    'capai_rka' => count($select3) > 0 ? ($select3[0]->rka <> 0 ? round(($select3[0]->nilai/$select3[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select3) > 0 ? ($select3[0]->yoy <> 0 ? round((($select3[0]->nilai-$select3[0]->yoy)/$select3[0]->yoy)*100,1) : 0) : 0,
                ],
                'opex' => [
                    'nilai' => count($select4) > 0 ? round($select4[0]->nilai,0) : 0,
                    'rka' => count($select4) > 0 ? round($select4[0]->rka,0) : 0,
                    'yoy' => count($select4) > 0 ? round($select4[0]->yoy,0) : 0,
                    'capai_rka' => count($select4) > 0 ? ($select4[0]->rka <> 0 ? round(($select4[0]->nilai/$select4[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select4) > 0 ? ($select4[0]->yoy <> 0 ? round((($select4[0]->nilai-$select4[0]->yoy)/$select4[0]->yoy)*100,1) : 0) : 0,
                ],
                'net_income' => [
                    'nilai' => count($select5) > 0 ? round($select5[0]->nilai,0) : 0,
                    'rka' => count($select5) > 0 ? round($select5[0]->rka,0) : 0,
                    'yoy' => count($select5) > 0 ? round($select5[0]->yoy,0) : 0,
                    'capai_rka' => count($select5) > 0 ? ($select5[0]->rka <> 0 ? round(($select5[0]->nilai/$select5[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select5) > 0 ? ($select5[0]->yoy <> 0 ? round((($select5[0]->nilai-$select5[0]->yoy)/$select5[0]->yoy)*100,1) : 0) : 0,
                ]
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    
}
