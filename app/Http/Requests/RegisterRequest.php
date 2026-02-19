<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
   public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'type_id' => ['required', 'integer'],
            'company_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Name is required',
            'email.required'      => 'Email address is required',
            'email.unique'        => 'This email already exist',
            'password.required'   => 'Password is required',
            'password.min'        => 'Password must be at least 8 characters',
            'type_id.required'    => 'User type is required',
            'company_id.required' => 'Company id is required',
        ];
    }
}
