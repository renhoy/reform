// JavaScript para formulario de tarifas
// {"_META_file_path_": "refor/assets/js/tariff-form.js"}

class TariffFormHandler {
    constructor() {
        this.csvData = [];
        this.jsonData = [];
        this.initialize();
    }

    initialize() {
        this.initializeEventListeners();
        this.initializeColorPickers();
        
        // Si hay datos existentes, mostrar sección de tarifa
        const csvData = document.getElementById('csv_data').value;
        if (csvData && csvData !== '') {
            this.jsonData = JSON.parse(csvData);
            this.showTariffSection();
        }
    }

    initializeEventListeners() {
        // Archivo CSV
        const fileInput = document.getElementById('csv_file');
        const uploadArea = document.querySelector('.upload-area');

        if (fileInput && uploadArea) {
            // Drag and drop
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    this.handleFileUpload();
                }
            });

            fileInput.addEventListener('change', () => this.handleFileUpload());
        }

        // Botones de acción
        document.getElementById('clearAll')?.addEventListener('click', () => this.clearAll());
        document.getElementById('downloadTemplate')?.addEventListener('click', () => this.downloadTemplate());
        document.getElementById('exportCsv')?.addEventListener('click', () => this.exportCsv());
        document.getElementById('showJson')?.addEventListener('click', () => this.showJson());
        document.getElementById('deleteTariff')?.addEventListener('click', () => this.deleteTariff());
    }

    initializeColorPickers() {
        const primaryPreview = document.getElementById('primaryColorPreview');
        const secondaryPreview = document.getElementById('secondaryColorPreview');
        const primaryInput = document.getElementById('primary_color');
        const secondaryInput = document.getElementById('secondary_color');

        if (primaryPreview && primaryInput) {
            primaryPreview.addEventListener('click', () => {
                primaryInput.click();
            });

            primaryInput.addEventListener('change', (e) => {
                primaryPreview.style.background = e.target.value;
            });
        }

        if (secondaryPreview && secondaryInput) {
            secondaryPreview.addEventListener('click', () => {
                secondaryInput.click();
            });

            secondaryInput.addEventListener('change', (e) => {
                secondaryPreview.style.background = e.target.value;
            });
        }
    }

    clearAll() {
        if (confirm('Se van a eliminar todos los datos. ¿Continuar?\n\nEsta acción no se puede deshacer.')) {
            // Limpiar formulario
            document.getElementById('tariffForm').reset();
            
            // Limpiar datos CSV
            this.jsonData = [];
            this.csvData = [];
            document.getElementById('csv_data').value = '';
            
            // Mostrar sección de upload y ocultar tarifa
            document.getElementById('csvUploadSection').style.display = 'block';
            document.getElementById('tariffSection').style.display = 'none';
            
            // Restaurar colores por defecto
            this.resetColorPickers();
        }
    }

    resetColorPickers() {
        const primaryPreview = document.getElementById('primaryColorPreview');
        const secondaryPreview = document.getElementById('secondaryColorPreview');
        const primaryInput = document.getElementById('primary_color');
        const secondaryInput = document.getElementById('secondary_color');
        
        if (primaryPreview && primaryInput) {
            primaryInput.value = '#e8951c';
            primaryPreview.style.background = '#e8951c';
        }
        
        if (secondaryPreview && secondaryInput) {
            secondaryInput.value = '#109c61';
            secondaryPreview.style.background = '#109c61';
        }
    }

    downloadTemplate() {
        const templateContent = `"Nivel","ID","Nombre","Descripción","Ud","%IVA","PVP"
"Capítulo",1,"Nombre del Capítulo 1",,,,
"Subcapítulo","1.1","Nombre del Subcapítulo 1.1",,,,
"Apartado","1.1.1","Nombre del Apartado 1.1.1",,,,
"Partida","1.1.1.1","Nombre del Partida 1.1.1.1","Descripción de la Partida 1.1.1.1","Unidad","5,00","125,00"
"Capítulo",2,"Nombre del Capítulo 2",,,,
"Subcapítulo","2.1","Nombre del Subcapítulo 2.1",,,,
"Partida","2.1.1","Nombre del Partida 2.1.1","Descripción de la Partida 2.1.1","hora","10,00","20,00"
"Capítulo",3,"Nombre del Capítulo 3",,,,
"Partida","3.1","Nombre del Partida 3.1","Descripción de la Partida 3.1","m","21,00","5,00"`;

        this.downloadFile(templateContent, 'plantilla-tarifa.csv', 'text/csv;charset=utf-8;');
    }

    handleFileUpload() {
        const fileInput = document.getElementById('csv_file');
        const file = fileInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => this.processCSV(e.target.result);
        reader.readAsText(file);
    }

    processCSV(csvContent) {
        try {
            // Aquí se procesaría el CSV (simplificado para el ejemplo)
            // En el código real usarías la lógica de csv-processor.php
            AppUtils.showMessage('CSV procesado correctamente', 'success');
            this.showTariffSection();
        } catch (error) {
            AppUtils.showMessage('Error al procesar el archivo: ' + error.message, 'error');
        }
    }

    showTariffSection() {
        // Ocultar sección de upload y mostrar tarifa
        document.getElementById('csvUploadSection').style.display = 'none';
        document.getElementById('tariffSection').style.display = 'block';
        
        // Mostrar jerarquía si hay datos
        if (this.jsonData.length > 0) {
            this.displayHierarchy();
        }
    }

    displayHierarchy() {
        const container = document.getElementById("hierarchyOutput");
        if (container && this.jsonData.length > 0) {
            container.innerHTML = this.buildHierarchyHTML(this.jsonData);
            this.initializeAccordions();
        }
    }

    buildHierarchyHTML(data) {
        // Simplificado - en el código real usarías la lógica completa
        let html = "<p>Jerarquía de tarifa cargada</p>";
        return html;
    }

    initializeAccordions() {
        document.querySelectorAll(".level-header").forEach((header) => {
            header.addEventListener("click", () => {
                // Lógica de acordeón
            });
        });
    }

    exportCsv() {
        if (this.jsonData.length === 0) return;
        AppUtils.showMessage('Exportando CSV...', 'info');
    }

    showJson() {
        console.log('JSON de la tarifa:', JSON.stringify(this.jsonData, null, 2));
        AppUtils.showMessage('JSON mostrado en consola', 'info');
    }

    deleteTariff() {
        if (confirm('Se eliminará la tarifa y tendrá que subir otra.\n\n¿Continuar?')) {
            // Limpiar datos
            this.jsonData = [];
            this.csvData = [];
            document.getElementById('csv_data').value = '';
            
            // Mostrar sección de upload y ocultar tarifa
            document.getElementById('csvUploadSection').style.display = 'block';
            document.getElementById('tariffSection').style.display = 'none';
            
            // Limpiar input file
            const fileInput = document.getElementById('csv_file');
            if (fileInput) fileInput.value = '';
        }
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.tariffFormHandler = new TariffFormHandler();
});