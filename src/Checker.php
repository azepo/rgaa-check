<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck;

use Azepo\RgaaCheck\Rule\AriaReferencesRule;
use Azepo\RgaaCheck\Rule\ButtonsRule;
use Azepo\RgaaCheck\Rule\FormsRule;
use Azepo\RgaaCheck\Rule\FramesRule;
use Azepo\RgaaCheck\Rule\HeadingsRule;
use Azepo\RgaaCheck\Rule\ImagesRule;
use Azepo\RgaaCheck\Rule\LanguageRule;
use Azepo\RgaaCheck\Rule\LinksRule;
use Azepo\RgaaCheck\Rule\StructureRule;
use Azepo\RgaaCheck\Rule\TablesRule;
use Azepo\RgaaCheck\Rule\ZoomRule;
use Symfony\Component\Finder\Finder;

/**
 * Applique des règles d'accessibilité aux pages HTML d'un dossier.
 *
 * Ces contrôles couvrent ce qu'un programme peut vérifier dans le code HTML. Ils ne remplacent
 * ni un audit RGAA, ni les tests au clavier, au zoom et avec un lecteur d'écran.
 */
final readonly class Checker
{
    /** Dossiers ignorés lors d'un contrôle récursif. */
    private const EXCLUDED_DIRECTORIES = ['vendor', 'node_modules'];

    /**
     * @param iterable<Rule> $rules
     */
    public function __construct(private iterable $rules)
    {
    }

    public static function withDefaultRules(): self
    {
        return new self(self::defaultRules());
    }

    /**
     * @return list<Rule>
     */
    public static function defaultRules(): array
    {
        return [
            new ImagesRule(),
            new HeadingsRule(),
            new LinksRule(),
            new StructureRule(),
            new TablesRule(),
            new ButtonsRule(),
            new LanguageRule(),
            // Règles en observation : elles donnent des avertissements.
            new FramesRule(),
            new FormsRule(),
            new ZoomRule(),
            new AriaReferencesRule(),
        ];
    }

    /**
     * Contrôle une page donnée sous forme de texte.
     *
     * @return list<Issue>
     */
    public function checkHtml(string $file, string $html): array
    {
        return $this->checkPage(Page::fromHtml($file, $html));
    }

    /**
     * Contrôle tous les fichiers .html d'un dossier.
     *
     * @param bool         $recursive contrôler aussi les sous-dossiers (sauf vendor/ et node_modules/)
     * @param list<string> $exclude   noms de fichiers à ignorer : motifs « glob » (« google*.html ») ou expressions régulières
     */
    public function checkDirectory(string $directory, bool $recursive = true, array $exclude = []): Report
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('Le dossier « %s » n\'existe pas.', $directory));
        }

        $finder = (new Finder())->files()->in($directory)->name('*.html')->sortByName();
        $recursive ? $finder->exclude(self::EXCLUDED_DIRECTORIES) : $finder->depth(0);
        if ([] !== $exclude) {
            $finder->notName($exclude);
        }

        $files = [];
        $issues = [];
        $titles = [];

        foreach ($finder as $file) {
            $page = Page::fromHtml(str_replace('\\', '/', $file->getRelativePathname()), $file->getContents());
            $files[] = $page->file;
            array_push($issues, ...$this->checkPage($page));

            $title = $page->elements('title')[0] ?? null;
            if (null !== $title && '' !== Page::text($title)) {
                $titles[Page::text($title)][] = $page->file;
            }
        }

        // RGAA 8.6 : un titre de page identifie la page. Deux pages ne peuvent donc pas porter le même.
        foreach ($titles as $title => $pages) {
            if (\count($pages) > 1) {
                foreach ($pages as $page) {
                    $issues[] = new Issue($page, 'structure', '8.6', sprintf('titre de page « %s » partagé avec %s', $title, implode(', ', array_diff($pages, [$page]))));
                }
            }
        }

        return new Report($files, $issues);
    }

    /**
     * @return list<Issue>
     */
    private function checkPage(Page $page): array
    {
        $issues = [];
        foreach ($this->rules as $rule) {
            foreach ($rule->check($page) as $issue) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }
}
