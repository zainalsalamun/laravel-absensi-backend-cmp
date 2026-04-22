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
        if (! Schema::hasColumn('attendances', 'photo_in') || ! Schema::hasColumn('attendances', 'photo_out')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('attendances', 'photo_in')) {
                    $table->string('photo_in')->nullable()->after('latlon_in');
                }

                if (! Schema::hasColumn('attendances', 'photo_out')) {
                    $table->string('photo_out')->nullable()->after('latlon_out');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'photo_in')) {
                $table->dropColumn('photo_in');
            }

            if (Schema::hasColumn('attendances', 'photo_out')) {
                $table->dropColumn('photo_out');
            }
        });
    }
};
