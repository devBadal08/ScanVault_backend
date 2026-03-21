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
        Schema::table('photos', function (Blueprint $table) {

            // For company + user photo counting
            $table->index(
                ['company_id', 'user_id', 'type'],
                'idx_photos_company_user_type'
            );

            // For folder gallery loading
            $table->index(
                ['folder_id', 'created_at'],
                'idx_photos_folder_created'
            );

        });

        Schema::table('folders', function (Blueprint $table) {

            // For faster folder search inside company
            $table->index(
                ['company_id', 'name'],
                'idx_folders_company_name'
            );

            $table->index(
                ['parent_id', 'created_at'],
                'idx_folders_parent_created'
            );

            $table->index(
                ['user_id', 'name'],
                'idx_folders_user_name'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {

            $table->dropIndex('idx_photos_company_user_type');
            $table->dropIndex('idx_photos_folder_created');

        });

        Schema::table('folders', function (Blueprint $table) {

            $table->dropIndex('idx_folders_company_name');
            $table->dropIndex('idx_folders_parent_created');
            $table->dropIndex('idx_folders_user_name');

        });
    }
};
