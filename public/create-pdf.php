<?php
// {"_META_file_path_": "public/create-pdf.php"}
// Crear PDF vía RapidPDF

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uuid = $input['uuid'] ?? null;

if (!$uuid) {
    echo json_encode(['success' => false, 'error' => 'UUID requerido']);
    exit;
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE uuid = ? AND user_id = ?");
    $stmt->execute([$uuid, $_SESSION['user_id']]);
    $budget = $stmt->fetch();
    
    if (!$budget) {
        echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
        exit;
    }
    
    // Preparar payload para RapidPDF
    $tariff_data = json_decode($budget['json_tariff_data'], true);
    $budget_data = json_decode($budget['json_budget_data'], true);
    
    $payload = [
        'template' => $tariff_data['template'] ?? '41200-00001',
        'budget' => [
            'uuid' => $budget['uuid'],
            'client' => [
                'name' => $budget['client_name'],
                'nif_nie' => $budget['client_nif_nie'],
                'phone' => $budget['client_phone'],
                'email' => $budget['client_email'],
                'address' => $budget['client_address']
            ],
            'company' => [
                'name' => $tariff_data['name'],
                'nif' => $tariff_data['nif'],
                'address' => $tariff_data['address'],
                'contact' => $tariff_data['contact'],
                'logo_url' => $tariff_data['logo_url']
            ],
            'items' => $budget_data['items'],
            'totals' => $budget_data['totals'],
            'colors' => [
                'primary' => $tariff_data['primary_color'],
                'secondary' => $tariff_data['secondary_color']
            ],
            'notes' => [
                'summary' => $tariff_data['summary_note'],
                'conditions' => $tariff_data['conditions_note']
            ]
        ]
    ];
    
    // Enviar a RapidPDF
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PDF_SERVICE_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . PDF_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        echo json_encode(['success' => false, 'error' => 'Error de conexión con RapidPDF']);
        exit;
    }
    
    $pdfResponse = json_decode($response, true);
    
    if (!$pdfResponse['success']) {
        echo json_encode(['success' => false, 'error' => 'Error generando PDF']);
        exit;
    }
    
    $pdfUrl = $pdfResponse['pdf_url'];
    
    // Guardar URL en base de datos
    $stmt = $pdo->prepare("UPDATE budgets SET pdf_url = ? WHERE uuid = ?");
    $stmt->execute([$pdfUrl, $uuid]);
    
    echo json_encode(['success' => true, 'pdf_url' => $pdfUrl]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}