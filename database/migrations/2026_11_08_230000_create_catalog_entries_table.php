<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared code catalogue: product data per barcode / QR code, learned from
 * saved products. Deliberately without any reference to a user account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_entries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 512)->unique();
            $table->string('name', 120);
            $table->string('brand', 120)->nullable();
            $table->string('category', 32)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 16)->nullable();
            $table->string('latin_name', 160)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_entries');
    }
};
