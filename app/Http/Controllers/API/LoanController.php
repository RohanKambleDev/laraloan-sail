<?php

namespace App\Http\Controllers\API;

use App\Models\Loan;
use App\Traits\API\RestTrait;
use App\Models\ScheduledPayment;
use App\Http\Controllers\Controller;
use App\Services\LoanService;
use App\Http\Requests\API\Loan\CreateRequest;
use App\Http\Requests\API\Loan\ApproveRequest;
use App\Http\Requests\API\Loan\PaymentRequest;
use App\Models\User;

class LoanController extends Controller
{
    use RestTrait;

    /**
     * list all loan taken by the logged in user
     * and if Admin is logged in then all the loans
     * of all users will be displayed
     *
     * @param  Loan $loan
     * @param  LoanService $loanService
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Loan $loan, LoanService $loanService)
    {
        $responseData = $loanService->listLoans($loan);
        return $this->successResponse($responseData['data'], $responseData['message'], $responseData['statusCode']);
    }

    /**
     * create a Loan
     *
     * @param  CreateRequest $request
     * @param  Loan $loan
     * @param  LoanService $loanService
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request, Loan $loan, LoanService $loanService, User $user)
    {
        $requestData = $request->validated(); // get validated request data
        $responseData = $loanService->create($requestData, $loan, $user);
        return $this->successResponse($responseData['data'], $responseData['message'], $responseData['statusCode']);
    }

    /**
     * show individual loan details
     *
     * @param  string $uuid
     * @param  Loan $loan
     * @param  LoanService $loanService
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($uuid, Loan $loan, LoanService $loanService)
    {
        $responseData = $loanService->viewLoan($uuid, $loan);
        return $this->successResponse($responseData['data'], $responseData['message'], $responseData['statusCode']);
    }

    /**
     * To make a payment againt a Loan installment
     *
     * @param  PaymentRequest $request
     * @param  ScheduledPayment $scheduledPayment
     * @param  LoanService $loanService
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function makePayment(PaymentRequest $request, ScheduledPayment $scheduledPayment, LoanService $loanService)
    {
        $requestData = $request->validated(); // get validated request data
        $responseData = $loanService->repayLoan($requestData, $scheduledPayment);
        return $this->successResponse($responseData['data'], $responseData['message'], $responseData['statusCode']);
    }

    /**
     * approve
     *
     * @param  ApproveRequest $request
     * @param  Loan $loan
     * @param  LoanService $loanService
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(ApproveRequest $request, Loan $loan, LoanService $loanService)
    {
        $requestData = $request->validated(); // get validated request data
        $responseData = $loanService->approveLoan($requestData, $loan);
        return $this->successResponse($responseData['data'], $responseData['message'], $responseData['statusCode']);
    }
}
