<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Rental;
use Illuminate\Auth\Access\HandlesAuthorization;

class RentalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Rental');
    }

    public function view(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('View:Rental');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Rental');
    }

    public function update(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('Update:Rental');
    }

    public function delete(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('Delete:Rental');
    }

    public function restore(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('Restore:Rental');
    }

    public function forceDelete(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('ForceDelete:Rental');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Rental');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Rental');
    }

    public function replicate(AuthUser $authUser, Rental $rental): bool
    {
        return $authUser->can('Replicate:Rental');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Rental');
    }

}