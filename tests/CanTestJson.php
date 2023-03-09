<?php

namespace Tests;

use Illuminate\Support\Facades\File;

trait CanTestJson
{
    /**
     * @param array<string, string> $json
     */
    protected function assertFileContainsJson(string $path, array $json): void
    {
        $content = File::get($path);
        $jsonContent = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($jsonContent));

        foreach ($json as $key => $value) {
            $this->assertArrayHasKey($key, $jsonContent);
            $this->assertEquals($value, $jsonContent[$key]);
        }
    }

    /**
     * @param array<string, string> $json
     */
    protected function assertFileContainsExactJson(string $path, array $json): void
    {
        $content = File::get($path);
        $jsonContent = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($jsonContent));

        $jsonContentKeys = collect($jsonContent)->keys();
        $jsonContentValues = collect($jsonContent)->values();

        $jsonKeys = collect($json)->keys();
        $jsonValues = collect($json)->values();

        $this->assertCount(collect($jsonKeys)->count(), $jsonContentKeys);
        $this->assertCount(collect($jsonValues)->count(), $jsonContentValues);

        foreach ($jsonKeys as $index => $value) {
            $this->assertEquals($value, $jsonContentKeys[$index]);
        }

        foreach ($jsonValues as $index => $value) {
            $this->assertEquals($value, $jsonContentValues[$index]);
        }
    }

    /**
     * @param array<string, string> $json
     */
    protected function assertFileDoesntContainJson(string $path, array $json): void
    {
        $content = File::get($path);
        $jsonContent = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($jsonContent));

        foreach ($json as $key => $value) {
            $this->assertArrayNotHasKey($key, $jsonContent);

            if (array_key_exists($key, $jsonContent)) {
                $this->assertNotEquals($value, $jsonContent[$key]);
            }
        }
    }
}
