<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('channels', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::connection('landlord')->table('channels')->insert([
            ['id' => 1,  'name' => 'InternalChat',     'slug' => 'internal-chat',          'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 2,  'name' => 'Whatsapp',          'slug' => 'whatsapp',               'active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3,  'name' => 'Widget',             'slug' => 'widget',                 'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 4,  'name' => 'Messenger',          'slug' => 'messenger',              'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 6,  'name' => 'E-mail',             'slug' => 'email',                  'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 7,  'name' => 'Voip',               'slug' => 'voip',                   'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'name' => 'Telegram Bot',       'slug' => 'telegram-bot',           'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'name' => 'Whatsapp API',       'slug' => 'whatsapp-business-api',  'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'ConversationPage',   'slug' => 'conversation-page',      'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'name' => 'SDK',                'slug' => 'sdk',                    'active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'name' => 'Instagram',          'slug' => 'instagram',              'active' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('channels');
    }
};
