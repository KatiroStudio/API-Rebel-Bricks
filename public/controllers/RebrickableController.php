<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// S'assurer qu'aucune sortie n'est envoyée avant les en-têtes
ob_start();

// Charger les variables d'environnement
require_once __DIR__ . '/../../config/env.php';

class RebrickableController {
    private $config;
    private $baseUrl;
    private $apiKey;

    public function __construct() {
        try {
            $this->config = require_once __DIR__ . '/../../config/rebrickable.php';
            $this->baseUrl = $this->config['base_url'];
            $this->apiKey = $this->config['api_key'];
            
            if (empty($this->apiKey)) {
                throw new Exception('Clé API Rebrickable manquante');
            }
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function sendJsonResponse($data) {
        // Nettoyer toute sortie précédente
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Définir les en-têtes
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        
        // Envoyer la réponse
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function makeRequest($endpoint) {
        try {
            $url = $this->baseUrl . $endpoint;
            error_log('URL Rebrickable: ' . $url);
            error_log('Clé API utilisée: ' . $this->apiKey);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: key ' . $this->apiKey,
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log('Code HTTP Rebrickable: ' . $httpCode);
            error_log('Réponse Rebrickable: ' . $response);
            
            if (curl_errno($ch)) {
                throw new Exception('Erreur cURL: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode === 401) {
                throw new Exception('Clé API Rebrickable invalide');
            } elseif ($httpCode === 429) {
                throw new Exception('Trop de requêtes. Veuillez attendre quelques secondes.');
            } elseif ($httpCode !== 200) {
                throw new Exception('Erreur HTTP: ' . $httpCode);
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            error_log('Erreur Rebrickable: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getThemes() {
        $result = $this->makeRequest($this->config['endpoints']['themes']);
        $this->sendJsonResponse($result);
    }

    public function getSets($themeId = null) {
        $endpoint = $this->config['endpoints']['sets'];
        if ($themeId) {
            $endpoint .= '?theme_id=' . urlencode($themeId);
        }
        $result = $this->makeRequest($endpoint);
        $this->sendJsonResponse($result);
    }

    public function getColors() {
        $result = $this->makeRequest($this->config['endpoints']['colors']);
        $this->sendJsonResponse($result);
    }

    public function getParts() {
        $result = $this->makeRequest($this->config['endpoints']['parts']);
        $this->sendJsonResponse($result);
    }

    public function getMinifigs() {
        $result = $this->makeRequest($this->config['endpoints']['minifigs']);
        $this->sendJsonResponse($result);
    }

    public function getSetsStats() {
        try {
            // Faire un appel avec page_size=1 et ordering pour obtenir les métadonnées et les années
            $endpoint = $this->config['endpoints']['sets'] . '?page_size=1&ordering=year';
            error_log('Endpoint Sets Stats: ' . $endpoint);
            
            $result = $this->makeRequest($endpoint);
            error_log('Résultat Sets Stats: ' . json_encode($result));
            
            if ($result['success'] && isset($result['data']['count'])) {
                // Faire un appel pour obtenir le set le plus ancien
                $oldestEndpoint = $this->config['endpoints']['sets'] . '?page_size=1&ordering=year';
                $oldestResult = $this->makeRequest($oldestEndpoint);
                
                // Faire un appel pour obtenir le set le plus récent
                $newestEndpoint = $this->config['endpoints']['sets'] . '?page_size=1&ordering=-year';
                $newestResult = $this->makeRequest($newestEndpoint);
                
                $oldestYear = $oldestResult['success'] && isset($oldestResult['data']['results'][0]['year']) 
                    ? $oldestResult['data']['results'][0]['year'] 
                    : null;
                    
                $newestYear = $newestResult['success'] && isset($newestResult['data']['results'][0]['year']) 
                    ? $newestResult['data']['results'][0]['year'] 
                    : null;
                
                $this->sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'totalSets' => $result['data']['count'],
                        'oldestYear' => $oldestYear,
                        'newestYear' => $newestYear
                    ]
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'error' => 'Impossible de récupérer le nombre total de sets'
                ]);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Gérer la requête
try {
    // Nettoyer toute sortie précédente
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée. Utilisez GET.');
    }
    
    $controller = new RebrickableController();
    $action = $_GET['action'] ?? 'themes';

    switch ($action) {
        case 'themes':
            $controller->getThemes();
            break;
        case 'sets':
            $themeId = $_GET['theme_id'] ?? null;
            $controller->getSets($themeId);
            break;
        case 'sets_stats':
            $controller->getSetsStats();
            break;
        case 'colors':
            $controller->getColors();
            break;
        case 'parts':
            $controller->getParts();
            break;
        case 'minifigs':
            $controller->getMinifigs();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
} catch (Exception $e) {
    error_log('Erreur dans le gestionnaire de requête: ' . $e->getMessage());
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} 