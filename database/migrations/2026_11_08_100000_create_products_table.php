<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            // Not unique on DB level: soft-deleted products may keep their barcode.
            // Uniqueness among active products is enforced by validation.
            $table->string('barcode', 64)->nullable()->index();
            $table->string('category', 32)->index();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('initial_quantity', 10, 2)->default(0);
            $table->string('unit', 10)->default('ml');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
