<?php

namespace Tests\Unit\API;

use Tests\TestCase;
use App\Services\LoanService;

class LoanTest extends TestCase
{
    public function test_loan_service_methods()
    {
        // method to create a loan
        $exists = method_exists(new LoanService(), 'create');
        $this->assertTrue($exists);

        // method to list the loan
        $exists = method_exists(new LoanService(), 'listLoans');
        $this->assertTrue($exists);

        // method to view a loan
        $exists = method_exists(new LoanService(), 'viewLoan');
        $this->assertTrue($exists);

        // method to repay loan
        $exists = method_exists(new LoanService(), 'repayLoan');
        $this->assertTrue($exists);

        // method to approve laon
        $exists = method_exists(new LoanService(), 'approveLoan');
        $this->assertTrue($exists);

        // method to close the loan / mark loan as paid
        $exists = method_exists(new LoanService(), 'closeLoan');
        $this->assertTrue($exists);

        // method to get the status of loan - Pending, Approved, Paid
        $exists = method_exists(new LoanService(), 'getStatus');
        $this->assertTrue($exists);
    }
}
