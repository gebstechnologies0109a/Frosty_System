<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $month
 * @property int $personal_points
 * @property bool $qualified
 */
class Qualification extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'personal_points',
        'qualified',
    ];

    protected function casts(): array
    {
        return [
            'personal_points' => 'integer',
            'qualified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Qualification> */
        return $this->belongsTo(User::class);
    }
}
