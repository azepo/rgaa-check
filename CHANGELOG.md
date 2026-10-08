# Journal des versions

## Non publié

### Ajouté

- Options `--only` et `--skip` pour choisir les règles ; méthodes `Report::only()` et `Report::without()`.
- Fichier de référence des défauts connus : options `--baseline` et `--generate-baseline`, classe `Baseline`. Permet d'adopter l'outil sur un site existant sans tout corriger d'abord.
- Cinq contrôles en observation, qui donnent des avertissements :
  - `cadres` : cadre sans `title` (RGAA 2.1) ;
  - `formulaires` : champ sans étiquette (11.1) ;
  - `zoom` : zoom interdit ou limité par la balise viewport (10.4) ;
  - `aria` : `aria-labelledby`, `aria-describedby` ou `<label for>` vers un identifiant absent (7.1, 11.1) ;
  - `langue` : code de langue invalide (8.4).
- Le rapport JSON contient le nombre de défauts ignorés (`ignored`).

## 0.1.0 — extraction

Première version, extraite du générateur du site allpeople.site.

- Sept règles : `images`, `titres`, `liens`, `structure` (et `couleurs`), `tableaux`, `boutons`, `langue`.
- Chaque défaut porte le numéro du critère RGAA 4.1 auquel il se rattache.
- Exécutable `rgaa-check`, rapports texte et JSON, codes de sortie 0, 1 et 2.
- Calcul de contraste (`Contrast`).

Par rapport au code d'origine : un tableau `role="presentation"` est ignoré, l'annonce d'un nouvel onglet est acceptée en anglais, la détection des passages en anglais ne s'applique qu'aux pages en français, et les sous-dossiers sont contrôlés par défaut.
