// Manager para formulario de creación de presupuestos
// {"_META_file_path_": "refor/assets/js/budget-form.js"}

class BudgetFormManager {
    constructor() {
        this.initEventListeners();
        this.calculateTotals();
    }

    initEventListeners() {
        // Selector de tipo de cliente
        document.querySelectorAll('.client-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleClientTypeChange(e.target.dataset.type);
            });
        });

        // Cambios en cantidades
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', () => {
                this.updateItemTotal(input);
                this.calculateTotals();
            });
        });

        // Validación del formulario
        document.getElementById('budgetForm').addEventListener('submit', (e) => {
            this.handleFormSubmit(e);
        });
    }

    handleClientTypeChange(type) {
        // Actualizar botones activos
        document.querySelectorAll('.client-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-type="${type}"]`).classList.add('active');
        
        // Actualizar campo hidden
        document.getElementById('clientType').value = type;
        
        // Mostrar/ocultar campos según tipo
        const webGroup = document.getElementById('webGroup');
        const nifGroup = document.getElementById('nifGroup');
        const nifLabel = nifGroup.querySelector('label');
        
        if (type === 'empresa') {
            webGroup.style.display = 'block';
            nifLabel.textContent = 'NIF *';
            nifGroup.querySelector('input').required = true;
        } else if (type === 'autonomo') {
            webGroup.style.display = 'none';
            nifLabel.textContent = 'NIF/NIE *';
            nifGroup.querySelector('input').required = true;
        } else {
            webGroup.style.display = 'none';
            nifLabel.textContent = 'NIF/NIE';
            nifGroup.querySelector('input').required = false;
        }
    }

    updateItemTotal(input) {
        const quantity = parseFloat(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        const total = quantity * price;
        
        const totalElement = document.getElementById(`total_${input.name.match(/\[(.*?)\]/)[1]}`);
        if (totalElement) {
            totalElement.textContent = this.formatPrice(total);
        }
    }

    calculateTotals() {
        let baseTotal = 0;
        let ivaTotal = 0;
        
        document.querySelectorAll('.quantity-input').forEach(input => {
            const quantity = parseFloat(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const ivaRate = parseFloat(input.dataset.iva) || 21;
            
            const itemTotal = quantity * price;
            const baseAmount = itemTotal / (1 + ivaRate / 100);
            const ivaAmount = itemTotal - baseAmount;
            
            baseTotal += baseAmount;
            ivaTotal += ivaAmount;
        });
        
        const finalTotal = baseTotal + ivaTotal;
        
        document.getElementById('baseAmount').textContent = this.formatPrice(baseTotal);
        document.getElementById('ivaAmount').textContent = this.formatPrice(ivaTotal);
        document.getElementById('totalAmount').textContent = this.formatPrice(finalTotal);
    }

    handleFormSubmit(e) {
        const form = e.target;
        const formData = new FormData(form);
        
        // Validar que hay al menos una partida con cantidad > 0
        let hasItems = false;
        document.querySelectorAll('.quantity-input').forEach(input => {
            if (parseFloat(input.value) > 0) {
                hasItems = true;
            }
        });
        
        if (!hasItems) {
            e.preventDefault();
            this.showNotification('Debe seleccionar al menos una partida con cantidad mayor a 0', 'error');
            return;
        }
        
        // Validar campos requeridos del cliente
        const clientName = formData.get('client_name');
        if (!clientName.trim()) {
            e.preventDefault();
            this.showNotification('El nombre del cliente es requerido', 'error');
            return;
        }
        
        const clientType = formData.get('client_type');
        const nifValue = formData.get('client_nif_nie');
        
        if ((clientType === 'empresa' || clientType === 'autonomo') && !nifValue.trim()) {
            e.preventDefault();
            this.showNotification(`El NIF${clientType === 'empresa' ? '' : '/NIE'} es requerido para ${clientType}s`, 'error');
            return;
        }
        
        // Mostrar loading
        this.showLoading('Creando presupuesto...');
    }

    formatPrice(amount) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    showNotification(message, type = 'info') {
        const existing = document.querySelector('.notification-toast');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `notification-toast notification-toast--${type}`;
        notification.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 4000);
    }

    showLoading(message = 'Procesando...') {
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
}

window.BudgetFormManager = BudgetFormManager;