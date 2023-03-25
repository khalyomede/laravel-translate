<?php

namespace Khalyomede\LaravelTranslate\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\AbstractNode;
use Stillat\BladeParser\Nodes\ArgumentGroupNode;
use Stillat\BladeParser\Nodes\CommentNode;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Nodes\EchoNode;
use Stillat\BladeParser\Parser\DocumentParser;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\SplFileInfo;

final class Translate extends Command
{
    /**
     * @var Collection<int, string>
     */
    public static Collection $phpKeys;

    protected $signature = "translate {--d|dry-run : Only return a non-zero code if keys are missing.}";

    protected $description = "Add missing translation keys for the lang of your choice.";

    // -- next
    public function handle(): int
    {
        self::$phpKeys = collect();

        $this->info("Fetching langs...");

        // Get all langs
        $langs = self::getLangs();

        $this->info("Fetching files to parse...");

        // Get all files
        $filePaths = self::getFilePaths();

        $this->info("Fetching translation keys...");

        $bar = $this->output->createProgressBar($filePaths->count());

        // Get all translation keys
        $foundKeys = self::getAllTranslationKeys($bar, $filePaths);

        $bar->finish();

        $this->info("");

        // Keep track of added keys for end output
        $addedKeys = collect();

        $this->info("Found {$foundKeys->count()} keys across {$filePaths->count()} files for {$langs->count()} lang(s).");

        $bar = $this->output->createProgressBar($langs->count());

        foreach ($langs as $lang) {
            // Get all keys of lang file
            $currentKeys = self::getLangKeys($lang);

            // Figure out which new keys will be added
            $addedKeys = self::getKeyThatWillBeAdded($currentKeys, $foundKeys);

            // Get final keys
            $newKeys = self::shouldRemoveMissingKeys()
                ? self::removeMissingKeys($currentKeys, $foundKeys)
                : $foundKeys->merge($currentKeys);

            // Sort keys if needed
            $newKeys = self::shouldSortKeys()
                ? self::sortKeys($newKeys)
                : $newKeys;

            // Write on file if needed
            if ($this->shouldWriteOnFile()) {
                self::writeOnFile($lang, $newKeys);
            }

            $bar->advance();
        }

        $bar->finish();

        $this->line("");

        // Display number of added keys (without taking into account current keys)
        if ($this->shouldWriteOnFile()) {
            $this->info("Added {$addedKeys->count()} new key(s) on each lang files.");
        } else {
            $this->info("{$addedKeys->count()} key(s) would have been added (using --dry-run) on each lang files.");
        }

        // In dry-run mode, return non-zero code if some missing keys have been found
        return !$this->shouldWriteOnFile() && $addedKeys->isNotEmpty()
            ? 1
            : 0;
    }

    /**
     * @return Collection<int, string>
     */
    private static function getFilePaths(): Collection
    {
        $filePaths = collect([]);
        $include = config("translate.include");

        assert(is_array($include));

        collect($include)
            ->each(function (string $path) use ($filePaths): void {
                if (!is_dir($path)) {
                    throw new InvalidArgumentException("$path is not a directory");
                }

                collect(File::allFiles($path))
                    ->map(fn (SplFileInfo $fileInfo): string => $fileInfo->getPathname())
                    ->filter(fn (string $path): bool => self::isBladeFile($path) || self::isPhpFile($path))
                    ->each(fn (string $filePath): Collection => $filePaths->push($filePath));
            });

        return $filePaths;
    }

    /**
     * @return Collection<int, string>
     */
    private static function getLangs(): Collection
    {
        $langs = config("translate.langs");

        assert(is_array($langs));

        return collect($langs);
    }

    private static function shouldRemoveMissingKeys(): bool
    {
        return config("translate.remove_missing_keys") === true;
    }

    private function shouldWriteOnFile(): bool
    {
        return $this->option("dry-run") !== true;
    }

    private function shouldSortKeys(): bool
    {
        return config("translate.sort_keys") === true;
    }

    /**
     * @param Collection<string, string> $items
     *
     * @return Collection<string, string>
     */
    private function sortKeys(Collection $items): Collection
    {
        return $items->sortKeys();
    }

    /**
     * @param Collection<string, string> $items
     */
    private static function writeOnFile(string $lang, Collection $items): void
    {
        File::put(self::langFilePath($lang), $items->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private static function langFilePath(string $lang): string
    {
        return lang_path("$lang.json");
    }

    /**
     * @return Collection<string, string>
     */
    private function getLangKeys(string $lang): Collection
    {
        $content = File::get(self::langFilePath($lang));
        $keysAndValues = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($keysAndValues));

        return collect($keysAndValues);
    }

    /**
     * @param Collection<int, string> $filePaths
     *
     * @return Collection<string, string>
     */
    private static function getAllTranslationKeys(ProgressBar $bar, Collection $filePaths): Collection
    {
        $phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $phpTraverser = new NodeTraverser();

        $phpTraverser->addVisitor(new class () extends NodeVisitorAbstract {
            public function leaveNode(Node $node)
            {
                if ($node instanceof FuncCall && $node->name instanceof Name && collect(["__", "trans", "trans_choice"])->contains($node->name->parts[0])) {
                    $firstArgument = collect($node->getArgs())
                        ->filter(fn (Arg $arg): bool => $arg->value instanceof String_)
                        ->first();

                    if (!($firstArgument instanceof Arg)) {
                        return null;
                    }

                    /** @phpstan-ignore-next-line Access to an undefined property PhpParser\Node\Expr::$value. */
                    Translate::$phpKeys->push($firstArgument->value->value);
                }
            }
        });

        $translationKeys = collect();

        foreach ($filePaths as $filePath) {
            $fileContent = File::get($filePath);

            if (self::isBladeFile($filePath)) {
                $document = Document::fromText($fileContent);

                $langs = $document->findDirectivesByName("lang");
                $choices = $document->findDirectivesByName("choice");
                $components = $document->allOfType(ComponentNode::class);
                $echos = $document->allOfType(EchoNode::class);

                /**
                 * @todo To refactor once this issue is solved: https://github.com/Stillat/blade-parser/issues/15
                 */
                $sections = $document->findDirectivesByName("section");

                // langs & choices
                $directives = $choices->merge($langs);

                foreach ($directives as $directive) {
                    assert($directive instanceof DirectiveNode);

                    $firstArgument = $directive
                        ->arguments
                        ?->getValues()
                        ->map(fn (mixed $value): string => strval(preg_replace("/^(\"|')/", "", strval($value))))
                        ->map(fn (mixed $value): string => strval(preg_replace("/(\"|')$/", "", strval($value))))
                        ->first() ?? "";

                    if (self::langKeyIsShortKey($firstArgument)) {
                        continue;
                    }

                    $translationKeys->push($firstArgument);
                }

                // Components
                foreach ($components as $component) {
                    assert($component instanceof ComponentNode);

                    $component
                        ->getParameters()
                        ->filter(fn (mixed $parameter): bool => $parameter instanceof ParameterNode && $parameter->type === ParameterType::DynamicVariable)
                        ->map(fn (ParameterNode $p): string => $p->value)
                        ->each(function ($nodeContent) use ($phpParser, $translationKeys): void {
                            $functions = ["__", "trans", "trans_choice"];

                            foreach ($functions as $function) {
                                if (preg_match("/" . $function . "\s*\(\s*(\"|')/", $nodeContent) === 1) {
                                    $code = "<?php " . preg_replace('/^({{|{!!)|(!!}|}})$/', "", $nodeContent);

                                    $code = preg_match("/\s*;\s*^/", $code) === 1
                                        ? $code
                                        : $code . ";";

                                    $ast = $phpParser->parse($code);

                                    assert(is_array($ast));

                                    $expression = $ast[0];

                                    if ($expression instanceof Expression) {
                                        $functionCall = $expression->expr;

                                        if ($functionCall instanceof FuncCall) {
                                            $arguments = $functionCall->getArgs();
                                            $argument = $arguments[0];

                                            if ($argument->value instanceof String_) {
                                                $key = $argument->value->value;

                                                if (self::langKeyIsShortKey($key)) {
                                                    continue;
                                                }

                                                $translationKeys->push($key);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                }

                // Echos
                $echos
                    ->map(fn (mixed $echo): string => $echo instanceof EchoNode ? $echo->innerContent : "")
                    ->each(function (mixed $nodeContent) use ($phpParser, $translationKeys): void {
                        assert(is_string($nodeContent));

                        $functions = ["__", "trans", "trans_choice"];

                        foreach ($functions as $function) {
                            if (preg_match("/" . $function . "\s*\(\s*(\"|')/", $nodeContent) === 1) {
                                $code = "<?php " . preg_replace('/^({{|{!!)|(!!}|}})$/', "", $nodeContent);

                                $code = preg_match("/\s*;\s*^/", $code) === 1
                                    ? $code
                                    : $code . ";";

                                $ast = $phpParser->parse($code);

                                assert(is_array($ast));

                                $expression = $ast[0];

                                if ($expression instanceof Expression) {
                                    $functionCall = $expression->expr;

                                    if ($functionCall instanceof FuncCall) {
                                        $arguments = $functionCall->getArgs();
                                        $argument = $arguments[0];

                                        if ($argument->value instanceof String_) {
                                            $key = $argument->value->value;

                                            if (self::langKeyIsShortKey($key)) {
                                                continue;
                                            }

                                            $translationKeys->push($key);
                                        }
                                    }
                                }
                            }
                        }
                    });

                // Sections
                $sections
                    ->map(fn (mixed $node): string => $node instanceof DirectiveNode ? strval($node->arguments?->getValues()[1] ?? "") : "")
                    ->filter(fn (mixed $nodeContent): bool => collect(["__", "trans", "trans_choice"])->filter(fn (string $function): bool => str_starts_with(ltrim(strval($nodeContent)), $function))->isNotEmpty())
                    ->each(function (mixed $nodeContent) use ($phpParser, $translationKeys): void {
                        $code = "<?php " . preg_replace('/^({{|{!!)|(!!}|}})$/', "", strval($nodeContent));

                        $code = preg_match("/\s*;\s*^/", $code) === 1
                            ? $code
                            : $code . ";";

                        $ast = $phpParser->parse($code);

                        assert(is_array($ast));

                        $expression = $ast[0];

                        if ($expression instanceof Expression) {
                            $functionCall = $expression->expr;

                            if ($functionCall instanceof FuncCall) {
                                $arguments = $functionCall->getArgs();
                                $argument = $arguments[0];

                                if ($argument->value instanceof String_) {
                                    $key = $argument->value->value;

                                    if (self::langKeyIsShortKey($key)) {
                                        return;
                                    }

                                    $translationKeys->push($key);
                                }
                            }
                        }
                    });
            } elseif (self::isPhpFile($filePath)) {
                $content = File::get($filePath);

                $ast = $phpParser->parse($content);

                assert(is_array($ast));

                $ast = $phpTraverser->traverse($ast);

                foreach (self::$phpKeys as $phpKey) {
                    if (self::langKeyIsShortKey($phpKey)) {
                        continue;
                    }

                    $translationKeys->push($phpKey);
                }
            }

            $bar->advance();
        }

        return $translationKeys
            ->concat(self::modelsTranslationKeys())
            ->filter(fn (string $key): bool => !empty($key))
            ->filter(function (string $key) use ($phpParser): bool {
                $ast = [];

                try {
                    $ast = $phpParser->parse("<?php $key;") ?? [];
                } catch (Exception) {
                    return true;
                }

                $expression = $ast[0];

                if (!($expression instanceof Expression)) {
                    return false;
                }

                if ($expression->expr instanceof ConstFetch) {
                    return true;
                }

                return false;
            })
            ->diff(self::keysToIgnore())
            ->flip()
            ->mapWithKeys(fn (int $key, string $value): array => [
                $value => "",
            ]);
    }

    /**
     * @return Collection<int, string>
     */
    private static function modelsTranslationKeys(): Collection
    {
        $models = config("translate.models");

        assert(is_array($models));

        /**
         * @var Collection<int, string>
         */
        $keys = collect($models)
            ->map(function (string $column, string $model): Collection {
                /**
                 * @var Collection<int, string>
                 *
                 * @phpstan-ignore-next-line Parameter #1 $callback of function call_user_func expects callable(): mixed, array{string, 'pluck'} given.
                 */
                $keys = call_user_func([$model, "pluck"], $column);

                return $keys;
            })
            ->flatten();

        return $keys;
    }

    private static function isPhpFile(string $path): bool
    {
        return !str_ends_with($path, ".blade.php") && str_ends_with($path, ".php");
    }

    private static function isBladeFile(string $path): bool
    {
        return str_ends_with($path, ".blade.php");
    }

    private static function langKeyIsShortKey(string $key): bool
    {
        $looksLikeShortKey = preg_match("/^\w[\w0-9_]+(\.[\w0-9_]+){1,}\w$/", $key) === 1;
        $doesntContainSpaces = preg_match("/\s+/", $key) !== 1;

        return $looksLikeShortKey && $doesntContainSpaces;
    }

    /**
     * @param Collection<string, string> $currentKeys
     * @param Collection<string, string> $newKeys
     *
     * @return Collection<string, string>
     */
    private static function getKeyThatWillBeAdded(Collection $currentKeys, Collection $newKeys): Collection
    {
        return $newKeys->diffKeys($currentKeys);
    }

    /**
     * @param Collection<string, string> $currentKeys
     * @param Collection<string, string> $newKeys
     *
     * @return Collection<string, string>
     */
    private static function removeMissingKeys(Collection $currentKeys, Collection $newKeys): Collection
    {
        return $newKeys->mapWithKeys(function (string $value, string $key) use ($currentKeys): array {
            if ($currentKeys->has($key)) {
                return [
                    $key => $currentKeys->get($key) ?? $value,
                ];
            }

            return [
                $key => $value,
            ];
        });
    }

    /**
     * @return Collection<int, string>
     */
    private static function keysToIgnore(): Collection
    {
        $keysToIgnore = config("translate.ignore_keys");

        assert(is_array($keysToIgnore));

        return collect($keysToIgnore);
    }
}
