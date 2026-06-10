<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function dispositivoMobile(): BelongsTo
    {
        return $this->belongsTo(DispositivoMobile::class, 'dispositivo_mobile_id');
    }
}
