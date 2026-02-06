<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CustomerCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerCategory');
    }

    public function view(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('View:CustomerCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerCategory');
    }

    public function update(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('Update:CustomerCategory');
    }

    public function delete(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('Delete:CustomerCategory');
    }

    public function restore(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('Restore:CustomerCategory');
    }

    public function forceDelete(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('ForceDelete:CustomerCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerCategory');
    }

    public function replicate(AuthUser $authUser, CustomerCategory $customerCategory): bool
    {
        return $authUser->can('Replicate:CustomerCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerCategory');
    }

}