<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_account', function (Blueprint $table) {
            // Preserve the permissions of existing administrator accounts.
            $table->string('role')->default('admin');
        });
    }

    public function down(): void
    {
        Schema::table('users_account', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
