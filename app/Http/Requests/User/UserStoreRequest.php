<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
     * Role rules: must exist, and only a superadmin may assign the superadmin role.
     */
    protected function roleRules(): array
    {
        $rules = ['required', 'string', Rule::exists('roles', 'name')];
        if (! $this->user()->isSuperAdmin()) {
            $rules[] = Rule::notIn(['superadmin']);
        }

        return $rules;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => $this->roleRules(),
        ];
    }
}
