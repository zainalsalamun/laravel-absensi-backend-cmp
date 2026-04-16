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
        Schema::create('face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('face_embedding')->comment('JSON encoded face feature data');
            $table->string('photo_url')->comment('Stored face enrollment photo');
            $table->text('face_features')->nullable()->comment('Additional face features for matching');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_enrollments');
    }
};
