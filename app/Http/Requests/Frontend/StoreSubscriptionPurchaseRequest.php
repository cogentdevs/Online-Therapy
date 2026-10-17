<?php

namespace App\Http\Requests\Frontend;

use App\Models\PaymentAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSubscriptionPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_account_id' => [
                'required',
                'integer',
                Rule::exists((new PaymentAccount)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'payment_slip' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payment_account_id.required' => 'Please select a payment account.',
            'payment_account_id.exists' => 'The selected payment account is not available.',
            'payment_slip.required' => 'Please upload your payment slip.',
            'payment_slip.file' => 'The payment slip must be a valid file.',
            'payment_slip.mimes' => 'The payment slip must be a JPG, JPEG, PNG, WEBP, or PDF file.',
            'payment_slip.mimetypes' => 'The payment slip file type is invalid.',
            'payment_slip.max' => 'The payment slip must not be larger than 5 MB.',
            'transaction_id.max' => 'The transaction or reference number may not be greater than 100 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $transactionId = $this->input('transaction_id');

        if (is_string($transactionId)) {
            $normalizedTransactionId = Str::of($transactionId)->trim()->toString();
            $this->merge([
                'transaction_id' => $normalizedTransactionId !== '' ? $normalizedTransactionId : null,
            ]);
        }
    }
}
