<?php

namespace App\Modules\Assistant\Prompt;

use RuntimeException;

/**
 * Loads the wording the model is told from the JSON files in Templates/, so a
 * prompt can be reworded without touching PHP. Placeholders are written as
 * {name} and filled in by render().
 */
class PromptLibrary
{
    /** @var array<string, array<string, mixed>> */
    private array $loaded = [];

    public function __construct(private readonly string $directory = __DIR__.'/Templates') {}

    /**
     * The decoded contents of Templates/{name}.json.
     *
     * @return array<string, mixed>
     */
    public function load(string $name): array
    {
        return $this->loaded[$name] ??= $this->read($name);
    }

    /**
     * Fills the {placeholders} of a template with the given values.
     *
     * @param  array<string, string>  $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($template, $replacements);
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $name): array
    {
        $path = "{$this->directory}/{$name}.json";
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("Prompt template [{$name}] was not found at {$path}.");
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Prompt template [{$name}] is not valid JSON: ".json_last_error_msg());
        }

        return $decoded;
    }
}
