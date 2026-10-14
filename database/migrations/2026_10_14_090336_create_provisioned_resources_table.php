<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisioned_resources', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('initial_status')->default('busy');
            $table->json('payload');
            $table->timestamps();

            $table->index(['type', 'server_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioned_resources');
    }
};
