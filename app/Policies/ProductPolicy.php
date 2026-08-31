<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Solo los usuarios administradores pueden crear, editar o eliminar
 * productos del catálogo. La consulta (index/show) es pública y no
 * pasa por esta policy.
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->is_admin;
    }
}
