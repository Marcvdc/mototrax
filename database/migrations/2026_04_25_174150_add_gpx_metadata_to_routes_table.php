<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('difficulty');
            $table->json('bbox')->nullable()->after('is_public');
            $table->decimal('start_lat', 10, 7)->nullable()->after('bbox');
            $table->decimal('start_lng', 10, 7)->nullable()->after('start_lat');
            $table->decimal('end_lat', 10, 7)->nullable()->after('start_lng');
            $table->decimal('end_lng', 10, 7)->nullable()->after('end_lat');
            $table->unsignedInteger('waypoint_count')->nullable()->after('end_lng');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->decimal('distance', 9, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'is_public',
                'bbox',
                'start_lat',
                'start_lng',
                'end_lat',
                'end_lng',
                'waypoint_count',
            ]);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->decimal('distance', 8, 2)->nullable()->change();
        });
    }
};
