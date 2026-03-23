<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('address')->nullable();

            // Presisi cukup aman untuk koordinat GPS
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->unsignedInteger('radius_meter')->default(100);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_locations');
    }
};
