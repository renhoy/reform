// Custom Select inteligente
function initCustomSelect() {
    document.querySelectorAll('.badge-select').forEach(select => {
        if (select.dataset.customized) return; // Ya procesado
        createCustomSelectForElement(select);
    });
}

function shouldUseModal(selectElement) {
    const isMobile = window.innerWidth <= 768;
    return isMobile || true; // Siempre usar modal para consistencia
}

function getBadgeClass(selectValue) {
    const valueToClassMap = {
        'draft': 'badge--secondary',
        'pending': 'badge--warning', 
        'sent': 'badge--info',
        'approved': 'badge--success',
        'rejected': 'badge--danger',
        'expired': 'badge--black',
        'active': 'badge--success',
        'inactive': 'badge--danger',
        'public': 'badge--success',
        'private': 'badge--danger'
    };
    
    let baseClass = 'badge-select';
    const newBadgeClass = valueToClassMap[selectValue] || 'badge--secondary';
    return `${baseClass} ${newBadgeClass}`;
}

function createCustomSelectForElement(select) {
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-select-wrapper';
    
    const selectedOption = select.options[select.selectedIndex];
    const initialClasses = getBadgeClass(selectedOption.value);
    
    wrapper.innerHTML = `
        <div class="custom-select-display ${initialClasses}">
            ${selectedOption.text}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6,9 12,15 18,9"></polyline>
            </svg>
        </div>
    `;
    
    select.parentNode.insertBefore(wrapper, select);
    select.style.display = 'none';
    select.dataset.customized = 'true';
    
    const display = wrapper.querySelector('.custom-select-display');
    
    display.addEventListener('click', (e) => {
        e.stopPropagation();
        openSelectModal(select, wrapper);
    });
    
    select.addEventListener('change', () => {
        updateDisplayFromSelect(select, wrapper);
    });
    
    wrapper.dataset.originalSelect = select.dataset.customId = Date.now().toString();
}

function updateDisplayFromSelect(select, wrapper) {
    const display = wrapper.querySelector('.custom-select-display');
    const selectedOption = select.options[select.selectedIndex];
    const newClasses = getBadgeClass(selectedOption.value);
    
    display.innerHTML = `
        ${selectedOption.text}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6,9 12,15 18,9"></polyline>
        </svg>
    `;
    display.className = `custom-select-display ${newClasses}`;
}

function openSelectModal(select, wrapper) {
    let modal = document.getElementById('select-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'select-modal';
        modal.className = 'select-modal-overlay';
        modal.innerHTML = `
            <div class="select-modal">
                <div class="select-modal-header">
                    <div class="select-modal-title">Seleccionar opción</div>
                    <button class="select-modal-close" type="button">&times;</button>
                </div>
                <div class="select-modal-options"></div>
            </div>
        `;
        document.body.appendChild(modal);
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeSelectModal();
        });
        
        modal.querySelector('.select-modal-close').addEventListener('click', closeSelectModal);
    }
    
    const optionsContainer = modal.querySelector('.select-modal-options');
    optionsContainer.innerHTML = Array.from(select.options).map((option, index) => 
        `<div class="custom-option ${option.selected ? 'selected' : ''}" 
             data-value="${option.value}" 
             data-index="${index}">
            ${option.text}
        </div>`
    ).join('');
    
    optionsContainer.querySelectorAll('.custom-option').forEach(option => {
        option.addEventListener('click', () => {
            const index = parseInt(option.dataset.index);
            const previousValue = select.value;
            
            select.selectedIndex = index;
            
            if (select.value !== previousValue) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            closeSelectModal();
        });
    });
    
    modal.classList.add('show');
    
    const selectedOption = optionsContainer.querySelector('.custom-option.selected');
    if (selectedOption) {
        selectedOption.scrollIntoView({ block: 'center' });
    }
}

function closeSelectModal() {
    const modal = document.getElementById('select-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', initCustomSelect);

// Cerrar modal con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeSelectModal();
    }
});
