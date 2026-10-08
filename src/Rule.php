<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

/**
 * Une règle d'accessibilité, vérifiée sur chaque page.
 *
 * Pour écrire la vôtre : implémentez cette interface et passez-la au Checker.
 */
interface Rule
{
    /**
     * @return iterable<Issue>
     */
    public function check(Page $page): iterable;
}
