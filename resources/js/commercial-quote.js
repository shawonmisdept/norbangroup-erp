function roundMoney(value) {
    return Math.round((Number(value) || 0) * 10000) / 10000;
}

function roundTotal(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
}

function computeConsumption(line) {
    const consumption = Number(line.consumption) || 0;
    const rate = Number(line.rate) || 0;
    const wastage = Number(line.wastage_pct) || 0;

    if (consumption <= 0 || rate <= 0) {
        return 0;
    }

    return consumption * rate * (1 + wastage / 100);
}

function computeLine(line, quantity, bases) {
    if (! line.enabled) {
        return 0;
    }

    switch (line.calc) {
    case 'consumption':
        return computeConsumption(line);
    case 'lump':
        return quantity > 0 ? (Number(line.lump_total) || 0) / quantity : 0;
    case 'percent': {
        const percent = Number(line.percent) || 0;

        if (percent <= 0) {
            return 0;
        }

        const base = bases[line.percent_base] ?? 0;

        return base * (percent / 100);
    }
    default:
        return Number(line.amount_pc) || 0;
    }
}

function buildBases(sections) {
    const materialCodes = ['fabric', 'yarn_fabric'];
    const processingCodes = ['processing', 'value_addition'];
    const exclude = ['overhead', 'profit', 'development'];

    let materials = 0;
    let trimsSubtotal = 0;
    let processing = 0;
    let beforeOverhead = 0;

    sections.forEach((section) => {
        const subtotal = section.lines.reduce((sum, line) => sum + (Number(line.computed_pc) || 0), 0);

        if (materialCodes.includes(section.code)) {
            materials += subtotal;
        }

        if (section.code === 'trims') {
            trimsSubtotal = section.lines
                .filter((line) => line.code !== 'trims_wastage')
                .reduce((sum, line) => sum + (Number(line.computed_pc) || 0), 0);
        }

        if (processingCodes.includes(section.code)) {
            processing += subtotal;
        }

        if (! exclude.includes(section.code)) {
            beforeOverhead += subtotal;
        }
    });

    return {
        materials,
        trims_subtotal: trimsSubtotal,
        processing,
        before_overhead: beforeOverhead,
        before_profit: beforeOverhead,
    };
}

function recalculateBreakdown(breakdown) {
    const quantity = Math.max(1, Number(breakdown.quantity) || 1);

    breakdown.sections.forEach((section) => {
        section.lines.forEach((line) => {
            if (line.calc !== 'percent') {
                line.computed_pc = roundMoney(computeLine(line, quantity, {}));
            }
        });

        section.subtotal_pc = roundMoney(
            section.lines.reduce((sum, line) => sum + (Number(line.computed_pc) || 0), 0),
        );
    });

    let bases = buildBases(breakdown.sections);

    breakdown.sections.forEach((section) => {
        section.lines.forEach((line) => {
            if (line.calc === 'percent' && line.enabled) {
                line.computed_pc = roundMoney(computeLine(line, quantity, bases));

                if (line.percent_base === 'trims_subtotal') {
                    bases.trims_subtotal += line.computed_pc;
                }
            }
        });

        section.subtotal_pc = roundMoney(
            section.lines.reduce((sum, line) => sum + (Number(line.computed_pc) || 0), 0),
        );

        if (section.code === 'overhead') {
            bases.before_profit = bases.before_overhead + section.subtotal_pc;
        }
    });

    const pricePerPc = roundTotal(
        breakdown.sections.reduce((sum, section) => sum + (Number(section.subtotal_pc) || 0), 0),
    );

    breakdown.summary = {
        price_per_pc: pricePerPc,
        order_total: roundTotal(pricePerPc * quantity),
    };

    return breakdown;
}

function extractCustomLines(breakdown) {
    const custom = {};

    (breakdown?.sections || []).forEach((section) => {
        const lines = (section.lines || []).filter((line) => line.custom);

        if (lines.length) {
            custom[section.code] = JSON.parse(JSON.stringify(lines));
        }
    });

    return custom;
}

function mergeCustomLines(breakdown, customBySection) {
    breakdown.sections.forEach((section) => {
        const extras = customBySection[section.code] || [];

        extras.forEach((line) => {
            if (! section.lines.some((existing) => existing.code === line.code)) {
                section.lines.push(JSON.parse(JSON.stringify(line)));
            }
        });
    });

    return breakdown;
}

function createCustomLine() {
    return {
        code: `custom_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`,
        label: '',
        calc: 'amount',
        unit: 'kg',
        optional: true,
        custom: true,
        enabled: true,
        consumption: 0,
        rate: 0,
        wastage_pct: 0,
        amount_pc: 0,
        lump_total: 0,
        percent: 0,
        percent_base: null,
        computed_pc: 0,
    };
}

export function registerCommercialQuoteBreakdown(Alpine) {
    Alpine.data('commercialQuoteBreakdown', (config = {}) => ({
        garmentType: config.garmentType || 'woven',
        quoteBasis: config.quoteBasis || 'fob',
        currency: config.currency || 'BDT',
        quantity: Math.max(1, Number(config.quantity) || 1),
        breakdown: config.breakdown || { sections: [], summary: {} },
        templates: config.templates || {},
        activeTab: 'costing',
        openSections: {},

        init() {
            if (! this.breakdown?.sections?.length) {
                this.applyTemplate(false);
            } else {
                this.recalculate();
            }

            this.initOpenSections();
        },

        initOpenSections() {
            this.breakdown.sections.forEach((section) => {
                const hasValue = section.lines.some((line) =>
                    line.enabled && (
                        (Number(line.computed_pc) || 0) > 0
                        || (Number(line.amount_pc) || 0) > 0
                        || (Number(line.consumption) || 0) > 0
                        || (Number(line.lump_total) || 0) > 0
                    ),
                );

                this.openSections[section.code] = hasValue
                    || section.code === 'other'
                    || ['fabric', 'yarn_fabric', 'processing'].includes(section.code);
            });
        },

        applyTemplate(confirmChange = true) {
            const template = this.templates[this.garmentType]?.[this.quoteBasis];

            if (! template) {
                return;
            }

            if (confirmChange && this.breakdown?.sections?.length) {
                const hasValues = this.breakdown.sections.some((section) =>
                    section.lines.some((line) =>
                        (Number(line.consumption) || 0) > 0
                        || (Number(line.rate) || 0) > 0
                        || (Number(line.amount_pc) || 0) > 0
                        || (Number(line.lump_total) || 0) > 0,
                    ),
                );

                if (hasValues && ! window.confirm('Change template? Line values reset; custom items are kept.')) {
                    return;
                }
            }

            const customLines = extractCustomLines(this.breakdown);

            this.breakdown = JSON.parse(JSON.stringify(template));
            this.breakdown.garment_type = this.garmentType;
            this.breakdown.quote_basis = this.quoteBasis;
            this.breakdown.currency = this.currency;
            this.breakdown.quantity = this.quantity;

            mergeCustomLines(this.breakdown, customLines);
            this.recalculate();
            this.initOpenSections();
        },

        onGarmentTypeChange() {
            this.applyTemplate(true);
        },

        onQuoteBasisChange() {
            this.applyTemplate(true);
        },

        onCurrencyChange() {
            if (this.breakdown) {
                this.breakdown.currency = this.currency;
            }
        },

        recalculate() {
            if (! this.breakdown?.sections) {
                return;
            }

            this.breakdown.quantity = this.quantity;
            this.breakdown.garment_type = this.garmentType;
            this.breakdown.quote_basis = this.quoteBasis;
            this.breakdown.currency = this.currency;
            recalculateBreakdown(this.breakdown);
        },

        toggleSection(code) {
            this.openSections[code] = ! this.openSections[code];
        },

        isSectionOpen(code) {
            return !! this.openSections[code];
        },

        toggleLine(line) {
            line.enabled = ! line.enabled;
            this.recalculate();
        },

        addCustomLine(sectionCode) {
            const section = this.breakdown.sections.find((item) => item.code === sectionCode);

            if (! section) {
                return;
            }

            section.lines.push(createCustomLine());
            this.openSections[sectionCode] = true;
            this.recalculate();
        },

        removeCustomLine(section, lineIndex) {
            const line = section.lines[lineIndex];

            if (! line?.custom) {
                return;
            }

            section.lines.splice(lineIndex, 1);
            this.recalculate();
        },

        sectionHasActiveLines(section) {
            return section.lines.some((line) => line.enabled && (Number(line.computed_pc) || 0) > 0);
        },

        formatMoney(value) {
            const symbol = this.currency === 'USD' ? '$' : '৳';

            return symbol + Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        formatPc(value) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 4,
            });
        },

        syncHiddenFields() {
            this.recalculate();

            const jsonField = this.$refs.breakdownJson;
            const amountField = this.$refs.quoteAmount;
            const pricePcField = this.$refs.pricePerPc;

            if (jsonField) {
                jsonField.value = JSON.stringify(this.breakdown);
            }

            if (amountField) {
                amountField.value = this.breakdown.summary?.order_total ?? '';
            }

            if (pricePcField) {
                pricePcField.value = this.breakdown.summary?.price_per_pc ?? '';
            }
        },
    }));
}
