<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The short information sheet, as distinct from StoreListingDraftRequest.
 *
 * Three required fields and nothing else: whether it is being rented or passed
 * on, and how to reach you. Everything the wizard insists on
 * — the points balance, the week number, the city, a plan — is optional here
 * on purpose, because the whole promise of this form is that a specialist
 * calls and goes over it with you. Demanding a season and a usage year before
 * that conversation would make it the wizard with fewer boxes, which is not
 * worth a second page.
 *
 * The trade is that these drafts reach the queue thinner than wizard ones.
 * That is the specialist's job, not the owner's homework.
 */
class StorePropertyInformationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) feature('listing_wizard');
    }

    public function rules(): array
    {
        return [
            // No `kind`. The form no longer asks, so accepting one from the
            // request would let a submission name a category the site does
            // not advertise. The controller sets it.
            'mode' => ['required', Rule::in(['rent', 'own'])],

            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],

            'resort_name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:200'],
            'club_name' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:64'],

            'description' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'owner_name' => 'name',
            'owner_email' => 'email address',
            'resort_name' => 'property name',
            'address' => 'property address',
            'club_name' => 'club',
            'description' => 'details',
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Let us know whether you are renting it out or passing it on.',
        ];
    }
}
