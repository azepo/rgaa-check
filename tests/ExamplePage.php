<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Tests;

/**
 * Une page conforme, que les tests abîment pour vérifier que chaque règle réagit.
 */
final class ExamplePage
{
    public const TITLE = 'Page de test – exemple.test';

    public static function html(string $content = '<p>Bonjour.</p>', string $title = self::TITLE): string
    {
        return <<<HTML
            <!doctype html>
            <html lang="fr">
            <head><title>$title</title></head>
            <body>
              <a href="#contenu" class="visually-hidden-focusable">Aller au contenu</a>
              <header>
                <nav>
                  <button type="button" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
                  <a href="https://exemple.test/">Accueil</a>
                </nav>
              </header>
              <main id="contenu" tabindex="-1">
                <h1>Titre de la page</h1>
                $content
              </main>
              <footer><p>2026</p></footer>
            </body>
            </html>
            HTML;
    }
}
