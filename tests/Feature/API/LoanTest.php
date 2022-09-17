<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\User;
use App\Models\Status;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanTest extends TestCase
{
    public function sanctumLogin($role = 'customer')
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user);
    }

    public function afterTest($reponseArr)
    {
        // first assert base common keys expected in response
        $responseKeys = $this->responseKeys();
        foreach ($responseKeys as $responseKey) {
            $this->assertArrayHasKey($responseKey, $reponseArr);
        }
    }

    public function test_a_customer_can_create_loan()
    {
        // prepare
        $input = [
            'amount' => 5000,
            'term' => 2
        ];

        // perform
        $this->sanctumLogin();
        $response = $this->postJson(route('loan-create'), $input)->assertOk();

        // predict
        // collect the reponse, in array
        $reponseArr = $response->json();
        $this->assertDatabaseHas('loans', ['user_id' => $reponseArr['data']['loan']['user_id']]);

        $this->afterTest($reponseArr);
    }

    public function test_an_admin_can_approve_loan()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        $this->sanctumLogin();
        $response = $this->postJson(route('loan-create'), $input);
        $reponseArr = $response->json();
        $input = [
            'loan_uuid' => $reponseArr['data']['loan']['uuid'],
        ];

        // perform
        $this->sanctumLogin('admin');
        $response = $this->postJson(route('loan-approve'), $input)->assertOk();

        // predict
        // collect the reponse, in array
        $this->assertDatabaseHas('loans', ['status_id' => Status::getIdBySlug('approved')]);
        $reponseArr = $response->json();
        $this->afterTest($reponseArr);
    }

    public function test_a_customer_can_view_loan_list_of_only_self_with_no_loan_created()
    {
        $this->sanctumLogin();
        $this->getJson(route('loan-index'))->assertNoContent();
    }

    public function test_a_customer_can_view_loan_list_of_only_self()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);
        $loanReponseArr = $loanResponse->json();

        $response = $this->getJson(route('loan-index'))->assertOk();
        $reponseArr = $response->json();

        // compare the uuid of loan created and loan list fetched
        $this->assertEquals($loanReponseArr['data']['loan']['uuid'], $reponseArr['data'][0]['uuid']);
        $this->afterTest($reponseArr);
    }

    public function test_an_admin_can_view_loan_list_of_all_members()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);
        $loanReponseArr1 = $loanResponse->json();

        $input = [
            'amount' => 300,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);
        $loanReponseArr2 = $loanResponse->json();

        $this->sanctumLogin('admin');
        $response = $this->getJson(route('loan-index'))->assertOk();
        $reponseArr = $response->json();

        // compare the uuid of loan created and loan list fetched
        $this->assertEquals($loanReponseArr1['data']['loan']['uuid'], $reponseArr['data'][0]['uuid']);
        $this->assertEquals($loanReponseArr2['data']['loan']['uuid'], $reponseArr['data'][1]['uuid']);
        $this->afterTest($reponseArr);
    }

    public function test_a_customer_can_view_only_self_loan()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);
        $reponseArr = $loanResponse->json();
        $uuid = $reponseArr['data']['loan']['uuid'];

        $response = $this->getJson(route('loan-show', $uuid))->assertOk();
        $reponseArr = $response->json();
        $this->afterTest($reponseArr);
    }

    public function test_a_customer_cannot_view_other_users_loan()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);
        $loanReponseArr1 = $loanResponse->json();
        $uuid1 = $loanReponseArr1['data']['loan']['uuid'];

        $input = [
            'amount' => 300,
            'term' => 3
        ];
        $this->sanctumLogin();
        $loanResponse = $this->postJson(route('loan-create'), $input);

        $this->get(route('loan-show', $uuid1))->assertForbidden();
    }

    public function loanRepaymentTest()
    {
        // prepare
        $input = [
            'amount' => 10.000,
            'term' => 3
        ];
        // create a user with role customer
        $userMember = \App\Models\User::factory()->create();
        $userMember->assignRole('customer');
        // login
        Sanctum::actingAs($userMember);

        // create a loan
        $response = $this->postJson(route('loan-create'), $input);
        $reponseArr = $response->json();
        $input = [
            'loan_uuid' => $reponseArr['data']['loan']['uuid'],
        ];

        // perform
        // login as admin
        $this->sanctumLogin('admin');
        // approve the loan as admin
        $response = $this->postJson(route('loan-approve'), $input);
        $reponseArr = $response->json();
        $input = [
            'scheduled_payment_uuid' => $reponseArr['data']['scheduled_payments'][0]['uuid'],
            'amount' => $reponseArr['data']['scheduled_payments'][0]['amount'],
        ];

        // again login as same customer who created the loan to repay the loan
        Sanctum::actingAs($userMember);
        $this->postJson(route('loan-payment'), $input)->assertOk();

        return $reponseArr;
    }

    public function test_a_customer_can_repay_loan()
    {
        $this->loanRepaymentTest();
    }

    public function test_a_customer_can_only_repay_a_loan_if_the_loan_belongs_to_him()
    {
        $reponseArr = $this->loanRepaymentTest();

        $input = [
            'scheduled_payment_uuid' => $reponseArr['data']['scheduled_payments'][0]['uuid'],
            'amount' => $reponseArr['data']['scheduled_payments'][0]['amount'],
        ];

        $userMember = \App\Models\User::factory()->create();
        $userMember->assignRole('customer');
        Sanctum::actingAs($userMember);
        $this->postJson(route('loan-payment'), $input)->assertForbidden();
    }

    public function test_a_loan_is_marked_as_paid_once_all_emis_are_paid()
    {
        $reponseArr = $this->loanRepaymentTest();

        // looping over all the emis/scheduled payments of the loan
        // and paying each emi
        foreach ($reponseArr['data']['scheduled_payments'] as $scheduledPayments) {

            //Write the response in laravel-testing.log
            // Log::channel('testing')->info(1, [$scheduledPayments]);

            $input = [
                'scheduled_payment_uuid' => $scheduledPayments['uuid'],
                'amount' => $scheduledPayments['amount'],
            ];
            $this->postJson(route('loan-payment'), $input)->assertOk();
        }
        // check if the status id of the loan is updated to the id os Paid status
        $this->assertDatabaseHas('loans', ['status_id' => Status::getIdBySlug('paid')]);
    }
}
