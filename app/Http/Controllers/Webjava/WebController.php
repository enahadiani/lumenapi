<?php

namespace App\Http\Controllers\Webjava;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class WebController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'dbsaife';
    public $guard = 'webjava';

    function generateSEO($id, $judul)
    {
        $seo = str_replace(" ", "-", strtolower($judul));
        $seo = str_replace(array("(",")","'","/","'\'",':','"',',','?','%'), "", $seo);
        return "$id/$seo";
    }

    public function getMenu(Request $request)
    {
        try {
            
            $domain = $request->domain;
            $sql = "select nama, link, nu, jenis from lab_konten_menu a left join lab_domain b on a.kode_lokasi=b.kode_lokasi where level_menu = '0' and domain='$domain' order by nu";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $html = "";
            $url = $request->url_web;
            for($ctr = 0; $ctr < count($res); $ctr++)
            {

                if($res[$ctr]["jenis"] == "Induk"){
                    $link_induk = "<a href='#' class='dropdown-toggle' data-toggle='dropdown'>".$res[$ctr]["nama"]." <i class='fa fa-chevron-down'></i></a>";
                }else if($res[$ctr]["jenis"] == "Fix"){
                    $data_menu1 = DB::connection($this->sql)->select("select distinct id_form from lab_form where kode_form='".$res[$ctr]["link"]."'");
                    $data_menu1 = json_decode(json_encode($data_menu1),true);
                    // $link = str_replace("_","/", $data_menu1[0]["id_form"]);
                    $tmp = explode("_",$data_menu1[0]["id_form"]);
                    $link = $tmp[2];
                    $link_induk = "<a href='#' class='a_link' data-href='".$url."/".$link."'>".$res[$ctr]["nama"]."</a>";
                }else{
                    $data_menu1 = DB::connection($this->sql)->select("select id, judul from lab_konten a left join lab_domain b on a.kode_lokasi=b.kode_lokasi where domain='$domain' and id='".$res[$ctr]["link"]."'");
                    $data_menu1 = json_decode(json_encode($data_menu1),true);
                    
                    $seo = $this->generateSEO($data_menu1[0]["id"], $data_menu1[0]["judul"]);
                    $link_induk = "<a href='#' class='a_link' data-href='".$url."/page/$seo'>".$res[$ctr]["nama"]."</a>";
                }

                $html .="
                    <li class='dropdown' style='padding-bottom:0px;'>
                        $link_induk
                ";

                if(ISSET($res[$ctr+1]["nu"])){
                
                    $query2 = DB::connection($this->sql)->select("select nama, link, jenis from lab_konten_menu a left join lab_domain b on a.kode_lokasi=b.kode_lokasi where domain='$domain' and level_menu = '1' and nu >= ".$res[$ctr]["nu"]." and nu < ".$res[$ctr+1]["nu"]." order by nu, level_menu");
                }else{
                    
                    $query2 = DB::connection($this->sql)->select("select nama, link, jenis from lab_konten_menu a left join lab_domain b on a.kode_lokasi=b.kode_lokasi where domain='$domain' and level_menu = '1' and nu > ".$res[$ctr]["nu"]." order by nu, level_menu");
                }
                
                $query2 = json_decode(json_encode($query2),true);
                $html .="
                    <ul class='dropdown-menu'>";
                foreach($query2 as $data){
                    if($data["jenis"] == "Fix"){
                        $data_menu1 = DB::connection($this->sql)->select("select distinct id_form from lab_form where kode_form='".$data["link"]."'");
                        $data_menu1 = json_decode(json_encode($data_menu1),true);

                        // $link = str_replace("_","/", $data_menu1["id_form"]);
                        $tmp = explode("_",$data_menu1[0]["id_form"]);
                        $link = $tmp[2];
                        $link_induk = "<a href='#' class='a_link' data-href='".$url."/".$link."'>".$data["nama"]."</a>";
                    }else{
                        $data_menu1 = DB::connection($this->sql)->select("select id, judul from lab_konten a left join lab_domain b on a.kode_lokasi=b.kode_lokasi where domain='$domain' and id='".$data["link"]."'");
                        $data_menu1 = json_decode(json_encode($data_menu1),true);

                        $seo = $this->generateSEO($data_menu1[0]["id"], $data_menu1[0]["judul"]);
                        $link_induk = "<a href='#' class='a_link' data-href='".$url."/page/$seo'>".$data["nama"]."</a>";
                    }

                    $html .="<li>$link_induk</li>";
                }
                
                $html .="</ul>
                    </li>";
            }

            $logo = DB::connection($this->sql)->select("SELECT logo FROM lokasi where kode_lokasi='22'");
            if(count($logo) > 0){
                $success['logo'] = $logo[0]->logo;
            }else{
                $success['logo'] = "-";
            }
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['html'] = $html;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['html'] = "";
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getGallery(Request $request)
    {
        try {

            $res = DB::connection($this->sql)->select("SELECT kode_ktg, nama FROM lab_konten_ktg where jenis='Gambar' and nama <> '-' and nama <> '_' and kode_lokasi='22'");
            $success["daftar_kategori"] = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("SELECT a.nama, keterangan, file_gambar, tgl_input, b.kode_ktg as nama_ktg FROM lab_konten_galeri a inner join lab_konten_ktg b on a.kode_ktg=b.kode_ktg and a.kode_lokasi=b.kode_lokasi where a.jenis='Galeri' and b.nama <> '-' and b.nama <> '_' and flag_aktif = '1' and a.kode_lokasi='22'");
            $success["daftar_gambar"] =  json_decode(json_encode($res2),true);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKontak(Request $request)
    {
        try {
            $res = DB::connection($this->sql)->select("SELECT judul, keterangan, latitude, longitude FROM lab_konten_kontak a where flag_aktif='1' and a.kode_lokasi='22' ");
            $success["kontak"] =  json_decode(json_encode($res),true);
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPage($id)
    {
        try {
            $sql="select a.id, a.judul, b.file_gambar as header_url, a.tgl_input, a.nik_user, a.keterangan  
            from lab_konten a 
            left join lab_konten_galeri b on a.header_url=b.id and a.kode_lokasi=b.kode_lokasi
            where a.id=$id and a.kode_klp='KLP02' and a.kode_lokasi='22' ";
        
            $res = DB::connection($this->sql)->select($sql);

            $success["page"] =  json_decode(json_encode($res),true);
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
