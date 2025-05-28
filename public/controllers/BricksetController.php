<?php

// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// S'assurer qu'aucune sortie n'est envoyée avant les en-têtes
ob_start();

// Charger les variables d'environnement
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Vérifier si c'est une requête AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // C'est une requête AJAX
} else {
    // Rediriger vers la page d'accueil si ce n'est pas une requête AJAX
    header('Location: /');
    exit;
}

class BricksetController {
    private $config;
    private $baseUrl;
    private $apiKey;
    private $username;
    private $password;
    private $userHash;

    public function __construct() {
        try {
            $this->config = require __DIR__ . '/../../config/brickset.php';
            $this->baseUrl = 'https://brickset.com/api/v3.asmx';
            $this->apiKey = $this->config['api_key'];
            $this->username = $this->config['username'];
            $this->password = $this->config['password'];
            
            // Log des valeurs de configuration
            error_log('Configuration Brickset:');
            error_log('API Key: ' . ($this->apiKey ? 'présente' : 'manquante'));
            error_log('Username: ' . ($this->username ? 'présent' : 'manquant'));
            error_log('Password: ' . ($this->password ? 'présent' : 'manquant'));
            
            // Vérifier que les paramètres sont présents
            if (empty($this->apiKey) || empty($this->username) || empty($this->password)) {
                throw new Exception('Configuration incomplète. Vérifiez les variables d\'environnement.');
            }
            
            $this->userHash = $this->getUserHash();
        } catch (Exception $e) {
            error_log('Erreur dans le constructeur: ' . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'error' => 'Erreur de configuration: ' . $e->getMessage()]);
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

    private function getUserHash() {
        try {
            $url = $this->baseUrl . '/login';
            $params = [
                'apiKey' => $this->apiKey,
                'username' => $this->username,
                'password' => $this->password
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Erreur cURL: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Erreur HTTP: ' . $httpCode);
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
                throw new Exception('Erreur de connexion: ' . ($data['message'] ?? 'Erreur inconnue'));
            }

            return $data['hash'];
        } catch (Exception $e) {
            error_log('Erreur lors de la connexion à Brickset: ' . $e->getMessage());
            return null;
        }
    }

    private function makeRequest($method, $params = []) {
        try {
            if (!$this->userHash) {
                throw new Exception('Non authentifié');
            }

            $params['apiKey'] = $this->apiKey;
            $params['userHash'] = $this->userHash;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/' . $method);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Erreur cURL: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Erreur HTTP: ' . $httpCode);
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['status'])) {
                throw new Exception('Réponse invalide du serveur');
            }

            if ($data['status'] !== 'success') {
                throw new Exception($data['message'] ?? 'Erreur inconnue');
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            error_log('Erreur lors de la requête Brickset: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function themes() {
        try {
            $result = $this->makeRequest('getThemes');
            
            if ($result['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'data' => $result['data']['themes']
                ]);
            }
            
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sets($theme = null) {
        try {
            $params = [];
            if ($theme) {
                $params['theme'] = $theme;
            }
            
            $result = $this->makeRequest('getSets', $params);
            $this->sendJsonResponse($result);
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée. Utilisez POST.');
    }
    
    $controller = new BricksetController();
    $action = $_GET['action'] ?? 'themes';

    switch ($action) {
        case 'themes':
            $controller->themes();
            break;
        case 'sets':
            $theme = $_GET['theme'] ?? null;
            $controller->sets($theme);
            break;
        default:
            $controller->sendJsonResponse(['success' => false, 'error' => 'Action non reconnue']);
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