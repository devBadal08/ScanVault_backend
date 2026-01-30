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
        Schema::table('folders', function (Blueprint $table) {
            $table->index(['company_id', 'user_id'], 'idx_folders_company_user');
            $table->index('parent_id', 'idx_folders_parent');
            $table->index('path', 'idx_folders_path');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->index('folder_id', 'idx_photos_folder');
            $table->index('company_id', 'idx_photos_company');
            $table->index('user_id', 'idx_photos_user');
            $table->index(['folder_id', 'company_id'], 'idx_photos_folder_company');
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'parent_id')) {
                $table->index('parent_id', 'idx_companies_parent');
            }
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->unique(['company_id', 'user_id'], 'idx_company_user_unique');
            $table->index('company_id', 'idx_company_user_company');
            $table->index('user_id', 'idx_company_user_user');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex('idx_folders_company_user');
            $table->dropIndex('idx_folders_parent');
            $table->dropIndex('idx_folders_path');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex('idx_photos_folder');
            $table->dropIndex('idx_photos_company');
            $table->dropIndex('idx_photos_user');
            $table->dropIndex('idx_photos_folder_company');
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'parent_id')) {
                $table->dropIndex('idx_companies_parent');
            }
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropUnique('idx_company_user_unique');
            $table->dropIndex('idx_company_user_company');
            $table->dropIndex('idx_company_user_user');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
        });
    }
};
