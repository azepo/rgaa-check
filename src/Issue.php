<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

/**
 * Un défaut d'accessibilité relevé dans une page.
 */
final readonly class Issue
{
    /**
     * @param string $file      page concernée, chemin relatif au dossier contrôlé
     * @param string $rule      identifiant de la règle : « images », « titres », « liens »…
     * @param string $criterion critère du RGAA 4.1 auquel le défaut se rattache : « 1.1 », « 9.1 »…
     */
    public function __construct(
        public string $file,
        public string $rule,
        public string $criterion,
        public string $message,
        public Severity $severity = Severity::Error,
    ) {
    }

    /**
     * @return array{file: string, rule: string, criterion: string, message: string, severity: string}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'rule' => $this->rule,
            'criterion' => $this->criterion,
            'message' => $this->message,
            'severity' => $this->severity->value,
        ];
    }
}
