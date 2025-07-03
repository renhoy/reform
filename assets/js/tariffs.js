// Solución optimizada para badge-select en tarifas
// {"_META_file_path_": "refor/assets/js/tariffs.js"}

class TariffsManager {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        // Escuchar eventos de click para acciones generales
        document.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            
            if (action === 'duplicate-tariff') this.handleDuplicateTariff(e);
            if (action === 'delete-tariff') this.handleDeleteTariff(e);
        });

        // Escuchar cambios en los badge-select
        document.addEventListener('change', async (e) => {
            if (e.target.matches('.badge-select[data-tariff-id]')) {
                await this.handleBadgeChange(e.target);
            }
        });
    }

    async handleBadgeChange(select) {
        const tariffId = select.dataset.tariffId;
        const action = select.dataset.action;
        const value = select.value;
        
        if (!tariffId || !action) return;

        try {
            const endpoint = action === 'toggle-access' ? 
                'process/update-tariff-access.php' : 
                'process/update-tariff-status.php';
                
            const field = action === 'toggle-access' ? 'access' : 'status';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tariff_id: tariffId,
                    [field]: value
                })
            });

            const result = await response.json();
            
            if (!result.success) {
                this.showError('Error al guardar');
                select.value = value === 'active' ? 'inactive' : 'active';
                // Disparar evento change para actualizar UI
                select.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            this.showError('Error de conexión');
            select.value = value === 'active' ? 'inactive' : 'active';
            // Disparar evento change para actualizar UI
            select.dispatchEvent(new Event('change'));
        }
    }

    async handleDuplicateTariff(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        
        const response = await fetch('process/duplicate-tariff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tariff_id: tariffId })
        });

        const result = await response.json();
        
        if (result.success) {
            window.location.href = 'tariffs.php?success=duplicated';
        } else {
            this.showError('Error al duplicar');
        }
    }

    handleDeleteTariff(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        
        if (!confirm('¿Eliminar tarifa?')) return;

        fetch('process/delete-tariff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tariff_id: tariffId })
        }).then(r => r.json()).then(result => {
            if (result.success) {
                window.location.href = 'tariffs.php?success=deleted';
            } else {
                this.showError('Error al eliminar');
            }
        });
    }

    showError(message) {
        const notification = document.createElement('div');
        notification.className = 'notification notification--error';
        notification.innerHTML = `
            <i data-lucide="alert-circle"></i>
            <span>${message}</span>
        `;
        
        document.querySelector('.container').insertAdjacentElement('afterbegin', notification);
        lucide.createIcons();
        
        setTimeout(() => notification.remove(), 3000);
    }
}

window.TariffsManager = TariffsManager;