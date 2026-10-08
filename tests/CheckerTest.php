<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Tests;

use Azepo\RgaaCheck\Baseline;
use Azepo\RgaaCheck\Checker;
use Azepo\RgaaCheck\Console\CheckCommand;
use Azepo\RgaaCheck\Issue;
use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/rgaa-check-'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    public function testADirectoryOfCompliantPagesPasses(): void
    {
        $this->write('a.html', ExamplePage::html('<p>A</p>', 'Page A'));
        $this->write('b.html', ExamplePage::html('<p>B</p>', 'Page B'));
        $this->write('robots.txt', 'User-agent: *');

        $report = Checker::withDefaultRules()->checkDirectory($this->directory);

        self::assertSame(['a.html', 'b.html'], $report->files);
        self::assertSame([], $report->issues);
        self::assertTrue($report->passes());
    }

    public function testSubdirectoriesAreCheckedExceptDependencies(): void
    {
        $this->write('a.html', ExamplePage::html('<p>A</p>', 'Page A'));
        $this->write('blog/b.html', ExamplePage::html('<p>B</p>', 'Page B'));
        $this->write('vendor/paquet/doc.html', '<p>fragment</p>');
        $this->write('node_modules/paquet/doc.html', '<p>fragment</p>');

        self::assertSame(['a.html', 'blog/b.html'], Checker::withDefaultRules()->checkDirectory($this->directory)->files);
        self::assertSame(['a.html'], Checker::withDefaultRules()->checkDirectory($this->directory, recursive: false)->files);
    }

    public function testFilesCanBeExcluded(): void
    {
        $this->write('a.html', ExamplePage::html());
        $this->write('google0123abcd.html', 'google-site-verification');

        $report = Checker::withDefaultRules()->checkDirectory($this->directory, exclude: ['google*.html']);

        self::assertSame(['a.html'], $report->files);
        self::assertTrue($report->passes());
    }

    public function testTwoPagesCannotShareATitle(): void
    {
        $this->write('a.html', ExamplePage::html('<p>A</p>', 'Symfony 6.3'));
        $this->write('b.html', ExamplePage::html('<p>B</p>', 'Symfony 6.3'));

        $report = Checker::withDefaultRules()->checkDirectory($this->directory);

        self::assertFalse($report->passes());
        self::assertSame(
            ['a.html : 8.6 : titre de page « Symfony 6.3 » partagé avec b.html', 'b.html : 8.6 : titre de page « Symfony 6.3 » partagé avec a.html'],
            array_map(static fn (Issue $issue): string => $issue->file.' : '.$issue->criterion.' : '.$issue->message, $report->errors()),
        );
    }

    public function testAWarningDoesNotFailTheReport(): void
    {
        $this->write('a.html', ExamplePage::html('<p>If you run this command as follows, the result is in the terminal.</p>'));

        $report = Checker::withDefaultRules()->checkDirectory($this->directory);

        self::assertTrue($report->passes());
        self::assertCount(1, $report->warnings());
        self::assertSame(1, $report->toArray()['warnings']);
    }

    public function testAMissingDirectoryIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Checker::withDefaultRules()->checkDirectory($this->directory.'/absent');
    }

    public function testACustomRuleCanBeAdded(): void
    {
        $rule = new class implements Rule {
            public function check(Page $page): iterable
            {
                if ([] === $page->elements('footer')) {
                    return;
                }
                yield $page->issue('maison', '—', 'règle maison déclenchée');
            }
        };

        $issues = (new Checker([...Checker::defaultRules(), $rule]))->checkHtml('page.html', ExamplePage::html());

        self::assertCount(1, $issues);
        self::assertSame('maison', $issues[0]->rule);
    }

    public function testTheCommandFailsOnAFaultyPageAndNamesTheProblem(): void
    {
        $this->write('fautive.html', ExamplePage::html('<img src="a.jpg"><h3>Niveau sauté</h3>'));

        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        self::assertSame(Command::FAILURE, $command->execute(['dossier' => $this->directory]));
        self::assertStringContainsString('image sans attribut alt', $command->getDisplay());
        self::assertStringContainsString('niveau de titre sauté', $command->getDisplay());
        self::assertStringContainsString('2 erreur(s)', $command->getDisplay());
    }

    public function testTheCommandSucceedsOnACompliantDirectory(): void
    {
        $this->write('a.html', ExamplePage::html());

        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory]));
        self::assertStringContainsString('1 page(s) contrôlée(s), 0 erreur(s)', $command->getDisplay());
    }

    public function testTheCommandCanOutputJsonAndFailOnWarnings(): void
    {
        $this->write('a.html', ExamplePage::html('<img src="decor.png" alt="">'));

        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory, '--format' => 'json']));
        $json = json_decode($command->getDisplay(), true);
        self::assertIsArray($json);
        self::assertSame(['a.html'], $json['files']);
        self::assertSame(0, $json['errors']);
        self::assertSame(1, $json['warnings']);

        self::assertSame(Command::FAILURE, $command->execute(['dossier' => $this->directory, '--fail-on-warning' => true]));
    }

    public function testTheCommandRejectsAMissingDirectoryAndAnUnknownFormat(): void
    {
        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        self::assertSame(Command::INVALID, $command->execute(['dossier' => $this->directory.'/absent']));
        self::assertSame(Command::INVALID, $command->execute(['dossier' => $this->directory, '--format' => 'xml']));
    }

    public function testIssuesCanBeFilteredByRule(): void
    {
        $this->write('a.html', ExamplePage::html('<img src="a.jpg"><h3>Niveau sauté</h3>'));
        $report = Checker::withDefaultRules()->checkDirectory($this->directory);

        self::assertSame(['images'], array_map(static fn (Issue $issue): string => $issue->rule, $report->only(['images'])->issues));
        self::assertSame(['titres'], array_map(static fn (Issue $issue): string => $issue->rule, $report->without(['images'])->issues));

        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));
        self::assertSame(Command::FAILURE, $command->execute(['dossier' => $this->directory, '--only' => ['images']]));
        self::assertStringContainsString('1 erreur(s)', $command->getDisplay());
        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory, '--skip' => ['images, titres']]));
    }

    public function testABaselineHidesKnownIssuesButNotNewOnes(): void
    {
        $this->write('a.html', ExamplePage::html('<img src="a.jpg"><img src="a.jpg">'));
        $baseline = $this->directory.'/reference.json';
        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        // Premier passage : deux défauts identiques, enregistrés comme connus.
        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory, '--generate-baseline' => $baseline]));
        self::assertStringContainsString('2 défaut(s) enregistré(s)', $command->getDisplay());
        self::assertSame(2, Baseline::fromFile($baseline)->count());

        // Rien de nouveau : le contrôle réussit et dit ce qu'il a ignoré.
        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory, '--baseline' => $baseline]));
        self::assertStringContainsString('2 défaut(s) déjà connu(s) ignoré(s)', $command->getDisplay());

        // Une troisième image fautive, identique aux deux premières, et un défaut d'une autre nature : tous deux signalés.
        $this->write('a.html', ExamplePage::html('<img src="a.jpg"><img src="a.jpg"><img src="a.jpg"><h3>Niveau sauté</h3>'));
        self::assertSame(Command::FAILURE, $command->execute(['dossier' => $this->directory, '--baseline' => $baseline]));
        self::assertStringContainsString('2 erreur(s)', $command->getDisplay());

        // Un défaut corrigé ne fait pas échouer le contrôle.
        $this->write('a.html', ExamplePage::html('<img src="a.jpg">'));
        self::assertSame(Command::SUCCESS, $command->execute(['dossier' => $this->directory, '--baseline' => $baseline]));
    }

    public function testAnUnreadableBaselineIsRejected(): void
    {
        $this->write('a.html', ExamplePage::html());
        $this->write('casse.json', '{"version": 99}');
        $command = new CommandTester(new CheckCommand(Checker::withDefaultRules()));

        self::assertSame(Command::INVALID, $command->execute(['dossier' => $this->directory, '--baseline' => $this->directory.'/absent.json']));
        self::assertSame(Command::INVALID, $command->execute(['dossier' => $this->directory, '--baseline' => $this->directory.'/casse.json']));
    }

    private function write(string $file, string $content): void
    {
        $path = $this->directory.'/'.$file;
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0o777, true);
        }
        file_put_contents($path, $content);
    }
}
