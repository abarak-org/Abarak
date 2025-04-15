<?php

header('Content-Type: application/json');

// Vérifier que la méthode utilisée est POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit;
}

// Définir le répertoire de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
    error_log("Erreur: Impossible de créer le dossier de logs.");
    http_response_code(500);
    echo json_encode(["error" => "Erreur interne"]);
    exit;
}

$logFile = $logDir . '/tracking_' . date('Y-m-d') . '.log';
if (!file_exists($logFile)) {
    if (touch($logFile) === false) {
        error_log("Erreur: Impossible de créer le fichier de log.");
        http_response_code(500);
        echo json_encode(["error" => "Erreur interne"]);
        exit;
    }
    chmod($logFile, 0644);
}

// Récupérer les données brutes envoyées en POST
$data = file_get_contents('php://input');
if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Aucune donnée reçue"]);
    exit;
}

// Décoder le JSON envoyé
$json = json_decode($data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "JSON invalide"]);
    exit;
}

// Assainir les informations principales
$event = isset($json['event']) ? filter_var($json['event'], FILTER_SANITIZE_STRING) : 'unknown';
$visitor_id = isset($json['visitor_id']) ? filter_var($json['visitor_id'], FILTER_SANITIZE_STRING) : 'unknown';
// Utilisation de l'heure du serveur pour l'horodatage
$serverTimestamp = date('c');

// Extra data : retirer les champs déjà utilisés
$extra = $json;
unset($extra['event'], $extra['visitor_id'], $extra['timestamp']);
$extraData = json_encode($extra, JSON_UNESCAPED_UNICODE);

// Récupérer l'adresse IP et le User-Agent du client
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Implémenter une limitation de fréquence (rate limiting) avec APCu, si disponible
if (function_exists('apcu_fetch') && function_exists('apcu_store')) {
    $rateLimitKey = 'tracker_' . $visitor_id;
    $lastEventTime = apcu_fetch($rateLimitKey);
    $currentTime = microtime(true);
    if ($lastEventTime !== false && ($currentTime - $lastEventTime < 1)) {
        http_response_code(429);
        echo json_encode(["error" => "Trop de requêtes. Veuillez patienter."]);
        exit;
    }
    apcu_store($rateLimitKey, $currentTime, 10);
}

$logLine = sprintf(
    "[%s] IP: %s, UA: %s, Visitor: %s, Event: %s, Data: %s\n",
    $serverTimestamp,
    $clientIP,
    $userAgent,
    $visitor_id,
    $event,
    $extraData
);

// Écriture dans le fichier log avec gestion du verrouillage pour éviter les conflits concurrents
if (file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
    error_log("Erreur lors de l'écriture du log dans tracker.php.");
    http_response_code(500);
    echo json_encode(["error" => "Erreur interne"]);
    exit;
}

http_response_code(200);
echo json_encode(["message" => "Événement enregistré"]);
?>