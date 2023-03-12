<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Khalyomede\LaravelTranslate\Commands\Translate;
use Tests\Misc\App\Models\BookType;
use Tests\Misc\Database\Seeders\BookTypeSeeder;
use Tests\TestCase;

final class TranslateTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", "{}");
    }

    public function testReturnsNonZeroCodeIfUsingDryRunFlagAndItFoundMissingTranslationKeysFromBladeFiles(): void
    {
        $this->assertTrue(true);
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/book",
                ],
            ],
        ]);

        $this->artisan(Translate::class, [
            "--dry-run" => true,
        ])
            ->assertExitCode(1);
    }

    public function testReturnsNonZeroCodeIfUsingDryRunFlagAndItFoundMissingTranslationKeysFromPhpFiles(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                ],
            ],
        ]);

        $this->artisan(Translate::class, [
            "--dry-run" => true,
        ])
            ->assertExitCode(1);
    }

    public function testCanFindMissingKeysFromBladeFiles(): void
    {
        $this->assertTrue(true);

        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/book",
                ]
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "List of books" => "",
            "Welcome to the list of books." => "",
            "This list shows an excerpt of each books." => "",
            ":count books displayed." => "",
            ":count authors found." => "",
        ]);
    }

    public function testCanFindMissingKeysFromPhpFiles(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
        ]);
    }

    public function testCanRemoveTranslationKeysNotFound(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["Book copied." => ""], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "remove_missing_keys" => true,
                "include" => [
                    "tests/misc/app",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
        ]);

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book copied." => "",
        ]);
    }

    public function testDoesNotRemoveMissingKeysIfConfigIsDisabled(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["Book copied." => ""], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "remove_missing_keys" => false,
                "include" => [
                    "tests/misc/app",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book copied." => "",
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
        ]);
    }

    public function testSortAllTranslationKeysWhenNewKeysHaveBeenFoundOnPhpAndBladeFilesAndSortKeyOptionisEnabled(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "sort_keys" => true,
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views/book",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            ":count authors found." => "",
            ":count books displayed." => "",
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
            "List of books" => "",
            "This list shows an excerpt of each books." => "",
            "Welcome to the list of books." => "",
        ]);
    }

    public function testDoesNotSortAllTranslationsKeysWhenNewKeysHaveBeenFoundOnPhpAndBladeFileAndSortKeyOptionIsDisabled(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "sort_keys" => false,
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views/book",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
            "List of books" => "",
            "Welcome to the list of books." => "",
            "This list shows an excerpt of each books." => "",
            ":count books displayed." => "",
            ":count authors found." => "",
        ]);
    }

    public function testCanAddNewKeyFoundFromModelsData(): void
    {
        $this->seed(BookTypeSeeder::class);

        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [],
                "models" => [
                    BookType::class => "name",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Drama" => "",
            "Fantastic" => "",
            "Adventure" => "",
        ]);
    }

    public function testCanRemoveUnusedKeysWhenNewKeysFoundFromModelsData(): void
    {
        $this->seed(BookTypeSeeder::class);

        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["Book copied." => ""], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "remove_missing_keys" => true,
                "include" => [],
                "models" => [
                    BookType::class => "name",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Drama" => "",
            "Fantastic" => "",
            "Adventure" => "",
        ]);
    }

    public function testKeepsKeysUnusedWhenFindingKeysFromModelsData(): void
    {
        $this->seed(BookTypeSeeder::class);

        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["Book copied." => ""], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "remove_missing_keys" => false,
                "include" => [],
                "models" => [
                    BookType::class => "name",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book copied." => "",
            "Drama" => "",
            "Fantastic" => "",
            "Adventure" => "",
        ]);
    }

    public function testCanSortKeysWhenNewKeysFoundFromModelsData(): void
    {
        $this->seed(BookTypeSeeder::class);

        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "sort_keys" => true,
                "include" => [],
                "models" => [
                    BookType::class => "name",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsExactJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Adventure" => "",
            "Drama" => "",
            "Fantastic" => "",
        ]);
    }

    public function testDontPullKeysFromCommentOfBladeFile(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/auth",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Email" => "",
            "Email Password Reset Link" => "",
        ]);
    }

    public function testItIgnoresKeysWithDotsAndUnderscores(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/user",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "user.index.title" => "",
        ]);
    }

    public function testItFormatsTranslationFileWithReturnLinesAndFourSpacesIndentation(): void
    {
        $this->markTestIncomplete();
    }
}
