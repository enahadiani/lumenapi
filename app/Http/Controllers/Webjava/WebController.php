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
    public $lokasi = '22';

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

    public function getGallery(Request $request)
    {
        try {

            $res = DB::connection($this->sql)->select("SELECT kode_ktg, nama FROM lab_konten_ktg where jenis='Gambar' and nama <> '-' and nama <> '_' and kode_lokasi='".$this->lokasi."' ");
            $success["daftar_kategori"] = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("SELECT a.nama, keterangan, file_gambar, tgl_input, b.kode_ktg as nama_ktg FROM lab_konten_galeri a inner join lab_konten_ktg b on a.kode_ktg=b.kode_ktg and a.kode_lokasi=b.kode_lokasi where a.jenis='Galeri' and b.nama <> '-' and b.nama <> '_' and flag_aktif = '1' and a.kode_lokasi='".$this->lokasi."' ");
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

        try {
            if(ctype_digit($page) AND $page > 0){
                
                $success['active_page'] = $page;
                $items_per_page = 5;

                // periode filter
                if(ctype_digit($bln) AND ctype_digit($thn) AND $bln != null AND $thn != null){
                    $periode = "and month(tanggal) = '".$bln."' and year(tanggal) = '".$thn."' ";
                }else{
                    $periode = "";
                }

                $count = DB::connection($this->sql)->select("select count(a.id) as jml from lab_konten a where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' $periode ");

                $offset = ($page - 1) * $items_per_page;

                $success["item_per_page"] = $items_per_page;
                $success["jumlah_artikel"] = $count[0]->jml;

                if($count[0]->jml > 0){
                    $res = DB::connection($this->sql)->select("select a.id, tanggal, judul, a.keterangan, a.nik_user, a.tgl_input, c.file_gambar as header_url, c.file_type from lab_konten a left join lab_konten_klp b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi left join lab_konten_galeri c on a.header_url=c.id and a.kode_lokasi=c.kode_lokasi where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' $periode order by tanggal desc OFFSET $offset ROWS FETCH NEXT $items_per_page ROWS ONLY");

                    $success["daftar_artikel"] = json_decode(json_encode($res),true);
                }else{
                    $success["daftar_artikel"] = array();
                }

                $res2 = DB::connection($this->sql)->select("select count(a.id) as jml, month(tanggal) as bulan, year(tanggal) as tahun from lab_konten a where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' group by month(tanggal), year(tanggal)");

                $success["archive"] = json_decode(json_encode($res2),true);

                $res3 = DB::connection($this->sql)->select("select count(id) as jml, b.kode_kategori, b.nama from lab_konten a join lab_konten_kategori b on a.kode_kategori=b.kode_kategori and a.kode_lokasi=b.kode_lokasi where a.kode_klp='KLP01' and a.kode_lokasi='".$this->lokasi."' group by b.nama, b.kode_kategori");

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

    // public function search(Request $request){
    //     $konten=null, $jenis=null, $page='1';

    //     $search_string = $this->input->get('str', TRUE);
        
        
    //     if(($konten != null OR in_array($konten, array('all', 'news', 'article'))) AND $jenis != null AND ISSET($search_string)){

    //         $acceptable = array('tag','categories','string');

    //         if(in_array($jenis, $acceptable)){
    //             if($konten == 'news'){
    //                 $kode_klp = "and a.kode_klp = 'KLP01'";
    //             }else if($konten =='article'){
    //                 $kode_klp = "and a.kode_klp = 'KLP03'";
    //             }else{
    //                 $kode_klp = "and (a.kode_klp = 'KLP01' OR a.kode_klp = 'KLP03')";
    //             }

    //             // $search_string = $this->db->qstr($search_string);
    //             switch($jenis){
    //                 case 'tag': 
    //                     $where = "a.tag like ".$this->db->qstr("%$search_string%");
    //                 break;
    //                 case 'categories': 
    //                     $where = "a.kode_kategori = ".$this->db->qstr("$search_string");
    //                 break;
    //                 // case 'date': 
    //                 //     $where = "and a.month(tanggal) = ".$this->db->qstr($bln)." and a.year(tanggal) = ".$this->db->qstr($thn);
    //                 // break;
    //                 case 'string': 
    //                     $where = "a.keterangan like ".$this->db->qstr("%$search_string%");
    //                 break;
    //             }
            
    //             $data['active_page'] = $page;
    //             $items_per_page = 5;
    
    //             $count = $this->sai->getRowArray("select count(a.id) as jml from lab_konten a where a.kode_lokasi='".$this->lokasi."' $kode_klp and $where");
    
    //             // $number_of_item = ($count['jml']/$page >= $items_per_page ? $items_per_page : ($count['jml'] % $items_per_page));
                
    //             // $offset = $page - 1;
    //             $offset = ($page - 1) * $items_per_page;
    
    //             $data["item_per_page"] = $items_per_page;
    //             $data["jumlah_artikel"] = $count["jml"];
    
    //             if($count["jml"] > 0){
    //                 $data["daftar_artikel"] = $this->sai->getResultArray("select a.id, tanggal, judul, a.keterangan, a.nik_user, a.tgl_input, c.file_gambar as header_url, c.file_type from lab_konten a left join lab_konten_klp b on a.kode_klp=b.kode_klp and a.kode_lokasi=b.kode_lokasi left join lab_konten_galeri c on a.header_url=c.id and a.kode_lokasi=c.kode_lokasi where a.kode_lokasi='".$this->lokasi."' $kode_klp and $where order by tanggal desc OFFSET $offset ROWS FETCH NEXT $items_per_page ROWS ONLY");
    //             }else{
    //                 $data["daftar_artikel"] = array();
    //             }

    //             $data["search_string"] = $search_string;
    //             $data["url_paging"] = "/webjava/Index/search/$konten/$jenis";
    //             $data["page"] = "webjava/vSearchResult";
    //             $this->load->view("webjava/templateWeb", $data);
    //         }else{
    //             redirect('/');
    //         }
    //     }else{
    //         redirect('/');
    //     }
    // }
    
}
