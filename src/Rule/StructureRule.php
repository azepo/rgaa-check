<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

/**
 * RGAA 8.1 à 8.6, 9.2, 10.1 et 12.7 : document valide, langue, titre de page, zones et lien d'évitement.
 */
final readonly class StructureRule implements Rule
{
    private const BLOCKS = [
        'address', 'article', 'aside', 'blockquote', 'details', 'div', 'dl', 'fieldset', 'figcaption', 'figure', 'footer',
        'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'ul',
    ];
    private const TEXT_CONTAINERS = ['p', 'span', 'em', 'strong', 'i', 'b', 'code', 'small', 'label'];

    public function check(Page $page): iterable
    {
        if (1 !== preg_match('/^\s*<!doctype html>/i', $page->source)) {
            yield $page->issue('structure', '8.1', 'la page doit commencer par <!doctype html>');
        }
        foreach ($page->parseErrors as $error) {
            // Un bloc dans un <p> ou une balise mal fermée apparaît ici.
            yield $page->issue('structure', '8.2', 'HTML invalide : '.(string) preg_replace('/^Line \d+, Col \d+: /', '', $error));
        }

        $html = $page->elements('html')[0] ?? null;
        if (null === $html || '' === trim($html->getAttribute('lang'))) {
            yield $page->issue('structure', '8.3', 'la langue de la page n\'est pas indiquée (<html lang="…">)');
        }

        $titles = $page->elements('title');
        if ([] === $titles || '' === Page::text($titles[0])) {
            yield $page->issue('structure', '8.5', 'la page n\'a pas de titre (<title>)');
        }

        // Le titre de l'onglet est du texte : une balise y serait affichée telle quelle.
        if (1 === preg_match('#<title>[^<]*<(?!/title>)#i', $page->source)) {
            yield $page->issue('structure', '8.6', 'le titre de la page (<title>) contient une balise HTML');
        }

        $ids = [];
        foreach ($page->all() as $element) {
            $id = $element->getAttribute('id');
            if ('' !== $id) {
                $ids[$id] = ($ids[$id] ?? 0) + 1;
            }
            $parent = $element->parentNode;
            if (\in_array($element->tagName, self::BLOCKS, true) && $parent instanceof \DOMElement && \in_array($parent->tagName, self::TEXT_CONTAINERS, true)) {
                yield $page->issue('structure', '8.2', sprintf('bloc <%s> placé dans un <%s>', $element->tagName, $parent->tagName));
            }
            if ('' !== $element->getAttribute('style') && 1 === preg_match('/color|background/i', $element->getAttribute('style'))) {
                yield $page->issue('couleurs', '10.1', sprintf('couleur écrite dans un attribut style sur <%s> : utiliser une classe CSS, dont le contraste peut être validé une fois pour toutes', $element->tagName));
            }
        }
        foreach ($ids as $id => $count) {
            if ($count > 1) {
                yield $page->issue('structure', '8.2', sprintf('identifiant « %s » utilisé %d fois', $id, $count));
            }
        }

        foreach (['header', 'nav', 'main', 'footer'] as $landmark) {
            if ([] === $page->elements($landmark)) {
                yield $page->issue('structure', '9.2', sprintf('zone <%s> absente', $landmark));
            }
        }
        if (\count($page->elements('main')) > 1) {
            yield $page->issue('structure', '9.2', 'plusieurs zones <main>');
        }

        $skipLink = false;
        foreach ($page->elements('a') as $link) {
            $target = $link->getAttribute('href');
            if (str_starts_with($target, '#') && \strlen($target) > 1 && isset($ids[substr($target, 1)])) {
                $targetElement = $this->element($page, substr($target, 1));
                $skipLink = $skipLink || (null !== $targetElement && 'main' === $targetElement->tagName);
            }
        }
        if (!$skipLink) {
            yield $page->issue('structure', '12.7', 'lien d\'évitement vers <main> absent');
        }
    }

    private function element(Page $page, string $id): ?\DOMElement
    {
        foreach ($page->all() as $element) {
            if ($element->getAttribute('id') === $id) {
                return $element;
            }
        }

        return null;
    }
}
