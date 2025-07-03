// Gestor de presupuestos
class BudgetsManager {
    constructor() {
        this.initEventListeners();
        this.initBadgeSelect();
    }

    initEventListeners() {
        document.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            
            if (action === 'duplicate-budget') this.handleDuplicateBudget(e);
            if (action === 'delete-budget') this.handleDeleteBudget(e);
        });
    }

    initBadgeSelect() {
        document.querySelectorAll('.badge-select[data-budget-id]').forEach(select => {
            if (select.dataset.listenerAttached) return;
            
            select.addEventListener('change', (e) => {
                this.handleBadgeChange(e.target);
            });
            
            select.dataset.listenerAttached = 'true';
        });
    }

    async handleBadgeChange(select) {
        const budgetId = select.dataset.budgetId;
        const status = select.value;
        
        if (!budgetId) return;

        try {
            const response = await fetch('process/update-budget-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    budget_id: budgetId,
                    status: status
                })
            });

            const result = await response.json();
            
            if (!result.success) {
                this.showError('Error al guardar');
                location.reload();
            } else {
                this.updateStats();
            }
        } catch (error) {
            this.showError('Error de conexión');
            location.reload();
        }
    }

    async updateStats() {
        try {
            const response = await fetch('process/get-budget-stats.php');
            const stats = await response.json();
            
            ['total', 'approved', 'rejected', 'sent', 'expired', 'pending', 'draft'].forEach(key => {
                const el = document.querySelector(`[data-stat="${key}"]`);
                if (el) el.textContent = stats[key] || 0;
            });
        } catch (error) {
            console.error('Error stats:', error);
        }
    }

    async handleDuplicateBudget(e) {
        const budgetId = e.target.closest('[data-budget-id]').dataset.budgetId;
        
        const response = await fetch('process/duplicate-budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ budget_id: budgetId })
        });

        const result = await response.json();
        
        if (result.success) {
            window.location.href = 'budgets.php?success=duplicated';
        } else {
            this.showError('Error al duplicar');
        }
    }

    handleDeleteBudget(e) {
        const budgetId = e.target.closest('[data-budget-id]').dataset.budgetId;
        
        if (!confirm('¿Eliminar presupuesto?')) return;

        fetch('process/delete-budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ budget_id: budgetId })
        }).then(r => r.json()).then(result => {
            if (result.success) {
                window.location.href = 'budgets.php?success=deleted';
            } else {
                this.showError('Error al eliminar');
            }
        });
    }

    showError(message) {
        const div = document.createElement('div');
        div.style.cssText = 'position:fixed;top:20px;right:20px;background:#ef4444;color:white;padding:10px;border-radius:5px;z-index:9999';
        div.textContent = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    window.budgetsManager = new BudgetsManager();
});
