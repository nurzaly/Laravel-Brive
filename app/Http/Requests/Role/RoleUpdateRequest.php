<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
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
     * Only a superadmin may grant the permission-management permissions.
     */
    protected function permissionItemRules(): array
    {
        $exists = Rule::exists('permissions', 'id');
        if (! $this->user()->isSuperAdmin()) {
            $exists->whereNotIn('name', ['create permission', 'read permission', 'update permission', 'delete permission']);
        }
        return [$exists];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|unique:roles,name,' . $this->role->id,
            'permissions'   => ['required', 'array'],
            'permissions.*' => $this->permissionItemRules(),
        ];
    }
}
