<?php

namespace App\Http\Requests\API\Loan;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'amount' => 'required|numeric',
            'term' => 'required|integer|numeric'
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'Loan cannot be created without a loan amount.',
            'term.integer'    => 'Loan term must always be an integer.',
            'term.required'   => 'Loan cannot be created without a loan term.'
        ];
    }
}
