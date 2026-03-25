<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('timezone')->default('UTC');
            $table->unsignedTinyInteger('dispatch_window_start')->default(8);
            $table->unsignedTinyInteger('dispatch_window_end')->default(20);
            $table->unsignedInteger('daily_dispatch_limit')->default(1000);
            $table->unsignedInteger('min_cadence_minutes')->default(5);
            $table->unsignedInteger('duplicate_window_hours')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenants');
    }
};
