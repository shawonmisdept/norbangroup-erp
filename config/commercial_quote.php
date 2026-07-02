<?php

return [
    'garment_types' => [
        'woven' => 'Woven',
        'knit'  => 'Knit',
    ],

    'quote_bases' => [
        'cm'  => 'CM / CMT',
        'fob' => 'FOB',
    ],

    'currencies' => [
        'BDT' => 'BDT (৳)',
        'USD' => 'USD ($)',
    ],

    /*
    | Line calc types:
    | - consumption: consumption × rate × (1 + wastage_pct/100)
    | - amount: direct amount_pc
    | - lump: lump_total ÷ order quantity
    | - percent: percent of named base (materials|processing|before_profit)
    |
    | visible_for: cm, fob — line shown when basis matches
    */
    'templates' => [
        'woven' => [
            [
                'code'  => 'fabric',
                'label' => 'Fabric & Material',
                'lines' => [
                    ['code' => 'main_fabric', 'label' => 'Main Fabric', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 5, 'optional' => false],
                    ['code' => 'lining', 'label' => 'Lining Fabric', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 3, 'optional' => true],
                    ['code' => 'interlining', 'label' => 'Interlining / Fusible', 'calc' => 'consumption', 'unit' => 'm', 'default_wastage_pct' => 3, 'optional' => true],
                    ['code' => 'pocketing', 'label' => 'Pocketing', 'calc' => 'consumption', 'unit' => 'm', 'default_wastage_pct' => 3, 'optional' => true],
                ],
            ],
            [
                'code'  => 'trims',
                'label' => 'Trims & Accessories',
                'lines' => [
                    ['code' => 'sewing_thread', 'label' => 'Sewing Thread', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'buttons', 'label' => 'Buttons', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'zippers', 'label' => 'Zippers', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'labels', 'label' => 'Labels (Main / Care / Size)', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'packing_trims', 'label' => 'Packing Material (Poly / Carton / Hanger)', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'other_trims', 'label' => 'Other Trims', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'trims_wastage', 'label' => 'Trims Wastage', 'calc' => 'percent', 'percent_base' => 'trims_subtotal', 'optional' => true],
                ],
            ],
            [
                'code'  => 'processing',
                'label' => 'Processing',
                'lines' => [
                    ['code' => 'cutting_making', 'label' => 'Cutting & Making (CM)', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'packing_labour', 'label' => 'Packing (Labour)', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'value_addition',
                'label' => 'Value Addition',
                'lines' => [
                    ['code' => 'printing', 'label' => 'Printing', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'embroidery', 'label' => 'Embroidery', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'washing', 'label' => 'Garment Washing', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'finishing', 'label' => 'Finishing / Iron', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'testing',
                'label' => 'Testing & Compliance',
                'lines' => [
                    ['code' => 'lab_test', 'label' => 'Lab Test (allocated / pc)', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'compliance', 'label' => 'Compliance / Certificate (allocated / pc)', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'         => 'logistics',
                'label'        => 'Logistics & Shipment',
                'visible_for'  => ['fob'],
                'lines'        => [
                    ['code' => 'inland_transport', 'label' => 'Inland Transport', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'forwarder_docs', 'label' => 'Forwarder / Documentation', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'customs_local', 'label' => 'Customs / Local Charges', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'overhead',
                'label' => 'Factory Overhead',
                'lines' => [
                    ['code' => 'factory_oh', 'label' => 'Factory Overhead', 'calc' => 'percent', 'percent_base' => 'processing', 'default_percent' => 8, 'optional' => false],
                    ['code' => 'rejection', 'label' => 'Rejection / Rework Allowance', 'calc' => 'percent', 'percent_base' => 'before_overhead', 'default_percent' => 2, 'optional' => true],
                ],
            ],
            [
                'code'  => 'profit',
                'label' => 'Profit',
                'lines' => [
                    ['code' => 'factory_profit', 'label' => 'Factory Profit', 'calc' => 'percent', 'percent_base' => 'before_profit', 'default_percent' => 10, 'optional' => false],
                ],
            ],
            [
                'code'  => 'development',
                'label' => 'Development (Order Level → / pc)',
                'lines' => [
                    ['code' => 'sample_dev', 'label' => 'Sample / Development', 'calc' => 'lump', 'optional' => true],
                    ['code' => 'pattern_grading', 'label' => 'Pattern / Grading', 'calc' => 'lump', 'optional' => true],
                    ['code' => 'moq_surcharge', 'label' => 'MOQ Surcharge', 'calc' => 'lump', 'optional' => true],
                ],
            ],
            [
                'code'         => 'other',
                'label'        => 'Other / Custom Items',
                'allow_custom' => true,
                'lines'        => [],
            ],
        ],

        'knit' => [
            [
                'code'  => 'yarn_fabric',
                'label' => 'Yarn & Knit Fabric',
                'lines' => [
                    ['code' => 'yarn', 'label' => 'Yarn', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 5, 'optional' => false],
                    ['code' => 'knitting', 'label' => 'Knitting Charge', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 0, 'optional' => false],
                    ['code' => 'dyeing_finishing', 'label' => 'Dyeing / Finishing (Fabric)', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 3, 'optional' => false],
                    ['code' => 'rib_collar', 'label' => 'Rib / Collar & Cuff Fabric', 'calc' => 'consumption', 'unit' => 'kg', 'default_wastage_pct' => 5, 'optional' => true],
                ],
            ],
            [
                'code'  => 'trims',
                'label' => 'Trims & Accessories',
                'lines' => [
                    ['code' => 'sewing_thread', 'label' => 'Sewing Thread', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'labels', 'label' => 'Labels (Main / Care / Size)', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'packing_trims', 'label' => 'Packing Material', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'other_trims', 'label' => 'Other Trims', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'trims_wastage', 'label' => 'Trims Wastage', 'calc' => 'percent', 'percent_base' => 'trims_subtotal', 'optional' => true],
                ],
            ],
            [
                'code'  => 'processing',
                'label' => 'Processing',
                'lines' => [
                    ['code' => 'cutting_making', 'label' => 'Cutting & Making (CM)', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'packing_labour', 'label' => 'Packing (Labour)', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'value_addition',
                'label' => 'Value Addition',
                'lines' => [
                    ['code' => 'printing', 'label' => 'Printing', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'embroidery', 'label' => 'Embroidery', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'washing', 'label' => 'Garment Washing', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'finishing', 'label' => 'Finishing / Iron', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'testing',
                'label' => 'Testing & Compliance',
                'lines' => [
                    ['code' => 'lab_test', 'label' => 'Lab Test (allocated / pc)', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'compliance', 'label' => 'Compliance / Certificate (allocated / pc)', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'         => 'logistics',
                'label'        => 'Logistics & Shipment',
                'visible_for'  => ['fob'],
                'lines'        => [
                    ['code' => 'inland_transport', 'label' => 'Inland Transport', 'calc' => 'amount', 'optional' => false],
                    ['code' => 'forwarder_docs', 'label' => 'Forwarder / Documentation', 'calc' => 'amount', 'optional' => true],
                    ['code' => 'customs_local', 'label' => 'Customs / Local Charges', 'calc' => 'amount', 'optional' => true],
                ],
            ],
            [
                'code'  => 'overhead',
                'label' => 'Factory Overhead',
                'lines' => [
                    ['code' => 'factory_oh', 'label' => 'Factory Overhead', 'calc' => 'percent', 'percent_base' => 'processing', 'default_percent' => 8, 'optional' => false],
                    ['code' => 'rejection', 'label' => 'Rejection / Rework Allowance', 'calc' => 'percent', 'percent_base' => 'before_overhead', 'default_percent' => 2, 'optional' => true],
                ],
            ],
            [
                'code'  => 'profit',
                'label' => 'Profit',
                'lines' => [
                    ['code' => 'factory_profit', 'label' => 'Factory Profit', 'calc' => 'percent', 'percent_base' => 'before_profit', 'default_percent' => 10, 'optional' => false],
                ],
            ],
            [
                'code'  => 'development',
                'label' => 'Development (Order Level → / pc)',
                'lines' => [
                    ['code' => 'sample_dev', 'label' => 'Sample / Development', 'calc' => 'lump', 'optional' => true],
                    ['code' => 'pattern_grading', 'label' => 'Pattern / Grading', 'calc' => 'lump', 'optional' => true],
                    ['code' => 'moq_surcharge', 'label' => 'MOQ Surcharge', 'calc' => 'lump', 'optional' => true],
                ],
            ],
            [
                'code'         => 'other',
                'label'        => 'Other / Custom Items',
                'allow_custom' => true,
                'lines'        => [],
            ],
        ],
    ],
];
