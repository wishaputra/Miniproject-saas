<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope yang otomatis menambahkan WHERE company_id = ?
 * ke setiap query pada model yang menggunakan trait BelongsToCompany.
 *
 * PENTING: company_id SELALU diambil dari auth()->user()->company_id,
 * TIDAK PERNAH dari request/URL/body — ini adalah garda utama tenant isolation.
 * Sumber company_id yang salah = data bocor antar tenant.
 */
class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Guard berlapis: (1) ada session auth, (2) user object tidak null.
        // Skip scope di konteks tanpa auth: artisan command, queue worker, seeder.
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        // Sumber kebenaran tenant: HANYA dari user yang terautentikasi,
        // bukan dari request()->input(), route param, atau apapun dari client.
        $builder->where($model->getTable() . '.company_id', $user->company_id);
    }
}
