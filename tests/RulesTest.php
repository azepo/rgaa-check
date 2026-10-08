<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Tests;

use Azepo\RgaaCheck\Checker;
use Azepo\RgaaCheck\Issue;
use Azepo\RgaaCheck\Severity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Chaque règle détecte une page fautive, la rattache au bon critère du RGAA,
 * et ne signale rien sur une page conforme.
 */
final class RulesTest extends TestCase
{
    public function testACompliantPageTriggersNoRule(): void
    {
        $content = <<<'HTML'
            <h2>Section</h2>
            <p>Un paragraphe avec <a href="https://exemple.test/page.html">un lien interne</a>,
               <a href="https://www.php.net/" target="_blank" aria-label="le manuel PHP – ouvre dans une nouvelle fenêtre (onglet)">le manuel PHP</a>
               et <span lang="en">a sentence in English that is marked with the right language</span>.</p>
            <h3>Sous-section</h3>
            <figure class="schema"><img src="a.jpg" alt="Schéma : deux bases synchronisées"></figure>
            <pre><code>if ($this and $that) { return the_value; }</code></pre>
            <table><caption>Contenu</caption><thead><tr><th scope="col">id</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>
            <table role="presentation"><tr><td>mise en forme</td></tr></table>
            HTML;

        self::assertSame([], $this->issues(ExamplePage::html($content)));
    }

    /**
     * @return iterable<string, array{string, string, string}> [page fautive, critère RGAA, extrait du message attendu]
     */
    public static function faultyPages(): iterable
    {
        $page = ExamplePage::html(...);

        // Images
        yield 'image sans alt' => [$page('<img src="a.jpg">'), '1.1', 'image sans attribut alt : a.jpg'];
        yield 'alt sans information' => [$page('<img src="a.jpg" alt="Image 1">'), '1.3', 'texte alternatif sans information « Image 1 »'];
        yield 'alt « Listing 2. »' => [$page('<img src="a.jpg" alt="Listing 2.">'), '1.3', 'texte alternatif sans information'];

        // Titres
        yield 'aucun h1' => [str_replace('<h1>Titre de la page</h1>', '', $page()), '9.1', 'exactement un <h1> (0 trouvé(s))'];
        yield 'deux h1' => [$page('<h1>Second titre</h1>'), '9.1', 'exactement un <h1> (2 trouvé(s))'];
        yield 'niveau sauté' => [$page('<h3>Trop profond</h3>'), '9.1', 'niveau de titre sauté : de h1 à h3'];
        yield 'titre vide' => [$page('<h2> </h2>'), '9.1', 'titre <h2> vide'];

        // Liens
        yield 'lien sans intitulé' => [$page('<a href="https://exemple.test/x.html"></a>'), '6.2', 'lien sans intitulé'];
        yield 'lien sans href' => [$page('<a onclick="aller()">Introduction</a>'), '6.2', 'lien sans href'];
        yield 'intitulé vague' => [$page('<p>Pour la suite, <a href="x.html">cliquez ici</a>.</p>'), '6.1', 'intitulé de lien non explicite : « cliquez ici »'];
        yield 'aria-label sans le texte visible' => [$page('<a href="x.html" aria-label="FrankenPHP">Utilisation des workers</a>'), '6.1', 'ne reprend pas le texte visible'];
        yield 'nouvel onglet non annoncé' => [$page('<a href="https://www.php.net/" target="_blank">Manuel PHP</a>'), '13.2', 'nouvel onglet sans l\'annoncer : « Manuel PHP »'];

        // Structure
        yield 'doctype absent' => [str_replace('<!doctype html>', '', $page()), '8.1', 'doit commencer par <!doctype html>'];
        yield 'langue absente' => [str_replace('<html lang="fr">', '<html>', $page()), '8.3', 'la langue de la page n\'est pas indiquée'];
        yield 'titre de page vide' => [$page('<p>Bonjour.</p>', ''), '8.5', 'la page n\'a pas de titre'];
        yield 'balise dans le titre de page' => [$page('<p>Bonjour.</p>', '<span lang="en">CQRS</span>'), '8.6', 'le titre de la page (<title>) contient une balise HTML'];
        yield 'identifiant en double' => [$page('<p id="a">Un</p><p id="a">Deux</p>'), '8.2', 'identifiant « a » utilisé 2 fois'];
        yield 'bloc dans un paragraphe' => [$page('<p>Texte<ul><li>liste</li></ul></p>'), '8.2', 'HTML invalide'];
        yield 'bloc dans un span' => [$page('<span>Texte<div>bloc</div></span>'), '8.2', 'bloc <div> placé dans un <span>'];
        yield 'balise mal fermée' => [$page('<section><h2>Titre</section></h2>'), '8.2', 'HTML invalide'];
        yield 'zone main absente' => [str_replace(['<main id="contenu" tabindex="-1">', '</main>'], ['<div id="contenu">', '</div>'], $page()), '9.2', 'zone <main> absente'];
        yield 'lien d\'évitement absent' => [str_replace('href="#contenu"', 'href="#ailleurs"', $page()), '12.7', 'lien d\'évitement vers <main> absent'];
        yield 'couleur en attribut style' => [$page('<p style="color: #ff0000">Rouge</p>'), '10.1', 'couleur écrite dans un attribut style'];

        // Tableaux
        yield 'tableau sans légende' => [$page('<table><thead><tr><th scope="col">id</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'), '5.4', 'tableau sans légende'];
        yield 'tableau sans en-tête' => [$page('<table><caption>Données</caption><tbody><tr><td>1</td></tr></tbody></table>'), '5.6', 'tableau sans cellule d\'en-tête'];
        yield 'en-tête sans scope' => [$page('<table><caption>Données</caption><thead><tr><th>id</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'), '5.7', 'en-tête « id » sans attribut scope'];

        // Boutons
        yield 'bouton sans icône' => [str_replace('<span class="navbar-toggler-icon"></span>', '', $page()), '11.9', 'bouton « Menu » sans contenu visible'];
        yield 'bouton sans nom' => [$page('<button type="button"><span></span></button>'), '11.9', 'bouton sans nom'];
    }

    #[DataProvider('faultyPages')]
    public function testEachRuleDetectsAFaultyPage(string $html, string $criterion, string $expectedMessage): void
    {
        $errors = $this->issues($html, Severity::Error);

        self::assertNotEmpty($errors, 'La page fautive n\'a déclenché aucune erreur.');
        self::assertStringContainsString($criterion.' | ', implode("\n", $errors));
        self::assertStringContainsString($expectedMessage, implode("\n", $errors));
    }

    public function testEnglishWithoutLangIsAWarningNotAnError(): void
    {
        $html = ExamplePage::html('<p>If you run this command as follows, the result is in the terminal.</p>');

        self::assertSame([], $this->issues($html, Severity::Error));
        self::assertStringContainsString('8.7 | passage probablement en anglais', implode("\n", $this->issues($html, Severity::Warning)));
    }

    public function testMarkedEnglishAndCodeGiveNoWarning(): void
    {
        $html = ExamplePage::html('<p lang="en">If you run this command as follows, the result is in the terminal.</p><pre><code>// when you use the value of this</code></pre>');

        self::assertSame([], $this->issues($html));
    }

    public function testTheLanguageRuleOnlyAppliesToFrenchPages(): void
    {
        $html = str_replace('<html lang="fr">', '<html lang="en">', ExamplePage::html('<p>If you run this command as follows, the result is in the terminal.</p>'));

        self::assertSame([], $this->issues($html));
    }

    public function testAnEmptyAltIsAWarning(): void
    {
        $html = ExamplePage::html('<img src="decor.png" alt="">');

        self::assertSame([], $this->issues($html, Severity::Error));
        self::assertStringContainsString('1.2 | image avec un alt vide', implode("\n", $this->issues($html, Severity::Warning)));
    }

    public function testANewTabAnnouncedInEnglishIsAccepted(): void
    {
        $html = ExamplePage::html('<a href="https://www.php.net/" target="_blank" aria-label="PHP manual (opens in a new tab)">PHP manual</a>');

        self::assertSame([], $this->issues($html, Severity::Error));
    }

    /**
     * @return iterable<string, array{string, string, string}> [page fautive, critère RGAA, extrait de l'avertissement attendu]
     */
    public static function pagesUnderObservation(): iterable
    {
        $page = ExamplePage::html(...);

        yield 'cadre sans titre' => [$page('<iframe src="carte.html"></iframe>'), '2.1', 'cadre sans attribut title : carte.html'];
        yield 'champ sans étiquette' => [$page('<form><input type="text" name="nom"></form>'), '11.1', 'champ <input> sans étiquette : nom'];
        yield 'liste déroulante sans étiquette' => [$page('<form><select name="pays"><option>France</option></select></form>'), '11.1', 'champ <select> sans étiquette : pays'];
        yield 'étiquette vide' => [$page('<form><label for="n"></label><input id="n" name="nom"></form>'), '11.1', 'champ <input> sans étiquette : nom'];
        yield 'zoom interdit' => [str_replace('<title>', '<meta name="viewport" content="width=device-width, user-scalable=no"><title>', $page()), '10.4', 'interdit le zoom'];
        yield 'zoom limité' => [str_replace('<title>', '<meta name="viewport" content="width=device-width, maximum-scale=1.0"><title>', $page()), '10.4', 'limite le zoom à 1.0'];
        yield 'code de langue invalide' => [$page('<p lang="anglais">Hello.</p>'), '8.4', 'code de langue invalide sur <p> : lang="anglais"'];
        yield 'aria-labelledby dans le vide' => [$page('<section aria-labelledby="absent"><p>Texte.</p></section>'), '7.1', 'aria-labelledby de <section> vise l\'identifiant « absent »'];
        yield 'label for dans le vide' => [$page('<form><label for="absent">Nom</label><input id="n" name="nom" aria-label="Nom"></form>'), '11.1', '<label for="absent"> vise un identifiant qui n\'existe pas'];
    }

    #[DataProvider('pagesUnderObservation')]
    public function testARuleUnderObservationWarnsWithoutFailing(string $html, string $criterion, string $expectedMessage): void
    {
        self::assertSame([], $this->issues($html, Severity::Error), 'Une règle en observation ne doit pas donner d\'erreur.');
        self::assertStringContainsString($criterion.' | ', implode("\n", $this->issues($html, Severity::Warning)));
        self::assertStringContainsString($expectedMessage, implode("\n", $this->issues($html, Severity::Warning)));
    }

    /**
     * Les pages conformes qui ressemblent à une faute : c'est ce qui protège des fausses alertes.
     *
     * @return iterable<string, array{string}>
     */
    public static function compliantLookalikes(): iterable
    {
        $page = ExamplePage::html(...);

        yield 'cadre avec titre' => [$page('<iframe src="carte.html" title="Carte du quartier"></iframe>')];
        yield 'label for' => [$page('<form><label for="n">Nom</label><input id="n" name="nom"></form>')];
        yield 'label englobant' => [$page('<form><label>Nom <input name="nom"></label></form>')];
        yield 'aria-label' => [$page('<form><input type="search" name="q" aria-label="Rechercher"></form>')];
        yield 'aria-labelledby' => [$page('<form><span id="t">Nom</span><input name="nom" aria-labelledby="t"></form>')];
        yield 'title sur le champ' => [$page('<form><input name="q" title="Rechercher"></form>')];
        yield 'champs sans étiquette attendue' => [$page('<form><input type="hidden" name="jeton"><input type="submit" value="Envoyer"><button type="submit">Valider</button></form>')];
        yield 'viewport courant' => [str_replace('<title>', '<meta name="viewport" content="width=device-width, initial-scale=1"><title>', $page())];
        yield 'zoom autorisé jusqu\'à 5' => [str_replace('<title>', '<meta name="viewport" content="width=device-width, maximum-scale=5"><title>', $page())];
        yield 'codes de langue valides' => [$page('<p lang="en">Hello.</p><p lang="pt-BR">Olá.</p><p lang="zh-Hans">你好</p>')];
        yield 'références ARIA valides' => [$page('<section aria-labelledby="s"><h2 id="s">Section</h2><p aria-describedby="s">Texte.</p></section>')];
    }

    #[DataProvider('compliantLookalikes')]
    public function testACompliantLookalikeTriggersNothing(string $html): void
    {
        self::assertSame([], $this->issues($html));
    }

    public function testEveryRuleOfThePackageIsADefaultRule(): void
    {
        $files = array_map(static fn (string $file): string => basename($file, '.php'), glob(\dirname(__DIR__).'/src/Rule/*.php') ?: []);
        $defaults = array_map(static fn (object $rule): string => (new \ReflectionClass($rule))->getShortName(), Checker::defaultRules());
        sort($files);
        sort($defaults);

        self::assertSame($files, $defaults, 'Nouvelle règle : l\'ajouter à Checker::defaultRules(), à faultyPages() et à docs/regles.md.');
    }

    /**
     * @return list<string> « critère | message » de chaque défaut
     */
    private function issues(string $html, ?Severity $severity = null): array
    {
        $issues = array_filter(
            Checker::withDefaultRules()->checkHtml('page.html', $html),
            static fn (Issue $issue): bool => null === $severity || $issue->severity === $severity,
        );

        return array_values(array_map(static fn (Issue $issue): string => $issue->criterion.' | '.$issue->message, $issues));
    }
}
