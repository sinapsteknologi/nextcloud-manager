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
    	if( !Schema::hasTable('nextcloud_files')) {
	        Schema::create('nextcloud_files', function (Blueprint $table) {
	            $table->id();
	            $table->string('name');
	            $table->string('path');
	            $table->string('url')->nullable();
	            $table->string('share_id')->nullable();
	            $table->timestamps();
	        });
    	}
    }

    public function down(): void
    {
    	if(Schema::hasTable('nextcloud_files')) {
        	Schema::dropIfExists('files');
    	}
    }
};
