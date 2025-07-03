<?php
// {"_META_file_path_": "refor/includes/csv-processor.php"}
// Procesamiento de archivos CSV para tarifas

function processCSVToJSON($csvContent) {
    $rows = parseCSV($csvContent);
    if (empty($rows)) return false;
    
    $headers = $rows[0];
    $expectedFields = ['Nivel', 'ID', 'Nombre', 'Descripción', 'Ud', '%IVA', 'PVP'];
    $fieldIndices = [];
    
    foreach ($expectedFields as $field) {
        $index = array_search($field, array_map('trim', $headers));
        if ($index !== false) $fieldIndices[$field] = $index;
    }
    
    $essentialFields = ['Nivel', 'ID', 'Nombre'];
    foreach ($essentialFields as $field) {
        if (!isset($fieldIndices[$field])) return false;
    }
    
    $validLevels = ['capitulo', 'subcapitulo', 'apartado', 'partida'];
    $levelMap = [
        'capitulo' => 'chapter',
        'subcapitulo' => 'subchapter', 
        'apartado' => 'section',
        'partida' => 'item'
    ];
    
    $jsonData = [];
    
    for ($i = 1; $i < count($rows); $i++) {
        $values = $rows[$i];
        $row = [];
        
        foreach ($expectedFields as $field) {
            $index = $fieldIndices[$field] ?? null;
            $row[$field] = ($index !== null && isset($values[$index])) 
                ? trim($values[$index]) 
                : '';
        }
        
        $normalizedLevel = normalizeLevel($row['Nivel']);
        
        if (in_array($normalizedLevel, $validLevels) && $row['ID'] && $row['Nombre']) {
            $jsonObject = [
                'level' => $levelMap[$normalizedLevel],
                'id' => $row['ID'],
                'name' => $row['Nombre'],
                'amount' => '0.00'
            ];
            
            if ($jsonObject['level'] === 'item') {
                $jsonObject['description'] = $row['Descripción'] ?? '';
                $jsonObject['unit'] = $row['Ud'] ?? '';
                $jsonObject['quantity'] = '0.00';
                $jsonObject['iva_percentage'] = formatCSVNumber($row['%IVA']);
                $jsonObject['pvp'] = formatCSVNumber($row['PVP']);
            }
            
            $jsonData[] = $jsonObject;
        }
    }
    
    return $jsonData;
}

function parseCSV($csvContent) {
    $rows = [];
    $currentRow = [];
    $currentField = '';
    $inQuotes = false;
    $i = 0;
    
    while ($i < strlen($csvContent)) {
        $char = $csvContent[$i];
        $nextChar = isset($csvContent[$i + 1]) ? $csvContent[$i + 1] : null;
        
        if ($char === '"') {
            if ($inQuotes && $nextChar === '"') {
                $currentField .= '"';
                $i += 2;
                continue;
            } else {
                $inQuotes = !$inQuotes;
            }
        } elseif ($char === ',' && !$inQuotes) {
            $currentRow[] = trim($currentField);
            $currentField = '';
        } elseif (($char === "\n" || ($char === "\r" && $nextChar === "\n")) && !$inQuotes) {
            if ($char === "\r" && $nextChar === "\n") $i++;
            
            if ($currentField || !empty($currentRow)) {
                $currentRow[] = trim($currentField);
                if (array_filter($currentRow, 'strlen')) {
                    $rows[] = $currentRow;
                }
                $currentRow = [];
                $currentField = '';
            }
        } else {
            $currentField .= $char;
        }
        $i++;
    }
    
    if ($currentField || !empty($currentRow)) {
        $currentRow[] = trim($currentField);
        if (array_filter($currentRow, 'strlen')) {
            $rows[] = $currentRow;
        }
    }
    
    return $rows;
}

function normalizeLevel($text) {
    if (!$text) return '';
    return strtolower(trim(
        preg_replace('/[^a-z0-9\s-]/i', '', 
            iconv('UTF-8', 'ASCII//TRANSLIT', $text)
        )
    ));
}

function formatCSVNumber($value) {
    if (!$value) return '0.00';
    $cleanValue = str_replace(['"', ','], ['', '.'], $value);
    $number = floatval($cleanValue);
    return number_format($number, 2, '.', '');
}

function jsonToCSV($jsonData) {
    if (!$jsonData || empty($jsonData)) return "";

    $levelMap = [
        'chapter' => 'Capítulo',
        'subchapter' => 'Subcapítulo',
        'section' => 'Apartado',
        'item' => 'Partida',
    ];

    $headers = ['Nivel', 'ID', 'Nombre', 'Descripción', 'Ud', '%IVA', 'PVP'];
    $rows = ['"' . implode('","', $headers) . '"'];

    foreach ($jsonData as $item) {
        $row = [
            $levelMap[$item['level']] ?? '',
            $item['id'] ?? '',
            $item['name'] ?? '',
            $item['description'] ?? '',
            $item['unit'] ?? '',
            isset($item['iva_percentage']) ? str_replace('.', ',', $item['iva_percentage']) : '',
            isset($item['pvp']) ? str_replace('.', ',', $item['pvp']) : '',
        ];
        
        // Escapar campos que contienen comas o comillas
        $escapedRow = array_map(function($field) {
            $stringField = (string)$field;
            if (strpos($stringField, ',') !== false || 
                strpos($stringField, '"') !== false || 
                strpos($stringField, "\n") !== false) {
                return '"' . str_replace('"', '""', $stringField) . '"';
            }
            return $stringField;
        }, $row);
        
        $rows[] = implode(',', $escapedRow);
    }

    return implode("\n", $rows);
}

function generateCSVTemplate() {
    return '"Nivel","ID","Nombre","Descripción","Ud","%IVA","PVP"
"Capítulo",1,"Nombre del Capítulo 1",,,,
"Subcapítulo","1.1","Nombre del Subcapítulo 1.1",,,,
"Apartado","1.1.1","Nombre del Apartado 1.1.1",,,,
"Partida","1.1.1.1","Nombre del Partida 1.1.1.1","Descripción de la Partida 1.1.1.1","Unidad","5,00","125,00"
"Capítulo",2,"Nombre del Capítulo 2",,,,
"Subcapítulo","2.1","Nombre del Subcapítulo 2.1",,,,
"Partida","2.1.1","Nombre del Partida 2.1.1","Descripción de la Partida 2.1.1","hora","10,00","20,00"
"Capítulo",3,"Nombre del Capítulo 3",,,,
"Partida","3.1","Nombre del Partida 3.1","Descripción de la Partida 3.1","m","21,00","5,00"';
}

function uploadCSVFile($fileData) {
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }
    
    $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
    if (!in_array($fileData['type'], $allowedTypes)) {
        throw new Exception('Solo se permiten archivos CSV');
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($fileData['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande');
    }
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $fileData['name']);
    $filePath = UPLOAD_DIR . $fileName;
    
    if (move_uploaded_file($fileData['tmp_name'], $filePath)) {
        return $filePath;
    } else {
        throw new Exception('Error al guardar el archivo');
    }
}