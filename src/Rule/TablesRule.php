<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

/**
 * RGAA 5.4, 5.6 et 5.7 : un tableau de données a une légende et des en-têtes reliés à leurs cellules.
 *
 * Un tableau de mise en forme doit porter role="presentation" : il est alors ignoré.
 */
final readonly class TablesRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('table') as $table) {
            if (\in_array(strtolower($table->getAttribute('role')), ['presentation', 'none'], true)) {
                continue;
            }
            $captions = $table->getElementsByTagName('caption');
            if (0 === $captions->length || '' === Page::text($captions->item(0) ?? $table)) {
                yield $page->issue('tableaux', '5.4', 'tableau sans légende (<caption>)');
            }
            $headers = $table->getElementsByTagName('th');
            if (0 === $headers->length) {
                yield $page->issue('tableaux', '5.6', 'tableau sans cellule d\'en-tête (<th>)');
            }
            foreach ($headers as $header) {
                if ('' === $header->getAttribute('scope') && '' === $header->getAttribute('id')) {
                    yield $page->issue('tableaux', '5.7', sprintf('en-tête « %s » sans attribut scope', Page::text($header)));
                }
            }
        }
    }
}
