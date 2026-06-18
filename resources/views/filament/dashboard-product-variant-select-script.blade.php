<script>
    (() => {
        const ROOT_SELECTOR = '.dashboard-product-variant-select';
        const SUMMARY_CLASS = 'dashboard-variant-display-summary';
        const MAX_NAME_LENGTH = 22;

        function getAlpineSelect(selectRoot) {
            if (!window.Alpine || !selectRoot) {
                return null;
            }

            try {
                return window.Alpine.$data(selectRoot)?.select ?? null;
            } catch (error) {
                return null;
            }
        }

        function getFieldRoot(selectRoot) {
            return selectRoot.closest('.dashboard-product-variant-select');
        }

        function flattenOptions(options) {
            if (!Array.isArray(options)) {
                return [];
            }

            const flattened = [];

            for (const option of options) {
                if (option?.options && Array.isArray(option.options)) {
                    flattened.push(...flattenOptions(option.options));
                } else if (option?.value !== undefined && option?.value !== null) {
                    flattened.push(option);
                }
            }

            return flattened;
        }

        function getOptionValues(selectRoot) {
            const fieldRoot = getFieldRoot(selectRoot);

            if (fieldRoot?.dataset?.dashboardOptionValues) {
                try {
                    const values = JSON.parse(fieldRoot.dataset.dashboardOptionValues);

                    if (Array.isArray(values) && values.length > 0) {
                        return values.map((value) => String(value));
                    }
                } catch (error) {
                    // Ignore invalid server-side cache.
                }
            }

            const alpineSelect = getAlpineSelect(selectRoot);

            return flattenOptions(alpineSelect?.options ?? alpineSelect?.originalOptions ?? [])
                .map((option) => String(option.value));
        }

        function getSelectedFromBadges(selectRoot) {
            const badgesContainer = selectRoot.querySelector('.fi-select-input-value-badges-ctn');

            if (!badgesContainer) {
                return [];
            }

            return Array.from(badgesContainer.querySelectorAll('.fi-badge[data-value]')).map((badge) => ({
                value: String(badge.getAttribute('data-value')),
                label: badge.querySelector('.fi-badge-label')?.textContent?.trim() ?? badge.getAttribute('data-value'),
            }));
        }

        function getSelectedItems(selectRoot) {
            const fromBadges = getSelectedFromBadges(selectRoot);

            if (fromBadges.length > 0) {
                return fromBadges;
            }

            const alpineSelect = getAlpineSelect(selectRoot);

            if (!alpineSelect || !Array.isArray(alpineSelect.state) || alpineSelect.state.length === 0) {
                return [];
            }

            return alpineSelect.state.map((value) => {
                const stringValue = String(value);

                return {
                    value: stringValue,
                    label: alpineSelect.labelRepository?.[value]
                        ?? alpineSelect.labelRepository?.[stringValue]
                        ?? stringValue,
                };
            });
        }

        function isAllVariantsSelected(selectRoot) {
            const selectedValues = getSelectedItems(selectRoot).map((item) => item.value);
            const optionValues = getOptionValues(selectRoot);

            if (selectedValues.length === 0 || optionValues.length === 0) {
                return false;
            }

            return optionValues.every((value) => selectedValues.includes(value));
        }

        function extractProductName(label) {
            const text = (label ?? '').replace(/<[^>]*>/g, '').trim();
            const separatorIndex = text.indexOf(' - ');

            return separatorIndex >= 0 ? text.slice(0, separatorIndex).trim() : text;
        }

        function truncateName(name) {
            if (name.length <= MAX_NAME_LENGTH) {
                return name;
            }

            return `${name.slice(0, MAX_NAME_LENGTH).trimEnd()}....`;
        }

        function updateCompactDisplay(selectRoot) {
            const valueContainer = selectRoot.querySelector('.fi-select-input-value-ctn');
            const badgesContainer = selectRoot.querySelector('.fi-select-input-value-badges-ctn');

            if (!valueContainer || !badgesContainer) {
                return;
            }

            valueContainer.querySelectorAll(`.${SUMMARY_CLASS}`).forEach((node) => node.remove());

            const badges = Array.from(badgesContainer.querySelectorAll('.fi-badge'));
            badgesContainer.removeAttribute('style');
            selectRoot.classList.remove('has-compact-summary');

            if (badges.length <= 1) {
                return;
            }

            const firstLabel = badges[0].querySelector('.fi-badge-label')?.textContent?.trim() ?? '';
            const extraCount = badges.length - 1;

            const summary = document.createElement('span');
            summary.className = SUMMARY_CLASS;
            summary.textContent = `${truncateName(extractProductName(firstLabel))} +${extraCount}`;
            summary.title = badges
                .map((badge) => badge.querySelector('.fi-badge-label')?.textContent?.trim() ?? '')
                .filter(Boolean)
                .join('\n');

            badgesContainer.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';

            valueContainer.prepend(summary);
            selectRoot.classList.add('has-compact-summary');
        }

        function syncOptionCheckboxes(selectRoot) {
            selectRoot.querySelectorAll('.fi-select-input-option').forEach((option) => {
                const isSelected = option.getAttribute('aria-selected') === 'true'
                    || option.classList.contains('fi-selected');

                option.classList.toggle('dashboard-variant-option-checked', isSelected);
            });
        }

        function hideFilamentEmptyMessage(selectRoot) {
            selectRoot.querySelectorAll('.fi-select-input-message').forEach((node) => node.remove());
        }

        function ensureOptionsList(selectRoot) {
            const alpineSelect = getAlpineSelect(selectRoot);
            const dropdown = selectRoot.querySelector('.fi-dropdown-panel');

            if (!dropdown) {
                return selectRoot.querySelector('.fi-dropdown-list');
            }

            let optionsList = alpineSelect?.optionsList ?? selectRoot.querySelector('.fi-dropdown-list');

            if (!optionsList) {
                optionsList = document.createElement('ul');
                optionsList.className = 'fi-dropdown-list';

                if (alpineSelect) {
                    alpineSelect.optionsList = optionsList;
                }
            }

            optionsList.className = 'fi-dropdown-list';

            if (optionsList.parentNode !== dropdown) {
                dropdown.appendChild(optionsList);
            }

            return optionsList;
        }

        function clearRestoredOptions(selectRoot) {
            selectRoot.querySelectorAll('[data-dashboard-restored-option]').forEach((node) => node.remove());
        }

        function createRestoredOption(selectRoot, value, label) {
            const option = document.createElement('li');
            option.className = 'fi-dropdown-list-item fi-select-input-option fi-selected dashboard-variant-option-checked';
            option.setAttribute('role', 'option');
            option.setAttribute('data-value', value);
            option.setAttribute('aria-selected', 'true');
            option.setAttribute('tabindex', '0');
            option.setAttribute('data-dashboard-restored-option', '1');

            const labelSpan = document.createElement('span');
            labelSpan.textContent = label;
            option.appendChild(labelSpan);

            option.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                getAlpineSelect(selectRoot)?.selectOption(value);
            });

            return option;
        }

        function restoreSelectedOptionsInDropdown(selectRoot) {
            const selectedItems = getSelectedItems(selectRoot);

            if (selectedItems.length === 0) {
                selectRoot.removeAttribute('data-all-selected');

                return;
            }

            if (isAllVariantsSelected(selectRoot)) {
                selectRoot.setAttribute('data-all-selected', '1');
            } else {
                selectRoot.removeAttribute('data-all-selected');
            }

            hideFilamentEmptyMessage(selectRoot);

            const optionsList = ensureOptionsList(selectRoot);

            if (!optionsList) {
                return;
            }

            const existingValues = new Set(
                Array.from(optionsList.querySelectorAll('.fi-select-input-option[data-value]'))
                    .map((option) => option.getAttribute('data-value'))
                    .filter(Boolean),
            );

            const missingItems = selectedItems.filter(({ value }) => value && !existingValues.has(value));

            if (missingItems.length === 0) {
                syncOptionCheckboxes(selectRoot);

                return;
            }

            missingItems.forEach(({ value, label }) => {
                optionsList.appendChild(createRestoredOption(selectRoot, value, label));
            });

            syncOptionCheckboxes(selectRoot);
        }

        function refreshDropdown(selectRoot) {
            updateCompactDisplay(selectRoot);
            restoreSelectedOptionsInDropdown(selectRoot);
            syncOptionCheckboxes(selectRoot);
        }

        function scheduleDropdownRefresh(selectRoot) {
            [0, 50, 150, 300].forEach((delay) => {
                window.setTimeout(() => refreshDropdown(selectRoot), delay);
            });
        }

        function observeDropdown(selectRoot) {
            const dropdown = selectRoot.querySelector('.fi-dropdown-panel');

            if (!dropdown || dropdown.dataset.dashboardVariantDropdownObserver) {
                return;
            }

            dropdown.dataset.dashboardVariantDropdownObserver = '1';

            new MutationObserver(() => {
                scheduleDropdownRefresh(selectRoot);
            }).observe(dropdown, {
                childList: true,
                subtree: true,
            });
        }

        function initSelectField(field) {
            const selectRoot = field.querySelector('.fi-select-input');

            if (!selectRoot) {
                return;
            }

            if (!selectRoot.dataset.dashboardVariantInit) {
                selectRoot.dataset.dashboardVariantInit = '1';

                observeDropdown(selectRoot);

                const badgesContainer = selectRoot.querySelector('.fi-select-input-value-badges-ctn');

                if (badgesContainer) {
                    new MutationObserver(() => scheduleDropdownRefresh(selectRoot)).observe(badgesContainer, {
                        childList: true,
                        subtree: true,
                        characterData: true,
                    });
                }

                selectRoot.addEventListener('click', () => {
                    scheduleDropdownRefresh(selectRoot);
                });
            }

            scheduleDropdownRefresh(selectRoot);
        }

        function initDashboardVariantSelects() {
            document.querySelectorAll(ROOT_SELECTOR).forEach(initSelectField);
        }

        document.addEventListener('DOMContentLoaded', initDashboardVariantSelects);
        document.addEventListener('livewire:init', () => {
            initDashboardVariantSelects();
            Livewire.hook('morph.updated', () => {
                window.setTimeout(initDashboardVariantSelects, 0);
            });
        });
        document.addEventListener('livewire:navigated', initDashboardVariantSelects);
    })();
</script>
