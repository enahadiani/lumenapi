<?php

namespace App\Jobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;
use Carbon\Carbon;

class SendSukkaNotifJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $no_pesan = [];
    private $dbc = '';
    public function __construct($no_pesan,$dbc)
    {
        $this->no_pesan = $no_pesan;
        $this->dbc = $dbc;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::connection($this->dbc)->beginTransaction();
        try{
			$getpsn = DB::connection($this->dbc)->select("select a.kode_lokasi,a.judul,a.pesan,a.nik,a.ref1,a.ref2
			from app_notif_m a
			where a.no_bukti=? and a.sts_kirim=0
			",array($this->no_pesan));

			if(count($getpsn) > 0){
				$nik_kirim = $getpsn[0]->nik;
				$title = $getpsn[0]->judul;
				$message = $getpsn[0]->pesan;
				$no_bukti = $getpsn[0]->ref1;
				$modul = $getpsn[0]->ref2;
                $kode_lokasi = $getpsn[0]->kode_lokasi;

				// NOTIF WEB
				event(new \App\Events\NotifSukka($title,$message,$nik_kirim));
				
				$insd = DB::connection($this->dbc)->insert("insert into app_notif_d (kode_lokasi,no_bukti,id_device,nik,no_urut) values (?, ?, ?, ?, ?)",array($kode_lokasi,$this->no_pesan,'saisukka-channel-'.$nik_kirim,$nik_kirim,0));

				// NOTIF ANDROID

				$getid = DB::connection($this->dbc)->select("select a.id_device
				from users_device a
				where a.nik=? and a.kode_lokasi=?  ",array($nik_kirim,$kode_lokasi));
				$getid = json_decode(json_encode($getid),true);
				$arr_id = array();
				if(count($getid) > 0){
					$no=1;
					for($i=0;$i<count($getid);$i++){
						$ins[$i] = DB::connection($this->dbc)->insert("insert into app_notif_d (kode_lokasi,no_bukti,id_device,nik,no_urut) values (?, ?, ?, ?, ?)",array($kode_lokasi,$this->no_pesan,$getid[$i]['id_device'],$nik_kirim,$no));
						array_push($arr_id, $getid[$i]['id_device']);
						$no++;
					}
					$payload = array(
						'title' => $title,
						'body' => $message,
						'click_action' => 'detail_pengajuan',
						'key' => array(
							'no_bukti' => $no_bukti,
							'modul' => $modul
						)
					);
					$res = $this->gcm($arr_id,$payload);
					$hasil= json_decode($res,true);
					$success['hasil'] = $hasil;
					if(isset($hasil['success'])){
						if($hasil['failure'] > 0){
							$sts = 0;
							$msg_n = "Notif FCM gagal dikirim";
						}else{
							$msg_n = "Notif FCM berhasil dikirim";
							$sts = 1;
						}
					}else{
						$msg_n = "Notif FCM gagal dikirim";
						$sts = 0;
					}
				}

				$updpsn = DB::connection($this->dbc)->table('app_notif_m')
				->where('no_bukti',$this->no_pesan)
				->where('kode_lokasi',$kode_lokasi)
				->update(['sts_kirim'=>1]);
			}

			DB::connection($this->dbc)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            Log::info($success);
        } catch (\Throwable $e) {
			DB::connection($this->dbc)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            Log::error($success);
        }
    }
}
