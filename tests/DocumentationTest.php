<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Tests;

use PHPUnit\Framework\TestCase;

/**
 * La couverture annoncée dans la documentation doit être celle du code.
 */
final class DocumentationTest extends TestCase
{
    public function testTheCoverageTableListsExactlyTheCriteriaOfTheRules(): void
    {
        $documented = $this->coverageTable();
        $inCode = $this->criteriaInCode();

        self::assertSame(array_keys($inCode), array_keys($documented), 'docs/regles.md, tableau « Par critère » : un critère manque ou est en trop.');
        foreach ($inCode as $criterion => $severity) {
            self::assertSame($severity, $documented[$criterion], sprintf('Gravité du critère %s : le tableau ne dit pas la même chose que le code.', $criterion));
        }
    }

    public function testTheAnnouncedTotalsMatchTheTable(): void
    {
        $documented = $this->coverageTable();
        $total = \count($documented);
        $errors = \count(array_filter($documented, static fn (string $severity): bool => 'erreur' === $severity));

        $rules = (string) file_get_contents(\dirname(__DIR__).'/docs/regles.md');
        $readme = (string) file_get_contents(\dirname(__DIR__).'/README.md');

        self::assertStringContainsString(sprintf('**%d critères touchés sur 106**', $total), $rules);
        self::assertStringContainsString(sprintf('| **Total** | **106** | **%d** | **%d** |', $total, $errors), $rules);
        self::assertStringContainsString(sprintf('Il touche %d des 106 critères', $total), $readme);
        self::assertStringContainsString(sprintf('Seuls %d font échouer le contrôle ; les %d autres', $errors, $total - $errors), $readme);

        // Le tableau par thématique doit donner les mêmes totaux.
        preg_match_all('/^\| \d+\. [^|]+\| (\d+) \| (\d+) \| (\d+) \|$/m', $rules, $rows, \PREG_SET_ORDER);
        self::assertCount(13, $rows);
        self::assertSame(106, array_sum(array_column($rows, 1)));
        self::assertSame($total, array_sum(array_column($rows, 2)));
        self::assertSame($errors, array_sum(array_column($rows, 3)));
    }

    /**
     * @return array<string, string> critère => gravité, d'après le tableau « Par critère »
     */
    private function coverageTable(): array
    {
        $rules = (string) file_get_contents(\dirname(__DIR__).'/docs/regles.md');
        preg_match_all('/^\| (\d+\.\d+) \| `[^|]+\| (?:large|partielle|minime) \| (erreur|avertissement) \|/m', $rules, $rows, \PREG_SET_ORDER);

        $table = [];
        foreach ($rows as $row) {
            $table[$row[1]] = $row[2];
        }
        uksort($table, static fn (string $a, string $b): int => version_compare($a, $b));

        return $table;
    }

    /**
     * @return array<string, string> critère => gravité la plus forte relevée dans le code
     */
    private function criteriaInCode(): array
    {
        $criteria = [];
        $files = [...(glob(\dirname(__DIR__).'/src/Rule/*.php') ?: []), \dirname(__DIR__).'/src/Checker.php'];
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            // $page->issue('règle', 'N.N', …, Severity::Warning) ou new Issue($page, 'règle', 'N.N', …)
            preg_match_all("/(?:->issue\\(|new Issue\\(\\\$page, )'[a-z]+', '(\\d+\\.\\d+)'(.*?);\\n/s", $code, $calls, \PREG_SET_ORDER);
            foreach ($calls as $call) {
                $severity = str_contains($call[2], 'Severity::Warning') ? 'avertissement' : 'erreur';
                if ('erreur' === $severity || !isset($criteria[$call[1]])) {
                    $criteria[$call[1]] = $severity;
                }
            }
        }
        uksort($criteria, static fn (string $a, string $b): int => version_compare($a, $b));

        return $criteria;
    }
}
