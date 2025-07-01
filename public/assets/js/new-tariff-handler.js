// JavaScript para crear tarifa con CSV processor
// {"_META_file_path_": "public/assets/js/new-tariff-handler.js"}

class NewTariffHandler {
    constructor() {
        this.csvData = [];
        this.jsonData = [];
        this.initialize();
    }

    initialize() {
        this.initializeEventListeners();
        this.initializeFileUpload();
    }

    initializeEventListeners() {
        // Botones de acción
        document.getElementById('clearAll')?.addEventListener('click', () => this.clearAll());
        document.getElementById('downloadTemplate')?.addEventListener('click', () => this.downloadTemplate());
        document.getElementById('exportPreview')?.addEventListener('click', () => this.exportPreview());
        document.getElementById('deletePreview')?.addEventListener('click', () => this.deletePreview());
    }

    initializeFileUpload() {
        const fileInput = document.getElementById('csv_file');
        const uploadArea = document.querySelector('.upload-area');
        const logoInput = document.getElementById('logo_file');
        const imageUploadArea = document.getElementById('imageUploadArea');

        if (fileInput && uploadArea) {
            // CSV drag and drop
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
                    this.handleCSVUpload();
                }
            });

            fileInput.addEventListener('change', () => this.handleCSVUpload());
        }

        // Image drag and drop
        if (imageUploadArea) {
            imageUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                imageUploadArea.classList.add('dragover');
            });

            imageUploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                imageUploadArea.classList.remove('dragover');
            });

            imageUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                imageUploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    logoInput.files = files;
                    this.handleLogoUpload({ target: { files } });
                }
            });
        }

        // Logo upload
        if (logoInput) {
            logoInput.addEventListener('change', (e) => this.handleLogoUpload(e));
        }

        // Remove image button
        document.getElementById('removeImageBtn')?.addEventListener('click', () => this.removeImage());
    }

    handleLogoUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
        if (!validTypes.includes(file.type)) {
            this.showMessage('Solo se permiten archivos JPG, PNG o SVG', 'error');
            return;
        }

        // Subir archivo
        const formData = new FormData();
        formData.append('logo', file);

        fetch('upload-logo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('logo_url').value = data.url;
                this.showImagePreview(data.url);
                this.showMessage('Logo subido correctamente', 'success');
            } else {
                this.showMessage('Error al subir logo: ' + data.error, 'error');
            }
        })
        .catch(error => {
            this.showMessage('Error al subir logo', 'error');
        });
    }

    showImagePreview(imageUrl) {
        const uploadArea = document.getElementById('imageUploadArea');
        const previewContainer = document.getElementById('imagePreviewContainer');
        
        uploadArea.style.display = 'none';
        previewContainer.style.display = 'block';
        
        const img = document.getElementById('imagePreview');
        img.src = imageUrl;
    }

    removeImage() {
        const imageUrl = document.getElementById('logo_url').value;
        
        if (imageUrl) {
            // Eliminar del servidor
            fetch('delete-logo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: imageUrl })
            })
            .then(() => {
                this.resetImageUpload();
            })
            .catch(() => {
                this.resetImageUpload();
            });
        } else {
            this.resetImageUpload();
        }
    }

    resetImageUpload() {
        document.getElementById('logo_url').value = '';
        document.getElementById('logo_file').value = '';
        document.getElementById('imageUploadArea').style.display = 'block';
        document.getElementById('imagePreviewContainer').style.display = 'none';
    }

    handleCSVUpload() {
        const fileInput = document.getElementById('csv_file');
        const file = fileInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => this.processCSV(e.target.result);
        reader.readAsText(file);
    }

    processCSV(csvContent) {
        try {
            const rows = this.parseCSV(csvContent);
            if (rows.length === 0) {
                this.showMessage("Error: El archivo CSV está vacío o es inválido", 'error');
                return;
            }

            const headers = rows[0];
            const expectedFields = ["Nivel", "ID", "Nombre", "Descripción", "Ud", "%IVA", "PVP"];
            const fieldIndices = {};

            expectedFields.forEach((field) => {
                const index = headers.findIndex((header) => header.trim() === field);
                if (index !== -1) fieldIndices[field] = index;
            });

            const essentialFields = ["Nivel", "ID", "Nombre"];
            const missingFields = essentialFields.filter((field) => !(field in fieldIndices));

            if (missingFields.length > 0) {
                this.showMessage(`Error: Faltan campos esenciales: ${missingFields.join(", ")}`, 'error');
                return;
            }

            const validLevels = ["capitulo", "subcapitulo", "apartado", "partida"];
            this.csvData = [];

            for (let i = 1; i < rows.length; i++) {
                const values = rows[i];
                const row = {};

                expectedFields.forEach((field) => {
                    const index = fieldIndices[field];
                    row[field] = index !== undefined && values[index] ? values[index].toString().trim() : "";
                });

                const normalizedLevel = this.normalizeLevel(row.Nivel);

                if (normalizedLevel && validLevels.includes(normalizedLevel) && row.ID && row.Nombre) {
                    row.NivelNormalizado = normalizedLevel;
                    this.csvData.push(row);
                }
            }

            if (this.csvData.length === 0) {
                this.showMessage("Error: No se encontraron datos válidos", 'error');
                return;
            }

            this.convertToJSON();
            this.showPreview();
        } catch (error) {
            this.showMessage('Error al procesar el archivo: ' + error.message, 'error');
        }
    }

    parseCSV(csvContent) {
        const rows = [];
        let currentRow = [];
        let currentField = "";
        let inQuotes = false;
        let i = 0;

        while (i < csvContent.length) {
            const char = csvContent[i];
            const nextChar = csvContent[i + 1];

            if (char === '"') {
                if (inQuotes && nextChar === '"') {
                    currentField += '"';
                    i += 2;
                    continue;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === "," && !inQuotes) {
                currentRow.push(currentField.trim());
                currentField = "";
            } else if ((char === "\n" || (char === "\r" && nextChar === "\n")) && !inQuotes) {
                if (char === "\r" && nextChar === "\n") i++;

                if (currentField || currentRow.length > 0) {
                    currentRow.push(currentField.trim());
                    if (currentRow.some((field) => field.length > 0)) {
                        rows.push(currentRow);
                    }
                    currentRow = [];
                    currentField = "";
                }
            } else if (char === "\r" && nextChar !== "\n" && !inQuotes) {
                if (currentField || currentRow.length > 0) {
                    currentRow.push(currentField.trim());
                    if (currentRow.some((field) => field.length > 0)) {
                        rows.push(currentRow);
                    }
                    currentRow = [];
                    currentField = "";
                }
            } else {
                currentField += char;
            }
            i++;
        }

        if (currentField || currentRow.length > 0) {
            currentRow.push(currentField.trim());
            if (currentRow.some((field) => field.length > 0)) {
                rows.push(currentRow);
            }
        }

        return rows;
    }

    normalizeLevel(text) {
        if (!text) return "";
        return text
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9\s-]/g, "")
            .trim()
            .replace(/\s+/g, "")
            .replace(/-+/g, "");
    }

    convertToJSON() {
        const levelMap = {
            capitulo: "chapter",
            subcapitulo: "subchapter",
            apartado: "section",
            partida: "item",
        };

        this.jsonData = this.csvData.map((row) => {
            const jsonObject = {
                level: levelMap[row.NivelNormalizado],
                id: row.ID,
                name: row.Nombre,
                amount: "0.00",
            };

            if (jsonObject.level === "item") {
                Object.assign(jsonObject, {
                    description: row.Descripción || "",
                    unit: row.Ud || "",
                    quantity: "0.00",
                    iva_percentage: this.formatNumber(row["%IVA"]),
                    pvp: this.formatNumber(row.PVP),
                });
            }

            return jsonObject;
        });

        // Guardar en campo oculto
        document.getElementById('csv_data').value = JSON.stringify(this.jsonData);
    }

    formatNumber(value) {
        if (!value) return "0.00";
        const cleanValue = value.replace(/"/g, "").replace(",", ".");
        const number = parseFloat(cleanValue);
        return isNaN(number) ? "0.00" : number.toFixed(2);
    }

    formatNumberForDisplay(value) {
        return value.replace(".", ",");
    }

    showPreview() {
        // Ocultar upload section
        document.getElementById('csvUploadSection').style.display = 'none';
        document.getElementById('csvFormatSection').style.display = 'none';
        
        // Mostrar preview section
        document.getElementById('previewSection').style.display = 'block';
        
        // Mostrar jerarquía
        this.displayHierarchy();
        
        this.showMessage('CSV procesado correctamente', 'success');
    }

    displayHierarchy() {
        const container = document.getElementById("hierarchyOutput");
        if (container) {
            container.innerHTML = this.buildHierarchyHTML(this.jsonData);
            this.initializeAccordions();
        }
    }

    buildHierarchyHTML(data) {
        let html = "";
        let i = 0;

        while (i < data.length) {
            const item = data[i];
            const children = this.getDirectChildren(data, i);

            const containerClass = item.level === "item" 
                ? "level-container level-item" 
                : `level-container level-${item.level}`;
            
            html += `<div class="${containerClass}">`;
            html += `<div class="level-header" data-level="${item.level}" data-id="${item.id}">`;
            html += `<span class="level-toggle collapsed">▼</span>`;
            html += `<span>${item.name}</span>`;
            html += `</div>`;

            if (item.level === "item") {
                const ivaFormatted = this.formatNumberForDisplay(item.iva_percentage);
                const pvpFormatted = this.formatNumberForDisplay(item.pvp);

                html += `<div class="collapsible" data-item-id="${item.id}">`;
                html += `<div class="level-item-details">`;
                html += `<span>${item.unit}</span>`;
                html += `<span>%IVA: ${ivaFormatted}</span>`;
                html += `<span class="item-price">PVP: ${pvpFormatted} €</span>`;
                html += `<span>${item.description}</span>`;
                html += `</div>`;
                html += `</div>`;
            }

            if (children.items.length > 0) {
                html += `<div class="collapsible" data-parent-id="${item.id}">`;
                html += `<div class="level-children">`;
                html += this.buildHierarchyHTML(children.items);
                html += `</div>`;
                html += `</div>`;
                i = children.nextIndex;
            } else {
                i++;
            }

            html += `</div>`;
        }

        return html;
    }

    getDirectChildren(data, parentIndex) {
        const parentItem = data[parentIndex];
        const children = [];
        let i = parentIndex + 1;

        while (i < data.length) {
            const currentItem = data[i];

            if (this.isDirectChild(parentItem, currentItem)) {
                const childWithDescendants = this.getItemWithDescendants(data, i);
                children.push(...childWithDescendants.items);
                i = childWithDescendants.nextIndex;
            } else {
                break;
            }
        }

        return { items: children, nextIndex: i };
    }

    getItemWithDescendants(data, startIndex) {
        const items = [data[startIndex]];
        const parentItem = data[startIndex];
        let i = startIndex + 1;

        while (i < data.length && this.isDescendant(parentItem, data[i])) {
            items.push(data[i]);
            i++;
        }

        return { items: items, nextIndex: i };
    }

    isDirectChild(parent, potential) {
        const parentId = parent.id;
        const potentialId = potential.id;
        return potentialId.startsWith(parentId + ".") && 
               potentialId.split(".").length === parentId.split(".").length + 1;
    }

    isDescendant(ancestor, potential) {
        const ancestorId = ancestor.id;
        const potentialId = potential.id;
        return potentialId.startsWith(ancestorId + ".") && 
               potentialId.split(".").length > ancestorId.split(".").length;
    }

    initializeAccordions() {
        document.querySelectorAll(".level-header").forEach((header) => {
            header.addEventListener("click", () => {
                const container = header.parentElement;
                const content = container.querySelector(".collapsible");
                const toggle = header.querySelector(".level-toggle");

                if (content) {
                    if (content.classList.contains("active")) {
                        content.classList.remove("active");
                        toggle.classList.add("collapsed");
                    } else {
                        content.classList.add("active");
                        toggle.classList.remove("collapsed");
                    }
                }
            });
        });
    }

    clearAll() {
        if (confirm('Se van a eliminar todos los datos. ¿Continuar?\n\nEsta acción no se puede deshacer.')) {
            // Limpiar formulario
            document.getElementById('tariffForm').reset();
            
            // Limpiar datos CSV
            this.jsonData = [];
            this.csvData = [];
            document.getElementById('csv_data').value = '';
            
            // Mostrar sección upload
            document.getElementById('csvUploadSection').style.display = 'block';
            document.getElementById('csvFormatSection').style.display = 'block';
            document.getElementById('previewSection').style.display = 'none';
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

    exportPreview() {
        if (this.jsonData.length === 0) return;
        const csvContent = this.jsonToCSV(this.jsonData);
        this.downloadFile(csvContent, 'tarifa_exportada.csv', 'text/csv;charset=utf-8;');
    }

    deletePreview() {
        if (confirm('Se eliminará la previsualización de tarifa.\n\n¿Continuar?')) {
            this.clearAll();
        }
    }

    jsonToCSV(jsonData) {
        if (!jsonData || jsonData.length === 0) return "";

        const levelMap = {
            chapter: "Capítulo",
            subchapter: "Subcapítulo",
            section: "Apartado",
            item: "Partida",
        };

        const headers = ["Nivel", "ID", "Nombre", "Descripción", "Ud", "%IVA", "PVP"];
        const rows = [headers];

        jsonData.forEach((item) => {
            const row = [
                levelMap[item.level] || "",
                item.id || "",
                item.name || "",
                item.description || "",
                item.unit || "",
                item.iva_percentage ? item.iva_percentage.replace(".", ",") : "",
                item.pvp ? item.pvp.replace(".", ",") : "",
            ];
            rows.push(row);
        });

        return rows
            .map((row) =>
                row
                    .map((field) => {
                        const stringField = String(field);
                        if (
                            stringField.includes(",") ||
                            stringField.includes('"') ||
                            stringField.includes("\n")
                        ) {
                            return '"' + stringField.replace(/"/g, '""') + '"';
                        }
                        return stringField;
                    })
                    .join(",")
            )
            .join("\n");
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

    showMessage(message, type) {
        // Remover mensajes anteriores
        const existingMessages = document.querySelectorAll('.error-message, .success-message');
        existingMessages.forEach(msg => msg.remove());

        const messageDiv = document.createElement('div');
        messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
        messageDiv.textContent = message;

        const container = document.querySelector('.message-container');
        container.appendChild(messageDiv);

        // Auto-remove después de 5 segundos
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', () => {
    window.newTariffHandler = new NewTariffHandler();
});