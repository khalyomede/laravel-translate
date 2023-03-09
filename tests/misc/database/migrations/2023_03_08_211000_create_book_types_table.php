<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create("book_types", function (Blueprint $table): void {
            $table->id();
            $table->string("name")->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("book_types");
    }
};
