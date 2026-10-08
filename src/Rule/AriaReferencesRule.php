<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 7.1 et 11.1 : une référence vers un autre élément doit viser un identifiant qui existe.
 *
 * Un aria-labelledby, un aria-describedby ou un <label for> qui pointe dans le vide ne donne
 * aucun nom à l'élément, sans que rien ne le montre à l'écran.
 *
 * Règle en observation : elle donne un avertissement.
 */
final readonly class AriaReferencesRule implements Rule
{
    public function check(Page $page): iterable
    {
        $ids = [];
        foreach ($page->all() as $element) {
            if ('' !== $element->getAttribute('id')) {
                $ids[$element->getAttribute('id')] = true;
            }
        }

        foreach ($page->all() as $element) {
            foreach (['aria-labelledby', 'aria-describedby'] as $attribute) {
                foreach (preg_split('/\s+/', trim($element->getAttribute($attribute)), -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $id) {
                    if (!isset($ids[$id])) {
                        yield $page->issue('aria', '7.1', sprintf('%s de <%s> vise l\'identifiant « %s », qui n\'existe pas', $attribute, $element->tagName, $id), Severity::Warning);
                    }
                }
            }
            $for = $element->getAttribute('for');
            if ('label' === $element->tagName && '' !== $for && !isset($ids[$for])) {
                yield $page->issue('aria', '11.1', sprintf('<label for="%s"> vise un identifiant qui n\'existe pas (« %s »)', $for, mb_substr(Page::text($element), 0, 50)), Severity::Warning);
            }
        }
    }
}
