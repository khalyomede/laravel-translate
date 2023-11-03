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
                "description" => "Intense human conflicts and emotions.",
            ],
            [
                "id" => BookType::FANTASTIC,
                "name" => "Fantastic",
                "description" => "Magical worlds and creatures.",
            ],
            [
                "id" => BookType::ADVENTURE,
                "name" => "Adventure",
                "description" => "Thrilling quests and daring journeys.",
            ],
        ]);
    }
}
