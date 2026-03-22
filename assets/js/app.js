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

    const createTxButtons = document.querySelectorAll('[data-create-transaction]');
    const editTxButtons = document.querySelectorAll('[data-edit-transaction]');
    const txSheetModalElement = document.getElementById('transactionSheetModal');
    const txSheetModal = txSheetModalElement ? bootstrap.Modal.getOrCreateInstance(txSheetModalElement) : null;
    const bindValue = function (selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.value = value || '';
        }
    };

    const openSheetCreate = function (sourceButton) {
        const today = new Date().toISOString().slice(0, 10);
        const sheetTitle = document.getElementById('transaction-sheet-title');
        if (sheetTitle) {
            sheetTitle.textContent = 'Tambah Transaksi';
        }

        bindValue('#tx-sheet-action', 'create-transaction');
        bindValue('#tx-sheet-id', '');
        bindValue('#tx-sheet-date', today);
        bindValue('#tx-sheet-title-input', '');
        bindValue('#tx-sheet-type', 'expense');
        bindValue('#tx-sheet-amount', '');
        bindValue('#tx-sheet-paired-account', '');
        bindValue('#tx-sheet-paired-category', '');
        bindValue('#tx-sheet-filter-book-id', sourceButton ? sourceButton.dataset.filterBookId : '');
        bindValue('#tx-sheet-filter-account', sourceButton ? sourceButton.dataset.filterAccount : '');
        bindValue('#tx-sheet-filter-date-from', sourceButton ? sourceButton.dataset.filterDateFrom : '');
        bindValue('#tx-sheet-filter-date-to', sourceButton ? sourceButton.dataset.filterDateTo : '');
        bindValue('#tx-sheet-filter-keyword', sourceButton ? sourceButton.dataset.filterKeyword : '');
        bindValue('#tx-sheet-account', sourceButton ? sourceButton.dataset.defaultAccount : '');

        const typeSelect = document.getElementById('tx-sheet-type');
        if (typeSelect) {
            typeSelect.dispatchEvent(new Event('change'));
        }
        if (txSheetModal) {
            txSheetModal.show();
        }
    };

    createTxButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openSheetCreate(button);
        });
    });

    editTxButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const sheetTitle = document.getElementById('transaction-sheet-title');
            if (sheetTitle) {
                sheetTitle.textContent = 'Edit Transaksi';
            }

            bindValue('#tx-sheet-action', 'update-transaction');
            bindValue('#tx-sheet-id', button.dataset.transactionId);
            bindValue('#tx-sheet-date', button.dataset.date);
            bindValue('#tx-sheet-title-input', button.dataset.title);
            bindValue('#tx-sheet-type', button.dataset.type);
            bindValue('#tx-sheet-account', button.dataset.accountName);
            bindValue('#tx-sheet-amount', button.dataset.amount);
            bindValue('#tx-sheet-paired-account', button.dataset.pairedAccountName);
            bindValue('#tx-sheet-paired-category', button.dataset.pairedCategory);
            bindValue('#tx-sheet-filter-book-id', button.dataset.filterBookId);
            bindValue('#tx-sheet-filter-account', button.dataset.filterAccount);
            bindValue('#tx-sheet-filter-date-from', button.dataset.filterDateFrom);
            bindValue('#tx-sheet-filter-date-to', button.dataset.filterDateTo);
            bindValue('#tx-sheet-filter-keyword', button.dataset.filterKeyword);

            const typeSelect = document.getElementById('tx-sheet-type');
            if (typeSelect) {
                typeSelect.dispatchEvent(new Event('change'));
            }
            bindValue('#tx-sheet-category', button.dataset.category);

            if (txSheetModal) {
                txSheetModal.show();
            }
        });
    });
});
