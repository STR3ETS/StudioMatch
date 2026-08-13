<?php

namespace App\Models;

use App\Enums\OwnerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'phone', 'owner_type', 'btw_plichtig', 'kvk_number', 'vat_number', 'stripe_account_id', 'stripe_details_submitted', 'stripe_payouts_enabled'])]
class HostProfile extends Model
{

    protected function casts(): array
    {
        return [
            'owner_type' => OwnerType::class,
            'btw_plichtig' => 'boolean',
            'stripe_details_submitted' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
