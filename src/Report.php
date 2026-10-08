<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

/**
 * Résultat du contrôle d'un dossier de pages.
 */
final readonly class Report
{
    /**
     * @param list<string> $files   pages contrôlées
     * @param list<Issue>  $issues
     * @param int          $ignored défauts déjà connus, écartés par un fichier de référence (voir Baseline)
     */
    public function __construct(
        public array $files,
        public array $issues,
        public int $ignored = 0,
    ) {
    }

    /**
     * @return list<Issue>
     */
    public function errors(): array
    {
        return array_values(array_filter($this->issues, static fn (Issue $issue): bool => Severity::Error === $issue->severity));
    }

    /**
     * @return list<Issue>
     */
    public function warnings(): array
    {
        return array_values(array_filter($this->issues, static fn (Issue $issue): bool => Severity::Warning === $issue->severity));
    }

    /** Vrai si aucune erreur n'a été relevée. Les avertissements ne comptent pas. */
    public function passes(): bool
    {
        return [] === $this->errors();
    }

    /**
     * Ne garde que les défauts de certaines règles.
     *
     * @param list<string> $rules identifiants de règles : « images », « liens »…
     */
    public function only(array $rules): self
    {
        return new self($this->files, array_values(array_filter($this->issues, static fn (Issue $issue): bool => \in_array($issue->rule, $rules, true))), $this->ignored);
    }

    /**
     * Écarte les défauts de certaines règles.
     *
     * @param list<string> $rules identifiants de règles : « langue », « couleurs »…
     */
    public function without(array $rules): self
    {
        return new self($this->files, array_values(array_filter($this->issues, static fn (Issue $issue): bool => !\in_array($issue->rule, $rules, true))), $this->ignored);
    }

    /**
     * @return array{files: list<string>, errors: int, warnings: int, ignored: int, issues: list<array{file: string, rule: string, criterion: string, message: string, severity: string}>}
     */
    public function toArray(): array
    {
        return [
            'files' => $this->files,
            'errors' => \count($this->errors()),
            'warnings' => \count($this->warnings()),
            'ignored' => $this->ignored,
            'issues' => array_map(static fn (Issue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
