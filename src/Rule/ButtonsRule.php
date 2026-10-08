<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

/**
 * RGAA 11.9 : un bouton a un nom, et quelque chose à afficher.
 *
 * Un bouton sans texte ni élément enfant est invisible : cas typique d'un bouton de menu
 * dont l'icône (un <span> vide stylé en CSS) a été supprimée par un nettoyage du HTML.
 */
final readonly class ButtonsRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('button') as $button) {
            $text = Page::text($button);
            $label = trim($button->getAttribute('aria-label'));

            if ('' === $text && '' === $label) {
                yield $page->issue('boutons', '11.9', 'bouton sans nom (ni texte, ni aria-label)');
            }
            if ('' === $text && 0 === $button->getElementsByTagName('*')->length) {
                yield $page->issue('boutons', '11.9', sprintf('bouton « %s » sans contenu visible (icône manquante ?)', $label));
            }
        }
    }
}
