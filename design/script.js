// Custom Select inteligente - Popup modal cuando es necesario
// {"_META_file_path_": "design/script2.js"}

function initCustomSelect() {
    // Procesar todos los badge-select sin excepción de plataforma
    document.querySelectorAll('.badge-select').forEach(select => {
        if (select.dataset.customized) return; // Ya procesado
        
        createCustomSelectForElement(select);
    });
}

function shouldUseModal(selectElement) {
    const isMobile = window.innerWidth <= 768;
    
    // Siempre usar modal en móvil/tablet
    if (isMobile) return true;
    
    // En desktop, detectar si habría overflow
    const rect = selectElement.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const estimatedDropdownHeight = 160; // altura máxima aproximada del dropdown
    
    return (rect.bottom + estimatedDropdownHeight) > viewportHeight;
}

function getBadgeClass(selectValue, selectElement) {
    // Mapear valores a clases de badge según el contexto
    const valueToClassMap = {
        // Estados de presupuestos
        'draft': 'badge--secondary',
        'pending': 'badge--warning', 
        'sent': 'badge--info',
        'approved': 'badge--success',
        'rejected': 'badge--danger',
        'expired': 'badge--black',
        
        // Estados de tarifas/plantillas
        'active': 'badge--success',
        'inactive': 'badge--danger',
        
        // Acceso
        'public': 'badge--success',
        'private': 'badge--danger'
    };
    
    // Buscar en las clases actuales del select para mantener el tipo base
    let baseClass = 'badge-select';
    
    // Obtener la nueva clase según el valor
    const newBadgeClass = valueToClassMap[selectValue] || 'badge--secondary';
    
    return `${baseClass} ${newBadgeClass}`;
}

function createCustomSelectForElement(select) {
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-select-wrapper';
    
    // Obtener las clases del select original para mantener colores
    const selectedOption = select.options[select.selectedIndex];
    const initialClasses = getBadgeClass(selectedOption.value, select);
    
    // Crear el HTML del custom select
    wrapper.innerHTML = `
        <div class="custom-select-display ${initialClasses}">
            ${selectedOption.text}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6,9 12,15 18,9"></polyline>
            </svg>
        </div>
    `;
    
    // Insertar el wrapper antes del select original
    select.parentNode.insertBefore(wrapper, select);
    select.style.display = 'none';
    select.dataset.customized = 'true';
    
    const display = wrapper.querySelector('.custom-select-display');
    
    // Manejar click en el display
    display.addEventListener('click', (e) => {
        e.stopPropagation();
        
        if (shouldUseModal(select)) {
            openSelectModal(select, wrapper);
        } else {
            // En desktop, usar modal siempre para evitar problemas de posicionamiento
            openSelectModal(select, wrapper);
        }
    });
    
    // Escuchar cambios en el select original para actualizar display
    select.addEventListener('change', () => {
        updateDisplayFromSelect(select, wrapper);
    });
    
    // Almacenar referencia
    wrapper.dataset.originalSelect = select.dataset.customId = Date.now().toString();
}

function updateDisplayFromSelect(select, wrapper) {
    const display = wrapper.querySelector('.custom-select-display');
    const newSelectedOption = select.options[select.selectedIndex];
    const newClasses = getBadgeClass(newSelectedOption.value, select);
    
    display.innerHTML = `
        ${newSelectedOption.text}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6,9 12,15 18,9"></polyline>
        </svg>
    `;
    display.className = `custom-select-display ${newClasses}`;
}

function openSelectModal(select, wrapper) {
    // Crear modal si no existe
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
        
        // Event listeners del modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeSelectModal();
        });
        
        modal.querySelector('.select-modal-close').addEventListener('click', closeSelectModal);
    }
    
    // Llenar opciones
    const optionsContainer = modal.querySelector('.select-modal-options');
    optionsContainer.innerHTML = Array.from(select.options).map((option, index) => 
        `<div class="custom-option ${option.selected ? 'selected' : ''}" 
             data-value="${option.value}" 
             data-index="${index}">
            ${option.text}
        </div>`
    ).join('');
    
    // Event listeners para las opciones
    optionsContainer.querySelectorAll('.custom-option').forEach(option => {
        option.addEventListener('click', () => {
            const index = parseInt(option.dataset.index);
            const previousValue = select.value;
            
            // Actualizar select original
            select.selectedIndex = index;
            
            // Disparar evento change si el valor cambió
            if (select.value !== previousValue) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // Actualizar display visual
            updateDisplayFromSelect(select, wrapper);
            
            closeSelectModal();
        });
    });
    
    // Mostrar modal
    modal.classList.add('show');
    
    // Focus en la opción seleccionada
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

function restoreNativeSelects() {
    // Esta función ya no es necesaria pero la mantengo para compatibilidad
    document.querySelectorAll('.custom-select-wrapper').forEach(wrapper => {
        const originalSelect = wrapper.nextElementSibling;
        if (originalSelect && originalSelect.tagName === 'SELECT' && originalSelect.dataset.customized) {
            originalSelect.style.display = '';
            delete originalSelect.dataset.customized;
            wrapper.remove();
        }
    });
}

// Cerrar modal con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeSelectModal();
    }
});

// Manejar navegación con teclado en el modal
document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('select-modal');
    if (!modal || !modal.classList.contains('show')) return;
    
    const options = modal.querySelectorAll('.custom-option');
    const currentSelected = modal.querySelector('.custom-option.selected');
    let currentIndex = Array.from(options).indexOf(currentSelected);
    
    switch(e.key) {
        case 'ArrowDown':
            e.preventDefault();
            currentIndex = Math.min(currentIndex + 1, options.length - 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            currentIndex = Math.max(currentIndex - 1, 0);
            break;
        case 'Enter':
            e.preventDefault();
            if (options[currentIndex]) {
                options[currentIndex].click();
            }
            return;
        default:
            return;
    }
    
    // Actualizar selección visual
    options.forEach(opt => opt.classList.remove('selected'));
    options[currentIndex].classList.add('selected');
    options[currentIndex].scrollIntoView({ block: 'nearest' });
});

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar iconos de Lucide primero
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Luego inicializar custom selects
    initCustomSelect();
});

// Reinicializar al redimensionar la ventana
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        // Solo reinicializar si hay cambios significativos de tamaño
        const newIsMobile = window.innerWidth <= 768;
        const currentIsMobile = document.querySelectorAll('.custom-select-wrapper').length > 0;
        
        if (newIsMobile !== currentIsMobile) {
            initCustomSelect();
        }
    }, 250);
});

// Función para agregar nuevos selects dinámicamente
function addCustomSelectToNewElements() {
    document.querySelectorAll('.badge-select:not([data-customized])').forEach(select => {
        createCustomSelectForElement(select);
    });
}

// Exportar funciones para uso externo si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initCustomSelect,
        addCustomSelectToNewElements,
        closeSelectModal
    };
}