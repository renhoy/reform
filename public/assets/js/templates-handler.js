// Manejador de plantillas
// {"_META_file_path_": "public/assets/js/templates-handler.js"}

let isEditMode = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeModal();
    initializeForm();
});

function initializeModal() {
    const modal = document.getElementById('templateModal');
    const closeBtn = document.querySelector('.close-modal');
    
    closeBtn.addEventListener('click', closeModal);
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

function initializeForm() {
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTemplate();
    });
}

function openTemplateModal(templateId = null) {
    isEditMode = !!templateId;
    const modal = document.getElementById('templateModal');
    const title = document.getElementById('modalTitle');
    
    title.textContent = isEditMode ? 'Editar Plantilla' : 'Crear Plantilla';
    
    if (isEditMode) {
        loadTemplateData(templateId);
    } else {
        clearForm();
    }
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('templateModal').style.display = 'none';
    clearForm();
}

function clearForm() {
    document.getElementById('templateForm').reset();
    document.getElementById('template_id').value = '';
    document.getElementById('template_primary_color').value = '#e8951c';
    document.getElementById('template_secondary_color').value = '#109c61';
}

function loadTemplateData(templateId) {
    fetch(`get-template.php?id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const template = data.template;
                const templateData = JSON.parse(template.template_data);
                
                document.getElementById('template_id').value = template.id;
                document.getElementById('template_name').value = template.name;
                document.getElementById('template_description').value = template.description;
                
                // Cargar datos de plantilla
                Object.keys(templateData).forEach(key => {
                    const input = document.getElementById(`template_${key}`);
                    if (input) {
                        input.value = templateData[key] || '';
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function saveTemplate() {
    const formData = new FormData(document.getElementById('templateForm'));
    
    // Construir template_data JSON
    const templateData = {
        title: formData.get('title') || '',
        description: formData.get('template_description') || '',
        logo_url: formData.get('logo_url') || '',
        company_name: formData.get('company_name') || '',
        nif: formData.get('nif') || '',
        address: formData.get('address') || '',
        contact: formData.get('contact') || '',
        template: '41200-00001',
        primary_color: formData.get('primary_color') || '#e8951c',
        secondary_color: formData.get('secondary_color') || '#109c61',
        summary_note: formData.get('summary_note') || '',
        conditions_note: formData.get('conditions_note') || '',
        legal_note: formData.get('legal_note') || '',
        json_tariff_data: []
    };
    
    const payload = {
        id: formData.get('template_id') || null,
        name: formData.get('name'),
        description: formData.get('description'),
        template_data: templateData
    };
    
    fetch('save-template.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.href = 'templates.php?saved=1';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la plantilla');
    });
}

function useTemplate(templateId) {
    if (confirm('¿Usar esta plantilla para crear una nueva tarifa?')) {
        location.href = `upload-tariff.php?template=${templateId}`;
    }
}

function editTemplate(templateId) {
    openTemplateModal(templateId);
}

function duplicateTemplate(templateId) {
    if (confirm('¿Duplicar esta plantilla?')) {
        fetch('duplicate-template.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ template_id: templateId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function deleteTemplate(templateId) {
    if (confirm('¿Eliminar esta plantilla? Esta acción no se puede deshacer.')) {
        location.href = `templates.php?delete=1&id=${templateId}`;
    }
}