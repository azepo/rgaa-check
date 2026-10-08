# rgaa-check

Contrôles automatiques d'accessibilité sur des pages HTML, rattachés aux critères du RGAA 4.1.

L'outil lit les fichiers `.html` d'un dossier et signale les défauts qu'un programme peut reconnaître : image sans texte alternatif, niveau de titre sauté, lien sans intitulé, tableau sans légende, lien d'évitement absent… Il sort avec un code d'erreur dès qu'un défaut est trouvé, ce qui permet de bloquer une mise en ligne ou une intégration continue.

## Ce que l'outil ne fait pas

**Il ne remplace pas un audit RGAA et ne permet pas de déclarer un site conforme.**

- Il touche 25 des 106 critères du RGAA 4.1, et aucun en entier : 6 largement, 15 partiellement, 4 de façon minime. Seuls 18 font échouer le contrôle ; les 7 autres donnent un avertissement. Rien n'est contrôlé pour les couleurs affichées et le multimédia. Le détail critère par critère est dans [docs/regles.md](docs/regles.md).
- Il lit le HTML tel qu'il est écrit dans le fichier. Il n'exécute pas le JavaScript et n'applique pas les feuilles de style : il ne voit donc ni les contrastes réels, ni le contenu ajouté par un script, ni l'ordre de tabulation.
- Il ne juge pas le sens : un texte alternatif présent mais faux passe le contrôle.
- La détection des passages en anglais suppose une page en français.

Restent à faire à la main : navigation au clavier, zoom à 200 %, affichage à 320 px de large, lecture avec un lecteur d'écran, contrastes, formulaires, médias.

L'outil sert à ne pas régresser : une fois une page corrigée, les défauts les plus courants ne reviennent pas sans que quelqu'un le voie.

## Installation

PHP 8.2 ou plus récent.

```bash
composer require --dev azepo/rgaa-check
```

Le paquet n'est pas encore publié sur Packagist. D'ici là, déclarer son dépôt dans le `composer.json` du projet :

```json
{
    "repositories": [
        { "type": "path", "url": "packages/rgaa-check" }
    ]
}
```

## En ligne de commande

```bash
vendor/bin/rgaa-check public/
```

| Option | Effet |
|---|---|
| `--no-recursive` | Ne contrôle pas les sous-dossiers |
| `--exclude=<motif>` | Ignore des fichiers par leur nom : `--exclude='google*.html'`. Option répétable |
| `--only=<règles>` | Ne garde que ces règles : `--only=images,liens` |
| `--skip=<règles>` | Écarte ces règles : `--skip=langue` |
| `--baseline=<fichier>` | Ignore les défauts déjà connus, enregistrés dans ce fichier (voir plus bas) |
| `--generate-baseline=<fichier>` | Enregistre les défauts actuels dans ce fichier, puis réussit |
| `--format=json` | Rapport en JSON, pour un autre outil |
| `--fail-on-warning` | Échoue aussi s'il reste des avertissements |

Les dossiers `vendor/` et `node_modules/` sont toujours ignorés.

| Code de sortie | Sens |
|---|---|
| 0 | Aucune erreur |
| 1 | Au moins une erreur (ou un avertissement avec `--fail-on-warning`) |
| 2 | Demande invalide : dossier absent, format inconnu |

Exemple de rapport :

```
Erreurs
-------

 -------------- -------- ------ ---------------------------------------
  Page           Règle    RGAA   Problème
 -------------- -------- ------ ---------------------------------------
  contact.html   images   1.1    image sans attribut alt : plan.png
  contact.html   titres   9.1    niveau de titre sauté : de h1 à h3
 -------------- -------- ------ ---------------------------------------

 [ERROR] Accessibilité : 12 page(s) contrôlée(s), 2 erreur(s), 0 avertissement(s).
```

### Erreur ou avertissement

- **Erreur** : le défaut est certain. Le contrôle échoue.
- **Avertissement** : le défaut est probable, mais seul un humain peut trancher (image à `alt` vide : décorative ou non ? passage en anglais ?). Le contrôle n'échoue pas, sauf avec `--fail-on-warning`.

### Règles en observation

Une règle nouvelle entre dans l'outil comme **avertissement** : elle signale, mais ne fait pas échouer le contrôle. Elle ne devient une erreur, dans une version ultérieure, qu'après avoir tourné sur des pages réelles sans fausse alerte. Les règles en observation sont marquées dans [docs/regles.md](docs/regles.md).

Si une règle se trompe sur vos pages, écartez-la avec `--skip` et signalez le cas : c'est exactement l'information que cette période sert à recueillir.

## Adopter l'outil sur un site existant

Le premier contrôle d'un site ancien relève souvent des dizaines de défauts. Les corriger tous avant d'activer le contrôle n'est pas réaliste ; le laisser en échec permanent le rend inutile. Le fichier de référence règle ce problème :

```bash
# 1. Une fois : enregistrer les défauts actuels
vendor/bin/rgaa-check public/ --generate-baseline=rgaa-baseline.json

# 2. Ensuite, à chaque contrôle : seuls les défauts nouveaux font échouer
vendor/bin/rgaa-check public/ --baseline=rgaa-baseline.json
```

- Le fichier se versionne avec le projet.
- Un défaut corrigé ne gêne pas : il reste simplement dans le fichier. Régénérer le fichier de temps en temps pour qu'il ne puisse pas être réintroduit sans être vu.
- Le fichier identifie un défaut par sa page, sa règle et son message. Renommer une page fait donc réapparaître ses défauts.
- L'objectif est de le vider : c'est la liste de ce qui reste à corriger.

## En intégration continue

Le contrôle porte sur du HTML produit : il se place après la génération du site ou des gabarits.

GitLab CI :

```yaml
accessibilite:
  stage: test
  script:
    - composer install --no-interaction
    - php bin/console app:build        # votre commande qui produit le HTML
    - vendor/bin/rgaa-check public/
```

GitHub Actions :

```yaml
- run: composer install --no-interaction
- run: php bin/console app:build
- run: vendor/bin/rgaa-check public/
```

Pour une application dynamique, enregistrer d'abord quelques pages représentatives (avec `curl` ou depuis un test fonctionnel), puis contrôler le dossier obtenu.

## Depuis du code PHP

```php
use Azepo\RgaaCheck\Baseline;
use Azepo\RgaaCheck\Checker;

$checker = Checker::withDefaultRules();

// Un dossier
$report = $checker->checkDirectory(__DIR__.'/public', recursive: true, exclude: ['google*.html']);

// Choisir les règles, ignorer les défauts déjà connus
$report = $report->without(['langue']);
$report = Baseline::fromFile(__DIR__.'/rgaa-baseline.json')->apply($report);

if (!$report->passes()) {
    foreach ($report->errors() as $issue) {
        echo "{$issue->file} [RGAA {$issue->criterion}] {$issue->message}\n";
    }
}

// Une page déjà en mémoire, par exemple la réponse d'un test fonctionnel
$issues = $checker->checkHtml('accueil', $html);
```

Dans un test PHPUnit :

```php
public function testLaPageDAccueilEstAccessible(): void
{
    $html = $this->client->request('GET', '/')->html();

    self::assertSame([], array_map(
        static fn ($issue) => $issue->message,
        Checker::withDefaultRules()->checkHtml('accueil', $html),
    ));
}
```

## Écrire une règle

Une règle est une classe qui implémente `Azepo\RgaaCheck\Rule` :

```php
use Azepo\RgaaCheck\Page;
use Azepo\RgaaCheck\Rule;

final readonly class CadresRule implements Rule
{
    public function check(Page $page): iterable
    {
        foreach ($page->elements('iframe') as $cadre) {
            if ('' === trim($cadre->getAttribute('title'))) {
                // identifiant de la règle, critère RGAA, message
                yield $page->issue('cadres', '2.1', 'cadre sans attribut title : '.$cadre->getAttribute('src'));
            }
        }
    }
}

$checker = new Checker([...Checker::defaultRules(), new CadresRule()]);
```

`Page` donne accès au document (`$page->document`, un `DOMDocument`), au texte source (`$page->source`) et à deux raccourcis : `elements('img', 'a')` et `all()`. Passer `Severity::Warning` en quatrième argument de `issue()` pour un défaut à faire confirmer par un humain.

Pour retirer une règle, construire le `Checker` avec la liste voulue au lieu de `defaultRules()`.

## Contrastes

Un contrôle HTML ne connaît pas les couleurs affichées. La classe `Contrast` permet en revanche de valider une palette une fois pour toutes dans vos tests :

```php
use Azepo\RgaaCheck\Contrast;

Contrast::ratio('#0051A8', 'white');                            // 7.66
Contrast::isEnough('#0051A8', '#ffffff');                       // true : texte courant, 4,5:1
Contrast::isEnough('#0593ff', '#ffffff', Contrast::LARGE_TEXT); // true : grand texte, 3:1
```

La règle `structure` signale par ailleurs toute couleur écrite dans un attribut `style`, pour que les couleurs restent dans des classes CSS dont le contraste a été vérifié.

## Développement

```bash
composer install
composer test
composer stan
```

Toute nouvelle règle :

1. entre comme avertissement (`Severity::Warning`) ;
2. est ajoutée à `Checker::defaultRules()` (un test échoue sinon) ;
3. a dans `tests/RulesTest.php` une page fautive **et** des pages conformes qui ressemblent à une faute : ce sont elles qui protègent des fausses alertes ;
4. a sa ligne dans `docs/regles.md`, avec un numéro de critère vérifié dans le référentiel ;
5. est essayée sur des pages réelles avant d'être publiée.

Le passage d'une règle d'avertissement à erreur peut faire échouer l'intégration continue d'un projet : il se fait dans une nouvelle version, annoncée dans [CHANGELOG.md](CHANGELOG.md).

## Licence

MIT. Voir [LICENSE](LICENSE).
