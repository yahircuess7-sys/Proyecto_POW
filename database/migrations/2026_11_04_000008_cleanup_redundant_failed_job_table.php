<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('failed_job');
    }

    public function down(): void
    {
        // La tabla oficial de Laravel es failed_jobs (en plural)
    }
};
