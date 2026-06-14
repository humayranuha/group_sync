<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the default name column if exists
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
            
            // Add first_name and last_name
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->after('id');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->after('first_name');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['student', 'professor', 'admin'])->default('student')->after('email');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->dropColumn(['first_name', 'last_name', 'role']);
        });
    }
};