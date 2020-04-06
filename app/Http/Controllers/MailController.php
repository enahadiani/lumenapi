<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; 

class MailController extends Controller
{
    public function send(Request $request)
    {
        $to_name = $request->input('to_name');
        $to_email = $request->input('to_email');
        $pesan = $request->input('message');
        $data = array("name"=>$to_name,"body"=>$pesan);
        
        try{
            Mail::send('mail',$data,function($message) use ($to_name,$to_email){
                $message->to($to_email)
                ->subject('Email send from Lumen SAI');
            });
            
            $success['status'] = true;
            $success['message'] = "Email berhasil dikirim ";
            return response()->json($success, 200);
            
        } catch (\Throwable $e) {
           
            $success['status'] = false;
            $success['message'] = "Email gagal dikirim ".$e;
            return response()->json($success, 200);
        }	

    }
}
