<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_oil_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('latin_name')->nullable();
            $table->string('certification')->nullable();
            $table->unsignedTinyInteger('comedogenic_rating')->nullable();
            $table->string('absorption', 16)->nullable();
            $table->json('use_areas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_oil_details');
    }
};
