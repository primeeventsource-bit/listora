<?php

namespace App\Http\Requests;

use App\Enums\PlanTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreListingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) feature('listing_wizard');
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['home', 'points', 'weeks'])],
            'mode' => ['required', Rule::in(['rent', 'own'])],

            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],

            'property_name' => ['nullable', 'string', 'max:160'],
            'club_name' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:64'],
            'region' => ['nullable', 'string', 'max:96'],

            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:6000'],

            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'sleeps' => ['nullable', 'integer', 'min:1', 'max:40'],
            'points' => ['nullable', 'integer', 'min:1', 'max:500000'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:53'],
            'season' => ['nullable', 'string', 'max:60'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', Rule::in(['total', 'night', 'week', 'point'])],

            'plan' => ['required', Rule::enum(PlanTier::class)],
        ];
    }

    /**
     * Each listing kind has a fact without which the listing cannot be
     * written: points needs a points balance, weeks needs a week number, a
     * property needs somewhere it is. Enforced here rather than left to the
     * reviewer, who would otherwise have to chase the owner for it.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                match ($this->input('kind')) {
                    'points' => $this->requireField($validator, 'points', 'Tell us how many club points this package carries.'),
                    'weeks' => $this->requireField($validator, 'week_number', 'Tell us which week number this is.'),
                    'home' => $this->requireField($validator, 'city', 'Tell us what city or area the property is in.'),
                    default => null,
                };
            },
        ];
    }

    private function requireField(Validator $validator, string $field, string $message): void
    {
        if (blank($this->input($field))) {
            $validator->errors()->add($field, $message);
        }
    }
}
