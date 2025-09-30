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
        Schema::create('folder_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_folder_id');
            $table->unsignedBigInteger('target_folder_id');
            $table->enum('link_type', ['partial', 'full']); // partial = still available, full = hide from dropdown
            $table->timestamps();

            $table->foreign('source_folder_id')->references('id')->on('folders')->onDelete('cascade');
            $table->foreign('target_folder_id')->references('id')->on('folders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_links');
    }
};
