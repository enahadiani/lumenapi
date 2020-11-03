<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    public function rata2Nilai(Request $request){
        $this->validate($request,[
            'kode_kelas' => 'required',
            'kode_matpel' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $rs = DB::connection($this->db)->select("select a.kode_kd,a.nama
            from sis_kd a
            inner join sis_tingkat b on a.kode_tingkat=b.kode_tingkat and a.kode_lokasi=b.kode_lokasi
            inner join sis_kelas c on b.kode_tingkat=c.kode_tingkat and b.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$request->kode_pp' and c.kode_kelas='$request->kode_kelas' 
            and a.kode_matpel='$request->kode_matpel'
            order by a.kode_kd
            ");
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['kode_kd']);
                }
            }
            $success['ctg']=$ctg;
            
            $sql2 = "select a.kode_kd,a.nama,b.kode_kelas,a.kode_matpel,isnull(c.rata2,0) as nilai,isnull(d.nilai_tertinggi,0) as nilai_tertinggi,isnull(e.nilai_terendah,0) as nilai_terendah 
            from sis_kd a 
            inner join sis_kelas b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and a.kode_tingkat=b.kode_tingkat
            left join (
                        select a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp,avg(b.nilai) as rata2
                        from sis_nilai_m a
                        inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        where a.kode_pp='$request->kode_pp'
                        group by a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp
            ) c on a.kode_kd=c.kode_kd and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp and a.kode_matpel=c.kode_matpel and b.kode_kelas=c.kode_kelas and a.kode_sem=c.kode_sem
            left join (
                        select a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp,max(b.nilai) as nilai_tertinggi
                        from sis_nilai_m a
                        inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        where a.kode_pp='$request->kode_pp'
                        group by a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp
            ) d on a.kode_kd=d.kode_kd and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp and a.kode_matpel=d.kode_matpel and b.kode_kelas=d.kode_kelas and a.kode_sem=d.kode_sem
            left join (
                        select a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp,min(b.nilai) as nilai_terendah
                        from sis_nilai_m a
                        inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        where a.kode_pp='$request->kode_pp'
                        group by a.kode_kd,a.kode_matpel,a.kode_kelas,a.kode_sem,a.kode_lokasi,a.kode_pp
            ) e on a.kode_kd=e.kode_kd and a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp and a.kode_matpel=e.kode_matpel and b.kode_kelas=e.kode_kelas and a.kode_sem=e.kode_sem
            where a.kode_pp='$request->kode_pp' and a.kode_lokasi='$kode_lokasi' and a.kode_matpel='$request->kode_matpel' and b.kode_kelas='$request->kode_kelas'
            order by a.kode_kd";
            // $success['sql2'] = $sql2;
            $rs2 = DB::connection($this->db)->select($sql2) ;

            $row = json_decode(json_encode($rs2),true);

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dtAvg[] = array($row[$i]["kode_kd"],round($row[$i]["nilai"],2));
                }

                for($i=0;$i<count($row);$i++){
                    $dtRange[] = array($row[$i]["kode_kd"],floatval($row[$i]["nilai_terendah"]),floatval($row[$i]["nilai_tertinggi"]));
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                $success['avg'] = $dtAvg;
                $success['range'] = $dtRange; 
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function jumDibawahKKM(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'kode_kelas' => 'required',
            'kode_sem' => 'required',
            'kode_ta' => 'required',
            'kode_matpel' => 'required',
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }else{
                $nik= '';
                $kode_lokasi= '';
                $kode_pp = '';
            }
            
            $res = DB::connection($this->db)->select("select a.kode_kd,a.kkm, isnull(round((CAST (d.jum as float) / c.jum)*100,1),0) as persen 
            from sis_kd a
            inner join sis_kelas b on a.kode_tingkat=b.kode_tingkat and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join (select a.kode_lokasi,a.kode_pp,a.kode_kelas,count(a.nis) as jum
                      from sis_siswa a
                      where a.flag_aktif=1
                      group by a.kode_lokasi,a.kode_pp,a.kode_kelas
                       ) c on b.kode_kelas=c.kode_kelas and b.kode_lokasi=c.kode_lokasi and b.kode_pp=c.kode_pp
            left join (select a.kode_kd,a.kode_pp,a.kode_lokasi,a.kode_matpel,a.kode_ta,a.kode_sem,a.kode_kelas,count(b.nis) as jum
                        from sis_nilai_m a 
                        inner join sis_nilai b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        inner join sis_siswa d on b.nis=d.nis and b.kode_lokasi=d.kode_lokasi and b.kode_pp=d.kode_pp
                        inner join sis_kelas e on d.kode_kelas=e.kode_kelas and d.kode_lokasi=e.kode_lokasi and d.kode_pp=e.kode_pp
                        inner join sis_kd c on a.kode_kd=c.kode_kd and a.kode_matpel=c.kode_matpel and a.kode_ta=c.kode_ta and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp and a.kode_sem=c.kode_sem and e.kode_tingkat=c.kode_tingkat
                        where (b.nilai < c.kkm)
                        group by a.kode_kd,a.kode_pp,a.kode_lokasi,a.kode_matpel,a.kode_ta,a.kode_sem,a.kode_kelas
                       ) d on a.kode_kd=d.kode_kd and b.kode_kelas=d.kode_kelas and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp and a.kode_sem=d.kode_sem and a.kode_matpel=d.kode_matpel and a.kode_ta=d.kode_ta 
            where a.kode_matpel='$request->kode_matpel' and b.kode_kelas='$request->kode_kelas' and a.kode_sem='$request->kode_sem' and a.kode_ta='$request->kode_ta' and a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                for($i=0;$i<count($res);$i++){
                    $daftar[] = array("y"=>floatval($res[$i]['persen']),"name"=>$res[$i]['kode_kd'],"key"=>$res[$i]['kode_kd']); 
                    $ctg[] = array($res[$i]['kode_kd']);
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['ctg'] = $ctg;
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
}