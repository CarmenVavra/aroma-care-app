<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Every user manages only their own inventory.
 */
class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }
}
