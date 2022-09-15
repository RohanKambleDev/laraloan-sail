<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Support\Str;
use App\Models\ScheduledPayment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\API\Loan\CreateRequest;
use App\Http\Requests\API\Loan\ApproveRequest;
use App\Http\Requests\API\Loan\PaymentRequest;
use NunoMaduro\Collision\Adapters\Phpunit\State;

class LoanController extends Controller
{
    /**
     * list all loan taken by the logged in user
     *
     * @return void
     */
    public function index(Loan $loan)
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            $loans = $loan->all(); // get the loans for all users
        } else {
            $loans = $user->loans()->get(); // get the loans for logged in use
        }

        if ($loans->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No Loan Found'
            ], 200);
        }

        return response()->json([
            'status' => true,
            'loans' => $loans->toArray(),
            'message' => 'Loans fetched successfully'
        ], 200);
    }

    private function getStatus($slug)
    {
        return Status::getIdBySlug($slug);
    }

    /**
     * create a loan for logged in user
     *
     * @param  mixed $createRequest
     * @return void
     */
    public function create(CreateRequest $request, Loan $loan)
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
            'status_id' => $this->getStatus('pending'),
            'frequency' => Loan::FREQUENCY
        ]);

        $scheduledPaymentAmount = ($loanAmount / $loanTerm);
        $schedulePaymentArr = [
            'date'      => now(),
            'amount'    => $scheduledPaymentAmount,
            'status_id' => $this->getStatus('pending'),
        ];
        $schedulePaymentCreateArr = [];
        for ($i = 0; $i < $loanTerm; $i++) {
            $schedulePaymentArr['uuid'] = Str::orderedUuid();
            $schedulePaymentArr['date'] = Carbon::parse($schedulePaymentArr['date'])->addDays(7)->format('Y-m-d h:i:s');
            $schedulePaymentCreateArr[] = $schedulePaymentArr;
        }

        $scheduledPaymentCreated = $loanCreated->scheduledPayments()->createMany($schedulePaymentCreateArr);
        return response()->json([
            'status' => true,
            'loan' => $loanCreated,
            'scheduledPayments' => $scheduledPaymentCreated,
            'message' => 'Loan created successfully'
        ], 200);
    }

    /**
     * show individual loan details
     *
     * @param  mixed $uuid
     * @return void
     */
    public function show($uuid, Loan $loan)
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            $loan = $loan->where('uuid', $uuid)->first(); // get the loan with specific uuid
        } else {
            $loan = $user->loans()->where('uuid', $uuid)->first(); // get the loan with specific uuid but only for logged in use
        }

        return response()->json([
            'status' => true,
            'scheduledPayments' => $loan->scheduledPayments->toArray(),
            'message' => 'Loan Details fetched successfully'
        ], 200);
    }

    public function makePayment(PaymentRequest $request, ScheduledPayment $scheduledPayment)
    {
        // get validated request data
        $requestData = $request->validated();

        $scheduled_payment_uuid = $requestData['scheduled_payment_uuid'];
        $amount = $requestData['amount'];

        $scheduledPaymentRecord = $scheduledPayment->where('uuid', $scheduled_payment_uuid)->get();
        if ($scheduledPaymentRecord->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No Scheduled Payment found'
            ], 200);
        }

        $this->authorize('repay-loan', $scheduledPaymentRecord);

        $isApproved = $scheduledPaymentRecord->first()->loan->status_id == $this->getStatus('approved');
        if ($isApproved) {
            $record = $scheduledPaymentRecord->first();
            if ($amount >= $record->amount) {
                $updated = $scheduledPayment->where('uuid', $scheduled_payment_uuid)
                    ->update([
                        'status_id' => $this->getStatus('paid'),
                        'amount_paid' => $amount
                    ]);
                if ($updated) {
                    return response()->json([
                        'status' => true,
                        'scheduledPayments' => $scheduledPayment->where('uuid', $scheduled_payment_uuid)->first()->toArray(),
                        'message' => 'Payment successful'
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'Amount less than the scheduled payment amount'
                ], 200);
            }
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Loan is not approved, so cannot make the payment'
            ], 200);
        }
    }

    public function approve(ApproveRequest $request, Loan $loan)
    {
        $loan_uuid = $request->only('loan_uuid');
        $loanData = $loan->where('uuid', $loan_uuid)->get();
        if ($loanData->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No Loan found'
            ], 200);
        }
        $updated = $loan->where('uuid', $loan_uuid)->update(['status_id' => $this->getStatus('approved')]);
        if ($updated) {
            return response()->json([
                'status' => true,
                'scheduledPayments' => $loan->where('uuid', $loan_uuid)->first()->toArray(),
                'message' => 'Loan Approved successfully'
            ], 200);
        }
    }
}
