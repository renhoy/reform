// JavaScript para gestión de tarifas
// {"_META_file_path_": "refor/assets/js/tariffs.js"}

class TariffsManager {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        // Delegación de eventos para botones de acción
        document.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            
            switch(action) {
                case 'view-tariff':
                    this.handleViewTariff(e);
                    break;
                case 'duplicate-tariff':
                    this.handleDuplicateTariff(e);
                    break;
                case 'delete-tariff':
                    this.handleDeleteTariff(e);
                    break;
                case 'toggle-access':
                    this.handleToggleAccess(e);
                    break;
                case 'toggle-status':
                    this.handleToggleStatus(e);
                    break;
                case 'close-modal':
                    this.closeModal();
                    break;
            }
        });

        // Cerrar modal al hacer click fuera
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    // Ver detalles de tarifa
    handleViewTariff(e) {
        const tariffData = JSON.parse(e.target.closest('[data-tariff-data]').dataset.tariffData);
        this.displayTariffDetails(tariffData);
        this.showModal('tariffModal');
    }

    displayTariffDetails(tariff) {
        const content = document.getElementById('tariffContent');
        
        content.innerHTML = `
            <div class="tariff-details">
                <div class="tariff-details__section">
                    <h4>Información General</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Título:</label>
                            <span>${this.escapeHtml(tariff.title || 'No disponible')}</span>
                        </div>
                        <div class="detail-item">
                            <label>Descripción:</label>
                            <span>${this.escapeHtml(tariff.description || 'Sin descripción')}</span>
                        </div>
                        <div class="detail-item">
                            <label>UUID:</label>
                            <span class="code">${tariff.uuid}</span>
                        </div>
                    </div>
                </div>

                <div class="tariff-details__section">
                    <h4>Información de Empresa</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Nombre:</label>
                            <span>${this.escapeHtml(tariff.name || 'No disponible')}</span>
                        </div>
                        <div class="detail-item">
                            <label>NIF:</label>
                            <span>${this.escapeHtml(tariff.nif || 'No disponible')}</span>
                        </div>
                        <div class="detail-item">
                            <label>Dirección:</label>
                            <span>${this.escapeHtml(tariff.address || 'No disponible')}</span>
                        </div>
                        <div class="detail-item">
                            <label>Contacto:</label>
                            <span>${this.escapeHtml(tariff.contact || 'No disponible')}</span>
                        </div>
                    </div>
                </div>

                <div class="tariff-details__section">
                    <h4>Configuración</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Plantilla:</label>
                            <span>${this.escapeHtml(tariff.template || 'Por defecto')}</span>
                        </div>
                        <div class="detail-item">
                            <label>Color primario:</label>
                            <div class="color-preview">
                                <span class="color-swatch" style="background-color: ${tariff.primary_color}"></span>
                                <span>${tariff.primary_color}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Color secundario:</label>
                            <div class="color-preview">
                                <span class="color-swatch" style="background-color: ${tariff.secondary_color}"></span>
                                <span>${tariff.secondary_color}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tariff-details__section">
                    <h4>Estados</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Acceso:</label>
                            <span class="badge badge--${tariff.access}">${this.capitalize(tariff.access)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Estado:</label>
                            <span class="badge badge--${tariff.status}">${this.capitalize(tariff.status)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Presupuestos:</label>
                            <span class="budgets-count">${tariff.budgets_count || 0}</span>
                        </div>
                    </div>
                </div>

                ${tariff.summary_note || tariff.conditions_note || tariff.legal_note ? `
                <div class="tariff-details__section">
                    <h4>Notas</h4>
                    ${tariff.summary_note ? `
                        <div class="detail-item">
                            <label>Resumen:</label>
                            <p>${this.escapeHtml(tariff.summary_note)}</p>
                        </div>
                    ` : ''}
                    ${tariff.conditions_note ? `
                        <div class="detail-item">
                            <label>Condiciones:</label>
                            <p>${this.escapeHtml(tariff.conditions_note)}</p>
                        </div>
                    ` : ''}
                    ${tariff.legal_note ? `
                        <div class="detail-item">
                            <label>Nota legal:</label>
                            <p>${this.escapeHtml(tariff.legal_note)}</p>
                        </div>
                    ` : ''}
                </div>
                ` : ''}
            </div>
        `;
    }

    // Duplicar tarifa
    async handleDuplicateTariff(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        
        try {
            this.showLoading('Duplicando tarifa...');
            
            const response = await fetch('process/duplicate-tariff.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tariff_id: tariffId })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Tarifa duplicada correctamente', 'success');
                setTimeout(() => {
                    window.location.href = 'tariffs.php?success=duplicated';
                }, 1500);
            } else {
                this.showNotification(result.message || 'Error al duplicar tarifa', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error al duplicar tarifa', 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Eliminar tarifa
    handleDeleteTariff(e) {
        const tariffId = e.target.closest('[data-tariff-id]').dataset.tariffId;
        const tariffName = e.target.closest('[data-tariff-name]').dataset.tariffName;
        
        this.showConfirmDialog(
            'Eliminar Tarifa',
            `¿Estás seguro de que quieres eliminar la tarifa "${tariffName}"? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    this.showLoading('Eliminando tarifa...');
                    
                    const response = await fetch('process/delete-tariff.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ tariff_id: tariffId })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        this.showNotification('Tarifa eliminada correctamente', 'success');
                        setTimeout(() => {
                            window.location.href = 'tariffs.php?success=deleted';
                        }, 1500);
                    } else {
                        this.showNotification(result.message || 'Error al eliminar tarifa', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showNotification('Error al eliminar tarifa', 'error');
                } finally {
                    this.hideLoading();
                }
            }
        );
    }

    // Cambiar acceso (público/privado)
    async handleToggleAccess(e) {
        const tariffId = e.target.dataset.tariffId;
        const currentAccess = e.target.classList.contains('badge--public') ? 'public' : 'private';
        const newAccess = currentAccess === 'public' ? 'private' : 'public';
        
        try {
            const response = await fetch('process/update-tariff-access.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    tariff_id: tariffId,
                    access: newAccess 
                })
            });

            const result = await response.json();
            
            if (result.success) {
                // Actualizar UI
                e.target.className = e.target.className.replace(
                    `badge--${currentAccess}`, 
                    `badge--${newAccess}`
                );
                e.target.textContent = this.capitalize(newAccess);
                
                this.showNotification('Acceso actualizado correctamente', 'success');
            } else {
                this.showNotification(result.message || 'Error al actualizar acceso', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error al actualizar acceso', 'error');
        }
    }

    // Cambiar estado (activo/inactivo)
    async handleToggleStatus(e) {
        const tariffId = e.target.dataset.tariffId;
        const currentStatus = e.target.classList.contains('badge--active') ? 'active' : 'inactive';
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        try {
            const response = await fetch('process/update-tariff-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    tariff_id: tariffId,
                    status: newStatus 
                })
            });

            const result = await response.json();
            
            if (result.success) {
                // Actualizar UI
                e.target.className = e.target.className.replace(
                    `badge--${currentStatus}`, 
                    `badge--${newStatus}`
                );
                e.target.textContent = this.capitalize(newStatus);
                
                this.showNotification('Estado actualizado correctamente', 'success');
            } else {
                this.showNotification(result.message || 'Error al actualizar estado', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error al actualizar estado', 'error');
        }
    }

    // Utilidades para modales
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }

    showConfirmDialog(title, message, onConfirm) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        
        const confirmButton = document.getElementById('confirmButton');
        confirmButton.onclick = () => {
            this.closeModal();
            onConfirm();
        };
        
        this.showModal('confirmModal');
    }

    // Utilidades para notificaciones
    showNotification(message, type = 'info') {
        // Remover notificaciones existentes
        const existing = document.querySelector('.notification-toast');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `notification-toast notification-toast--${type}`;
        
        const icon = type === 'success' ? 'check-circle' : 
                    type === 'error' ? 'alert-circle' : 'info';
        
        notification.innerHTML = `
            <i data-lucide="${icon}"></i>
            <span>${message}</span>
            <button class="notification-toast__close">
                <i data-lucide="x"></i>
            </button>
        `;

        document.body.appendChild(notification);
        lucide.createIcons();

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Cerrar manualmente
        notification.querySelector('.notification-toast__close').addEventListener('click', () => {
            notification.remove();
        });
    }

    showLoading(message = 'Cargando...') {
        // Remover loading existente
        const existing = document.querySelector('.loading-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="spinner"></div>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(overlay);
    }

    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }

    // Utilidades
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Exportar para uso global
window.TariffsManager = TariffsManager;