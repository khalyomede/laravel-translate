<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
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

    public function testReturnsZeroCodeIfNoNewKeysHaveBeenFound(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode([
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
            "Enter your informations" => "",
            "Home" => "",
            "Password reset" => "",
            "Email address" => "",
            "Enter your email address below to get a new password." => "",
            ":count authors found." => "",
            "List of books" => "",
            "Welcome to the list of books." => "",
            "This list shows an excerpt of each books." => "",
            ":count books displayed." => "",
            "Page of books" => "",
            "Premium" => "",
            "Free" => "",
            "Standard" => "",
            "test" => "",
            "test 2" => "",
            "test 3" => "",
            "Unable to perform anti-bot validation." => "",
            "New journey" => "",
            "Created :date" => "",
        ], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class, [
            "--dry-run" => true,
        ])
            ->assertSuccessful();
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
            "Page of books" => "",
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
            ":count authors found." => "",
            "List of books" => "",
            "Welcome to the list of books." => "",
            "This list shows an excerpt of each books." => "",
            ":count books displayed." => "",
            "Page of books" => "",
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
            "Drama" => "",
            "Fantastic" => "",
            "Adventure" => "",
            "Book copied." => "",
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
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $expected = File::get(__DIR__ . "/../../misc/resources/lang/fr.json");

        $this->assertEquals(1, preg_match('/^{\n\s{4}".*\n}$/ms', $expected));
    }

    public function testDoesntErasePreviouslyFilledKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["List of books" => "Liste des livres"], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "List of books" => "Liste des livres",
        ]);
    }

    public function testItDoesntAddNewKeysInDuplicate(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainDuplicatedKeys(__DIR__ . "/../../misc/resources/lang/fr.json");
    }

    public function testDoesntEncodeUnicodeCharactersWhenFindingNewKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode(["Unable to perform anti-bot validation." => "Impossible de procéder à la vérification anti-robot."], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views/register",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertTrue(str_contains(File::get(__DIR__ . "/../../misc/resources/lang/fr.json"), "Impossible de procéder à la vérification anti-robot."));
    }

    public function testDisplaysHowManyNewKeysHaveBeenAddedToFile(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful()
            ->expectsOutputToContain("Added 23 new key(s) on each lang files.");
    }

    public function testDisplayOnlyNewKeysAddedToFileWithoutTakingIntoAccountCurrentExistingKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode([
            "Book saved." => "",
            "Book updated." => "",
            "Deleted :count books." => "",
            "Enter your informations" => "",
            "Home" => "",
            "Password reset" => "",
            "Email address" => "",
            "Enter your email address below to get a new password." => "",
            ":count authors found." => "",
            "List of books" => "",
            "Welcome to the list of books." => "",
            "This list shows an excerpt of each books." => "",
            ":count books displayed." => "",
            "Page of books" => "",
            "Premium" => "",
            "Free" => "",
            "Standard" => "",
            "test" => "",
            "test 2" => "",
            "test 3" => "",
            "New journey" => "",
            "Created :date" => "",
        ], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful()
            ->expectsOutputToContain("Added 1 new key(s) on each lang files.");
    }

    public function testDisplayNumberOfKeysInConditionalIfNewKeysHaveBeenFoundWhenUsingDryRunOption(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "remove_missing_keys" => true,
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $command = $this->artisan(Translate::class, [
            "--dry-run" => true,
        ]);

        assert($command instanceof PendingCommand);

        $command->expectsOutputToContain("23 key(s) would have been added (using --dry-run) on each lang files.");
    }

    public function testDoesNotAddShortKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app/Rules",
                    "tests/misc/resources/views/contact",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "contact.create.page_title" => "",
            "validation.google_recaptcha_v3" => "",
            "pagination.next" => "",
        ]);
    }

    public function testDoNotEraseExistingKeysWhenUsingRemoveMissingKeysOption(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        $content = json_encode([
            "Something" => "Quelque chose",
            "Book saved." => "Livre sauvegardé.",
            "Book updated." => "Livre mis à jour.",
            "Deleted :count books." => ":count livres supprimés.",
            "List of books" => "Liste des livres",
            "Welcome to the list of books." => "Bienvenue sur la liste des livres.",
            "This list shows an excerpt of each books." => "Cette lise montre un extrait de chaque livres.",
            ":count books displayed." => ":count livres affichés.",
            ":count authors found." => ":count auteur trouvés.",
            "Unable to perform anti-bot validation." => "Impossible de procéder à la vérification anti-robot.",
        ], flags: JSON_PRETTY_PRINT);

        assert(is_string($content));

        File::put(__DIR__ . "/../../misc/resources/lang/fr.json", $content);

        config([
            "translate" => [
                "remove_missing_keys" => true,
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Book saved." => "Livre sauvegardé.",
            "Book updated." => "Livre mis à jour.",
            "Deleted :count books." => ":count livres supprimés.",
            "List of books" => "Liste des livres",
            "Welcome to the list of books." => "Bienvenue sur la liste des livres.",
            "This list shows an excerpt of each books." => "Cette lise montre un extrait de chaque livres.",
            ":count books displayed." => ":count livres affichés.",
            ":count authors found." => ":count auteur trouvés.",
            "Unable to perform anti-bot validation." => "Impossible de procéder à la vérification anti-robot.",
        ]);
    }

    public function testIgnoresEmptyNewKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "remove_missing_keys" => true,
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views/subscribe",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "" => "",
        ]);
    }

    public function testCanPullNewKeysFromBladeBindedValues(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/account",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Enter your informations" => "",
        ]);
    }

    public function testCanPullNewKeysFromBladeComponentInnerContent(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/plan",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Free" => "",
            "Standard" => "",
            "Premium" => "",
        ]);
    }

    public function testCanPullNewTranslationKeysFromSectionDirective(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Page of books" => "",
        ]);
    }

    public function testDoesNotPullTranslationKeyCorrespondingToPhpCodeKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/password-forgotten",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            '$user->type' => "",
            'ucfirst($user->type)' => "",
        ]);
    }

    public function testCanIgnoreKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app/Http/Controllers",
                ],
                "ignore_keys" => [
                    "Book saved.",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileDoesntContainJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            'Book saved.' => "",
        ]);
    }

    public function testDisplaysProgressBarWhenFetchingTranslationKeys(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful()
            ->expectsOutputToContain("14/14");
    }

    public function testDisplaysElapsedTimeAndMaxMemoryConsumption(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/app",
                    "tests/misc/resources/views",
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful()
            ->expectsOutputToContain("Time:")
            ->expectsOutputToContain("Max memory:");
    }

    public function testCanAddKeysFromTranslatables(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [],
                "translatables" => [
                    fn (): Collection => collect(["Red", "Blue", "Yellow"]),
                ]
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Red" => "",
            "Blue" => "",
            "Yellow" => "",
        ]);
    }

    public function testCanTranslateTextStartingWithNew(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/about-us"
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "New journey" => "",
        ]);
    }

    public function testCanTranslateKeysContainingColon(): void
    {
        $this->app?->useLangPath(__DIR__ . "/../../misc/resources/lang");

        config([
            "translate" => [
                "langs" => [
                    "fr",
                ],
                "include" => [
                    "tests/misc/resources/views/device"
                ],
            ],
        ]);

        $this->artisan(Translate::class)
            ->assertSuccessful();

        $this->assertFileContainsJson(__DIR__ . "/../../misc/resources/lang/fr.json", [
            "Created :date" => "",
        ]);
    }
}
