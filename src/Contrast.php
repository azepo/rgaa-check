<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

/**
 * Calcul du rapport de contraste entre deux couleurs (formule WCAG, reprise par le RGAA, critère 3.2).
 *
 * Un contrôle HTML ne peut pas connaître les couleurs réellement affichées : elles dépendent des
 * feuilles de style. Cette classe sert à valider une palette dans vos propres tests.
 */
final class Contrast
{
    /** Contraste minimal d'un texte courant. */
    public const TEXT = 4.5;
    /** Contraste minimal d'un grand texte (24 px, ou 18,5 px en gras) et d'un composant d'interface. */
    public const LARGE_TEXT = 3.0;

    private const NAMES = [
        'white' => '#ffffff', 'black' => '#000000', 'purple' => '#800080', 'green' => '#008000', 'red' => '#ff0000',
        'blue' => '#0000ff', 'gray' => '#808080', 'grey' => '#808080', 'orange' => '#ffa500', 'yellow' => '#ffff00',
        'whitesmoke' => '#f5f5f5', 'springgreen' => '#00ff7f', 'wheat' => '#f5deb3',
    ];

    /** Rapport de contraste, de 1 (aucun) à 21 (noir sur blanc). */
    public static function ratio(string $color, string $background): float
    {
        $a = self::luminance($color);
        $b = self::luminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /** Vrai si le couple texte/fond atteint le contraste demandé (arrondi à deux décimales, comme les outils courants). */
    public static function isEnough(string $color, string $background, float $minimum = self::TEXT): bool
    {
        return round(self::ratio($color, $background), 2) >= $minimum;
    }

    /** Forme canonique « #rrggbb » d'une couleur CSS : hexadécimale, rgb() ou nommée (noms courants seulement). */
    public static function hex(string $color): string
    {
        $color = strtolower(trim($color));
        $color = self::NAMES[$color] ?? $color;

        if (1 === preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $c)) {
            return '#'.$c[1].$c[1].$c[2].$c[2].$c[3].$c[3];
        }
        if (1 === preg_match('/^#[0-9a-f]{6}$/', $color)) {
            return $color;
        }
        if (1 === preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $color, $c)) {
            return sprintf('#%02x%02x%02x', min(255, (int) $c[1]), min(255, (int) $c[2]), min(255, (int) $c[3]));
        }

        throw new \InvalidArgumentException(sprintf('Couleur non reconnue : « %s ».', $color));
    }

    private static function luminance(string $color): float
    {
        $hex = self::hex($color);
        $channels = [];
        foreach ([1, 3, 5] as $position) {
            $value = hexdec(substr($hex, $position, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
