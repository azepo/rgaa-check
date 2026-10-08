<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

enum Severity: string
{
    /** Défaut certain : fait échouer le contrôle. */
    case Error = 'erreur';
    /** Défaut probable : demande le jugement d'un humain, ne fait pas échouer le contrôle. */
    case Warning = 'avertissement';
}
