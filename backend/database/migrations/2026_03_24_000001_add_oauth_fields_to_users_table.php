<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('huggy_id')->nullable()->unique()->after('id');
            $table->string('password')->nullable()->change();
            $table->text('huggy_access_token')->nullable()->after('password');
            $table->text('huggy_refresh_token')->nullable()->after('huggy_access_token');
            $table->timestamp('huggy_token_expires_at')->nullable()->after('huggy_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['huggy_id', 'huggy_access_token', 'huggy_refresh_token', 'huggy_token_expires_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
