<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['personal', 'business'])->default('personal')->after('email');
            $table->boolean('is_admin')->default(false)->after('account_type');
            $table->boolean('is_suspended')->default(false)->after('is_admin');
            $table->boolean('is_blocked')->default(false)->after('is_suspended');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'is_admin', 'is_suspended', 'is_blocked']);
        });
    }
};
