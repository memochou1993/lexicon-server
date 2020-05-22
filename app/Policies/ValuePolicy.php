<?php

namespace App\Policies;

use App\Enums\ErrorType;
use App\Enums\PermissionType;
use App\Models\User;
use App\Models\Value;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ValuePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if (! $user->tokenCan(PermissionType::VALUE_VIEW_ANY)) {
            return Response::deny(null, ErrorType::PERMISSION_DENIED);
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Value  $value
     * @return mixed
     */
    public function view(User $user, Value $value)
    {
        if (! $user->tokenCan(PermissionType::VALUE_VIEW)) {
            return Response::deny(null, ErrorType::PERMISSION_DENIED);
        }

        if (! $user->hasProject($value->key->project)) {
            return Response::deny(null, ErrorType::USER_NOT_IN_PROJECT);
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (! $user->tokenCan(PermissionType::VALUE_CREATE)) {
            return Response::deny(null, ErrorType::PERMISSION_DENIED);
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Value  $value
     * @return mixed
     */
    public function update(User $user, Value $value)
    {
        if (! $user->tokenCan(PermissionType::VALUE_UPDATE)) {
            return Response::deny(null, ErrorType::PERMISSION_DENIED);
        }

        if (! $user->hasProject($value->key->project)) {
            return Response::deny(null, ErrorType::USER_NOT_IN_PROJECT);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  Value  $value
     * @return mixed
     */
    public function delete(User $user, Value $value)
    {
        if (! $user->tokenCan(PermissionType::VALUE_DELETE)) {
            return Response::deny(null, ErrorType::PERMISSION_DENIED);
        }

        if (! $user->hasProject($value->key->project)) {
            return Response::deny(null, ErrorType::USER_NOT_IN_PROJECT);
        }

        return true;
    }
}
