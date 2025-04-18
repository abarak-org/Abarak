<?php
declare(strict_types=1);

/**
 * Sécurise une chaîne pour affichage HTML.
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sépare la requête en [ville, terme].
 */
function parseQuery(string $query): array
{
    $parts = preg_split('/\s+/', $query, 2, PREG_SPLIT_NO_EMPTY);
    if (count($parts) === 2) {
        return [$parts[0], $parts[1]];
    }
    return ['', $parts[0] ?? ''];
}

// Récupération et sanitation de la requête
$q = isset($_GET['q']) ? sanitize((string) $_GET['q']) : '';
list($city, $term) = parseQuery($q);

// Jeux de données statiques pour la démo
$stores = [
    ['name' => 'Commerce de Proximité A', 'distance' => '1,2 km'],
    ['name' => 'Magasin Local B', 'distance' => '2,5 km'],
    ['name' => 'Boutique C', 'distance' => '3,0 km'],
];
?><!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Résultats pour « <?= $term ?>» à <?= $city ?: 'votre localisation' ?></title>
    <link href="public/css/abarak_com_styles.css" rel="stylesheet" />
    <style>
        /* Ajustements éventuels pour la page de résultats */
        .results {
            list-style: none;
            padding: 0;
        }

        .results li {
            margin: 1em 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.5em;
        }
    </style>
</head>

<body class="front-page">
    <div class="container">
        <div class="text-container">
            <h1 class="title">ABARAK Demo</h1>
            <p class="subtitle">Résultats pour « <?= $term ?>» à <?= $city ?: 'votre localisation' ?></p>
        </div>
    </div>

    <ul class="results">
        <?php foreach ($stores as $store): ?>
            <li>
                <strong><?= sanitize($store['name']) ?></strong><br />
                Distance : <?= sanitize($store['distance']) ?><br />
                <!-- ici on pourrait ajouter avis, disponibilité, etc. -->
            </li>
        <?php endforeach; ?>
    </ul>

    <div style="text-align:center; margin-top:2em;">
        <a href="/" class="engine-button">← Nouvelle recherche</a>
    </div>
</body>

</html>