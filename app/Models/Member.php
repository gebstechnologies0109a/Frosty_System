<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'name',
        'member_code',
        'email',
        'referrer_member_id',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referrer_member_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Member::class, 'referrer_member_id');
    }

    public function kiloPurchases(): HasMany
    {
        return $this->hasMany(KiloPurchase::class);
    }

    public function pointLedger(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(MonthlyMemberSummary::class);
    }
}
