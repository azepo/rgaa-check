<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 2.1 : chaque cadre (<iframe>, <frame>) a un titre.
 *
 * Règle en observation : elle donne un avertissement.
 */
final readonly class FramesRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('iframe', 'frame') as $frame) {
            if ('' === trim($frame->getAttribute('title'))) {
                yield $page->issue('cadres', '2.1', sprintf('cadre sans attribut title : %s', $frame->getAttribute('src')), Severity::Warning);
            }
        }
    }
}
