// JavaScript principal común
// {"_META_file_path_": "refor/assets/js/main.js"}

// Utilidades globales
window.AppUtils = {
    // Mostrar confirmación
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    // Formatear números
    formatNumber: function(number, decimals = 2) {
        return number.toFixed(decimals).replace('.', ',');
    },
    
    // Hacer petición AJAX simple
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la petición');
                }
                return response.json();
            });
    },
    
    // Mostrar mensaje temporal
    showMessage: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.maxWidth = '300px';
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
};

// Inicialización global
document.addEventListener('DOMContentLoaded', function() {
    // Confirmar eliminaciones
    document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const confirmText = this.getAttribute('onclick').match(/confirm\('([^']+)'\)/);
            if (confirmText && !confirm(confirmText[1])) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Auto-submit para selects con onchange
    document.querySelectorAll('select[onchange]').forEach(select => {
        select.addEventListener('change', function() {
            const onchangeCode = this.getAttribute('onchange');
            if (onchangeCode) {
                eval(onchangeCode);
            }
        });
    });
});