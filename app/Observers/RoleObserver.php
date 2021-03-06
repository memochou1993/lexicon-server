<?php

namespace App\Observers;

use App\Models\Role;

class RoleObserver
{
    /**
     * Handle the role "deleted" event.
     *
     * @param  Role  $role
     * @return void
     */
    public function deleted(Role $role)
    {
        $role->users()->detach();
    }
}
