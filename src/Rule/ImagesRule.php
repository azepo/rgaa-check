<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 1.1, 1.2 et 1.3 : chaque image a un texte alternatif, et il dit quelque chose.
 */
final readonly class ImagesRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('img') as $image) {
            $src = $image->getAttribute('src');
            if (!$image->hasAttribute('alt')) {
                yield $page->issue('images', '1.1', sprintf('image sans attribut alt : %s', $src));
                continue;
            }
            $alt = trim($image->getAttribute('alt'));
            if ('' === $alt) {
                // Un alt vide est correct pour une image purement décorative : à confirmer par un humain.
                yield $page->issue('images', '1.2', sprintf('image avec un alt vide : à garder seulement si elle est décorative (%s)', $src), Severity::Warning);
            } elseif (1 === preg_match('/^(image|figure|listing|img|photo|picture|schéma|schema)\s*\d*\.?$/iu', $alt)) {
                yield $page->issue('images', '1.3', sprintf('texte alternatif sans information « %s » : %s', $alt, $src));
            }
        }
    }
}
