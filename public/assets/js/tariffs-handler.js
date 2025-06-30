// Manejador para sistema de tarifas
// {"_META_file_path_": "public/assets/js/tariffs-handler.js"}

document.addEventListener('DOMContentLoaded', function() {
    initializeSelects();
});

function initializeSelects() {
    // Status selects
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            updateTariffField(this.dataset.tariffId, this.dataset.field, this.value);
        });
    });

    // Access selects
    document.querySelectorAll('.access-select').forEach(select => {
        select.addEventListener('change', function() {
            updateTariffField(this.dataset.tariffId, this.dataset.field, this.value);
        });
    });
}

function updateTariffField(tariffId, field, value) {
    fetch('tariffs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `tariff_id=${tariffId}&field=${field}&value=${value}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error al actualizar');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        location.reload();
    });
}

function viewBudgets(tariffId) {
    window.location.href = `budgets.php?tariff_id=${tariffId}`;
}

function duplicateTariff(tariffId) {
    if (confirm('¿Duplicar esta tarifa?')) {
        fetch('duplicate-tariff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tariff_id: tariffId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al duplicar');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function deleteTariff(tariffId) {
    if (confirm('¿Eliminar esta tarifa? Esta acción no se puede deshacer.')) {
        fetch('delete-tariff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tariff_id: tariffId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}