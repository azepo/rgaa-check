<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

/**
 * RGAA 9.1 : un seul titre principal, pas de niveau sauté, pas de titre vide.
 */
final readonly class HeadingsRule implements Rule
{
    public function check(Page $page): iterable
    {
        $h1 = 0;
        $previous = 0;
        foreach ($page->all() as $element) {
            if (1 !== preg_match('/^h([1-6])$/', $element->tagName, $found)) {
                continue;
            }
            $level = (int) $found[1];
            $text = Page::text($element);

            if (1 === $level) {
                ++$h1;
            }
            if ('' === $text) {
                yield $page->issue('titres', '9.1', sprintf('titre <h%d> vide', $level));
            }
            if ($previous > 0 && $level > $previous + 1) {
                yield $page->issue('titres', '9.1', sprintf('niveau de titre sauté : de h%d à h%d (« %s »)', $previous, $level, mb_substr($text, 0, 60)));
            }
            $previous = $level;
        }

        if (1 !== $h1) {
            yield $page->issue('titres', '9.1', sprintf('la page doit avoir exactement un <h1> (%d trouvé(s))', $h1));
        }
    }
}
