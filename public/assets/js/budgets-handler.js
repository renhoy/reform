// Manejador para listado de presupuestos
// {"_META_file_path_": "public/assets/js/budgets-handler.js"}

let currentBudgetId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeFilters();
});

function initializeEventListeners() {
    // Apuntes modal
    document.querySelectorAll('.notes-icon').forEach(icon => {
        icon.addEventListener('click', function() {
            currentBudgetId = this.dataset.budgetId;
            openNotesModal(currentBudgetId);
        });
    });

    // Tarifa modal
    document.querySelectorAll('.btn-view-tariff').forEach(btn => {
        btn.addEventListener('click', function() {
            const tariffData = JSON.parse(this.dataset.tariffData);
            openTariffModal(tariffData);
        });
    });

    // Status change
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            updateBudgetStatus(this.dataset.budgetId, this.value);
        });
    });

    // Modal close
    document.querySelectorAll('.close-modal').forEach(close => {
        close.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Click outside modal
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

function initializeFilters() {
    const searchInput = document.getElementById('searchClient');
    const statusFilter = document.getElementById('filterStatus');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');

    [searchInput, statusFilter, dateFrom, dateTo].forEach(element => {
        element.addEventListener('input', filterBudgets);
    });
}

function filterBudgets() {
    const searchTerm = document.getElementById('searchClient').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    document.querySelectorAll('.table-row').forEach(row => {
        const clientText = row.querySelector('.client-info').textContent.toLowerCase();
        const status = row.querySelector('.status-select').value;
        const rowDate = row.querySelector('.date-info').textContent;

        let show = true;

        if (searchTerm && !clientText.includes(searchTerm)) show = false;
        if (statusFilter && status !== statusFilter) show = false;
        // Filtros de fecha se pueden implementar según necesidad

        row.style.display = show ? 'grid' : 'none';
    });
}

function openNotesModal(budgetId) {
    fetch(`get-budget-notes.php?id=${budgetId}`)
        .then(response => response.json())
        .then(data => {
            displayNotesHistory(data.observations || []);
            document.getElementById('notesModal').style.display = 'block';
        })
        .catch(error => console.error('Error:', error));
}

function displayNotesHistory(observations) {
    const container = document.getElementById('notesHistory');
    
    if (observations.length === 0) {
        container.innerHTML = '<p>No hay apuntes para este presupuesto.</p>';
        return;
    }

    const categoryIcons = {
        'llamada': '📞',
        'email': '📧',
        'reunion': '🤝',
        'nota': '📝'
    };

    container.innerHTML = observations.map(obs => `
        <div class="note-item">
            <div class="note-header">
                ${categoryIcons[obs.category] || '📝'} ${obs.user} - ${obs.timestamp}
            </div>
            <div class="note-content">${obs.note}</div>
        </div>
    `).join('');
}

function addNote() {
    const category = document.getElementById('noteCategory').value;
    const text = document.getElementById('noteText').value.trim();
    
    if (!text) {
        alert('Introduce un texto para el apunte');
        return;
    }

    const noteData = {
        budget_id: currentBudgetId,
        category: category,
        note: text
    };

    fetch('add-budget-note.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(noteData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('noteText').value = '';
            openNotesModal(currentBudgetId); // Refresh
            
            // Update icon if first note
            const icon = document.querySelector(`[data-budget-id="${currentBudgetId}"]`);
            if (icon) icon.classList.add('has-notes');
        }
    })
    .catch(error => console.error('Error:', error));
}

function openTariffModal(tariffData) {
    const container = document.getElementById('tariffHierarchy');
    container.innerHTML = buildTariffHierarchy(tariffData);
    document.getElementById('tariffModal').style.display = 'block';
}

function buildTariffHierarchy(tariffData) {
    if (!tariffData || !Array.isArray(tariffData)) {
        return '<p>No se pudieron cargar los datos de la tarifa.</p>';
    }

    return tariffData.map(item => {
        let html = `<div class="hierarchy-item ${item.level}">`;
        html += `<strong>${item.name}</strong>`;
        
        if (item.level === 'item') {
            html += `<span class="item-icons">📄📐📊💰</span>`;
        }
        
        html += `</div>`;
        return html;
    }).join('');
}

function updateBudgetStatus(budgetId, newStatus) {
    fetch('update-budget-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            budget_id: budgetId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update stats if needed
            location.reload(); // Simple refresh for stats
        } else {
            alert('Error al actualizar el estado');
        }
    })
    .catch(error => console.error('Error:', error));
}

function createPDF(budgetId) {
    fetch('create-budget-pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ budget_id: budgetId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.open(data.pdf_url, '_blank');
            location.reload(); // Update button state
        } else {
            alert('El servidor RapidPDF no responde inténtalo pasados unos minutos. Si persiste el error póngase en contacto con el administrador.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('El servidor RapidPDF no responde inténtalo pasados unos minutos. Si persiste el error póngase en contacto con el administrador.');
    });
}

function editBudget(uuid) {
    window.location.href = `budget-edit.php?uuid=${uuid}`;
}

function duplicateBudget(budgetId) {
    if (confirm('¿Duplicar este presupuesto?')) {
        fetch('duplicate-budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ budget_id: budgetId })
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

function deleteBudget(budgetId) {
    if (confirm('¿Eliminar este presupuesto? Esta acción no se puede deshacer.')) {
        fetch('delete-budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ budget_id: budgetId })
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