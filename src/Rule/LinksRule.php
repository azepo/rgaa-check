<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

/**
 * RGAA 6.1, 6.2 et 13.2 : chaque lien a un intitulé explicite ; un lien qui ouvre un nouvel onglet l'annonce.
 */
final readonly class LinksRule implements Rule
{
    private const VAGUE_LABELS = ['ici', 'cliquez ici', 'cliquer ici', 'lien', 'ce lien', 'en savoir plus', 'lire la suite', 'here', 'click here', 'read more', 'more'];

    public function check(Page $page): iterable
    {
        foreach ($page->elements('a') as $link) {
            $href = $link->getAttribute('href');
            $text = Page::text($link);
            $label = trim($link->getAttribute('aria-label'));
            $alts = [];
            foreach ($link->getElementsByTagName('img') as $image) {
                $alts[] = trim($image->getAttribute('alt'));
            }
            $name = '' !== $label ? $label : trim($text.' '.implode(' ', $alts));

            if (!$link->hasAttribute('href')) {
                yield $page->issue('liens', '6.2', sprintf('lien sans href, inutilisable au clavier : « %s »', mb_substr($text, 0, 50)));
            }
            if ('' === $name) {
                yield $page->issue('liens', '6.2', sprintf('lien sans intitulé : %s', $href));
                continue;
            }
            if (\in_array(mb_strtolower($text), self::VAGUE_LABELS, true)) {
                yield $page->issue('liens', '6.1', sprintf('intitulé de lien non explicite : « %s » (%s)', $text, $href));
            }
            if ('' !== $label && '' !== $text && !str_contains(mb_strtolower($label), mb_strtolower($text))) {
                yield $page->issue('liens', '6.1', sprintf('l\'aria-label « %s » ne reprend pas le texte visible « %s »', mb_substr($label, 0, 60), mb_substr($text, 0, 50)));
            }
            if ('_blank' === $link->getAttribute('target')
                && 1 !== preg_match('/nouvel(le)? (fenêtre|onglet)|new (window|tab)/iu', $label.' '.$link->getAttribute('title').' '.$text)) {
                yield $page->issue('liens', '13.2', sprintf('lien ouvert dans un nouvel onglet sans l\'annoncer : « %s »', mb_substr($text, 0, 50)));
            }
        }
    }
}
