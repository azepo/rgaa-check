<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Console;

use Azepo\RgaaCheck\Baseline;
use Azepo\RgaaCheck\Checker;
use Azepo\RgaaCheck\Issue;
use Azepo\RgaaCheck\Report;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande « rgaa-check <dossier> » : contrôle les pages HTML d'un dossier.
 *
 * Code de sortie : 0 si aucune erreur, 1 s'il y a au moins une erreur (ou un avertissement
 * avec --fail-on-warning), 2 si la demande est invalide.
 */
final class CheckCommand extends Command
{
    public function __construct(private readonly Checker $checker)
    {
        parent::__construct('check');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Contrôle l\'accessibilité (RGAA 4.1) des pages HTML d\'un dossier')
            ->addArgument('dossier', InputArgument::OPTIONAL, 'Dossier des pages à contrôler', '.')
            ->addOption('no-recursive', null, InputOption::VALUE_NONE, 'Ne pas contrôler les sous-dossiers')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Nom de fichier à ignorer, motif glob ou expression régulière (option répétable)')
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ne garder que ces règles : --only=images,liens (option répétable)')
            ->addOption('skip', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Écarter ces règles : --skip=langue (option répétable)')
            ->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Fichier de référence : les défauts qu\'il contient sont ignorés')
            ->addOption('generate-baseline', null, InputOption::VALUE_REQUIRED, 'Enregistre les défauts actuels dans ce fichier de référence, puis réussit')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format du rapport : text ou json', 'text')
            ->addOption('fail-on-warning', null, InputOption::VALUE_NONE, 'Échouer aussi quand il reste des avertissements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = $input->getArgument('dossier');
        $format = $input->getOption('format');
        $exclude = array_values(array_filter((array) $input->getOption('exclude'), \is_string(...)));

        if (!\is_string($directory) || !\in_array($format, ['text', 'json'], true)) {
            $io->error('Format inconnu : utiliser --format=text ou --format=json.');

            return Command::INVALID;
        }

        $only = self::rules($input->getOption('only'));
        $skip = self::rules($input->getOption('skip'));
        $baseline = $input->getOption('baseline');
        $newBaseline = $input->getOption('generate-baseline');

        try {
            $report = $this->checker->checkDirectory($directory, !$input->getOption('no-recursive'), $exclude);
            if ([] !== $only) {
                $report = $report->only($only);
            }
            if ([] !== $skip) {
                $report = $report->without($skip);
            }
            if (\is_string($newBaseline)) {
                $reference = Baseline::fromReport($report);
                $reference->save($newBaseline);
                $io->success(sprintf('%d défaut(s) enregistré(s) dans %s. Les prochains contrôles lancés avec --baseline=%s ne signaleront que les nouveaux.', $reference->count(), $newBaseline, $newBaseline));

                return Command::SUCCESS;
            }
            if (\is_string($baseline)) {
                $report = Baseline::fromFile($baseline)->apply($report);
            }
        } catch (\InvalidArgumentException $error) {
            $io->error($error->getMessage());

            return Command::INVALID;
        }

        if ('json' === $format) {
            $output->writeln((string) json_encode($report->toArray(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        } else {
            self::render($io, $report);
        }

        $failed = !$report->passes() || ($input->getOption('fail-on-warning') && [] !== $report->warnings());

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Identifiants de règles d'une option répétable, où chaque valeur peut elle-même être une liste : « images,liens ».
     *
     * @return list<string>
     */
    private static function rules(mixed $option): array
    {
        $rules = [];
        foreach ((array) $option as $value) {
            if (\is_string($value)) {
                array_push($rules, ...array_filter(array_map(trim(...), explode(',', $value)), static fn (string $rule): bool => '' !== $rule));
            }
        }

        return array_values(array_unique($rules));
    }

    /** Affiche un rapport en texte. Réutilisable dans vos propres commandes. */
    public static function render(SymfonyStyle $io, Report $report): void
    {
        foreach (['Erreurs' => $report->errors(), 'Avertissements (à vérifier par un humain)' => $report->warnings()] as $title => $issues) {
            if ([] === $issues) {
                continue;
            }
            $io->section($title);
            $io->table(['Page', 'Règle', 'RGAA', 'Problème'], array_map(
                static fn (Issue $issue): array => [$issue->file, $issue->rule, $issue->criterion, $issue->message],
                $issues,
            ));
        }

        $summary = sprintf('%d page(s) contrôlée(s), %d erreur(s), %d avertissement(s).', \count($report->files), \count($report->errors()), \count($report->warnings()));
        if ($report->ignored > 0) {
            $io->writeln(sprintf(' %d défaut(s) déjà connu(s) ignoré(s) (fichier de référence).', $report->ignored));
        }
        $report->passes() ? $io->success('Accessibilité : '.$summary) : $io->error('Accessibilité : '.$summary);
    }
}
