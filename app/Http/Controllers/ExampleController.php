<?php

namespace App\Http\Controllers;

class ExampleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/sample/{category}/things",
     *     operationId="/sample/category/things",
     *     tags={"yourtag"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="The category parameter in path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="criteria",
     *         in="query",
     *         description="Some optional other parameter",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns some sample category things",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
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
