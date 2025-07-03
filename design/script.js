// script.js restaurado

// ==========================
// 1. Aplicar colores a filas de la tabla según estado
// ==========================
function applyRowColor(row) {
  const select = row.querySelector('.badge-select');
  if (!select) return;
  const value = select.value;
  const map = {
    pending: 'row--warning',
    draft: 'row--secondary',
    sent: 'row--info',
    approved: 'row--success',
    rejected: 'row--danger',
    expired: 'row--black'
  };
  const newClass = map[value] || '';
  row.classList.forEach((c) => {
    if (c.startsWith('row--')) row.classList.remove(c);
  });
  if (newClass) row.classList.add(newClass);
}

function initBudgetRowColors() {
  document.querySelectorAll('.table-row--budgets').forEach((row) => {
    applyRowColor(row);
    const select = row.querySelector('.badge-select');
    if (select) {
      select.addEventListener('change', () => applyRowColor(row));
    }
  });
}

// ==========================
// 2. Inicialización global
// ==========================
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide && typeof lucide.createIcons === 'function') {
    lucide.createIcons();
  }
  initBudgetRowColors();
});
