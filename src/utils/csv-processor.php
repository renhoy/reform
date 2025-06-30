<?php
// {"_META_file_path_": "includes/csv-processor.php"}
// Funciones para procesamiento de CSV

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
                $jsonObject['iva_percentage'] = formatNumber($row['%IVA']);
                $jsonObject['pvp'] = formatNumber($row['PVP']);
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

function formatNumber($value) {
    if (!$value) return '0.00';
    $cleanValue = str_replace(['"', ','], ['', '.'], $value);
    $number = floatval($cleanValue);
    return number_format($number, 2, '.', '');
}