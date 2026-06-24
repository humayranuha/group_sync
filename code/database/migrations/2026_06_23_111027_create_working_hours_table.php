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
    Schema::create('working_hours', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');

        $table->date('date');
        $table->decimal('hours', 5, 2);

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
