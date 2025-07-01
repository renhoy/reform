// JavaScript específico para página de tarifas
// {"_META_file_path_": "refor/assets/js/tariffs.js"}

class TariffsManager {
    constructor() {
        this.initialize();
    }

    initialize() {
        this.initializeEventListeners();
        this.initializeLucideIcons();
    }

    initializeEventListeners() {
        // Botones de generar presupuesto
        document.querySelectorAll('.btn-generate').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleGenerate(e));
        });

        // Botones de ver presupuestos
        document.querySelectorAll('.btn-view-budgets').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleViewBudgets(e));
        });

        // Botones de editar
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleEdit(e));
        });

        // Botones de duplicar
        document.querySelectorAll('.btn-duplicate').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDuplicate(e));
        });

        // Botones de borrar
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDelete(e));
        });
    }

    initializeLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Generar presupuesto
    handleGenerate(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        window.location.href = `form.php?tariff_id=${tariffId}`;
    }

    // Ver presupuestos
    handleViewBudgets(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        window.location.href = `budgets.php?tariff_id=${tariffId}`;
    }

    // Editar tarifa
    handleEdit(e) {
        const tariffId = e.target.closest('[data-id]').dataset.id;
        window.location.href = `edit-tariff.php?id=${tariffId}`;
    }

    // Duplicar tarifa
    async handleDuplicate(e) {
        const tariffId = e.target.closest('[data-id]').dataset.id;
        
        if (!confirm('¿Duplicar esta tarifa?')) return;

        try {
            const response = await AppUtils.request('process/duplicate-tariff.php', {
                method: 'POST',
                body: JSON.stringify({
                    tariff_id: tariffId
                })
            });

            if (response.success) {
                AppUtils.showNotification('Tarifa duplicada correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(response.message || 'Error desconocido');
            }
        } catch (error) {
            AppUtils.showNotification('Error al duplicar tarifa', 'error');
        }
    }

    // Eliminar tarifa
    async handleDelete(e) {
        const tariffId = e.target.closest('[data-id]').dataset.id;
        
        if (!confirm('¿Eliminar esta tarifa?\n\nEsta acción no se puede deshacer.')) return;

        try {
            const response = await AppUtils.request('process/delete-tariff.php', {
                method: 'POST',
                body: JSON.stringify({
                    tariff_id: tariffId
                })
            });

            if (response.success) {
                AppUtils.showNotification('Tarifa eliminada correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(response.message || 'Error desconocido');
            }
        } catch (error) {
            AppUtils.showNotification(error.message || 'Error al eliminar tarifa', 'error');
        }
    }
}

// Funciones globales para los badges clickeables
window.toggleAccess = async function(element) {
    const tariffId = element.dataset.tariffId;
    const currentAccess = element.classList.contains('private') ? 'private' : 'public';
    const newAccess = currentAccess === 'private' ? 'public' : 'private';

    try {
        const response = await AppUtils.request('process/update-tariff-access.php', {
            method: 'POST',
            body: JSON.stringify({
                tariff_id: tariffId,
                access: newAccess
            })
        });

        if (response.success) {
            element.classList.remove('private', 'public');
            element.classList.add(newAccess);
            element.textContent = newAccess === 'private' ? 'Privado' : 'Público';
            AppUtils.showNotification('Acceso actualizado correctamente', 'success');
        } else {
            throw new Error(response.message || 'Error al actualizar');
        }
    } catch (error) {
        AppUtils.showNotification('Error al actualizar acceso', 'error');
    }
};

window.toggleStatus = async function(element) {
    const tariffId = element.dataset.tariffId;
    const currentStatus = element.classList.contains('active') ? 'active' : 'inactive';
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

    try {
        const response = await AppUtils.request('process/update-tariff-status.php', {
            method: 'POST',
            body: JSON.stringify({
                tariff_id: tariffId,
                status: newStatus
            })
        });

        if (response.success) {
            element.classList.remove('active', 'inactive');
            element.classList.add(newStatus);
            element.textContent = newStatus === 'active' ? 'Activa' : 'Inactiva';
            
            // Actualizar visibilidad del botón generar
            const generateBtn = document.querySelector(`.btn-generate[data-tariff-id="${tariffId}"]`);
            if (generateBtn) {
                generateBtn.style.display = newStatus === 'active' ? 'flex' : 'none';
            }
            
            AppUtils.showNotification('Estado actualizado correctamente', 'success');
        } else {
            throw new Error(response.message || 'Error al actualizar');
        }
    } catch (error) {
        AppUtils.showNotification('Error al actualizar estado', 'error');
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.tariffsManager = new TariffsManager();
});