// Manejadores iconos Lucide
// {"_META_file_path_": "public/assets/js/icon-buttons.js"}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iconos Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Manejadores de botones de acción
    initializeActionButtons();
});

function initializeActionButtons() {
    // Ver PDF
    document.querySelectorAll('.btn-view-pdf').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const url = this.dataset.pdfUrl;
            if (url) {
                window.open(url, '_blank');
            } else {
                e.preventDefault();
                alert('PDF no disponible');
            }
        });
    });
    
    // Crear PDF
    document.querySelectorAll('.btn-create-pdf').forEach(btn => {
        btn.addEventListener('click', function() {
            const budgetId = this.dataset.budgetId;
            createPDF(budgetId);
        });
    });
    
    // Generar presupuesto
    document.querySelectorAll('.btn-generate-budget').forEach(btn => {
        btn.addEventListener('click', function() {
            const tariffUuid = this.dataset.tariffUuid;
            window.location.href = `form.php?uuid=${tariffUuid}`;
        });
    });
    
    // Ver presupuestos de tarifa
    document.querySelectorAll('.btn-view-budgets').forEach(btn => {
        btn.addEventListener('click', function() {
            const tariffUuid = this.dataset.tariffUuid;
            window.location.href = `budgets.php?tariff_uuid=${tariffUuid}`;
        });
    });
    
    // Editar
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const entityType = this.dataset.type;
            const entityId = this.dataset.id;
            window.location.href = `edit-${entityType}.php?id=${entityId}`;
        });
    });
    
    // Duplicar
    document.querySelectorAll('.btn-duplicate').forEach(btn => {
        btn.addEventListener('click', function() {
            const entityType = this.dataset.type;
            const entityId = this.dataset.id;
            if (confirm('¿Duplicar este elemento?')) {
                window.location.href = `duplicate-${entityType}.php?id=${entityId}`;
            }
        });
    });
    
    // Borrar
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const entityType = this.dataset.type;
            const entityId = this.dataset.id;
            if (confirm('¿Eliminar este elemento? Esta acción no se puede deshacer.')) {
                window.location.href = `delete-${entityType}.php?id=${entityId}`;
            }
        });
    });
}

function createPDF(budgetId) {
    const btn = document.querySelector(`[data-budget-id="${budgetId}"]`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i>';
    }
    
    fetch('create-pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ budget_id: budgetId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar UI
            location.reload();
        } else {
            alert('Error al crear PDF: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error de conexión');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="file-stack"></i>';
            lucide.createIcons();
        }
    });
}

// Helper para crear botones de iconos
function createIconButton(icon, color = 'black', title = '', extraClasses = '') {
    return `<button class="btn-icon ${color} ${extraClasses}" title="${title}">
        <i data-lucide="${icon}"></i>
    </button>`;
}

// Helper para crear botones con contador
function createCounterButton(count, tariffUuid) {
    if (count > 0) {
        return `<button class="btn-icon black btn-view-budgets" data-tariff-uuid="${tariffUuid}" title="Ver ${count} presupuesto(s)">
            <i data-lucide="eye"></i>
            <span class="counter-badge">${count}</span>
        </button>`;
    }
    return '';
}