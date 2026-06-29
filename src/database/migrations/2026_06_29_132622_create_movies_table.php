<?php

declare(strict_types=1);

use App\Enums\MovieType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->unique();
            $table->string('title');
            $table->enum('type', MovieType::values())->default(MovieType::Movie->value);
            $table->string('year', 16)->default('');
            $table->string('poster_url', 512)->default('');
            $table->text('plot')->default('');
            $table->string('director')->default('');
            $table->text('actors')->default('');
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
