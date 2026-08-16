<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Offers can be switched off wholesale from the Settings console, and
        // an operator may require an account to submit one. Both are checked
        // here rather than in the controller so the API and the web form
        // cannot diverge on who is allowed to contact an owner.
        if (! setting('offers.enabled', true) || ! feature('offers')) {
            return false;
        }

        return ! setting('offers.require_account', false) || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],

            // Dollars at the edge; converted to integer cents in
            // offerAmountCents() so no float ever reaches the database.
            'offer_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],

            'arrive' => ['nullable', 'date'],
            'depart' => ['nullable', 'date', 'after_or_equal:arrive'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // A stay window is either both ends or neither. One date alone
                // reaches the owner as an unanswerable question.
                $arrive = $this->input('arrive');
                $depart = $this->input('depart');

                if (($arrive === null) !== ($depart === null)) {
                    $validator->errors()->add(
                        'depart',
                        'Give both an arrival and a departure date, or leave both blank.',
                    );
                }
            },
        ];
    }

    /**
     * The offer amount as integer cents, or null for a plain inquiry.
     *
     * Money crosses into the application exactly here. Multiplying by 100 in
     * float space and casting would drop a cent on values like 89.29, so the
     * conversion rounds explicitly.
     */
    public function offerAmountCents(): ?int
    {
        $amount = $this->input('offer_amount');

        return ($amount === null || $amount === '')
            ? null
            : (int) round(((float) $amount) * 100);
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Tell the owner a little more — at least a sentence.',
        ];
    }
}
