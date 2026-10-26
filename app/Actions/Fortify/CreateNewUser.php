<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:50', 'unique:users'],
            'apellidos_pat' => ['required', 'string', 'max:255'],
            'apellido_mat' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'apellidos_pat' => $input['apellidos_pat'],
            'apellido_mat' => $input['apellido_mat'] ?? null,
            'employee_number' => $input['employee_number'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
