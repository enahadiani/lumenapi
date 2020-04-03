<?php

namespace App\Http\Controllers\Gl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index()
    {
        
        
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
        
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function show($kode_klp)
    {
        try {

            $menu = DB::connection('sqlsrv2')->select("select a.*,b.form from menu a left join m_form b on a.kode_form=b.kode_form where a.kode_klp = '$kode_klp' and (isnull(a.jenis_menu,'-') = '-' OR a.jenis_menu = '') order by kode_klp, rowindex ");
            $menu = json_decode(json_encode($menu),true);
            
            if(count($menu) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $menu;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function edit(DevSiswa $DevSiswa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $nim)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function destroy($nim)
    {
        
    }

}
