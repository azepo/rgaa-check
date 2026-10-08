<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

/**
 * Fichier de référence des défauts déjà connus d'un projet.
 *
 * Sur un site existant, le premier contrôle relève souvent des dizaines de défauts. Le fichier de
 * référence les enregistre : les contrôles suivants n'échouent que sur les défauts nouveaux, et
 * les anciens se corrigent à leur rythme. Un défaut corrigé puis réintroduit est de nouveau signalé
 * dès que le fichier de référence a été régénéré.
 */
final readonly class Baseline
{
    private const VERSION = 1;

    /**
     * @param array<string, int> $known nombre d'occurrences connues, par défaut
     */
    private function __construct(private array $known)
    {
    }

    public static function fromReport(Report $report): self
    {
        $known = [];
        foreach ($report->issues as $issue) {
            $key = self::key($issue->file, $issue->rule, $issue->criterion, $issue->message);
            $known[$key] = ($known[$key] ?? 0) + 1;
        }

        return new self($known);
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Fichier de référence introuvable : « %s ».', $path));
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!\is_array($data) || self::VERSION !== ($data['version'] ?? null) || !\is_array($data['issues'] ?? null)) {
            throw new \InvalidArgumentException(sprintf('Fichier de référence illisible : « %s ». Le régénérer avec --generate-baseline.', $path));
        }

        $known = [];
        foreach ($data['issues'] as $entry) {
            if (!\is_array($entry)) {
                continue;
            }
            $key = self::key(self::text($entry, 'file'), self::text($entry, 'rule'), self::text($entry, 'criterion'), self::text($entry, 'message'));
            $count = $entry['count'] ?? 1;
            $known[$key] = ($known[$key] ?? 0) + (\is_int($count) ? max(1, $count) : 1);
        }

        return new self($known);
    }

    public function save(string $path): void
    {
        $issues = [];
        foreach ($this->known as $key => $count) {
            [$file, $rule, $criterion, $message] = explode("\x1f", $key, 4);
            $issues[] = ['file' => $file, 'rule' => $rule, 'criterion' => $criterion, 'message' => $message, 'count' => $count];
        }

        $json = json_encode(['version' => self::VERSION, 'issues' => $issues], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        if (false === @file_put_contents($path, $json."\n")) {
            throw new \InvalidArgumentException(sprintf('Impossible d\'écrire le fichier de référence « %s ».', $path));
        }
    }

    /** Nombre de défauts enregistrés. */
    public function count(): int
    {
        return array_sum($this->known);
    }

    /** Retire du rapport les défauts déjà connus, dans la limite du nombre d'occurrences enregistré. */
    public function apply(Report $report): Report
    {
        $remaining = $this->known;
        $issues = [];
        $ignored = 0;
        foreach ($report->issues as $issue) {
            $key = self::key($issue->file, $issue->rule, $issue->criterion, $issue->message);
            if (($remaining[$key] ?? 0) > 0) {
                --$remaining[$key];
                ++$ignored;
                continue;
            }
            $issues[] = $issue;
        }

        return new Report($report->files, $issues, $report->ignored + $ignored);
    }

    private static function key(string $file, string $rule, string $criterion, string $message): string
    {
        return implode("\x1f", [$file, $rule, $criterion, $message]);
    }

    /**
     * @param array<mixed> $entry
     */
    private static function text(array $entry, string $field): string
    {
        $value = $entry[$field] ?? '';

        return \is_string($value) ? $value : '';
    }
}
