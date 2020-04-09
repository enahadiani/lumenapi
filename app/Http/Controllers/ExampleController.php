<?php

namespace App\Http\Controllers;

class ExampleController extends Controller
{
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getThings(Request $request, $category)
    {
        $criteria= $request->input("criteria");
        if (! isset($category)) {
            return response()->json(null, Response::HTTP_BAD_REQUEST);
        }

        // ...

        return response()->json(["thing1", "thing2"], Response::HTTP_OK);
    }
}
