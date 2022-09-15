<?php

namespace App\Traits\API;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait RestTrait
{
    /**
     * Determines if request is an api call.
     *
     * If the request URI contains '/api/v'.
     *
     * @param Request $request
     * @return bool
     */
    public function isApiCall(Request $request)
    {
        return strpos($request->getUri(), '/api/v') !== false;
    }

    /**
     * successResponse
     *
     * @param  mixed $data
     * @param  mixed $message
     * @param  mixed $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, $message = '', $statusCode = Response::HTTP_OK)
    {
        $response = [
            'status' => 'Success',
            'message' => $message,
            'data' => $data
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * errorResponse
     *
     * @param  mixed $message
     * @param  mixed $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $statusCode)
    {
        $response = [
            'status' => 'Error',
            'message' => $message,
            'data' => []
        ];

        return response()->json($response, $statusCode);
    }
}
