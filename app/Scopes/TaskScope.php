<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TaskScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
     */
    public function apply(Builder $builder, Model $model): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || $user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder
            // A user with the user project role sees only their own tasks
            ->whereHas('users', static function (Builder $builder) use ($user) {
                $builder->where('id', $user->id);
            })
            ->orWhereHas('project.users', static function (Builder $builder) use ($user) {
                $builder
                    // If the user is a project auditor they can see all the project tasks
                    ->where('user_id', $user->id)
                    ->whereIn('projects_users.role_id', [1, 2, 3]);
            });
    }
}
