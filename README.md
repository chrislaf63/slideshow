# Diaporama

Application web légère : un diaporama d'images plein écran + un back-office
minimaliste pour ajouter, supprimer et réordonner les slides. Supporte les
formats image courants et les PDF (convertis en images, une par page).

Stack : PHP 8.3 + Apache, JavaScript vanilla, SortableJS pour le tri.
Aucune base de données : l'ordre des slides est stocké dans `slides.json`.

## Arborescence

```
slideshow/
├── Dockerfile               # image php:8.3-apache + poppler-utils
├── docker-compose.yml       # service + volumes de persistance
├── .env.example             # variables (mot de passe admin)
├── docker/
│   ├── entrypoint.sh        # corrige les permissions des volumes au démarrage
│   └── uploads-no-exec.conf # interdit l'exécution de scripts dans uploads/
├── data/                    # DONNÉES PERSISTANTES (montées en volume)
│   ├── slides.json          # liste ordonnée des slides (init: [])
│   └── uploads/             # fichiers uploadés + images générées des PDF
└── src/                     # CODE (vit dans l'image)
    ├── config.php           # chemins, mot de passe, limites, types autorisés
    ├── index.php            # le diaporama public
    ├── admin.php            # le back-office
    ├── api/
    │   ├── upload.php       # réception des fichiers
    │   ├── delete.php       # suppression d'une slide
    │   └── save_order.php   # enregistrement du nouvel ordre
    ├── lib/
    │   ├── store.php        # lecture/écriture de slides.json
    │   ├── pdf.php          # conversion PDF -> images (pdftoppm)
    │   └── auth.php         # protection par mot de passe du back-office
    └── assets/
        ├── css/             # styles diaporama + admin
        └── js/              # logique diaporama + admin
```

Séparation clé : `src/` (le code) est immuable et reconstruit avec l'image ;
`data/` (les données) vit dans un volume et survit aux reconstructions.

## Démarrage

1. Copier le fichier d'environnement et choisir un mot de passe :
   ```
   cp .env.example .env
   # éditer .env -> ADMIN_PASSWORD
   ```
   Puis ajouter `env_file: .env` au service dans `docker-compose.yml`, ou
   exporter la variable dans l'environnement.

2. Vérifier que `data/slides.json` existe et contient bien `[]`.
   (Docker exige que le fichier existe AVANT le montage, sinon il crée un
   dossier à la place.)

3. Construire et lancer :
   ```
   docker compose up --build -d
   ```

4. Ouvrir :
   - Diaporama : http://localhost:8080/
   - Back-office : http://localhost:8080/admin.php

## Back-office

Accessible sur `/admin.php`, protégé par mot de passe (`ADMIN_PASSWORD`).
Il permet de :
- déposer des fichiers (clic ou glisser-déposer), images et PDF ;
  les PDF sont convertis en une slide par page ;
- supprimer une slide (fichier effacé du disque) ;
- réordonner par glisser-déposer (SortableJS), avec enregistrement automatique.

Validation du type réel des fichiers (via `finfo`, pas l'extension), limite de
taille et de pages PDF définies dans `src/config.php`.

> SortableJS est chargé depuis un CDN. Pour un fonctionnement 100 % hors-ligne,
> télécharger `Sortable.min.js` dans `src/assets/js/` et ajuster le `<script>`
> de `admin.php`.

## Diaporama public

Accessible sur `/` (`index.php`). Affichage plein écran sur scène sombre :
- fondu enchaîné entre les slides (deux couches alternées) ;
- autoplay avec barre de progression ; durée et fondu réglables dans
  `src/config.php` (`SLIDE_INTERVAL_MS`, `SLIDE_FADE_MS`) ;
- préchargement de la slide suivante ;
- contrôles auto-masqués après inactivité (précédent / lecture-pause /
  suivant / plein écran) ;
- navigation clavier : ← → (naviguer), Espace (pause), F (plein écran) ;
- `object-fit: contain` : tout format affiché en entier, sans recadrage ;
- pause automatique quand l'onglet n'est pas visible.

## État

- [x] Structure du projet + configuration Docker
- [x] Back-office (auth, upload, conversion PDF, suppression, tri)
- [x] Diaporama public (fondu, autoplay, contrôles, clavier, plein écran)

Le projet est fonctionnellement complet.
