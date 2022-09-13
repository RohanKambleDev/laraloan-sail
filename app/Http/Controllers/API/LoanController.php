<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Loan\CreateRequest;

class LoanController extends Controller
{
    /**
     * list all loan taken by the logged in user
     *
     * @return void
     */
    public function index()
    {
        dd('in LoanController index');
    }

    /**
     * create a loan for logged in user
     *
     * @param  mixed $createRequest
     * @return void
     */
    public function create(CreateRequest $createRequest)
    {
        dd('in LoanController create');
        // dd($createRequest->all());
    }

    /**
     * show individual loan details
     *
     * @param  mixed $uuid
     * @return void
     */
    public function show($uuid)
    {
        dd($uuid);
    }
}
