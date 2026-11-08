<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('essential_oil_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('latin_name')->nullable();
            $table->json('effects_mental')->nullable();
            $table->json('effects_physical')->nullable();
            $table->json('usage_types')->nullable();
            $table->string('dilution_hint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essential_oil_details');
    }
};
