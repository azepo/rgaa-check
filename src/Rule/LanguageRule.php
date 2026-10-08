<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Rule;

use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use Azepo\RgaaCheck\Severity;

/**
 * RGAA 8.4 et 8.7 : les codes de langue sont valides ; dans une page en français, un passage
 * en anglais doit porter lang="en".
 *
 * La détection est approximative (mots anglais fréquents, absence d'accents) : c'est un avertissement,
 * à vérifier par un humain. La règle ne s'applique qu'aux pages et aux passages déclarés en français.
 */
final readonly class LanguageRule implements Rule
{
    private const ENGLISH_WORDS = 'the|and|with|your|you|this|that|from|should|would|must|have|are|is|of|to|for|in';

    public function check(Page $page): iterable
    {
        // Forme attendue : « fr », « en », « fr-FR », « zh-Hans »… et non « français » ou « FR_fr ».
        foreach ($page->all() as $element) {
            $code = trim($element->getAttribute('lang'));
            if ($element->hasAttribute('lang') && '' !== $code && 1 !== preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $code)) {
                yield $page->issue('langue', '8.4', sprintf('code de langue invalide sur <%s> : lang="%s"', $element->tagName, $code), Severity::Warning);
            }
        }

        $html = $page->elements('html')[0] ?? null;
        if (null === $html || !str_starts_with(strtolower(trim($html->getAttribute('lang'))), 'fr')) {
            return;
        }

        foreach ($page->elements('p', 'li', 'h1', 'h2', 'h3', 'h4', 'figcaption', 'a', 'span', 'td') as $element) {
            if ($this->inAnotherLanguageOrCode($element)) {
                continue;
            }
            // Seul le texte porté directement par l'élément est examiné : ses enfants le sont à leur tour.
            $text = '';
            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $text .= ' '.$child->data;
                }
            }
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));
            $words = preg_split('/\s+/u', $text, -1, \PREG_SPLIT_NO_EMPTY) ?: [];
            if (\count($words) < 4 || 1 === preg_match('/[àâçéèêëîïôùûœ]/iu', $text)) {
                continue;
            }
            if (preg_match_all('/\b('.self::ENGLISH_WORDS.')\b/i', $text) >= 2) {
                yield $page->issue('langue', '8.7', sprintf('passage probablement en anglais sans lang="en" : « %s »', mb_substr($text, 0, 70)), Severity::Warning);
            }
        }
    }

    private function inAnotherLanguageOrCode(\DOMElement $element): bool
    {
        for ($node = $element; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (\in_array($node->tagName, ['pre', 'code', 'script', 'style'], true)) {
                return true;
            }
            $language = $node->getAttribute('lang');
            if ('' !== $language && 'html' !== $node->tagName) {
                return !str_starts_with(strtolower($language), 'fr');
            }
        }

        return false;
    }
}
