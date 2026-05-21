<?php

use App\Services\OperatorProductDefaultsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OperatorProductDefaultsService::class)->backfillAllOperators();
    }

    public function down(): void
    {
        // Defaults are re-created on demand; no rollback needed.
    }
};
