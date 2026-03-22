document.addEventListener('DOMContentLoaded', function () {
    const quickAddButtons = document.querySelectorAll('.btn.btn-primary');
    quickAddButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.textContent.trim() === 'Quick Add') {
                window.alert('Gunakan menu Accounts, Transactions, Budgets, Debts, atau Settings untuk flow operasional yang sudah aktif.');
            }
        });
    });

    document.querySelectorAll('[data-category-filter]').forEach(function (typeSelect) {
        const scope = typeSelect.closest('form') || document;
        const categorySelect = scope.querySelector('[data-category-target]');
        if (!categorySelect) {
            return;
        }

        const syncCategories = function () {
            const selectedType = typeSelect.value;
            let firstVisible = null;
            Array.from(categorySelect.options).forEach(function (option) {
                const matches = option.dataset.type === selectedType;
                option.hidden = !matches;
                if (matches && firstVisible === null) {
                    firstVisible = option.value;
                }
            });

            const active = categorySelect.selectedOptions[0];
            if (!active || active.hidden) {
                categorySelect.value = firstVisible || '';
            }
        };

        typeSelect.addEventListener('change', syncCategories);
        syncCategories();
    });
});
