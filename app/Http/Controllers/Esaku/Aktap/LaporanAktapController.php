<?php

namespace App\Http\Controllers\Esaku\Aktap;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanAktapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $sql = 'tokoaws';

    function getAktap(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_klpakun','jenis','no_fa','kode_pp','catatan');
            $db_col_name = array('a.periode','a.kode_klpakun','a.jenis','a.no_fa','a.kode_pp','a.catatan');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql3 = "select a.kode_klpakun,a.kode_lokasi,b.nama as nama_klpakun,a.kode_pp,c.nama as nama_pp,a.catatan,a.no_fa,a.nama,convert(varchar,a.tgl_perolehan,103) as tgl,
                    a.umur,a.persen,aa.nilai,isnull(e.jml,0) as jml,
                isnull(d.akumulasi_sd,0)-isnull(f.wo_ap_sd,0) as akumulasi_sd,isnull(e.akumulasi_bln,0)-isnull(g.wo_ap,0) as akumulasi_bln,
                                isnull(d.akumulasi_sd,0)+isnull(e.akumulasi_bln,0)-isnull(g.wo_ap,0)-isnull(f.wo_ap_sd,0) as akumulasi_total,isnull(f.wo_sd,0)+isnull(g.wo,0) as wo,
                                case when isnull(g.wo,0)+isnull(f.wo_sd,0)=0 then a.nilai-isnull(d.akumulasi_sd,0)-isnull(e.akumulasi_bln,0) else 0 end as nilai_buku
            from fa_asset a
            inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.progress not in ('K','P') and a.jenis<>'I' and a.kode_lokasi=b.kode_lokasi
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            
            inner join (select b.no_fa,b.kode_lokasi,sum(case b.dc when 'D' then b.nilai else -b.nilai end) as nilai 
                        from fa_asset a
                        inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi 
                        $where
                        group by b.no_fa,b.kode_lokasi
                        ) aa on a.no_fa=aa.no_fa and a.kode_lokasi=aa.kode_lokasi
            
            left join (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as akumulasi_sd
                        from fasusut_d
                    where kode_lokasi='$kode_lokasi' and periode<'$periode_susut'
                        group by no_fa,kode_lokasi
                        )d on a.no_fa=d.no_fa and a.kode_lokasi=d.kode_lokasi
            left join (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as akumulasi_bln,
                            count(no_fa) as jml
                        from fasusut_d
                    where kode_lokasi='$kode_lokasi' and periode='$periode_susut'
                        group by no_fa,kode_lokasi
                        )e on a.no_fa=e.no_fa and a.kode_lokasi=e.kode_lokasi
            left join (select no_fa,kode_lokasi,sum(nilai) as wo_sd,sum(nilai_ap) as wo_ap_sd
                                    from fawoapp_d
                                    where kode_lokasi='$kode_lokasi' and periode<'$periode' 
                                    group by no_fa,kode_lokasi
                                    )f on a.no_fa=f.no_fa and a.kode_lokasi=f.kode_lokasi
                        left join (select no_fa,kode_lokasi,sum(nilai) as wo,sum(nilai_ap) as wo_ap
                                    from fawoapp_d
                                    where kode_lokasi='$kode_lokasi' and periode='$periode' 
                                    group by no_fa,kode_lokasi
                                    )g on a.no_fa=g.no_fa and a.kode_lokasi=g.kode_lokasi    
                    $where
            order by a.kode_akun,a.tgl_perolehan ";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
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
                $success["auth_status"] = 2;    
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getKartuAktap(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_klpakun','no_fa');
            $db_col_name = array('a.periode','a.kode_klpakun','a.no_fa');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql = "select a.kode_lokasi,a.no_fa,a.nama,a.nilai,a.kode_klpakun,b.nama as nama_klp,a.periode,a.kode_pp,c.nama as nama_pp,
            convert(varchar,a.tgl_perolehan,103) as tgl_perolehan,b.kode_akun,b.akun_bp,b.akun_deprs,d.nama as nama_akun,e.nama as nama_bp,f.nama as nama_deprs 
            from fa_asset a 
            inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi 
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            inner join masakun d on b.kode_akun=d.kode_akun and b.kode_lokasi=d.kode_lokasi
            inner join masakun e on b.akun_bp=e.kode_akun and b.kode_lokasi=e.kode_lokasi
            inner join masakun f on b.akun_deprs=f.kode_akun and b.kode_lokasi=f.kode_lokasi
            $where ";
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $nb = "";
                $resdata = array();
                $i=0;
                for($i=0;$i < count($res); $i++){
    
                    $sql2="select no_fasusut,periode,akun_bp,akun_ap,
                    case dc when 'D' then nilai else 0 end as debet,
                    case dc when 'C' then nilai else 0 end as kredit,nilai
                    from fasusut_d
                    where kode_lokasi='".$res[$i]['kode_lokasi']."' and periode<='$periode_susut' and no_fa in ('".$res[$i]['no_fa']."')
                    order by periode
                    " ;
                    $res2 = DB::connection($this->sql)->select($sql2);
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                    $i++;
                }
                
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

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

    function getSaldoAktap(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('kode_klpakun');
            $db_col_name = array('a.kode_klpakun');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }

            if($request->kode_pp[0] == "Range"){
               $filterpp = " and a.kode_pp between '".$request->kode_pp[1]."' and '".$request->kode_pp[2]."' ";
            }else if ($request->kode_pp[0] == "="){
               $filterpp = " and a.kode_pp = '".$request->kode_pp[1]."' ";
            }else{
               $filterpp = "";
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql = "select a.kode_klpakun,a.nama,
                isnull(b.so_awal,0)-isnull(dd.kredit,0) as so_awal,
                isnull(c.debet,0) as debet,
                isnull(d.kredit,0) as kredit,
                isnull(b.so_awal,0)-isnull(dd.kredit,0)+isnull(c.debet,0)-isnull(d.kredit,0) as so_akhir,
                isnull(e.ap,0)-isnull(ee.ap_wo,0) as ap_awal,
                isnull(f.ap,0)-isnull(ff.ap_wo,0) as ap_mutasi,
                isnull(e.ap,0)+isnull(f.ap,0)-isnull(ee.ap_wo,0)-isnull(ff.ap_wo,0) as ap_akhir
            from fa_klpakun a

            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as so_awal
                    from fa_asset a inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    )b on a.kode_klpakun=b.kode_klpakun  and a.kode_lokasi=b.kode_lokasi

            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as debet
                    from fa_asset a inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    ) c on a.kode_klpakun=c.kode_klpakun and a.kode_lokasi=c.kode_lokasi

            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as kredit
                    from fa_asset a
                    inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    )dd on a.kode_klpakun=dd.kode_klpakun and a.kode_lokasi=dd.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as kredit
                    from fa_asset a
                    inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    )d on a.kode_klpakun=d.kode_klpakun and a.kode_lokasi=d.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when b.dc='D' then b.nilai else -b.nilai end) as ap
                    from fa_asset a
                    inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode_susut' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    ) e on a.kode_klpakun=e.kode_klpakun and a.kode_lokasi=e.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when b.dc='D' then b.nilai else -b.nilai end) as ap
                    from fa_asset a
                    inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode='$periode_susut' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    ) f on a.kode_klpakun=f.kode_klpakun and a.kode_lokasi=f.kode_lokasi

            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when bb.dc='D' then bb.nilai else -bb.nilai end) as ap_wo
                    from fa_asset a
                    inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    inner join fasusut_d bb on b.no_fa=bb.no_fa and b.kode_lokasi=bb.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    )ee on a.kode_klpakun=ee.kode_klpakun and a.kode_lokasi=ee.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when bb.dc='D' then bb.nilai else -bb.nilai end) as ap_wo
                    from fa_asset a
                    inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                    inner join fasusut_d bb on b.no_fa=bb.no_fa and b.kode_lokasi=bb.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                    group by a.kode_klpakun,a.kode_lokasi
                    )ff on a.kode_klpakun=ff.kode_klpakun and a.kode_lokasi=ff.kode_lokasi	
            $where
            order by a.kode_klpakun ";
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

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

    function getSaldoAktapTahun(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_klpakun','periode');
            $db_col_name = array("substring(a.periode,1,4)",'a.kode_klpakun','a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }

            if($request->jenis[0] == "Range"){
               $filter_jenis = " where j.jenis between '".$request->jenis[1]."' and '".$request->jenis[2]."' ";
            }else if ($request->jenis[0] == "="){
               $filter_jenis = " where j.jenis = '".$request->jenis[1]."' ";
            }else{
               $filter_jenis = "";
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql = "select distinct a.kode_klpakun,a.nama,g.tahun,
            isnull(b.so_awal,0) as so_awal,
            isnull(dd.wo,0) as wo,
            isnull(e.ap,0) as ap ,
            isnull(b.so_awal,0)-isnull(e.ap,0) - isnull(dd.wo,0) as nilai_buku
            from fa_klpakun a
            left join fa_klp j on a.kode_klpakun=j.kode_klpakun 
            inner join (select a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4) as tahun
                        from fa_asset a			
                        $where and a.progress not in ('K','P') and a.jenis<>'I'
                        group by a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4)
                        )g on a.kode_klpakun=g.kode_klpakun and a.kode_lokasi=g.kode_lokasi
            
            left join (select a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4) as tahun,sum(b.nilai) as so_awal
                        from fa_asset a
                        inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        $where and a.progress not in ('K','P') and a.jenis<>'I'
                        group by a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4)
                    )b on g.kode_klpakun=b.kode_klpakun and g.tahun=b.tahun and a.kode_lokasi=b.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4) as tahun,sum(case when b.dc='D' then b.nilai else -b.nilai end) as ap
                        from fa_asset a
                        inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        left join fawoapp_d bb on a.no_fa=bb.no_fa and a.kode_lokasi=bb.kode_lokasi		   
                        $where and b.periode<='$periode' and a.progress not in ('K','P') and a.jenis<>'I' and bb.no_fa is null 
                        group by a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4)
                    ) e on g.kode_klpakun=e.kode_klpakun and g.tahun=e.tahun and a.kode_lokasi=e.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4) as tahun,sum(a.nilai) as wo
                        from fa_asset a
                        inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi		   
                        $where and b.periode<='$periode' and a.progress not in ('K','P') and a.jenis<>'I'
                        group by a.kode_klpakun,a.kode_lokasi,substring(a.periode,1,4)
                        )dd on g.kode_klpakun=dd.kode_klpakun and g.tahun=dd.tahun and a.kode_lokasi=dd.kode_lokasi   
            $filter_jenis	   
            order by a.kode_klpakun,g.tahun ";
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

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

    function getJurnalAktap(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_bukti');
            $db_col_name = array('a.periode','a.no_bukti');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql="select a.no_bukti,a.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.kode_akun,b.nama,a.kode_pp,a.kode_drk,a.keterangan, 
            case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit 
            from trans_j a  
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi $where and a.modul='AT'
            order by a.periode,a.no_bukti,a.dc desc ";

            if($request->format[1] == "Memo Jurnal"){
                $sql="select a.no_bukti,a.no_dokumen,a.kode_lokasi,a.periode,a.tanggal,convert(varchar,a.tanggal,103) as tanggal1,a.keterangan,a.kode_lokasi,a.nik_user,a.nik2,b.nama as nama_buat,c.nama as nama_setuju,d.kota
                from trans_m a
                inner join lokasi d on a.kode_lokasi=d.kode_lokasi
                left join karyawan b on a.nik_user=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik2=c.nik and a.kode_lokasi=c.kode_lokasi 
                $where and a.modul='AT' ";
            }
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                
                if($request->format[1] == "Memo Jurnal"){
                    
                    for($i=0;$i < count($res);$i++){
                        
                        $sql2=" select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                        from trans_j a
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='".$res[$i]['kode_lokasi']."' and a.modul='AT' and a.no_bukti in ('".$res[$i]['no_bukti']."')
                        order by a.dc desc 
                        " ;
                        $res2 = DB::connection($this->sql)->select($sql2);
                        $res[$i]['detail'] = json_decode(json_encode($res2),true);
                        
                    }
                }
                            
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJurnalWO(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','no_woapp');
            $db_col_name = array('a.periode','a.no_woapp');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql="select a.no_woapp as no_bukti,a.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.kode_akun,b.nama,a.kode_pp,a.kode_drk,a.keterangan, 
            case when a.dc='D' then a.nilai else 0 end as debet,case when a.dc='C' then a.nilai else 0 end as kredit 
            from fawoapp_j a  
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            $where
            order by a.periode,a.no_woapp,a.dc desc";

            if($request->format[1] == "Memo Jurnal"){
                $sql="select a.no_woapp,a.no_dokumen,a.kode_lokasi,a.periode,a.tanggal,convert(varchar,a.tanggal,103) as tanggal1,a.keterangan,a.kode_lokasi,
                a.nik_buat,a.nik_setuju,b.nama as nama_buat,c.nama as nama_setuju,d.kota
                from fawoapp_m a
                inner join lokasi d on a.kode_lokasi=d.kode_lokasi
                left join karyawan b on a.nik_buat=b.nik and a.kode_lokasi=b.kode_lokasi
                left join karyawan c on a.nik_setuju=c.nik and a.kode_lokasi=c.kode_lokasi $where ";
            }
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            $nb = "";
            $resdata = array();
            $i=0;
            if($request->format[1] == "Memp Jurnal"){

                foreach($rs as $row){
    
                    $resdata[]=(array)$row;
                    if($i == 0){
                        $nb .= "'$row->no_woapp'";
                    }else{
    
                        $nb .= ","."'$row->no_woapp'";
                    }
                    $i++;
                }
    
                if($nb == ""){
                    $filter_nb = "";
                }else{
                    $filter_nb = " and no_woapp in ('$row->no_woapp') ";
                }
                $sql2="select a.kode_akun,b.nama,a.keterangan,a.kode_pp,a.kode_drk,case dc when 'D' then nilai else 0 end as debet,case dc when 'C' then nilai else 0 end as kredit  
                from fawoapp_j a
                inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' $filter_nb
                order by a.dc desc
                " ;
                $res2 = DB::connection($this->sql)->select($sql2);
                $res2 = json_decode(json_encode($res2),true);
            }else{
                $res2 = array();
            }
            

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    function getSaldoAktapBln(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('tahun','kode_klpakun','periode');
            $db_col_name = array("substring(a.periode,1,4)",'a.kode_klpakun','a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";

            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
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
                    }
                }
            }

            if($request->kode_pp[0] == "Range"){
               $filter_pp = " where a.kode_pp between '".$request->kode_pp[1]."' and '".$request->kode_pp[2]."' ";
            }else if ($request->kode_pp[0] == "="){
               $filter_pp = " where a.kode_pp = '".$request->kode_pp[1]."' ";
            }else{
               $filter_pp = "";
            }
            $periode=$request->input('periode')[1];
            $periode_susut=$request->input('periode_susut')[1];
            $sql = "select a.kode_klpakun,a.nama,
                    isnull(b.so_awal,0)-isnull(dd.kredit,0) as so_awal,
                    isnull(c.debet,0) as debet,
                    isnull(d.kredit,0) as kredit,
                    isnull(b.so_awal,0)-isnull(dd.kredit,0)+isnull(c.debet,0)-isnull(d.kredit,0) as so_akhir,
                    isnull(e.ap,0)-isnull(ee.ap_wo,0) as ap_awal,
                    isnull(f.ap,0)-isnull(ff.ap_wo,0) as ap_mutasi,
                    isnull(e.ap,0)+isnull(f.ap,0)-isnull(ee.ap_wo,0)-isnull(ff.ap_wo,0) as ap_akhir
            from fa_klpakun a
            
            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as so_awal
                        from fa_asset a inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                    )b on a.kode_klpakun=b.kode_klpakun  and a.kode_lokasi=b.kode_lokasi
            
            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as debet
                        from fa_asset a inner join fa_nilai b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                    ) c on a.kode_klpakun=c.kode_klpakun and a.kode_lokasi=c.kode_lokasi
            
            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as kredit
                        from fa_asset a
                        inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                        )dd on a.kode_klpakun=dd.kode_klpakun and a.kode_lokasi=dd.kode_lokasi
                        
            left join (select a.kode_klpakun,a.kode_lokasi,sum(b.nilai) as kredit
                        from fa_asset a
                        inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                        )d on a.kode_klpakun=d.kode_klpakun and a.kode_lokasi=d.kode_lokasi
                        
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when b.dc='D' then b.nilai else -b.nilai end) as ap
                        from fa_asset a
                        inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode_susut' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                    ) e on a.kode_klpakun=e.kode_klpakun and a.kode_lokasi=e.kode_lokasi
                    
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when b.dc='D' then b.nilai else -b.nilai end) as ap
                        from fa_asset a
                        inner join fasusut_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode='$periode_susut' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                    ) f on a.kode_klpakun=f.kode_klpakun and a.kode_lokasi=f.kode_lokasi
            
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when bb.dc='D' then bb.nilai else -bb.nilai end) as ap_wo
                        from fa_asset a
                        inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        inner join fasusut_d bb on b.no_fa=bb.no_fa and b.kode_lokasi=bb.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode<'$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                        )ee on a.kode_klpakun=ee.kode_klpakun and a.kode_lokasi=ee.kode_lokasi
                        
            left join (select a.kode_klpakun,a.kode_lokasi,sum(case when bb.dc='D' then bb.nilai else -bb.nilai end) as ap_wo
                        from fa_asset a
                        inner join fawoapp_d b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi
                        inner join fasusut_d bb on b.no_fa=bb.no_fa and b.kode_lokasi=bb.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and b.periode='$periode' and a.progress not in ('K','P') and a.jenis<>'I' $filterpp
                        group by a.kode_klpakun,a.kode_lokasi
                        )ff on a.kode_klpakun=ff.kode_klpakun and a.kode_lokasi=ff.kode_lokasi	
            $this->filter  
            order by a.kode_klpakun ";
         
            $rs = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($rs),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

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




   


    

}
