<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\throwException;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LoanService
{
    use AuthorizesRequests;
    /**
     * getStatus
     * get id of the different loan/payment status
     * 1 - pending
     * 2 - approved
     * 3 - paid
     *
     * @param  mixed $slug
     * @return int id
     */
    private function getStatus($slug)
    {
        return Status::getIdBySlug($slug);
    }

    /**
     * list Loans
     * list the loans depending on the roles
     *
     * @param  Loan $loan
     * @return array
     */
    public function listLoans(Loan $loan)
    {
        try {
            $user = Auth::user();
            if ($user->hasRole('admin')) {
                // get the loans for all users
                $loans = $loan->all();
            } else {
                // get the loans for logged in use
                $loans = $user->loans()->get();
            }

            // success
            if ($loans->isEmpty()) {
                $responseData = ['data' => [], 'message' => 'No Loan Found', 'statusCode' => Response::HTTP_NO_CONTENT];
            } else {
                $responseData = ['data' => $loans->toArray(), 'message' => 'Loans fetched successfully', 'statusCode' => Response::HTTP_OK];
            }
        } catch (HttpResponseException $e) {
            throwException($e);
        }

        return $responseData;
    }

    /**
     * create Loan
     * create Loan entry and its scheduled payments
     * with loan status as 1 - pending
     * and payment status as 1 - pending
     *
     * @param  mixed $loan
     * @param  mixed $requestData
     * @return array
     */
    public function create($requestData, Loan $loan, User $user)
    {
        $loanAmount = $requestData['amount'];
        $loanTerm   = $requestData['term'];

        try {
            $loanCreated = $user->where('uuid', Auth::user()->uuid)->first()->loans()->create([
                'uuid'      => Str::orderedUuid(),
                'amount'    => $loanAmount,
                'term'      => $loanTerm,
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
        } catch (HttpResponseException $e) {
            throwException($e);
        }

        $responseData = [
            'data' => [
                'loan' => $loanCreated,
                'scheduled_payments' => $scheduledPaymentCreated,
            ],
            'message' => 'Loan created successfully',
            'statusCode' => Response::HTTP_OK
        ];

        return $responseData;
    }

    /**
     * view Loan depending on the roles
     *
     * @param  mixed $uuid
     * @param  mixed $loan
     * @return array
     */
    public function viewLoan($uuid, Loan $loan)
    {
        try {
            $user = Auth::user();
            if ($user->hasRole('admin')) {
                $loan = $loan->where('uuid', $uuid)->first(); // get the loan with specific uuid
            } else {
                $loan = $user->loans()->where('uuid', $uuid)->first(); // get the loan with specific uuid but only for logged in use
            }
        } catch (HttpResponseException $e) {
            throwException($e);
        }

        $responseData = [
            'data' => ['scheduled_payments' => $loan->scheduledPayments->toArray()],
            'message' => 'Loan Details fetched successfully',
            'statusCode' => Response::HTTP_OK
        ];

        return $responseData;
    }

    /**
     * Repay Loan
     *
     * @param  array $requestData
     * @param  ScheduledPayment $scheduledPayment
     *
     * @return array $responseData
     */
    public function repayLoan($requestData, $scheduledPayment)
    {
        // get the arguments required for loan payment
        $scheduled_payment_uuid = $requestData['scheduled_payment_uuid'];
        $amount = $requestData['amount'];

        try {
            // get the scheduled payment record for which payment is requested
            $scheduledPaymentRecord = $scheduledPayment->where('uuid', $scheduled_payment_uuid)->get();

            // if no record found
            if ($scheduledPaymentRecord->isEmpty()) {
                return [
                    'data' => [],
                    'message' => 'No Scheduled Payment found',
                    'statusCode' => Response::HTTP_NO_CONTENT
                ];
            }

            // make sure the user accessing this method has 'repay-loan' permission
            $this->authorize('repay-loan', $scheduledPaymentRecord);

            // make sure the loan is approved before marking it as paid
            $isApproved = $scheduledPaymentRecord->first()->loan->status_id == $this->getStatus('approved');
            if ($isApproved) {
                $record = $scheduledPaymentRecord->first();
                // allow to repay loan, only if the amount is greater than or equal to scheduled payment
                if ($amount >= $record->amount) {
                    $updated = $scheduledPayment->where('uuid', $scheduled_payment_uuid)
                        ->update([
                            'status_id' => $this->getStatus('paid'),
                            'amount_paid' => $amount
                        ]);
                    if ($updated) {
                        $responseData = [
                            'data' => [
                                'scheduled_payments' => $scheduledPayment->where('uuid', $scheduled_payment_uuid)->first()->toArray()
                            ],
                            'message' => 'Payment successful',
                            'statusCode' => Response::HTTP_OK
                        ];
                        // once the scheduled payment is marked as paid,
                        // check if all the scheduled payments are marked as paid
                        // if they are all paid, then mark the Loan as paid
                        if ($this->closeLoan($scheduledPaymentRecord)) {
                            $responseData['message'] = 'Full payment done, closing loan';
                        }
                    }
                } else {
                    $responseData = [
                        'data' => [],
                        'message' => 'Amount less than the scheduled payment amount',
                        'statusCode' => Response::HTTP_OK
                    ];
                }
            } else {
                $responseData = [
                    'data' => [],
                    'message' => 'Loan is not approved, so cannot make the payment',
                    'statusCode' => Response::HTTP_OK
                ];
            }
        } catch (HttpResponseException $e) {
            throwException($e);
        }

        return $responseData;
    }

    /**
     * Approve Loan
     *
     * @param  array $requestData
     * @param  Loan $loan
     *
     * @return array $responseData
     */
    public function approveLoan($requestData, Loan $loan)
    {
        $loan_uuid = $requestData['loan_uuid'];
        $loanData = $loan->where('uuid', $loan_uuid)->get();

        if ($loanData->isEmpty()) {
            $responseData = [
                'data' => [],
                'message' => 'No Loan found',
                'statusCode' => Response::HTTP_OK
            ];
        }

        // make sure the loan is in pending status, if it is not, do not proceed further
        // instead return a message as per the loan status
        if ($loanData->first()->status_id !== $this->getStatus('pending')) {
            if ($loanData->first()->status_id === $this->getStatus('approved')) {
                $message = 'Loan is already approved';
            } else {
                $message = 'Loan is paid';
            }
            $responseData = [
                'data' => [],
                'message' => $message,
                'statusCode' => Response::HTTP_OK
            ];
        }

        // mark the loan as approved
        $updated = $loan->where('uuid', $loan_uuid)->update(['status_id' => $this->getStatus('approved')]);
        if ($updated) {
            $responseData = [
                'data' => ['scheduled_payments' => $loan->where('uuid', $loan_uuid)->first()->scheduledPayments->toArray()],
                'message' => 'Loan Approved successfully',
                'statusCode' => Response::HTTP_OK
            ];
        }

        return $responseData;
    }

    /**
     * once all the EMI's status is (3 - Paid)
     * mark the Loan as paid
     *
     * once the scheduled payment is marked as paid,
     * check if all the scheduled payments are marked as paid
     * if they are all paid, then mark the Loan as paid
     * @param  mixed $scheduledPaymentRecord
     * @return void
     */
    public function closeLoan($scheduledPaymentRecord)
    {
        $updated        = 0;
        $loan_id        = $scheduledPaymentRecord->first()->loan_id;
        $totalEmis      = $scheduledPaymentRecord->first()->where('loan_id', $loan_id)->get()->pluck('status_id');
        $totalEmisCount = $totalEmis->count();
        $countBy        = $totalEmis->countBy()->all();

        if (isset($countBy)) {
            if ($countBy[$this->getStatus('paid')] == $totalEmisCount) {
                $updated = $scheduledPaymentRecord->first()->loan->update(['status_id' => $this->getStatus('paid')]);
            }
        }

        return $updated;
    }
}
