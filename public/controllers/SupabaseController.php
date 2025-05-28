<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// S'assurer qu'aucune sortie n'est envoyée avant les en-têtes
ob_start();

// Définir un gestionnaire d'erreurs personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erreur PHP [$errno] $errstr dans $errfile à la ligne $errline");
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

// Charger les variables d'environnement
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    logError('Fichier .env trouvé');
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    logError('Variables d\'environnement chargées', [
        'SUPABASE_URL' => getenv('SUPABASE_URL') ? 'défini' : 'non défini',
        'SUPABASE_KEY' => getenv('SUPABASE_KEY') ? 'défini' : 'non défini'
    ]);
} else {
    logError('Fichier .env non trouvé');
}

/**
 * Contrôleur pour gérer les interactions avec Supabase
 * 
 * Ce contrôleur gère :
 * - La connexion à la base de données Supabase
 * - La récupération des statistiques
 * - Le suivi des mises à jour
 * - La gestion des erreurs
 */
class SupabaseController {
    private $supabaseUrl;
    private $supabaseKey;

    /**
     * Constructeur du contrôleur
     * 
     * Initialise la connexion à Supabase en utilisant les variables d'environnement
     * @throws Exception si les variables d'environnement sont manquantes
     */
    public function __construct() {
        try {
            logError('Initialisation du contrôleur Supabase');
            
            $this->supabaseUrl = getenv('SUPABASE_URL');
            $this->supabaseKey = getenv('SUPABASE_KEY');
            
            if (!$this->supabaseUrl || !$this->supabaseKey) {
                throw new Exception('Variables d\'environnement Supabase manquantes');
            }
            
            logError('Configuration Supabase chargée', [
                'url' => $this->supabaseUrl,
                'key' => substr($this->supabaseKey, 0, 10) . '...'
            ]);
        } catch (Exception $e) {
            logError('Erreur dans le constructeur', ['error' => $e->getMessage()]);
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Envoie une réponse JSON au client
     * 
     * @param array $data Les données à envoyer
     * @throws Exception si l'encodage JSON échoue
     */
    private function sendJsonResponse($data) {
        try {
            // Nettoyer toute sortie précédente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Définir les en-têtes
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('X-Content-Type-Options: nosniff');
            
            // Envoyer la réponse
            $jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($jsonResponse === false) {
                throw new Exception('Erreur lors de l\'encodage JSON: ' . json_last_error_msg());
            }
            echo $jsonResponse;
            exit;
        } catch (Exception $e) {
            logError('Erreur lors de l\'envoi de la réponse JSON', ['error' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi de la réponse: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Effectue une requête à l'API Supabase
     * 
     * @param string $endpoint L'endpoint à appeler
     * @param string $method La méthode HTTP (GET, POST, etc.)
     * @param array|null $data Les données à envoyer
     * @return object La réponse de l'API
     * @throws Exception si la requête échoue
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = rtrim($this->supabaseUrl, '/') . '/rest/v1/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erreur cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception('Erreur HTTP ' . $httpCode . ': ' . $response);
        }
        
        return json_decode($response);
    }

    /**
     * Gère les requêtes entrantes
     * 
     * Route les requêtes vers les bonnes méthodes en fonction de l'action demandée
     * @throws Exception si l'action n'est pas valide
     */
    public function handleRequest() {
        try {
            logError('Début du traitement de la requête Supabase');
            
            // Nettoyer toute sortie précédente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Vérifier la méthode HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Méthode non autorisée. Utilisez GET.');
            }
            
            $action = $_GET['action'] ?? '';
            logError('Action demandée', ['action' => $action]);

            switch ($action) {
                case 'status':
                    $this->getStatus();
                    break;
                default:
                    $this->sendJsonResponse(['success' => false, 'error' => 'Action non valide']);
            }
        } catch (Exception $e) {
            logError('Erreur Supabase', ['error' => $e->getMessage()]);
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Récupère le statut actuel de la base de données
     * 
     * Retourne :
     * - Le nombre total de sets
     * - Le nombre total de thèmes
     * - La date de dernière mise à jour
     * @throws Exception si la récupération des données échoue
     */
    private function getStatus() {
        try {
            logError('Début de getStatus');

            // Récupérer les statistiques de la base de données
            $setsResponse = $this->makeRequest('sets?select=count');
            logError('Réponse sets', ['response' => json_encode($setsResponse)]);
            $totalSets = $setsResponse[0]->count ?? 0;

            $themesResponse = $this->makeRequest('themes?select=count');
            logError('Réponse themes', ['response' => json_encode($themesResponse)]);
            $totalThemes = $themesResponse[0]->count ?? 0;

            // Récupérer la dernière mise à jour depuis la table database_updates
            $lastUpdateResponse = $this->makeRequest('database_updates?select=update_date,update_type,description&order=update_date.desc&limit=1');
            logError('Réponse last update', ['response' => json_encode($lastUpdateResponse)]);
            
            $lastUpdate = null;
            if (!empty($lastUpdateResponse) && isset($lastUpdateResponse[0]->update_date)) {
                $lastUpdate = $lastUpdateResponse[0]->update_date;
                logError('Date de mise à jour trouvée', [
                    'date' => $lastUpdate,
                    'type' => $lastUpdateResponse[0]->update_type,
                    'description' => $lastUpdateResponse[0]->description
                ]);
            } else {
                logError('Aucune date de mise à jour trouvée');
            }

            $response = [
                'success' => true,
                'stats' => [
                    'totalSets' => $totalSets,
                    'totalThemes' => $totalThemes,
                    'lastUpdate' => $lastUpdate
                ]
            ];

            logError('Réponse finale', ['response' => json_encode($response)]);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            logError('Erreur lors de la récupération du statut Supabase', ['error' => $e->getMessage()]);
            $this->sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Initialiser et exécuter le contrôleur
try {
    $controller = new SupabaseController();
    $controller->handleRequest();
} catch (Exception $e) {
    logError('Erreur fatale', ['error' => $e->getMessage()]);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur fatale: ' . $e->getMessage()]);
    exit;
} 