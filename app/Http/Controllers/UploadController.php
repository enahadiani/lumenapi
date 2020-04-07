<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

use App\Upload;
use File;

class UploadController extends Controller
{
    // public function upload(Request $request)
    // {
    //     //TAMBAHKAN BAGIAN INI
    //     $this->validate($request, [
    //         'nama' => 'required|string',
    //         'foto' => 'nullable|image|mimes:jpg,jpeg,png'
    //     ]);
        
    //     try {
    //         //SEDIKIT TYPO DARI VARIABLE $filename, SEHINGGA PERBAHARUI SELURUH VARIABL TERKAIT
    //         $filename = null;
    //         if ($request->hasFile('foto')) {
    //             $filename = $request->nik . '.jpg';
    //             $file = $request->file('foto');
    //             $file->move(base_path('public/images'), $filename); //
    //         }
            
    //         return response()->json(['message' => 'CREATED','file_name'=>$filename], 200);

    //     } catch (\Exception $e) {
    //         //return error message
    //         return response()->json(['message' => 'Upload Failed!'.$e], 409);
    //     }

    // }


    public function upload(){
        $gambar = Upload::all();
        return response()->json(['daftar' => $gambar,'status'=>true], 200);
	}
 
	public function proses_upload(Request $request){
        
        $this->validate($request, [
			'file' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
			'keterangan' => 'required',
		]);
 
        try {
            // menyimpan data file yang diupload ke variabel $file
            $file = $request->file('file');
    
            $nama_file = time()."_".$file->getClientOriginalName();

            // $picName = uniqid() . '_' . $picName;
            $path = 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            $destinationPath = base_path($path); // upload path
            // File::makeDirectory($destinationPath, 0777, true, true);
            $file->move($destinationPath, $nama_file);
    
            //         // isi dengan nama folder tempat kemana file diupload
            // $tujuan_upload = base_path('public/images');
            // $file->move($tujuan_upload,$nama_file);
    
            Upload::create([
                'file_dok' => $nama_file,
                'nama' => $request->keterangan,
            ]);
    
            return response()->json(['message' => 'Upload Berhasil','status'=>true], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Upload Failed! '.$e,'status'=>false], 200);
        }
	}
   
}
