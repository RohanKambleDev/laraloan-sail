<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScheduledPayment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
        // get the loans for logged in use
        $loans = Auth::user()->loans()->get();

        return response()->json([
            'status' => true,
            'loans' => $loans->toArray(),
            'message' => 'Loans fetched successfully'
        ], 200);
    }

    /**
     * create a loan for logged in user
     *
     * @param  mixed $createRequest
     * @return void
     */
    public function create(CreateRequest $request, Loan $loan, Status $status, ScheduledPayment $scheduledPayment)
    {
        // get validated request data
        $requestData = $request->validated();

        $loanAmount = $requestData['amount'];
        $loanTerm   = $requestData['term'];

        $loanCreated = $loan->create([
            'uuid' => Str::orderedUuid(),
            'user_uuid' => Auth::user()->uuid,
            'amount' => $loanAmount,
            'term' => $loanTerm,
            'status_id' => $status->getIdBySlug('pending'),
            'frequency' => Loan::FREQUENCY
        ]);

        $scheduledPaymentAmount = ($loanAmount / $loanTerm);
        $schedulePaymentArr = [
            'uuid' => Str::orderedUuid(),
            'date' => now(),
            'amount' => $scheduledPaymentAmount,
            'status_id' => $status->getIdBySlug('pending'),
        ];
        $schedulePaymentCreateArr = [];
        for ($i = 0; $i < $loanTerm; $i++) {
            $schedulePaymentArr['date'] = Carbon::parse($schedulePaymentArr['date'])->addDays(7)->format('Y-m-d h:i:s');
            $schedulePaymentCreateArr[] = $schedulePaymentArr;
        }

        $scheduledPaymentCreated = $loanCreated->scheduledPayments()->createMany($schedulePaymentCreateArr);
        dd($loanCreated, $scheduledPaymentCreated);
    }

    /**
     * show individual loan details
     *
     * @param  mixed $uuid
     * @return void
     */
    public function show($uuid)
    {
        // get the loans for logged in use
        $loan = Auth::user()->loans()->where('uuid', $uuid)->first();
        return response()->json([
            'status' => true,
            'scheduledPayments' => $loan->scheduledPayments->toArray(),
            'message' => 'Loan Details fetched successfully'
        ], 200);
    }
}
