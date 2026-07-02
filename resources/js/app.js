import Alpine from 'alpinejs';
import './searchable-selects';
import './employee-pwa';
import './rental-pwa';
import './tms-trip-gps';
import { registerCommercialQuoteBreakdown } from './commercial-quote';
import { initConfirmHandlers, registerConfirmDialog } from './confirm-dialog';

Alpine.data('erpLiveClock', (timezone = 'UTC') => ({
    hour: '00',
    minute: '00',
    period: 'AM',
    timer: null,

    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },

    destroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    },

    tick() {
        const parts = new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        }).formatToParts(new Date());

        const get = (type) => parts.find((part) => part.type === type)?.value ?? '';

        this.hour = get('hour').padStart(2, '0');
        this.minute = get('minute').padStart(2, '0');
        this.period = get('dayPeriod').toUpperCase();
    },
}));

Alpine.data('notificationBell', (initialCount = 0, pollUrl = '', useFixedPanel = false) => ({
    open: false,
    unreadCount: initialCount,
    pollTimer: null,
    pollUrl,
    useFixedPanel,
    panelStyle: '',

    init() {
        if (this.pollUrl) {
            this.pollTimer = setInterval(() => this.refreshCount(), 60000);
        }

        if (this.useFixedPanel) {
            this._onResize = () => {
                if (this.open) {
                    this.updatePanelPosition();
                }
            };
            this._onScroll = () => {
                if (this.open) {
                    this.updatePanelPosition();
                }
            };

            window.addEventListener('resize', this._onResize);
            window.addEventListener('scroll', this._onScroll, true);
        }
    },

    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }

        if (this._onResize) {
            window.removeEventListener('resize', this._onResize);
        }

        if (this._onScroll) {
            window.removeEventListener('scroll', this._onScroll, true);
        }
    },

    toggle() {
        this.open = ! this.open;

        if (this.open && this.useFixedPanel) {
            this.$nextTick(() => this.updatePanelPosition());
        }
    },

    updatePanelPosition() {
        const trigger = this.$refs.trigger;

        if (! trigger) {
            return;
        }

        const rect = trigger.getBoundingClientRect();
        const right = Math.max(12, window.innerWidth - rect.right);

        this.panelStyle = `top:${rect.bottom + 8}px;right:${right}px;`;
    },

    async refreshCount() {
        if (! this.pollUrl) {
            return;
        }

        try {
            const response = await fetch(this.pollUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.count ?? 0;
            }
        } catch {
            // ignore polling errors
        }
    },
}));

Alpine.data('referenceFiles', () => ({
    techpack: [{ id: 1, fileName: null, previewUrl: null, isPdf: false }],
    artwork: [{ id: 2, fileName: null, previewUrl: null, isPdf: false }],
    nextId: 3,

    addTechpack() {
        this.techpack.push({ id: this.nextId++, fileName: null, previewUrl: null, isPdf: false });
    },

    addArtwork() {
        this.artwork.push({ id: this.nextId++, fileName: null, previewUrl: null, isPdf: false });
    },

    onSelect(slot, event) {
        const file = event.target.files[0];

        if (slot.previewUrl) {
            URL.revokeObjectURL(slot.previewUrl);
        }

        if (! file) {
            slot.fileName = null;
            slot.previewUrl = null;
            slot.isPdf = false;
            return;
        }

        slot.fileName = file.name;
        slot.isPdf = file.type === 'application/pdf';
        slot.previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    },

    canAddMore(slots) {
        return slots.some((slot) => slot.fileName);
    },
}));

Alpine.data('rolePermissions', (selected = []) => ({
    selected: [...selected],

    isChecked(key) {
        return this.selected.includes(key);
    },

    toggle(key, checked) {
        if (checked) {
            if (! this.selected.includes(key)) {
                this.selected.push(key);
            }
        } else {
            this.selected = this.selected.filter((item) => item !== key);
        }
    },

    setGroup(keys, checked) {
        keys.forEach((key) => this.toggle(key, checked));
        this.$nextTick(() => {
            keys.forEach((key) => {
                const input = this.$root.querySelector('[data-permission="' + key + '"]');

                if (input) {
                    input.checked = checked;
                }
            });
        });
    },

    groupKeys(groupName) {
        return Array.from(this.$root.querySelectorAll('[data-group="' + groupName + '"]'))
            .map((el) => el.dataset.permission);
    },
}));

Alpine.data('toastHub', () => ({
    toasts: [],
    nextId: 1,

    init() {
        const success = document.getElementById('flash-success');
        const error = document.getElementById('flash-error');

        if (success?.textContent) {
            this.push(success.textContent, 'success');
        }

        if (error?.textContent) {
            this.push(error.textContent, 'error');
        }
    },

    push(message, type = 'success', duration = 4500) {
        const id = this.nextId++;
        const toast = { id, message, type, visible: true };

        this.toasts.push(toast);

        if (duration > 0) {
            setTimeout(() => this.dismiss(id), duration);
        }
    },

    dismiss(id) {
        const toast = this.toasts.find((item) => item.id === id);

        if (toast) {
            toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter((item) => item.id !== id);
            }, 200);
        }
    },
}));

Alpine.data('employeeForm', (config = {}) => {
    const stepOrder = config.steps || ['setup', 'official', 'personal', 'contact', 'family', 'education', 'employment'];

    return {
        step: config.tab || 'setup',
        stepOrder,
        factoryId: config.factoryId || '',
        departmentId: config.departmentId || '',
        designationId: config.designationId || '',
        buildingId: config.buildingId || '',
        floorId: config.floorId || '',
        shiftId: config.shiftId || '',
        lineId: config.lineId || '',
        reportingToId: config.reportingToId || '',
        departments: config.departments || [],
        designations: config.designations || [],
        buildings: config.buildings || [],
        floors: config.floors || [],
        lines: config.lines || [],
        shifts: config.shifts || [],
        reportingCandidates: config.reportingCandidates || [],
        photoPreview: config.photoPreview || null,
        nomineePhotoPreview: config.nomineePhotoPreview || null,
        educationRows: config.educationRows?.length ? config.educationRows : [{
            degree: '', institution: '', board_or_university: '', passing_year: '', result: '',
        }],
        employmentRows: config.employmentRows?.length ? config.employmentRows : [{
            company_name: '', designation: '', department: '', joining_date: '', leaving_date: '', reason_for_leaving: '',
        }],
        displayName: config.displayName || '',
        displayEmployeeId: config.displayEmployeeId || '',
        displayPhone: config.displayPhone || '',
        status: config.status || 'active',
        submitError: '',
        _prevDepartmentId: null,
        init() {
            this.factoryId = String(this.factoryId || '');
            this.departmentId = String(this.departmentId || '');
            this.designationId = String(this.designationId || '');
            this.buildingId = String(this.buildingId || '');
            this.floorId = String(this.floorId || '');
            this.shiftId = String(this.shiftId || '');
            this.lineId = String(this.lineId || '');
            this.reportingToId = String(this.reportingToId || '');
            this.status = String(this.status || 'active');
            this._prevDepartmentId = this.departmentId;

            this.$nextTick(() => {
                this.initStepSelects();
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        this.refreshDynamicSelects();
                        window.syncTomSelects?.(this.$root);
                    }, 250);
                });
            });

            ['factoryId', 'departmentId'].forEach((field) => {
                this.$watch(field, () => {
                    this.refreshDynamicSelects();
                });
            });

            ['designationId', 'buildingId', 'floorId', 'lineId', 'shiftId', 'status'].forEach((field) => {
                this.$watch(field, () => {
                    this.$nextTick(() => window.syncTomSelects?.(this.$root));
                });
            });
        },
        syncTomSelects() {
            this.refreshDynamicSelects();
        },
        refreshDynamicSelects() {
            this.$nextTick(() => {
                this.rebuildOrgSelects();

                this.$root.querySelectorAll('select[data-dynamic-options]').forEach((el) => {
                    if (el.dataset.searchable !== 'true') {
                        if (el.tomselect) {
                            window.destroySearchableSelect?.(el);
                        }

                        return;
                    }

                    if (! el.tomselect) {
                        window.enhanceSelect?.(el);
                    }
                });

                window.syncTomSelects?.(this.$root);
            });
        },
        withPinnedSelection(items, selectedId, sourceList = null) {
            if (! selectedId) {
                return items;
            }

            if (items.some((item) => String(item.id) === String(selectedId))) {
                return items;
            }

            const selected = this.findById(sourceList ?? items, selectedId);

            if (! selected) {
                return items;
            }

            return [selected, ...items];
        },
        findById(items, id) {
            return items.find((item) => String(item.id) === String(id)) ?? null;
        },
        initStepSelects() {
            this.$nextTick(() => {
                setTimeout(() => {
                    const panel = this.$root.querySelector(`[data-wizard-step="${this.step}"]`);

                    if (panel) {
                        panel.querySelectorAll('select.erp-input, select.emp-input').forEach((el) => {
                            if (el.dataset.dynamicOptions === 'true') {
                                return;
                            }

                            if (el.tomselect) {
                                window.refreshSearchableSelect?.(el);
                            } else {
                                window.enhanceSelect?.(el);
                            }
                        });
                    }

                    window.syncTomSelects?.(this.$root);
                }, 80);
            });
        },
        progressPercent() {
            const idx = this.stepOrder.indexOf(this.step);

            return idx >= 0 ? Math.round(((idx + 1) / this.stepOrder.length) * 100) : 0;
        },
        isLastStep() {
            return this.step === this.stepOrder[this.stepOrder.length - 1];
        },
        isFirstStep() {
            return this.step === this.stepOrder[0];
        },
        goToStep(stepKey) {
            if (this.stepOrder.includes(stepKey)) {
                this.step = stepKey;
                this.initStepSelects();
            }
        },
        onPhotoSelected(event) {
            const file = event.target.files?.[0];

            if (! file || ! file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                this.photoPreview = loadEvent.target?.result ?? null;
            };
            reader.readAsDataURL(file);
        },
        onNomineePhotoSelected(event) {
            const file = event.target.files?.[0];

            if (! file || ! file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                this.nomineePhotoPreview = loadEvent.target?.result ?? null;
            };
            reader.readAsDataURL(file);
        },
        addEducationRow() {
            this.educationRows.push({
                degree: '', institution: '', board_or_university: '', passing_year: '', result: '',
            });
        },
        removeEducationRow(index) {
            if (this.educationRows.length > 1) {
                this.educationRows.splice(index, 1);
            }
        },
        addEmploymentRow() {
            this.employmentRows.push({
                company_name: '', designation: '', department: '', joining_date: '', leaving_date: '', reason_for_leaving: '',
            });
        },
        removeEmploymentRow(index) {
            if (this.employmentRows.length > 1) {
                this.employmentRows.splice(index, 1);
            }
        },
        filteredDepartments() {
            if (! this.factoryId) {
                return this.withPinnedSelection([], this.departmentId, this.departments);
            }

            const items = this.departments.filter((d) => String(d.factory_id) === String(this.factoryId));

            return this.withPinnedSelection(items, this.departmentId, this.departments);
        },
        filteredDesignations() {
            if (! this.departmentId) {
                return this.withPinnedSelection([], this.designationId, this.designations);
            }

            const linked = this.designations.filter(
                (d) => String(d.department_id) === String(this.departmentId)
            );

            if (linked.length > 0) {
                return this.withPinnedSelection(linked, this.designationId, this.designations);
            }

            const shared = this.designations.filter(
                (d) => d.department_id === null || d.department_id === undefined || d.department_id === ''
            );

            return this.withPinnedSelection(shared, this.designationId, this.designations);
        },
        populateSelect(select, items, selectedId) {
            if (! select) {
                return;
            }

            const value = String(selectedId || '');

            if (select.tomselect) {
                const ts = select.tomselect;
                ts.clear(true);
                ts.clearOptions();

                items.forEach((item) => {
                    ts.addOption({ value: String(item.id), text: item.name || '' });
                });

                ts.refreshOptions(false);
                ts.setValue(value, true);

                return;
            }

            while (select.options.length > 1) {
                select.remove(1);
            }

            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name || '';
                select.appendChild(option);
            });

            select.value = value;
        },
        rebuildOrgSelects() {
            this.populateSelect(this.$refs.departmentSelect, this.filteredDepartments(), this.departmentId);
            this.populateSelect(this.$refs.designationSelect, this.filteredDesignations(), this.designationId);
            this.populateSelect(this.$refs.shiftSelect, this.filteredShifts(), this.shiftId);
        },
        filteredBuildings() {
            if (! this.factoryId) {
                return [];
            }

            return this.buildings.filter((b) => String(b.factory_id) === String(this.factoryId));
        },
        filteredFloors() {
            if (! this.buildingId) {
                return [];
            }

            return this.floors.filter((f) => String(f.building_id) === String(this.buildingId));
        },
        filteredLines() {
            if (! this.floorId) {
                return [];
            }

            return this.lines.filter((l) => String(l.floor_id) === String(this.floorId));
        },
        filteredShifts() {
            if (! this.factoryId) {
                return this.withPinnedSelection([], this.shiftId, this.shifts);
            }

            const items = this.shifts.filter((s) => String(s.factory_id) === String(this.factoryId));

            return this.withPinnedSelection(items, this.shiftId, this.shifts);
        },
        filteredReportingCandidates() {
            if (! this.factoryId) {
                return [];
            }

            return this.reportingCandidates.filter((c) => String(c.factory_id) === String(this.factoryId));
        },
        onFactoryChange() {
            this.departmentId = '';
            this.designationId = '';
            this.buildingId = '';
            this.floorId = '';
            this.shiftId = '';
            this.lineId = '';
            this.reportingToId = '';
            this.refreshDynamicSelects();
        },
        onDepartmentChange() {
            if (this._prevDepartmentId !== null && String(this._prevDepartmentId) !== String(this.departmentId)) {
                this.designationId = '';
            }

            this._prevDepartmentId = this.departmentId;
            this.refreshDynamicSelects();
        },
        onBuildingChange() {
            this.floorId = '';
            this.lineId = '';
            this.refreshDynamicSelects();
        },
        onFloorChange() {
            this.lineId = '';
            this.refreshDynamicSelects();
        },
        validateCurrentStep() {
            if (this.step === 'setup') {
                return this.validateSetupFields(true);
            }

            return true;
        },
        validateSetupFields(showErrors = false) {
            const missing = [];

            if (! String(this.displayName ?? '').trim()) {
                missing.push('Employee Name');
            }

            if (! String(this.displayEmployeeId ?? '').trim()) {
                missing.push('Employee ID');
            }

            if (! String(this.factoryId ?? '').trim()) {
                missing.push('Factory / Unit');
            }

            if (! String(this.status ?? '').trim()) {
                missing.push('Status');
            }

            if (missing.length && showErrors) {
                this.submitError = 'Please fill required Employee Setup fields: ' + missing.join(', ');
            }

            return missing.length === 0;
        },
        prepareSubmit(event) {
            this.submitError = '';

            if (! this.validateSetupFields(true)) {
                event.preventDefault();
                this.step = 'setup';
            }
        },
        validateStep(stepKey) {
            if (stepKey === 'setup') {
                return this.validateSetupFields(false);
            }

            return true;
        },
        prevStep() {
            const idx = this.stepOrder.indexOf(this.step);

            if (idx > 0) {
                this.step = this.stepOrder[idx - 1];
                this.initStepSelects();
            }
        },
        nextStep() {
            if (! this.validateCurrentStep()) {
                return;
            }

            const idx = this.stepOrder.indexOf(this.step);

            if (idx < this.stepOrder.length - 1) {
                this.step = this.stepOrder[idx + 1];
                this.initStepSelects();
            }
        },
        selectedDepartmentName() {
            const dept = this.departments.find((d) => String(d.id) === String(this.departmentId));

            return dept?.name || '—';
        },
        selectedDesignationName() {
            const des = this.designations.find((d) => String(d.id) === String(this.designationId));

            return des?.name || '—';
        },
        selectedShiftName() {
            const shift = this.findById(this.shifts, this.shiftId);

            return shift?.name || '—';
        },
    };
});

Alpine.data('employeeTabs', (initialTab = 'personal') => ({
    tab: initialTab,
}));

Alpine.data('erpShell', (openGroups = {}, adminOpenInitial = false) => ({
    sidebarOpen: false,
    openGroups: { ...openGroups },
    adminOpen: adminOpenInitial,
    navSearch: '',
    navSearchEmpty: false,

    filterSidebarNav() {
        const nav = document.getElementById('erp-sidebar-nav');

        if (! nav) {
            return;
        }

        const query = this.navSearch.trim().toLowerCase();
        const labeled = nav.querySelectorAll('[data-nav-label]');

        nav.classList.toggle('nav-search-active', query.length > 0);

        if (! query) {
            labeled.forEach((el) => el.removeAttribute('data-nav-search-hidden'));
            nav.querySelectorAll('[data-nav-branch]').forEach((el) => el.removeAttribute('data-nav-search-hidden'));
            nav.querySelectorAll('[data-nav-section]').forEach((el) => el.removeAttribute('data-nav-search-hidden'));
            this.navSearchEmpty = false;

            return;
        }

        const matched = new Set();

        labeled.forEach((el) => {
            const label = (el.getAttribute('data-nav-label') || '').toLowerCase();

            if (label.includes(query)) {
                matched.add(el);
            }
        });

        labeled.forEach((el) => {
            if (matched.has(el)) {
                el.removeAttribute('data-nav-search-hidden');
            } else {
                el.setAttribute('data-nav-search-hidden', '');
            }
        });

        labeled.forEach((el) => {
            if (el.querySelector('[data-nav-label]:not([data-nav-search-hidden])')) {
                el.removeAttribute('data-nav-search-hidden');
            }
        });

        nav.querySelectorAll('[data-nav-branch]').forEach((branch) => {
            const hasMatch = branch.querySelector('[data-nav-label]:not([data-nav-search-hidden])');

            if (hasMatch) {
                branch.removeAttribute('data-nav-search-hidden');
                this.expandNavAncestors(branch, nav);
            } else {
                branch.setAttribute('data-nav-search-hidden', '');
            }
        });

        nav.querySelectorAll('[data-nav-label]:not([data-nav-search-hidden])').forEach((el) => {
            this.expandNavAncestors(el, nav);
        });

        const sections = nav.querySelectorAll('[data-nav-section]');

        sections.forEach((section, index) => {
            const nextSection = sections[index + 1] ?? null;
            let sibling = section.nextElementSibling;
            let sectionVisible = false;

            while (sibling && sibling !== nextSection) {
                if (! sibling.hasAttribute('data-nav-search-hidden')) {
                    sectionVisible = true;
                    break;
                }

                sibling = sibling.nextElementSibling;
            }

            if (sectionVisible) {
                section.removeAttribute('data-nav-search-hidden');
            } else {
                section.setAttribute('data-nav-search-hidden', '');
            }
        });

        this.navSearchEmpty = matched.size === 0;
    },

    expandNavAncestors(el, nav) {
        let parent = el.parentElement;

        while (parent && parent !== nav) {
            parent.removeAttribute('data-nav-search-hidden');

            const openKey = parent.getAttribute('data-nav-open-key');

            if (openKey === 'admin') {
                this.adminOpen = true;
            } else if (openKey) {
                this.openGroups[openKey] = true;
            }

            parent = parent.parentElement;
        }
    },

    clearNavSearch() {
        this.navSearch = '';
        this.filterSidebarNav();
    },
}));

Alpine.data('employeeIndexFilters', () => ({
    searchTimer: null,

    submit() {
        this.$refs.filterForm?.requestSubmit();
    },

    debouncedSearch() {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => this.submit(), 350);
    },

    onFactoryChange() {
        ['department_id', 'building_id', 'shift_id', 'designation_id'].forEach((name) => {
            const field = this.$refs.filterForm?.querySelector(`[name="${name}"]`);

            if (field) {
                field.value = '';
            }
        });

        this.submit();
    },

    onDepartmentChange() {
        const designation = this.$refs.filterForm?.querySelector('[name="designation_id"]');

        if (designation) {
            designation.value = '';
        }

        this.submit();
    },

    onSelectChange() {
        this.submit();
    },

    clearFilters() {
        window.location.href = this.$refs.filterForm?.action ?? window.location.pathname;
    },
}));

window.Alpine = Alpine;

registerConfirmDialog(Alpine);
registerCommercialQuoteBreakdown(Alpine);
Alpine.start();
initConfirmHandlers();
