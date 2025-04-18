<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------------
 * Abarak • Demo search endpoint
 * ------------------------------------------------------------------
 * - Analyse la requête [ville, terme] transmise via /search?q=…
 * - Filtre un jeu de données statique (pilotage démo) dépendant de
 *   la ville et du terme.
 * - Calcule la distance magasin ←→ centre‑ville (Haversine) pour
 *   illustrer l’hyper‑proximité.
 * - Affiche les résultats triés par distance croissante.
 * ------------------------------------------------------------------
 */

/* ---------- Helpers ------------------------------------------------ */

/** Sécurise une chaîne pour l’affichage HTML */
function esc(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF‑8');
}

/** Distance de Haversine en kilomètres, arrondie à 100 m près */
function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371; // Rayon terrestre en km
    $dlon = deg2rad($lon2 - $lon1);
    $dlat = deg2rad($lat2 - $lat1);

    $a = sin($dlat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon / 2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($r * $c, 1); // 0,1 km près
}

/** Coupe la requête en {ville, terme complet} */
function splitQuery(string $q): array
{
    $parts = preg_split('/\s+/', $q, 2, PREG_SPLIT_NO_EMPTY);
    return count($parts) === 2 ? [$parts[0], $parts[1]] : ['', $parts[0] ?? ''];
}

/* ---------- Jeu de données démo ----------------------------------- */

/**
 * $cities : coordonnées GPS simplifiées du centre‑ville
 * $stores : magasins disponibles pour chaque ville + liste de termes
 *           auxquels ils répondent (sémantique multi‑produits)
 */
$cities = [
    'Gaillon' => ['lat' => 49.155, 'lon' => 1.205],
    'Boulogne-Billancourt' => ['lat' => 48.835, 'lon' => 2.241],
    'Paris' => ['lat' => 48.857, 'lon' => 2.352],
];

$stores = [
    'Gaillon' => [
        [
            'name' => 'Auto Batterie Gaillon',
            'lat' => 49.152,
            'lon' => 1.210,
            'terms' => ['Batterie de voiture', 'Batterie d’ordinateur', 'Batterie de téléphone']
        ],
        [
            'name' => 'Boucherie du Centre',
            'lat' => 49.157,
            'lon' => 1.203,
            'terms' => ['Entrecôte']
        ],
        [
            'name' => 'Librairie La Carte',
            'lat' => 49.158,
            'lon' => 1.200,
            'terms' => ['Carte postale', 'Carte à jouer', 'Carte routière']
        ],
    ],
    'Boulogne-Billancourt' => [
        [
            'name' => 'Boulogne Batteries',
            'lat' => 48.834,
            'lon' => 2.241,
            'terms' => ['Batterie de voiture', 'Batterie (instrument de musique)']
        ],
        [
            'name' => 'Viandes d’Île‑de‑France',
            'lat' => 48.836,
            'lon' => 2.238,
            'terms' => ['Entrecôte']
        ],
        [
            'name' => 'Cartes & Co.',
            'lat' => 48.833,
            'lon' => 2.245,
            'terms' => ['Carte à puce', 'Carte bristol']
        ],
    ],
    'Paris' => [
        [
            'name' => 'Batteries Bastille',
            'lat' => 48.853,
            'lon' => 2.369,
            'terms' => ['Batterie de téléphone', 'Batterie (de cuisine)']
        ],
        [
            'name' => 'La Grande Boucherie',
            'lat' => 48.866,
            'lon' => 2.333,
            'terms' => ['Entrecôte']
        ],
        [
            'name' => 'Maison des Cartes',
            'lat' => 48.861,
            'lon' => 2.347,
            'terms' => ['Carte à jouer', 'Carte routière', 'Carte postale']
        ],
    ],
];

/* ---------- Traitement de la requête ------------------------------ */

$q = isset($_GET['q']) ? esc($_GET['q']) : '';
[$city, $term] = splitQuery($q);
$cityKey = array_key_exists($city, $cities) ? $city : ''; // ville valide ?
$results = [];

if ($cityKey !== '') {
    $origin = $cities[$cityKey];
    foreach ($stores[$cityKey] as $store) {
        // correspondance large, insensible à la casse / aux accents
        foreach ($store['terms'] as $t) {
            if (
                stripos(
                    iconv('UTF-8', 'ASCII//TRANSLIT', $t),
                    iconv('UTF-8', 'ASCII//TRANSLIT', $term)
                ) !== false
            ) {
                $distance = haversine(
                    $origin['lat'],
                    $origin['lon'],
                    $store['lat'],
                    $store['lon']
                );
                $results[] = array_merge($store, ['distance' => $distance]);
                break;
            }
        }
    }
    // Tri croissant par distance
    usort($results, fn($a, $b) => $a['distance'] <=> $b['distance']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats – <?= esc($term) ?> à <?= $cityKey ?: 'localisation inconnue' ?></title>
    <link href="public/css/abarak_com_styles.css" rel="stylesheet">
    <style>
        .results {
            list-style: none;
            padding: 0
        }

        .results li {
            margin: 1.2em 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: .7em
        }

        .badge {
            background: #0073b6;
            color: #fff;
            border-radius: 4px;
            padding: .1em .4em;
            font-size: .8em
        }
    </style>
</head>

<body class="front-page">
    <div class="container">
        <div class="text-container">
            <h1 class="title">ABARAK&nbsp;Demo</h1>
            <p class="subtitle">
                <?= $results ? 'Résultats pour'
                    : 'Aucun résultat pour' ?>
                «&nbsp;<?= esc($term) ?>&nbsp;» à
                <?= $cityKey ?: 'votre localisation' ?>
            </p>
        </div>
    </div>

    <ul class="results">
        <?php foreach ($results as $store): ?>
            <li>
                <strong><?= esc($store['name']) ?></strong>
                <span class="badge"><?= number_format($store['distance'], 1, ',', ' ') ?>km</span><br>
                <em><?= esc(implode(', ', $store['terms'])) ?></em>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$results): ?>
        <p style="text-align:center">Essayez un autre terme ou une autre ville.</p>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2em">
        <a href="/" class="engine-button">← Nouvelle recherche</a>
    </div>
</body>

</html>