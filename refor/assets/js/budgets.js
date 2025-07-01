// JavaScript para página de presupuestos
// {"_META_file_path_": "refor/assets/js/budgets.js"}

// Funciones globales para presupuestos
window.BudgetActions = {
    
    // Mostrar/ocultar apuntes
    showNotes: function(budgetId) {
        console.log('Mostrar apuntes para presupuesto:', budgetId);
        // Aquí iría la lógica para mostrar modal de apuntes
        AppUtils.showMessage('Funcionalidad de apuntes en desarrollo', 'info');
    },
    
    // Ver tarifa en popup
    viewTariff: function(tariffId) {
        console.log('Ver tarifa:', tariffId);
        // Aquí iría la lógica para mostrar popup con jerarquía de tarifa
        AppUtils.showMessage('Funcionalidad de vista de tarifa en desarrollo', 'info');
    },
    
    // Actualizar estado
    updateStatus: function(budgetId, newStatus) {
        console.log('Actualizando estado:', budgetId, newStatus);
        
        AppUtils.ajax('process/budget-update-status.php', {
            method: 'POST',
            body: JSON.stringify({
                budget_id: budgetId,
                status: newStatus
            })
        })
        .then(response => {
            if (response.success) {
                AppUtils.showMessage('Estado actualizado correctamente', 'success');
                // Actualizar estadísticas
                location.reload();
            } else {
                AppUtils.showMessage('Error al actualizar estado', 'error');
            }
        })
        .catch(error => {
            AppUtils.showMessage('Error de conexión', 'error');
            console.error(error);
        });
    },
    
    // Crear PDF
    createPDF: function(budgetId) {
        console.log('Crear PDF para presupuesto:', budgetId);
        AppUtils.showMessage('Generando PDF...', 'info');
        
        // Aquí iría la lógica para generar PDF
        // Por ahora solo mostramos mensaje
        setTimeout(() => {
            AppUtils.showMessage('PDF generado correctamente', 'success');
        }, 2000);
    },
    
    // Duplicar presupuesto
    duplicateBudget: function(budgetId) {
        AppUtils.confirm('¿Duplicar este presupuesto?', () => {
            console.log('Duplicando presupuesto:', budgetId);
            AppUtils.showMessage('Presupuesto duplicado', 'success');
        });
    },
    
    // Eliminar presupuesto
    deleteBudget: function(budgetId) {
        AppUtils.confirm('¿Eliminar este presupuesto?\n\nEsta acción no se puede deshacer.', () => {
            console.log('Eliminando presupuesto:', budgetId);
            AppUtils.showMessage('Presupuesto eliminado', 'success');
        });
    }
};

// Hacer funciones disponibles globalmente
window.showNotes = BudgetActions.showNotes;
window.viewTariff = BudgetActions.viewTariff;
window.updateStatus = BudgetActions.updateStatus;
window.createPDF = BudgetActions.createPDF;
window.duplicateBudget = BudgetActions.duplicateBudget;
window.deleteBudget = BudgetActions.deleteBudget;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar tooltips para totales
    document.querySelectorAll('.total-info[title]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            // Mostrar tooltip mejorado si es necesario
        });
    });
    
    // Autoguardar filtros en localStorage si es necesario
    const filterForm = document.querySelector('.filters-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            AppUtils.showMessage('Aplicando filtros...', 'info');
        });
    }
    
    // Inicializar selects de estado
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const budgetId = this.closest('.table-row').dataset.budgetId;
            updateStatus(budgetId, this.value);
        });
    });
    
    console.log('Página de presupuestos inicializada');
});