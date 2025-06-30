// Calculadora de presupuestos
// {"_META_file_path_": "js/form-calculator.js"}

document.addEventListener('DOMContentLoaded', function() {
    // Gestión de tipo de cliente
    document.querySelectorAll('.client-type').forEach(type => {
        type.addEventListener('click', function() {
            document.querySelectorAll('.client-type').forEach(t => t.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('clientType').value = this.dataset.type;
        });
    });

    // Cálculos dinámicos
    function formatNumber(num) {
        return num.toFixed(2).replace('.', ',');
    }

    function calculateTotals() {
        let totalBase = 0;
        const ivaAccumulator = {};
        const chapterTotals = {};
        const subchapterTotals = {};
        const sectionTotals = {};

        // Calcular totales por item
        document.querySelectorAll('.quantity-input').forEach(input => {
            const quantity = parseFloat(input.value) || 0;
            const pvp = parseFloat(input.dataset.pvp) || 0;
            const iva = parseFloat(input.dataset.iva) || 0;
            const itemId = input.dataset.itemId;
            
            const itemTotal = quantity * pvp;
            const baseAmount = itemTotal / (1 + iva / 100);
            const ivaAmount = itemTotal - baseAmount;
            
            document.querySelector(`[data-item="${itemId}"]`).textContent = formatNumber(itemTotal) + ' €';
            
            totalBase += baseAmount;
            
            if (iva > 0) {
                if (!ivaAccumulator[iva]) ivaAccumulator[iva] = 0;
                ivaAccumulator[iva] += ivaAmount;
            }

            // Acumular por niveles superiores
            const parts = itemId.split('.');
            const chapter = parts[0];
            const subchapter = parts.slice(0, 2).join('.');
            const section = parts.slice(0, 3).join('.');
            
            if (!chapterTotals[chapter]) chapterTotals[chapter] = 0;
            if (!subchapterTotals[subchapter]) subchapterTotals[subchapter] = 0;
            if (!sectionTotals[section]) sectionTotals[section] = 0;
            
            chapterTotals[chapter] += itemTotal;
            subchapterTotals[subchapter] += itemTotal;
            sectionTotals[section] += itemTotal;
        });

        // Actualizar totales por nivel
        Object.keys(chapterTotals).forEach(chapterId => {
            const element = document.querySelector(`[data-chapter="${chapterId}"]`);
            if (element) element.textContent = formatNumber(chapterTotals[chapterId]) + ' €';
        });

        Object.keys(subchapterTotals).forEach(subchapterId => {
            const element = document.querySelector(`[data-subchapter="${subchapterId}"]`);
            if (element) element.textContent = formatNumber(subchapterTotals[subchapterId]) + ' €';
        });

        Object.keys(sectionTotals).forEach(sectionId => {
            const element = document.querySelector(`[data-section="${sectionId}"]`);
            if (element) element.textContent = formatNumber(sectionTotals[sectionId]) + ' €';
        });

        // Actualizar totales finales
        document.getElementById('totalBase').textContent = formatNumber(totalBase) + ' €';
        
        // Desglose de IVAs
        const ivaBreakdown = document.getElementById('ivaBreakdown');
        ivaBreakdown.innerHTML = '';
        let totalIva = 0;
        
        Object.keys(ivaAccumulator).forEach(percentage => {
            const amount = ivaAccumulator[percentage];
            totalIva += amount;
            const row = document.createElement('div');
            row.className = 'totals-row';
            row.innerHTML = `<span>IVA ${formatNumber(parseFloat(percentage))}%:</span><span>${formatNumber(amount)} €</span>`;
            ivaBreakdown.appendChild(row);
        });

        const totalFinal = totalBase + totalIva;
        document.getElementById('totalFinal').textContent = formatNumber(totalFinal) + ' €';
    }

    // Event listeners para cálculos
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    // Cálculo inicial
    calculateTotals();
});