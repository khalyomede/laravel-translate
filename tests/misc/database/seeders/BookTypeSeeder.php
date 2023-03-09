<?php

namespace Tests\Misc\Database\Seeders;

use Illuminate\Database\Seeder;
use Tests\Misc\App\Models\BookType;

final class BookTypeSeeder extends Seeder
{
    public function run(): void
    {
        BookType::insert([
            [
                "id" => BookType::DRAMA,
                "name" => "Drama",
            ],
            [
                "id" => BookType::FANTASTIC,
                "name" => "Fantastic",
            ],
            [
                "id" => BookType::ADVENTURE,
                "name" => "Adventure",
            ],
        ]);
    }
}
