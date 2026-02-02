<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\Customer;

class CartPolicy
{
    public function update(Customer $customer, Cart $cart): bool
    {
        return $customer->id === $cart->customer_id;
    }

    public function delete(Customer $customer, Cart $cart): bool
    {
        return $customer->id === $cart->customer_id;
    }
}