<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // SQLite এর জন্য আলাদা approach
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite তে column modify করতে recreate করতে হবে
            DB::statement('PRAGMA foreign_keys=off');
            
            // নতুন টেবিল তৈরি
            DB::statement('
                CREATE TABLE audit_logs_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    action TEXT NOT NULL,
                    description TEXT,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
            
            // ডাটা কপি
            DB::statement('
                INSERT INTO audit_logs_new (id, user_id, action, created_at, updated_at)
                SELECT id, user_id, action, created_at, updated_at FROM audit_logs
            ');
            
            // পুরানো টেবিল ড্রপ
            DB::statement('DROP TABLE audit_logs');
            
            // নতুন টেবিল রিনেম
            DB::statement('ALTER TABLE audit_logs_new RENAME TO audit_logs');
            
            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // অন্যান্য ডাটাবেসের জন্য (MySQL, PostgreSQL)
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Rollback - আবার NOT NULL করতে
            DB::statement('PRAGMA foreign_keys=off');
            
            DB::statement('
                CREATE TABLE audit_logs_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    action TEXT NOT NULL,
                    description TEXT NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
            
            DB::statement('
                INSERT INTO audit_logs_old (id, user_id, action, description, created_at, updated_at)
                SELECT id, user_id, action, COALESCE(description, ""), created_at, updated_at FROM audit_logs
            ');
            
            DB::statement('DROP TABLE audit_logs');
            DB::statement('ALTER TABLE audit_logs_old RENAME TO audit_logs');
            
            DB::statement('PRAGMA foreign_keys=on');
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->text('description')->nullable(false)->change();
            });
        }
    }
};