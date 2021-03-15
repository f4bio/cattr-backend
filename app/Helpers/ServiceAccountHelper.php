<?php


namespace App\Helpers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ServiceAccountHelper
{
    public function findOrCreate(): User
    {
        /* @var Role $roleServiceAccount */
        $roleServiceAccount = Role::query()->where('name', '=', 'service_account')->first();

        if (!$roleServiceAccount) {
            $roleServiceAccount = new Role();
            $roleServiceAccount->fill(['name' => 'service_account',]);
            $roleServiceAccount->save();
        }

        /* @var User $user */
        $user = $roleServiceAccount->users()->first();

        if (!$user) {
            $user = new User();
            $user->fill([
                'full_name' => 'Service account',
                'email' => 'service@account.com',
                'password' => Hash::make('password'),
                'role_id' => $roleServiceAccount->id,
                'active' => true,
            ]);
            $user->save();
        }

        return $user;
    }
}
