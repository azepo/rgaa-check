# Règles et critères RGAA couverts

Chaque défaut signalé porte le numéro d'un critère du RGAA 4.1. Ce numéro indique **à quel critère le défaut se rattache**, pour le retrouver dans le référentiel. Il ne signifie pas que le critère entier est vérifié : la plupart des critères comportent plusieurs tests, dont une partie demande un jugement humain.

Le rattachement est indicatif. En cas de doute, le texte officiel du référentiel fait foi : <https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/>.

Les descriptions ci-dessous sont des résumés, pas le texte des critères.

## Couverture

**25 critères touchés sur 106**, dans 11 des 13 thématiques. Aucun critère n'est vérifié en entier.

« Touché » veut dire qu'au moins un défaut relevant du critère est détecté. Trois niveaux précisent jusqu'où va le contrôle :

- **large** : l'outil vérifie l'essentiel de ce qu'un programme peut vérifier pour ce critère. Il reste un jugement humain sur la pertinence.
- **partielle** : l'outil vérifie certains cas seulement (certaines balises, certaines fautes).
- **minime** : l'outil n'attrape qu'un cas particulier du critère.

### Par thématique

| Thématique | Critères du RGAA | Touchés | Dont en erreur |
|---|---|---|---|
| 1. Images | 9 | 3 | 2 |
| 2. Cadres | 2 | 1 | 0 |
| 3. Couleurs | 3 | 0 | 0 |
| 4. Multimédia | 13 | 0 | 0 |
| 5. Tableaux | 8 | 3 | 3 |
| 6. Liens | 2 | 2 | 2 |
| 7. Scripts | 5 | 1 | 0 |
| 8. Éléments obligatoires | 10 | 7 | 5 |
| 9. Structuration de l'information | 4 | 2 | 2 |
| 10. Présentation de l'information | 14 | 2 | 1 |
| 11. Formulaires | 13 | 2 | 1 |
| 12. Navigation | 11 | 1 | 1 |
| 13. Consultation | 12 | 1 | 1 |
| **Total** | **106** | **25** | **18** |

Les 7 critères qui ne donnent qu'un avertissement ne font pas échouer le contrôle, sauf avec `--fail-on-warning`.

### Par critère

| Critère | Règle | Niveau | Gravité | Limite principale |
|---|---|---|---|---|
| 1.1 | `images` | partielle | erreur | seulement les `<img>` |
| 1.2 | `images` | partielle | avertissement | signale un `alt` vide, sans savoir si l'image est décorative |
| 1.3 | `images` | partielle | erreur | seulement les textes passe-partout |
| 2.1 | `cadres` | large | avertissement | — |
| 5.4 | `tableaux` | large | erreur | — |
| 5.6 | `tableaux` | partielle | erreur | vérifie qu'il y a au moins un `<th>` |
| 5.7 | `tableaux` | partielle | erreur | vérifie la présence de `scope` ou `id`, pas l'association |
| 6.1 | `liens` | partielle | erreur | seulement une liste de formules vagues |
| 6.2 | `liens` | large | erreur | — |
| 7.1 | `aria` | minime | avertissement | seulement les références ARIA vers un identifiant absent |
| 8.1 | `structure` | large | erreur | — |
| 8.2 | `structure` | partielle | erreur | erreurs d'analyse, identifiants en double, blocs mal imbriqués |
| 8.3 | `structure` | large | erreur | — |
| 8.4 | `langue` | partielle | avertissement | vérifie la forme du code, pas qu'il désigne la bonne langue |
| 8.5 | `structure` | large | erreur | — |
| 8.6 | `structure` | partielle | erreur | balise dans le titre, titres en double |
| 8.7 | `langue` | minime | avertissement | seulement l'anglais dans une page en français, par approximation |
| 9.1 | `titres` | partielle | erreur | seulement les balises `<h1>` à `<h6>` |
| 9.2 | `structure` | partielle | erreur | présence des zones, pas leur contenu |
| 10.1 | `couleurs` | minime | erreur | seulement les couleurs dans un attribut `style` |
| 10.4 | `zoom` | minime | avertissement | seulement le blocage par la balise viewport |
| 11.1 | `formulaires`, `aria` | partielle | avertissement | présence d'une étiquette, pas sa position ni sa pertinence |
| 11.9 | `boutons` | partielle | erreur | seulement les `<button>` |
| 12.7 | `structure` | partielle | erreur | présence du lien d'évitement, pas son fonctionnement |
| 13.2 | `liens` | partielle | erreur | seulement `target="_blank"` |

Soit 6 critères couverts largement, 15 partiellement et 4 de façon minime. Ce tableau est comparé au code par un test : un critère ajouté dans une règle sans être déclaré ici fait échouer les tests.

Le nombre de critères par thématique et le rattachement de chaque contrôle à un critère ont été établis de mémoire du RGAA 4.1. Ils sont à confirmer dans le référentiel avant d'être cités dans un rapport.

## Vue d'ensemble par règle

| Règle | Critères touchés | Gravité |
|---|---|---|
| `images` | 1.1, 1.2, 1.3 | erreur, sauf `alt` vide : avertissement |
| `tableaux` | 5.4, 5.6, 5.7 | erreur |
| `liens` | 6.1, 6.2, 13.2 | erreur |
| `structure` | 8.1, 8.2, 8.3, 8.5, 8.6, 9.2, 12.7 | erreur |
| `couleurs` | 10.1 | erreur |
| `langue` | 8.7 | avertissement |
| `titres` | 9.1 | erreur |
| `boutons` | 11.9 | erreur |
| `cadres` | 2.1 | avertissement (en observation) |
| `formulaires` | 11.1 | avertissement (en observation) |
| `zoom` | 10.4 | avertissement (en observation) |
| `aria` | 7.1, 11.1 | avertissement (en observation) |

La règle `langue` contrôle aussi le critère 8.4 (en observation).

Une règle **en observation** vient d'être ajoutée : elle donne des avertissements, le temps de vérifier sur des pages réelles qu'elle ne se trompe pas. Les numéros de critères de ces règles, comme ceux des autres, sont à confirmer dans le référentiel avant de s'en servir dans un rapport.

## Détail par règle

### `images` — thématique 1

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 1.1 | Chaque `<img>` a un attribut `alt` | Les autres images porteuses d'information : `<svg>`, `<canvas>`, `<object>`, images en CSS |
| 1.2 | Un `alt` vide donne un avertissement | Que l'image est réellement décorative |
| 1.3 | Le `alt` n'est pas un mot passe-partout (« image », « photo 2 », « figure 1 ») | Que le texte décrit bien l'image ; description détaillée des images complexes |

### `tableaux` — thématique 5

Un tableau portant `role="presentation"` ou `role="none"` est considéré comme un tableau de mise en forme et ignoré.

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 5.4 | Chaque tableau de données a un `<caption>` non vide | Que la légende est pertinente |
| 5.6 | Le tableau a au moins une cellule `<th>` | Que toutes les cellules d'en-tête sont bien des `<th>` |
| 5.7 | Chaque `<th>` a un attribut `scope` ou un `id` | Que l'association en-tête/cellule est correcte, surtout dans un tableau complexe |

### `liens` — thématiques 6 et 13

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 6.1 | L'intitulé n'est pas une formule vague (« cliquez ici », « lire la suite », « en savoir plus ») ; un `aria-label` reprend le texte visible | Que l'intitulé se comprend dans son contexte |
| 6.2 | Chaque lien a un intitulé (texte, `aria-label` ou `alt` d'une image) et un attribut `href` | — |
| 13.2 | Un lien `target="_blank"` annonce l'ouverture d'une nouvelle fenêtre ou d'un nouvel onglet, en français ou en anglais | Les ouvertures déclenchées par un script |

### `structure` — thématiques 8, 9 et 12

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 8.1 | La page commence par `<!doctype html>` | — |
| 8.2 | Pas d'erreur d'analyse HTML5, pas d'`id` en double, pas de bloc (`<div>`, `<ul>`, `<pre>`…) dans un `<p>` ou un `<span>` | Une validation complète avec le validateur du W3C |
| 8.3 | `<html>` porte un attribut `lang` | Que le code de langue est le bon (8.4) |
| 8.5 | La page a un `<title>` non vide | — |
| 8.6 | Le `<title>` ne contient pas de balise ; deux pages du dossier n'ont pas le même titre | Que le titre décrit bien la page |
| 9.2 | Les zones `<header>`, `<nav>`, `<main>` et `<footer>` sont présentes, avec un seul `<main>` | Que chaque zone contient ce qu'elle doit contenir |
| 12.7 | Un lien d'ancre mène à l'élément `<main>` (lien d'évitement) | Que ce lien est le premier de la page, visible au focus, et qu'il fonctionne |

Le contrôle des zones suppose une page complète. Sur un fragment HTML, retirer cette règle.

### `couleurs` — thématique 10

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 10.1 | Aucune couleur n'est écrite dans un attribut `style` | Toute la présentation passe par les feuilles de style ; contrastes réels (3.2, 3.3) |

Cette règle sert surtout de garde-fou : des couleurs gardées dans des classes CSS peuvent être validées une fois avec `Contrast`.

### `langue` — thématique 8

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 8.4 (en observation) | Chaque attribut `lang` a la forme d'un code de langue : `fr`, `en`, `fr-FR`, `zh-Hans` | Que le code désigne la bonne langue |
| 8.7 | Dans une page en français, un passage qui semble être en anglais et ne porte pas `lang="en"` donne un avertissement | Les autres langues ; les faux positifs et les passages non détectés |

La détection repose sur des mots anglais très courants et sur l'absence de lettres accentuées. Le code (`<pre>`, `<code>`) est ignoré. Sur une page qui n'est pas déclarée en français, la règle ne fait rien.

### `titres` — thématique 9

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 9.1 | Exactement un `<h1>` ; pas de niveau sauté (un `<h3>` ne suit pas directement un `<h1>`) ; pas de titre vide | Que les titres décrivent le contenu ; les titres réalisés avec `role="heading"` |

Le RGAA n'impose pas un `<h1>` unique. C'est un choix de cet outil, plus strict que le référentiel, parce qu'il rend la structure plus simple à parcourir.

### `boutons` — thématique 11

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 11.9 | Chaque `<button>` a un nom (texte ou `aria-label`) et un contenu à afficher | Que l'intitulé est pertinent ; les boutons réalisés avec `<input>` ou `role="button"` |

Le second contrôle attrape un cas précis : un bouton de menu dont l'icône est un `<span>` vide stylé en CSS, supprimé par un nettoyage du HTML. Le bouton garde son `aria-label` mais devient invisible.

## Règles en observation

### `cadres` — thématique 2

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 2.1 | Chaque `<iframe>` et chaque `<frame>` a un attribut `title` non vide | Que le titre décrit le contenu du cadre (2.2) |

### `formulaires` — thématique 11

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 11.1 | Chaque `<input>`, `<select>` et `<textarea>` a une étiquette : `<label for>` non vide, `<label>` englobant, `aria-label`, `aria-labelledby` vers un identifiant existant, ou `title` | Que l'étiquette est pertinente (11.2) et accolée au champ ; regroupements, champs obligatoires, messages d'erreur |

Les champs `hidden`, `submit`, `reset`, `button` et `image` ne sont pas concernés. Un champ étiqueté seulement par un `placeholder` est signalé : un `placeholder` disparaît à la saisie et n'est pas une étiquette.

### `zoom` — thématique 10

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 10.4 | La balise `<meta name="viewport">` n'interdit pas le zoom (`user-scalable=no`) et ne le limite pas en dessous de 200 % (`maximum-scale`) | Que la page reste lisible et utilisable à 200 %, dans un navigateur |

### `aria` — thématiques 7 et 11

| Critère | Ce qui est contrôlé | Ce qui reste à vérifier à la main |
|---|---|---|
| 7.1 | Chaque identifiant cité par `aria-labelledby` ou `aria-describedby` existe dans la page | Tout le reste de l'usage d'ARIA : rôles, états, composants pilotés par script |
| 11.1 | Chaque `<label for>` vise un identifiant qui existe | — |

Le rattachement des références ARIA au critère 7.1 est celui qui m'a paru le plus proche ; il est à confirmer.
