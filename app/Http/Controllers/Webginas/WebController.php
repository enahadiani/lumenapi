<?php

namespace App\Http\Controllers\Webginas;

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
    public $guard = 'webginas';
    public $lokasi = '17';

    function generateSEO($id, $judul)
    {
        $seo = str_replace(" ", "-", strtolower($judul));
        $seo = str_replace(array("(",")","'","/","'\'",':','"',',','?','%'), "", $seo);
        return "$id/$seo";
    }

    function filterChar($str){
        $filtered = str_replace(" ", "-", strtolower($str));
        $filtered = str_replace(array("(",")","'","/","'\'",':','"',',','?','%'), "", $filtered);
        return $filtered;
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
                    $link = $tmp[1];
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
                        $link = $tmp[1];
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

            $logo = DB::connection($this->sql)->select("SELECT logo FROM lokasi where kode_lokasi='".$this->lokasi."'");
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

    public function getHome(Request $request)
    {
        try {

            $success["top_slider"] = json_decode(json_encode(DB::connection($this->sql)->select("select * from lab_konten_galeri where  jenis='Slider Atas' and kode_lokasi = '$this->lokasi'"),true));
            $success["slider"] = json_decode(json_encode(DB::connection($this->sql)->select("select * from lab_konten_galeri where  jenis='Slider' and kode_lokasi = '$this->lokasi'"),true));
            $success["ads_slider"] = json_decode(json_encode(DB::connection($this->sql)->select("select * from lab_konten_galeri where  jenis='Slider Kanan' and kode_lokasi = '$this->lokasi'"),true));
            $success["bottom_slider"] = json_decode(json_encode(DB::connection($this->sql)->select("select * from lab_konten_galeri where jenis='Slider Bawah' and kode_lokasi = '$this->lokasi'"),true));
            
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

            $res = DB::connection($this->sql)->select("SELECT kode_ktg, nama FROM lab_konten_ktg where jenis='Gambar' and nama <> '-' and nama <> '_' and kode_lokasi='".$this->lokasi."' ");
            $success["daftar_kategori"] = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("SELECT a.nama, a.keterangan, a.file_gambar, a.tgl_input, b.kode_ktg as nama_ktg FROM lab_konten_galeri a inner join lab_konten_ktg b on a.kode_ktg=b.kode_ktg and a.kode_lokasi=b.kode_lokasi where a.jenis='Galeri' and b.nama <> '-' and b.nama <> '_' and flag_aktif = '1' and a.kode_lokasi='".$this->lokasi."' ");
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
            $res = DB::connection($this->sql)->select("SELECT judul, keterangan, latitude, longitude FROM lab_konten_kontak a where flag_aktif='1' and a.kode_lokasi='".$this->lokasi."' ");
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
            where a.id=$id and a.kode_klp='KLP02' and a.kode_lokasi='".$this->lokasi."' ";
        
            $res = DB::connection($this->sql)->select($sql);

            $success["page"] =  json_decode(json_encode($res),true);
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNews(Request $request)
    {
        if(isset($request->page)){
            $page = $request->page;
        }else{
            $page='1';
        }

        if(isset($request->bulan)){
            $bln = $request->bulan;
        }else{
            $bln=null;
        }

        if(isset($request->tahun)){
            $thn = $request->tahun;
        }else{
            $thn=null;
        }
        
        if(isset($request->jenis)){
            $jenis = $request->jenis;
        }else{
            $jenis=null;
        }

        if(isset($request->str)){
            $search_string = $request->str;
        }else{
            $search_string=null;
        }
        
        switch($jenis){
            case 'tag': 
                $where = " and a.tag like '%".$search_string."%' ";
            break;
            case 'categories': 
                $where = " and a.kode_kategori = '".$search_string."' " ;
            break;
            case 'string': 
                $where = " and a.keterangan like '%".$search_string."%' ";
            break;
            default : 
                $where = "";
            break;
        }
    

        try {
            $success['page'] = $page;
            if(ctype_digit($page) AND $page > 0){
                
                $success['active_page'] = $page;
                $items_per_page = 5;

                // periode filter
                if(ctype_digit($bln) AND ctype_digit($thn) AND $bln != null AND $thn != null){
                    $periode = "and month(tanggal) = '".$bln."' and year(tanggal) = '".$thn."' ";
                }else{
                    $periode = "";
                }

                $count = DB::connection($this->sql)->select("select count(a.id) as jml from lab_konten a where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' $periode $where ");

                $offset = ($page - 1) * $items_per_page;

                $success["item_per_page"] = $items_per_page;
                $success["jumlah_artikel"] = $count[0]->jml;

                if($count[0]->jml > 0){
                    $res = DB::connection($this->sql)->select("select a.id, tanggal, judul, a.keterangan, a.nik_user, a.tgl_input, c.file_gambar as header_url, c.file_type from lab_konten a left join lab_konten_klp b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi left join lab_konten_galeri c on a.header_url=c.id and a.kode_lokasi=c.kode_lokasi where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' $periode  $where order by tanggal desc OFFSET $offset ROWS FETCH NEXT $items_per_page ROWS ONLY");

                    $success["daftar_artikel"] = json_decode(json_encode($res),true);
                }else{
                    $success["daftar_artikel"] = array();
                }

                $res2 = DB::connection($this->sql)->select("select count(a.id) as jml, month(tanggal) as bulan, year(tanggal) as tahun from lab_konten a where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' group by month(tanggal), year(tanggal)");

                $success["archive"] = json_decode(json_encode($res2),true);

                $res3 = DB::connection($this->sql)->select("select count(id) as jml, b.kode_kategori, b.nama from lab_konten a join lab_konten_kategori b on a.kode_kategori=b.kode_kategori and a.kode_lokasi=b.kode_lokasi where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' group by b.nama, b.kode_kategori");

                $success["categories"] = json_decode(json_encode($res3),true); 
                $success["periode"] = $bln."-".$thn;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getArticle(Request $request)
    {
        if(isset($request->page)){
            $page = $request->page;
        }else{
            $page='1';
        }

        if(isset($request->bln)){
            $bln = $request->bln;
        }else{
            $bln=null;
        }

        if(isset($request->thn)){
            $thn = $request->thn;
        }else{
            $thn=null;
        }

        if(isset($request->jenis)){
            $jenis = $request->jenis;
        }else{
            $jenis=null;
        }

        if(isset($request->str)){
            $search_string = $request->str;
        }else{
            $search_string=null;
        }
        
        switch($jenis){
            case 'tag': 
                $where = " and a.tag like '%".$search_string."%' ";
            break;
            case 'categories': 
                $where = " and a.kode_kategori = '".$search_string."' " ;
            break;
            case 'string': 
                $where = " and a.keterangan like '%".$search_string."%' ";
            break;
            default : 
                $where = "";
            break;
        }
    

        try {
            $success['page'] = $page;
            if(ctype_digit($page) AND $page > 0){
                
                $success['active_page'] = $page;
                $items_per_page = 5;

                // periode filter
                if(ctype_digit($bln) AND ctype_digit($thn) AND $bln != null AND $thn != null){
                    $periode = "and month(tanggal) = '".$bln."' and year(tanggal) = '".$thn."' ";
                }else{
                    $periode = "";
                }

                $count = DB::connection($this->sql)->select("select count(a.id) as jml from lab_konten a where a.kode_klp='KLP03' and a.kode_lokasi='".$this->lokasi."' $periode $where ");

                $offset = ($page - 1) * $items_per_page;

                $success["item_per_page"] = $items_per_page;
                $success["jumlah_artikel"] = $count[0]->jml;

                if($count[0]->jml > 0){
                    $res = DB::connection($this->sql)->select("select a.id, tanggal, judul, a.keterangan, a.nik_user, a.tgl_input, c.file_gambar as header_url, c.file_type from lab_konten a left join lab_konten_klp b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi left join lab_konten_galeri c on a.header_url=c.id and a.kode_lokasi=c.kode_lokasi where a.kode_klp='KLP03' and a.kode_lokasi='".$this->lokasi."' $periode  $where order by tanggal desc OFFSET $offset ROWS FETCH NEXT $items_per_page ROWS ONLY");

                    $success["daftar_artikel"] = json_decode(json_encode($res),true);
                }else{
                    $success["daftar_artikel"] = array();
                }

                $res2 = DB::connection($this->sql)->select("select count(a.id) as jml, month(tanggal) as bulan, year(tanggal) as tahun from lab_konten a where a.kode_klp='KLP03' and a.kode_lokasi='".$this->lokasi."' group by month(tanggal), year(tanggal)");

                $success["archive"] = json_decode(json_encode($res2),true);

                $res3 = DB::connection($this->sql)->select("select count(id) as jml, b.kode_kategori, b.nama from lab_konten a join lab_konten_kategori b on a.kode_kategori=b.kode_kategori and a.kode_lokasi=b.kode_lokasi where a.kode_klp='KLP03' and a.kode_lokasi='".$this->lokasi."' group by b.nama, b.kode_kategori");

                $success["categories"] = json_decode(json_encode($res3),true); 
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function readItem(Request $request)
    {
         
        $id = $request->id;

        if(isset($request->name)){
            $name = $request->name;
        }else{
            $name = null;
        }

        try{

            if(ctype_digit($id)){

                $sql="select a.id, judul, b.file_gambar as header_url, b.file_type, a.tgl_input, a.nik_user, a.keterangan, a.tag, a.kode_klp 
                from lab_konten a 
                left join lab_konten_galeri b on a.header_url=b.id  and a.kode_lokasi=b.kode_lokasi 
                where a.id='$id' and a.kode_lokasi='".$this->lokasi."' ";
                
                $res = DB::connection($this->sql)->select($sql);
                $success["artikel"] = json_decode(json_encode($res),true);             
                
                if(ISSET($res[0]->judul)){

                    $judul_seo = $this->filterChar($res[0]->judul);

                    $res2 = DB::connection($this->sql)->select("select count(a.id) as jml, month(tanggal) as bulan, year(tanggal) as tahun from lab_konten a where a.kode_klp='".$res[0]->kode_klp."' and a.kode_lokasi='".$this->lokasi."' group by month(tanggal), year(tanggal) ");
                    $success["archive"] = json_decode(json_encode($res2),true); 
                    
                    $res3 = DB::connection($this->sql)->select("select count(id) as jml, b.kode_kategori, b.nama from lab_konten a left join lab_konten_kategori b on a.kode_kategori=b.kode_kategori and a.kode_lokasi=b.kode_lokasi where a.kode_klp='".$res[0]->kode_klp."' and a.kode_lokasi='".$this->lokasi."' group by b.nama, b.kode_kategori");

                    $success["categories"] =  json_decode(json_encode($res3),true); 
                    
                }else{
                    $success["archive"] = [];
                    $success["categories"] = [];
                }
                
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);
            }else{
                $success['status'] = false;
                $success['message'] = "ID tidak valid ";
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getVideo()
    {
        try {
            $success["daftar_video"] = json_decode(json_encode(DB::connection($this->sql)->select("SELECT id, tgl_input, link, judul from lab_konten_video where flag_aktif = '1' and kode_lokasi = '".$this->lokasi."'")),true);

            $success["daftar_video_new"] = json_decode(json_encode(DB::connection($this->sql)->select("SELECT id, tgl_input, file_gambar as link, nama as judul from lab_konten_galeri where flag_aktif = '1' and kode_lokasi = '".$this->lokasi."' and file_type like 'video%' ")),true);
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getWatch($id)
    {
        try {
            $success["video"] = json_decode(json_encode(DB::connection($this->sql)->select("select id, tgl_input, link, judul, cast(keterangan as varchar(max)) as keterangan, 'youtube' as file_type from lab_konten_video where id='$id' and kode_lokasi = '".$this->lokasi."'
            UNION
            select id, tgl_input, file_gambar as link, nama as judul, keterangan, 'upload' as file_type from lab_konten_galeri where id='$id' and kode_lokasi = '".$this->lokasi."' ")),true);
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    
}
