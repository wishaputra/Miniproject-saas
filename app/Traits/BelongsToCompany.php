<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;

/**
 * Trait BelongsToCompany
 *
 * Di-use oleh semua model tenant-scoped (Project, Task).
 * Melakukan dua hal:
 * 1. Mendaftarkan CompanyScope sebagai global scope — setiap query model
 *    secara otomatis ter-filter WHERE company_id = auth()->user()->company_id
 * 2. Mengisi company_id secara otomatis saat record baru dibuat
 *    (model event 'creating') — client tidak pernah bisa memanipulasi company_id
 */
trait BelongsToCompany
{
    /**
     * Boot trait: register global scope dan auto-fill company_id saat creating.
     */
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function ($model) {
            // Hanya set jika ada user yang login dan company_id belum di-set
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }
}
