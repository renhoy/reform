<?php
// {"_META_file_path_": "refor/templates/tariff-form-fields.php"}
// Campos del formulario de tarifa reutilizables
?>

<div class="form-section">
    <h3>Información de la Tarifa</h3>
    <div class="form-group">
        <label for="tariff_name">Nombre de la Tarifa:</label>
        <input type="text" id="tariff_name" name="tariff_name" required 
               value="<?= htmlspecialchars($tariff['title'] ?? '') ?>">
    </div>
</div>

<div class="form-section">
    <h3>Datos de la Empresa (Encabezado de Formulario y del PDF)</h3>
    <div class="form-group">
        <label for="company_name">Nombre de la Empresa:</label>
        <input type="text" id="company_name" name="company_name" required 
               value="<?= htmlspecialchars($tariff['name'] ?? '') ?>">
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="company_nif">NIF/CIF:</label>
            <input type="text" id="company_nif" name="company_nif" 
                   value="<?= htmlspecialchars($tariff['nif'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="logo_url">URL del Logo:</label>
            <input type="url" id="logo_url" name="logo_url" 
                   value="<?= htmlspecialchars($tariff['logo_url'] ?? '') ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="company_address">Dirección (Calle, Número - CP, Localidad, (Provincia)):</label>
        <textarea id="company_address" name="company_address"><?= htmlspecialchars($tariff['address'] ?? '') ?></textarea>
    </div>
    
    <div class="form-group">
        <label for="company_contact">Contacto (Teléfono - Email - Web):</label>
        <input type="text" id="company_contact" name="company_contact" 
               value="<?= htmlspecialchars($tariff['contact'] ?? '') ?>">
    </div>
</div>

<div class="form-section">
    <h3>Configuración del PDF</h3>
    <div class="form-group">
        <label for="template">Plantilla PDF:</label>
        <input type="text" id="template" name="template" 
               value="<?= htmlspecialchars($tariff['template'] ?? '41200-00001') ?>">
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="primary_color">Color Primario:</label>
            <div class="color-picker">
                <div class="color-preview" id="primaryColorPreview" 
                     style="background: <?= $tariff['primary_color'] ?? '#e8951c' ?>"></div>
                <input type="color" id="primary_color" name="primary_color" 
                       value="<?= $tariff['primary_color'] ?? '#e8951c' ?>" style="display: none;">
            </div>
        </div>
        <div class="form-group">
            <label for="secondary_color">Color Secundario:</label>
            <div class="color-picker">
                <div class="color-preview" id="secondaryColorPreview" 
                     style="background: <?= $tariff['secondary_color'] ?? '#109c61' ?>"></div>
                <input type="color" id="secondary_color" name="secondary_color" 
                       value="<?= $tariff['secondary_color'] ?? '#109c61' ?>" style="display: none;">
            </div>
        </div>
    </div>
</div>

<div class="form-section">
    <h3>Textos Legales del PDF</h3>
    <div class="form-group">
        <label for="summary_note">Nota del Resumen (Aceptación y Métodos de Pago):</label>
        <textarea id="summary_note" name="summary_note"><?= htmlspecialchars($tariff['summary_note'] ?? '') ?></textarea>
    </div>
    
    <div class="form-group">
        <label for="conditions_note">Condiciones del Presupuesto (Cláusulas, garantías, incluido o no, etc):</label>
        <textarea id="conditions_note" name="conditions_note"><?= htmlspecialchars($tariff['conditions_note'] ?? '') ?></textarea>
    </div>
</div>

<div class="form-section">
    <h3>Condiciones Legales del Formulario</h3>
    <div class="form-group">
        <label for="legal_note">Información legal del Formulario:</label>
        <textarea id="legal_note" name="legal_note"><?= htmlspecialchars($tariff['legal_note'] ?? '') ?></textarea>
    </div>
</div>