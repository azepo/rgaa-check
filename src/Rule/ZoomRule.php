<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 10.4 : la page ne doit pas empêcher l'agrandissement du texte à 200 %.
 *
 * Seul le blocage par la balise <meta name="viewport"> est détecté. Que la page reste lisible
 * une fois agrandie se vérifie dans un navigateur.
 *
 * Règle en observation : elle donne un avertissement.
 */
final readonly class ZoomRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('meta') as $meta) {
            if ('viewport' !== strtolower(trim($meta->getAttribute('name')))) {
                continue;
            }
            $content = strtolower($meta->getAttribute('content'));
            if (1 === preg_match('/user-scalable\s*=\s*(no|0)\b/', $content)) {
                yield $page->issue('zoom', '10.4', 'la balise viewport interdit le zoom (user-scalable=no)', Severity::Warning);
            }
            if (1 === preg_match('/maximum-scale\s*=\s*([0-9.]+)/', $content, $found) && (float) $found[1] < 2.0) {
                yield $page->issue('zoom', '10.4', sprintf('la balise viewport limite le zoom à %s (maximum-scale) : il doit pouvoir atteindre 200 %%', $found[1]), Severity::Warning);
            }
        }
    }
}
