// Utilidades globales de la aplicación
// {"_META_file_path_": "refor/assets/js/main.js"}

class AppUtils {
    /**
     * Muestra una notificación temporal
     */
    static showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Formatea una fecha para mostrar
     */
    static formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    /**
     * Formatea una fecha y hora para mostrar
     */
    static formatDateTime(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Formatea un número como moneda
     */
    static formatCurrency(amount) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    /**
     * Formatea un número con decimales
     */
    static formatNumber(number, decimals = 2) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }

    /**
     * Realiza una petición AJAX con manejo de errores
     */
    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }

    /**
     * Debounce para limitar frecuencia de ejecución
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Confirma una acción con el usuario
     */
    static confirm(message, title = 'Confirmar acción') {
        return confirm(`${title}\n\n${message}`);
    }

    /**
     * Obtiene parámetros de la URL
     */
    static getUrlParams() {
        return new URLSearchParams(window.location.search);
    }

    /**
     * Actualiza la URL sin recargar la página
     */
    static updateUrl(params, replace = false) {
        const url = new URL(window.location);
        
        Object.entries(params).forEach(([key, value]) => {
            if (value === null || value === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
        });

        if (replace) {
            window.history.replaceState({}, '', url);
        } else {
            window.history.pushState({}, '', url);
        }
    }

    /**
     * Carga un script dinámicamente
     */
    static loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Valida un email
     */
    static isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Valida un NIF/NIE español
     */
    static isValidNIF(nif) {
        const regex = /^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i;
        if (!regex.test(nif)) return false;
        
        const letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        const number = parseInt(nif.slice(0, 8));
        const letter = nif.slice(8).toUpperCase();
        
        return letters[number % 23] === letter;
    }

    /**
     * Copia texto al portapapeles
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('Copiado al portapapeles', 'success');
        } catch (error) {
            console.error('Error copying to clipboard:', error);
            this.showNotification('Error al copiar', 'error');
        }
    }

    /**
     * Detecta si es dispositivo móvil
     */
    static isMobile() {
        return window.innerWidth <= 768;
    }

    /**
     * Scroll suave a un elemento
     */
    static scrollTo(element, offset = 0) {
        const elementPosition = element.offsetTop;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    /**
     * Formatea el tamaño de archivo
     */
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Genera un UUID simple
     */
    static generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}

// Event listeners globales
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iconos de Lucide si está disponible
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Manejar formularios con clase 'ajax-form'
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('[type="submit"]');
            
            // Deshabilitar botón durante envío
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando...';
            }

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    AppUtils.showNotification(result.message || 'Operación completada', 'success');
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 1000);
                    }
                } else {
                    AppUtils.showNotification(result.message || 'Error en la operación', 'error');
                }
            } catch (error) {
                AppUtils.showNotification('Error de conexión', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.dataset.originalText || 'Enviar';
                }
            }
        });
    });

    // Guardar texto original de botones de envío
    document.querySelectorAll('form [type="submit"]').forEach(btn => {
        btn.dataset.originalText = btn.textContent;
    });

    // Auto-resize para textareas
    document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // Confirmación para enlaces/botones con data-confirm
    document.addEventListener('click', function(e) {
        const element = e.target.closest('[data-confirm]');
        if (element) {
            const message = element.dataset.confirm;
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });

    // Tooltips simples
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            if (this.title && !this.dataset.tooltip) {
                this.dataset.tooltip = this.title;
                this.title = '';
            }
        });

        element.addEventListener('mouseleave', function() {
            if (this.dataset.tooltip) {
                this.title = this.dataset.tooltip;
                delete this.dataset.tooltip;
            }
        });
    });
});

// Estilos CSS adicionales para animaciones
const additionalStyles = `
@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.fade-in {
    animation: fadeIn 0.3s ease-in;
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--primary-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
`;

// Inyectar estilos adicionales
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);