<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

use Masterminds\HTML5;

/**
 * Une page HTML analysée comme le ferait un navigateur.
 */
final readonly class Page
{
    /**
     * @param list<string> $parseErrors erreurs relevées par l'analyseur HTML5
     */
    private function __construct(
        public string $file,
        public string $source,
        public \DOMDocument $document,
        public array $parseErrors,
    ) {
    }

    public static function fromHtml(string $file, string $source): self
    {
        $parser = new HTML5(['disable_html_ns' => true]);
        $document = $parser->loadHTML($source);

        $errors = [];
        foreach ($parser->getErrors() as $error) {
            if (\is_string($error)) {
                $errors[] = $error;
            }
        }

        return new self($file, $source, $document, $errors);
    }

    /**
     * @return list<\DOMElement> éléments portant l'une des balises données
     */
    public function elements(string ...$tags): array
    {
        $elements = [];
        foreach ($tags as $tag) {
            foreach ($this->document->getElementsByTagName($tag) as $element) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * @return list<\DOMElement> tous les éléments, dans l'ordre du document
     */
    public function all(): array
    {
        $elements = [];
        foreach ($this->document->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }

        return $elements;
    }

    /** Texte d'un élément, espaces réduits. */
    public static function text(\DOMNode $node): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $node->textContent));
    }

    public function issue(string $rule, string $criterion, string $message, Severity $severity = Severity::Error): Issue
    {
        return new Issue($this->file, $rule, $criterion, $message, $severity);
    }
}
