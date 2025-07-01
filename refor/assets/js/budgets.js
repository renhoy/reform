// JavaScript específico para página de presupuestos
// {"_META_file_path_": "refor/assets/js/budgets.js"}

class BudgetsManager {
    constructor() {
        this.currentBudgetId = null;
        this.initialize();
    }

    initialize() {
        this.initializeEventListeners();
        this.initializeModals();
        this.initializeLucideIcons();
    }

    initializeEventListeners() {
        // Botones de apuntes
        document.querySelectorAll('.notes-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleNotesClick(e));
        });

        // Selectores de estado
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', (e) => this.handleStatusChange(e));
        });

        // Botones de ver tarifa
        document.querySelectorAll('.view-tariff').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleViewTariff(e));
        });

        // Botones de ver PDF
        document.querySelectorAll('.btn-view-pdf').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleViewPDF(e));
        });

        // Botones de crear PDF
        document.querySelectorAll('.btn-create-pdf').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCreatePDF(e));
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

        // Formulario de apuntes
        const addNoteForm = document.getElementById('addNoteForm');
        if (addNoteForm) {
            addNoteForm.addEventListener('submit', (e) => this.handleAddNote(e));
        }
    }

    initializeModals() {
        // Cerrar modales
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });

        // Cerrar modal al hacer click fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
    }

    initializeLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Gestión de apuntes
    async handleNotesClick(e) {
        const budgetId = e.target.closest('[data-budget-id]').dataset.budgetId;
        this.currentBudgetId = budgetId;
        
        try {
            const notes = await this.fetchBudgetNotes(budgetId);
            this.displayNotes(notes);
            this.showModal('notesModal');
        } catch (error) {
            AppUtils.showNotification('Error al cargar apuntes', 'error');
        }
    }

    async fetchBudgetNotes(budgetId) {
        const response = await fetch(`process/get-budget-notes.php?budget_id=${budgetId}`);
        if (!response.ok) throw new Error('Error al obtener apuntes');
        return await response.json();
    }

    displayNotes(notes) {
        const container = document.getElementById('notesHistory');
        
        if (!notes.length) {
            container.innerHTML = '<p class="empty-notes">No hay apuntes registrados</p>';
            return;
        }

        container.innerHTML = notes.map(note => `
            <div class="note-item">
                <div class="note-header">
                    <span>${note.category} ${note.user}</span>
                    <span>${AppUtils.formatDateTime(note.timestamp)}</span>
                </div>
                <div class="note-text">${AppUtils.escapeHtml(note.note)}</div>
            </div>
        `).join('');
    }

    async handleAddNote(e) {
        e.preventDefault();
        
        const category = document.getElementById('noteCategory').value;
        const noteText = document.getElementById('noteText').value.trim();
        
        if (!noteText) return;

        try {
            const response = await fetch('process/add-budget-note.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    budget_id: this.currentBudgetId,
                    category: category,
                    note: noteText
                })
            });

            if (!response.ok) throw new Error('Error al añadir apunte');

            // Recargar apuntes
            const notes = await this.fetchBudgetNotes(this.currentBudgetId);
            this.displayNotes(notes);
            
            // Limpiar formulario
            document.getElementById('noteText').value = '';
            
            AppUtils.showNotification('Apunte añadido correctamente', 'success');
        } catch (error) {
            AppUtils.showNotification('Error al añadir apunte', 'error');
        }
    }

    // Gestión de estados
    async handleStatusChange(e) {
        const budgetId = e.target.dataset.budgetId;
        const newStatus = e.target.value;
        const oldStatus = e.target.dataset.oldValue || e.target.querySelector('option[selected]')?.value;

        try {
            const response = await fetch('process/update-budget-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    budget_id: budgetId,
                    status: newStatus
                })
            });

            if (!response.ok) throw new Error('Error al actualizar estado');

            // Actualizar estadísticas
            await this.updateStats();
            
            AppUtils.showNotification('Estado actualizado correctamente', 'success');
            
            // Guardar valor actual para futuras comparaciones
            e.target.dataset.oldValue = newStatus;

        } catch (error) {
            // Revertir cambio
            e.target.value = oldStatus;
            AppUtils.showNotification('Error al actualizar estado', 'error');
        }
    }

    async updateStats() {
        try {
            const response = await fetch('process/get-budget-stats.php');
            if (!response.ok) throw new Error('Error al obtener estadísticas');
            
            const stats = await response.json();
            
            const statsElement = document.querySelector('.stats-summary');
            statsElement.textContent = `Presupuestos Realizados (${stats.total}): 👍 Aprobados (${stats.approved}), ❌ Rechazados (${stats.rejected}), 📤 Enviados (${stats.sent}), ⏰ Expirados (${stats.expired}), ⏸️ Pendientes (${stats.pending}), ✍️ Borrador (${stats.draft})`;
        } catch (error) {
            console.error('Error actualizando estadísticas:', error);
        }
    }

    // Visualización de tarifa
    handleViewTariff(e) {
        const tariffData = JSON.parse(e.target.dataset.tariffData);
        this.displayTariff(tariffData);
        this.showModal('tariffModal');
    }

    displayTariff(tariffData) {
        const container = document.getElementById('tariffContent');
        
        // Mostrar información básica de la tarifa
        container.innerHTML = `
            <div class="tariff-info">
                <h4>Información de la Empresa</h4>
                <p><strong>Nombre:</strong> ${AppUtils.escapeHtml(tariffData.name || 'No disponible')}</p>
                <p><strong>NIF:</strong> ${AppUtils.escapeHtml(tariffData.nif || 'No disponible')}</p>
                <p><strong>Dirección:</strong> ${AppUtils.escapeHtml(tariffData.address || 'No disponible')}</p>
                <p><strong>Contacto:</strong> ${AppUtils.escapeHtml(tariffData.contact || 'No disponible')}</p>
            </div>
            
            <div class="tariff-structure">
                <h4>Estructura de la Tarifa</h4>
                <p>La estructura detallada de partidas se mostraría aquí con la jerarquía completa.</p>
            </div>
        `;
    }

    // Gestión de PDFs
    handleViewPDF(e) {
        const pdfUrl = e.target.closest('[data-pdf-url]').dataset.pdfUrl;
        if (pdfUrl) {
            window.open(pdfUrl, '_blank');
        }
    }

    async handleCreatePDF(e) {
        const budgetId = e.target.closest('[data-budget-id]').dataset.budgetId;
        const button = e.target.closest('button');
        
        // Deshabilitar botón durante el proceso
        button.disabled = true;
        button.style.opacity = '0.5';
        
        try {
            const response = await fetch('process/create-budget-pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    budget_id: budgetId
                })
            });

            if (!response.ok) throw new Error('Error al crear PDF');

            const result = await response.json();
            
            if (result.success && result.pdf_url) {
                // Abrir PDF y recargar página
                window.open(result.pdf_url, '_blank');
                setTimeout(() => location.reload(), 1000);
                
                AppUtils.showNotification('PDF creado correctamente', 'success');
            } else {
                throw new Error(result.message || 'Error desconocido');
            }

        } catch (error) {
            AppUtils.showNotification('Error al crear PDF', 'error');
        } finally {
            button.disabled = false;
            button.style.opacity = '1';
        }
    }

    // Acciones CRUD
    handleEdit(e) {
        const budgetId = e.target.closest('[data-id]').dataset.id;
        // Redirigir a página de edición
        window.location.href = `edit-budget.php?id=${budgetId}`;
    }

    async handleDuplicate(e) {
        const budgetId = e.target.closest('[data-id]').dataset.id;
        
        if (!confirm('¿Duplicar este presupuesto?')) return;

        try {
            const response = await fetch('process/duplicate-budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    budget_id: budgetId
                })
            });

            if (!response.ok) throw new Error('Error al duplicar');

            const result = await response.json();
            
            if (result.success) {
                AppUtils.showNotification('Presupuesto duplicado correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(result.message || 'Error desconocido');
            }

        } catch (error) {
            AppUtils.showNotification('Error al duplicar presupuesto', 'error');
        }
    }

    async handleDelete(e) {
        const budgetId = e.target.closest('[data-id]').dataset.id;
        
        if (!confirm('¿Eliminar este presupuesto?\n\nEsta acción no se puede deshacer.')) return;

        try {
            const response = await fetch('process/delete-budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    budget_id: budgetId
                })
            });

            if (!response.ok) throw new Error('Error al eliminar');

            const result = await response.json();
            
            if (result.success) {
                AppUtils.showNotification('Presupuesto eliminado correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(result.message || 'Error desconocido');
            }

        } catch (error) {
            AppUtils.showNotification('Error al eliminar presupuesto', 'error');
        }
    }

    // Utilidades de modal
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.budgetsManager = new BudgetsManager();
});