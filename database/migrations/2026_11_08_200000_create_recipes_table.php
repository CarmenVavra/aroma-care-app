<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('purpose');
            $table->string('category', 32)->index();
            $table->string('method', 32);
            $table->string('yield', 32);
            $table->json('steps');
            $table->text('safety_note')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');
            $table->string('category', 32);
            $table->decimal('amount', 8, 2);
            $table->string('unit', 16);
            // Lower-case search terms (German and botanical name). Empty = any product of the category.
            $table->json('match_terms');
            $table->boolean('optional')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
