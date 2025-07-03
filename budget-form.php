<?php
// {"_META_file_path_": "refor/budget-form.php"}
// Redirección a create-budget.php para compatibilidad con tariffs.php

if (isset($_GET['tariff_uuid'])) {
    header('Location: create-budget.php?tariff_uuid=' . urlencode($_GET['tariff_uuid']));
    exit;
}

header('Location: tariffs.php');
exit;