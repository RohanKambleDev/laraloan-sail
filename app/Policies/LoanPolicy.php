<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\ScheduledPayment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_loan_list')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if ($user->can('create_loan')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  string  $uuid
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, $uuid)
    {
        if ($user->hasRole('admin')) {
            if ($user->can('view_loan')) {
                return true;
            }
        } else {
            if ($user->can('view_loan') && $user->loans->contains('uuid', $uuid)) {
                return true;
            }
        }
    }

    /**
     * Determine whether the user can repay the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function repay(User $user, $scheduledPaymentRecord)
    {
        // a customer can only repay a loan if the loan belongs to him
        if ($scheduledPaymentRecord->first()->loan->user->uuid == $user->uuid) {
            if ($user->can('repay_loan')) {
                return true;
            }
        }
    }

    /**
     * Determine whether the user can approve the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user)
    {
        if ($user->can('approve_loan')) {
            return true;
        }
    }
}
