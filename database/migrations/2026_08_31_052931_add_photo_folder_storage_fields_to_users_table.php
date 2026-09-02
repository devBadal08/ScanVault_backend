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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('total_photos')->default(0);
            $table->unsignedBigInteger('total_folders')->default(0);
            $table->decimal('used_storage_mb', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('total_photos');
            $table->dropColumn('total_folders');
            $table->dropColumn('used_storage_mb');
        });
    }
};
