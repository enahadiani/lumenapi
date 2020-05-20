<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class UploadDokController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index()
    {
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvdago')->select("select a.no_reg,a.no_peserta,b.nama,a.tgl_input,e.nama as nama_paket,c.tgl_berangkat,a.flag_group,
            isnull(d.jum_upload,0) as jum_upload,isnull(f.jum_dok,0) as jum_dok,
            case when d.jum_upload = f.jum_dok then 'selesai' else '-' end as sts_dok 
            from dgw_reg a
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            inner join dgw_jadwal c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi
            left join ( select no_bukti,kode_lokasi,count(*) as jum_upload
                        from dgw_scan
                        group by no_bukti,kode_lokasi) d on a.no_reg=d.no_bukti and a.kode_lokasi=d.kode_lokasi
            left join ( select no_reg,kode_lokasi,count(*) as jum_dok
                        from dgw_reg_dok
                        group by no_reg,kode_lokasi) f on a.no_reg=f.no_reg and a.kode_lokasi=f.kode_lokasi
            inner join dgw_paket e on a.no_paket=e.no_paket and a.kode_lokasi=e.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
            'tgl_terima' => 'required',
            'no_reg' => 'required',
            'no_dokumen'=>'required|array',
            'nama_file.*'=>'required',
            'file_dok.*'=>'required|file|max:3072'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $arr_foto = array();
            $arr_nama = array();
            $i=0;
            if($request->hasfile('file_dok'))
            {
                foreach($request->file('file_dok') as $file)
                {                
                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('dago/'.$foto)){
                        Storage::disk('s3')->delete('dago/'.$foto);
                    }
                    Storage::disk('s3')->put('dago/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = str_replace(' ', '_', $request->input('nama_file')[$i]);
                    $i++;
                }
            }        
    
            if(count($arr_nama) > 0){
                for($i=0; $i<count($arr_nama);$i++){
                    
                    $del[$i] = DB::connection('sqlsrvdago')->table('dgw_scan')
                    ->where('no_bukti', $request->no_reg)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('modul', $request->no_dokumen[$i])
                    ->delete();
                    
                    $ins[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_scan(no_bukti,modul,no_gambar,kode_lokasi,nik) values (?, ?, ?, ?, ?) ", [$request->no_reg,$request->no_dokumen[$i],$arr_foto[$i],$kode_lokasi,$nik_user]);

                    $upd[$i] = DB::connection('sqlsrvdago')->table('dgw_reg_dok')
                    ->where('no_reg', $request->no_reg)    
                    ->where('no_dok', $request->no_dokumen[$i]) 
                    ->update(['tgl_terima' => $request->tgl_terima]);
                }
            }
    
            $success['status'] = true;
            $success['message'] = "Upload Dokumen berhasil disimpan.";
            
            DB::connection('sqlsrvdago')->commit();
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Dokumen gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard('dago')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_reg = $request->no_reg;

            $sql = "select a.no_reg,a.no_peserta,c.nama as nama_peserta,c.alamat,a.no_paket,b.nama as nama_paket,a.no_jadwal,d.tgl_berangkat
            from dgw_reg a
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi
            inner join dgw_peserta c on a.no_peserta=c.no_peserta and a.kode_lokasi=c.kode_lokasi
            inner join dgw_jadwal d on a.no_paket=d.no_paket and a.no_jadwal=d.no_jadwal and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_reg='$no_reg'
            ";
            $res = DB::connection('sqlsrvdago')->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2="select a.no_dokumen,a.deskripsi,a.jenis,isnull(convert(varchar,b.tgl_terima,111),'-') as tgl_terima,'".url('dago/storage')."/'+isnull(c.no_gambar,'-') as fileaddres,isnull(c.nik,'-') as nik 
            from dgw_dok a 
            inner join dgw_reg_dok b on a.no_dokumen=b.no_dok and b.no_reg='$no_reg'
            left join dgw_scan c on a.no_dokumen=c.modul and c.no_bukti ='$no_reg' 
            where a.kode_lokasi='$kode_lokasi' order by a.no_dokumen";
            $res2 = DB::connection('sqlsrvdago')->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data_reg'] = $res;
                $success['data_dokumen'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data_reg'] = [];
                $success['data_dokumen'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
