<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\FormRequest;
use App\Models\User;

class DestroyStatusRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        return $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:statuses,id',
        ];
    }
}
