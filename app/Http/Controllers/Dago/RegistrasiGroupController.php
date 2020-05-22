<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class RegistrasiGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select no_peserta from dgw_paket where id_peserta ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
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
            'no_reg' => 'required',
            'group.*.status_reg' => 'required',
            'group.*.no_peserta' => 'required',
            'biaya_tambahan.*.kode_biaya' => 'required',
            'biaya_tambahan.*.nilai' => 'required',
            'biaya_tambahan.*.jumlah' => 'required',
            'biaya_tambahan.*.total' => 'required',
            'biaya_dokumen.*.kode_biaya' => 'required',
            'biaya_dokumen.*.nilai' => 'required',
            'biaya_dokumen.*.jumlah' => 'required',
            'biaya_dokumen.*.total' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $group = $request->group;
            if(count($group) > 0){
                $sql = "select * from dgw_group_d where no_reg='".$request->no_reg."' and kode_lokasi='$kode_lokasi' ";

                $cekEdit = DB::connection('sqlsrvdago')->select($sql);
                $cekEdit = json_decode(json_encode($cekEdit),true);
                if(count($cekEdit)>0){
                    for($i=0; $i<count($group);$i++){
                        if($group[$i]['status_reg'] == "D"){

                            $del[$i] = DB::connection('sqlsrvdago')->table('dgw_reg')
                            ->where('kode_lokasi', $kode_lokasi)
                            ->where('no_reg', $group[$i]['no_reg_ref'])
                            ->delete();	

                            $del2[$i] = DB::connection('sqlsrvdago')->table('dgw_reg_dok')
                            ->where('kode_lokasi', $kode_lokasi)
                            ->where('no_reg', $group[$i]['no_reg_ref'])
                            ->delete();	
                            
                            $del3[$i] = DB::connection('sqlsrvdago')->table('dgw_reg_biaya')
                            ->where('kode_lokasi', $kode_lokasi)
                            ->where('no_reg', $group[$i]['no_reg_ref'])
                            ->delete();	

                            $del4[$i] = DB::connection('sqlsrvdago')->table('dgw_history_jadwal')
                            ->where('kode_lokasi', $kode_lokasi)
                            ->where('no_reg', $group[$i]['no_reg_ref'])
                            ->delete();	

                            $del5[$i] = DB::connection('sqlsrvdago')->table('dgw_group_d')
                            ->where('kode_lokasi', $kode_lokasi)
                            ->where('no_reg', $group[$i]['no_reg_ref'])
                            ->delete();	
                        }
                    }
                }
                
                $tmp = generateKode("dgw_reg", "no_reg", "REG/".substr(date('Ym'),2,4)."/", "0001");
                $temp = explode("/",$tmp);
                $id = intval($temp[1]);

                for($i=0; $i<count($group);$i++){
                    if($data['group_sts_reg'][$i] == "0"){

                        $no_reg = $temp[0]."/".sprintf("%04s",$id);
        
                        $ins[$i] = DB::connection('sqlsrvdago')->insert("insert into dgw_group_d(no_reg,no_peserta,no_reg_ref,kode_lokasi) values (?, ?, ?, ?)", array($request->no_reg,$group[$i]['no_anggota'],$no_reg,$kode_lokasi));	

                        $ins2[$i] = DB::connection('sqlsrvdago')->update("insert into dgw_reg (no_reg,tgl_input,no_peserta,no_paket,no_jadwal,no_agen,no_type,harga_room,info,kode_lokasi,no_quota,harga,uk_pakaian,no_marketing,kode_harga,periode,jenis,no_fee,no_peserta_ref,kode_pp,diskon,flag_group,brkt_dgn,hubungan,referal,ket_diskon) select '$no_reg' as no_reg,getdate(),'".$group[$i]['no_anggota']."' as no_peserta,no_paket,no_jadwal,no_agen,no_type,harga_room,info,kode_lokasi,no_quota,harga,uk_pakaian,no_marketing,kode_harga,periode,jenis,no_fee,no_peserta_ref,kode_pp,diskon,'0' as flag_group,brkt_dgn,hubungan,referal,ket_diskon from dgw_reg where no_reg = '".$request->no_reg."' and kode_lokasi='".$kode_lokasi."' ");	

                        $ins3[$i] = DB::connection('sqlsrvdago')->update("insert into dgw_reg_dok (no_dok,no_reg,ket,kode_lokasi,tgl_terima) 
                        select a.no_dok,'$no_reg' as no_reg,a.ket,a.kode_lokasi,'2099-12-31' from dgw_reg_dok a where a.no_reg='".$request->no_reg."' and a.kode_lokasi='$kode_lokasi'" );
        
                        $ins4[$i] = DB::connection('sqlsrvdago')->update("insert into dgw_reg_biaya (kode_biaya,no_reg,tarif,jml,nilai,kode_lokasi) 
                        select kode_biaya,'$no_reg' as no_reg,tarif,jml,nilai,kode_lokasi from dgw_reg_biaya where no_reg = '".$data['group_no_reg']."' and kode_lokasi='".$kode_lokasi."' ");
        
                        $ins5[$i] = DB::connection('sqlsrvdago')->update("insert into dgw_history_jadwal(no_reg,no_paket,no_jadwal,no_paket_lama,no_jadwal_lama,kode_lokasi)
                        select '$no_reg' as no_reg,no_paket,no_jadwal,no_paket_lama,no_jadwal_lama,kode_lokasi from dgw_history_jadwal where no_reg = '".$data['group_no_reg']."' and kode_lokasi='".$kode_lokasi."' ");
                        $id++;
                    }                   
    
                    
                }
            }

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Registrasi berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Registrasi gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function getGroup(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_reg = $request->no_reg;
            $res = DB::connection('sqlsrvdago')->select(  "select a.no_reg,a.no_peserta,c.nama as nama_peserta,c.alamat,a.no_paket,b.nama as nama_paket,a.no_jadwal,d.tgl_berangkat
            from dgw_reg a
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi
            inner join dgw_peserta c on a.no_peserta=c.no_peserta and a.kode_lokasi=c.kode_lokasi
            inner join dgw_jadwal d on a.no_paket=d.no_paket and a.no_jadwal=d.no_jadwal and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_reg='$no_reg'");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvdago')->select(  "select a.no_reg,a.no_peserta,no_reg_ref from dgw_group_d a
            where a.kode_lokasi='$kode_lokasi' and a.no_reg='$no_reg'
            ");
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }
}
