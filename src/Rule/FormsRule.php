<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 11.1 : chaque champ de formulaire a une étiquette.
 *
 * Étiquettes reconnues : <label for="…">, <label> englobant le champ, aria-label,
 * aria-labelledby (vers un identifiant qui existe) et, à défaut, title.
 *
 * Règle en observation : elle donne un avertissement.
 */
final readonly class FormsRule implements Rule
{
    /** Types d'<input> qui ne sont pas des champs à étiqueter. */
    private const WITHOUT_LABEL = ['hidden', 'submit', 'reset', 'button', 'image'];

    public function check(Page $page): iterable
    {
        $ids = [];
        $labelled = [];
        foreach ($page->all() as $element) {
            if ('' !== $element->getAttribute('id')) {
                $ids[$element->getAttribute('id')] = true;
            }
            if ('label' === $element->tagName && '' !== $element->getAttribute('for') && '' !== Page::text($element)) {
                $labelled[$element->getAttribute('for')] = true;
            }
        }

        foreach ($page->elements('input', 'select', 'textarea') as $field) {
            $type = strtolower($field->getAttribute('type'));
            if ('input' === $field->tagName && \in_array($type, self::WITHOUT_LABEL, true)) {
                continue;
            }
            if ($this->hasLabel($field, $ids, $labelled)) {
                continue;
            }
            $name = $field->getAttribute('name') ?: $field->getAttribute('id') ?: '?';
            yield $page->issue('formulaires', '11.1', sprintf('champ <%s> sans étiquette : %s', $field->tagName, $name), Severity::Warning);
        }
    }

    /**
     * @param array<string, true> $ids
     * @param array<string, true> $labelled identifiants visés par un <label for>
     */
    private function hasLabel(\DOMElement $field, array $ids, array $labelled): bool
    {
        if ('' !== trim($field->getAttribute('aria-label')) || '' !== trim($field->getAttribute('title'))) {
            return true;
        }
        if (isset($labelled[$field->getAttribute('id')]) && '' !== $field->getAttribute('id')) {
            return true;
        }
        foreach (preg_split('/\s+/', trim($field->getAttribute('aria-labelledby')), -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $id) {
            if (isset($ids[$id])) {
                return true;
            }
        }
        for ($node = $field->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if ('label' === $node->tagName) {
                return '' !== Page::text($node);
            }
        }

        return false;
    }
}
