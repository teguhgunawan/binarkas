document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    const toggleSidebar = function () {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            body.classList.toggle('sidebar-open');
            body.classList.remove('sidebar-hidden');
            return;
        }

        body.classList.toggle('sidebar-hidden');
        body.classList.remove('sidebar-open');
    };

    const closeSidebar = function () {
        body.classList.remove('sidebar-open');
    };

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeSidebar);
    }
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

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

    const accountPickerButtons = document.querySelectorAll('[data-account-choice]');
    const selectedAccountInput = document.querySelector('[data-account-input]');
    const selectedAccountLabel = document.querySelector('[data-account-label]');
    const accountPickerModalElement = document.getElementById('accountPickerModal');
    const accountPickerModal = accountPickerModalElement ? bootstrap.Modal.getOrCreateInstance(accountPickerModalElement) : null;

    accountPickerButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!selectedAccountInput || !selectedAccountLabel) {
                return;
            }
            selectedAccountInput.value = button.dataset.accountChoice || '__all';
            selectedAccountLabel.textContent = button.dataset.accountLabel || 'Semua Akun';
            if (accountPickerModal) {
                accountPickerModal.hide();
            }
        });
    });

    const editTxButtons = document.querySelectorAll('[data-edit-transaction]');
    const editTxModalElement = document.getElementById('transactionEditModal');
    const editTxModal = editTxModalElement ? bootstrap.Modal.getOrCreateInstance(editTxModalElement) : null;
    const bindValue = function (selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.value = value || '';
        }
    };

    editTxButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            bindValue('#edit-transaction-id', button.dataset.transactionId);
            bindValue('#edit-transaction-date', button.dataset.date);
            bindValue('#edit-transaction-title', button.dataset.title);
            bindValue('#edit-transaction-type', button.dataset.type);
            bindValue('#edit-transaction-account', button.dataset.accountName);
            bindValue('#edit-transaction-category', button.dataset.category);
            bindValue('#edit-transaction-amount', button.dataset.amount);
            bindValue('#edit-filter-account', button.dataset.filterAccount);
            bindValue('#edit-filter-date-from', button.dataset.filterDateFrom);
            bindValue('#edit-filter-date-to', button.dataset.filterDateTo);
            bindValue('#edit-filter-keyword', button.dataset.filterKeyword);

            const typeSelect = document.getElementById('edit-transaction-type');
            if (typeSelect) {
                typeSelect.dispatchEvent(new Event('change'));
            }

            if (editTxModal) {
                editTxModal.show();
            }
        });
    });
});
