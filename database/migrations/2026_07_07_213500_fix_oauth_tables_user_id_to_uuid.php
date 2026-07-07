<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->uuid('user_id')->change();
        });

        Schema::table('oauth_device_codes', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
        });

        Schema::table('oauth_device_codes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
