<?php
require_once __DIR__ . '/../controllers/RebrickableController.php';
require_once __DIR__ . '/../controllers/BricksetController.php';

// Récupérer l'URL demandée
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Router les requêtes
switch ($path) {
    case '/RB_API Rebel Bricks/public/controllers/BricksetController.php':
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
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        }
        break;

    case '/RB_API Rebel Bricks/public/controllers/RebrickableController.php':
        $controller = new RebrickableController();
        $action = $_GET['action'] ?? 'themes';
        switch ($action) {
            case 'themes':
                $response = $controller->getThemes();
                break;
            case 'sets':
                $themeId = $_GET['theme_id'] ?? null;
                $response = $controller->getSets($themeId);
                break;
            case 'colors':
                $response = $controller->getColors();
                break;
            case 'parts':
                $response = $controller->getParts();
                break;
            case 'minifigs':
                $response = $controller->getMinifigs();
                break;
            default:
                $response = ['success' => false, 'error' => 'Action non reconnue'];
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Route non trouvée'
        ]);
        break;
} 