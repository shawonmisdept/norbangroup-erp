<?php

use App\Models\AccessoriesItem;
use App\Models\Bank;
use App\Models\Brand;
use App\Models\Buyer;
use App\Models\BuyerClass;
use App\Models\Color;
use App\Models\CompanyCalendar;
use App\Models\Composition;
use App\Models\Department;
use App\Models\Designation;
use App\Models\FabricCategory;
use App\Models\Fabrication;
use App\Models\FabricType;
use App\Models\Factory;
use App\Models\Gsm;
use App\Models\ItemBodyPart;
use App\Models\Item;
use App\Models\Material;
use App\Models\MaterialType;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\PaymentStatus;
use App\Models\PriceStatus;
use App\Models\SampleType;
use App\Models\SampleStatus;
use App\Models\Season;
use App\Models\ShipmentMode;
use App\Models\ShipmentStatus;
use App\Models\ShipoutCondition;
use App\Models\ShortOrderStatus;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Sustainability;
use App\Models\TrimsStatus;
use App\Models\WovenStatus;
use App\Models\YarnStatus;
use App\Models\GarmentProductionStatus;
use App\Models\AccessoriesStatus;

return [

    'groups' => [
        'Organization'          => ['factories', 'departments', 'designations', 'company-calendars'],
        'Commercial'            => ['buyers', 'brands', 'seasons', 'classes'],
        'Product'               => ['items', 'colors', 'sizes', 'accessories-items', 'item-body-parts'],
        'Material & Fabric'     => ['material-types', 'materials', 'fabric-categories', 'fabric-types', 'fabrications', 'compositions', 'gsms', 'sustainabilities'],
        'Order & Shipment'      => ['order-types', 'shipment-modes', 'shipout-conditions', 'shipment-statuses'],
        'Production & Status'   => ['order-statuses', 'short-order-statuses', 'price-statuses', 'yarn-statuses', 'woven-statuses', 'trims-statuses', 'accessories-statuses', 'sample-statuses', 'garment-production-statuses', 'payment-statuses'],
        'Finance'               => ['banks'],
        'Supplier'              => ['supplier-types', 'suppliers'],
        'Sample'                => ['sample-types'],
    ],

    'modules' => [

        'factories' => [
            'label'        => 'Factory',
            'label_plural' => 'Factories',
            'model'        => Factory::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'address'   => ['type' => 'text', 'label' => 'Address'],
                'phone'     => ['type' => 'text', 'label' => 'Phone'],
                'attendance_lat' => ['type' => 'text', 'label' => 'Attendance GPS Lat', 'placeholder' => '23.8103'],
                'attendance_lng' => ['type' => 'text', 'label' => 'Attendance GPS Lng', 'placeholder' => '90.4125'],
                'attendance_radius_m' => ['type' => 'number', 'label' => 'Geofence Radius (m)', 'default' => 200],
                'mobile_checkin_enabled' => ['type' => 'boolean', 'label' => 'Mobile Check-in Enabled', 'default' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'address', 'phone', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'departments' => [
            'label'        => 'Department',
            'label_plural' => 'Departments',
            'model'        => Department::class,
            'with'         => ['factory'],
            'fields' => [
                'name'       => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'factory_id' => ['type' => 'relation', 'label' => 'Factory', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'is_active'  => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'factory_id', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'designations' => [
            'label'        => 'Designation',
            'label_plural' => 'Designations',
            'model'        => Designation::class,
            'with'         => ['department'],
            'fields' => [
                'name'          => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'department_id' => ['type' => 'relation', 'label' => 'Department', 'relation' => Department::class, 'display' => 'name', 'nullable' => true],
                'is_active'     => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'department_id', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'buyers' => [
            'label'        => 'Buyer',
            'label_plural' => 'Buyers',
            'model'        => Buyer::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'company'   => ['type' => 'text', 'label' => 'Company'],
                'email'     => ['type' => 'email', 'label' => 'Email'],
                'phone'     => ['type' => 'text', 'label' => 'Phone'],
                'country'   => ['type' => 'text', 'label' => 'Country'],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'company', 'email', 'country', 'is_active'],
            'search'  => ['name', 'code', 'company'],
        ],

        'brands' => [
            'label'        => 'Brand',
            'label_plural' => 'Brands',
            'model'        => Brand::class,
            'with'         => ['buyer'],
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'buyer_id'  => ['type' => 'relation', 'label' => 'Buyer', 'required' => true, 'relation' => Buyer::class, 'display' => 'name'],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'buyer_id', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'seasons' => [
            'label'        => 'Season',
            'label_plural' => 'Seasons',
            'model'        => Season::class,
            'fields' => [
                'name'       => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'year'       => ['type' => 'number', 'label' => 'Year'],
                'start_date' => ['type' => 'date', 'label' => 'Start Date'],
                'end_date'   => ['type' => 'date', 'label' => 'End Date'],
                'is_active'  => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'year', 'start_date', 'end_date', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'classes' => [
            'label'        => 'Class',
            'label_plural' => 'Classes',
            'model'        => BuyerClass::class,
            'with'         => ['buyer'],
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'buyer_id'  => ['type' => 'relation', 'label' => 'Buyer', 'required' => true, 'relation' => Buyer::class, 'display' => 'name'],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'buyer_id', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'items' => [
            'label'        => 'Item',
            'label_plural' => 'Items',
            'model'        => Item::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'image'     => ['type' => 'image', 'label' => 'Image'],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'image', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'colors' => [
            'label'        => 'Color',
            'label_plural' => 'Colors',
            'model'        => Color::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'hex_code'  => ['type' => 'text', 'label' => 'Hex Code', 'placeholder' => '#FFFFFF'],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'hex_code', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'sizes' => [
            'label'        => 'Size',
            'label_plural' => 'Sizes',
            'model'        => Size::class,
            'fields' => [
                'name'       => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'sort_order' => ['type' => 'number', 'label' => 'Sort Order', 'default' => 0],
                'is_active'  => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'sort_order', 'is_active'],
            'search'  => ['name', 'code'],
            'default_order' => ['sort_order' => 'asc', 'name' => 'asc'],
        ],

        'material-types' => [
            'label'        => 'Material Type',
            'label_plural' => 'Material Types',
            'model'        => MaterialType::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'materials' => [
            'label'        => 'Material',
            'label_plural' => 'Materials',
            'model'        => Material::class,
            'with'         => ['materialType'],
            'fields' => [
                'name'              => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'material_type_id'  => ['type' => 'relation', 'label' => 'Material Type', 'required' => true, 'relation' => MaterialType::class, 'display' => 'name'],
                'unit'              => ['type' => 'text', 'label' => 'Unit', 'placeholder' => 'kg, m, pcs'],
                'is_active'         => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'material_type_id', 'unit', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'fabrications' => [
            'label'        => 'Fabrication',
            'label_plural' => 'Fabrications',
            'model'        => Fabrication::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'compositions' => [
            'label'        => 'Composition',
            'label_plural' => 'Compositions',
            'model'        => Composition::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'fabric-types' => [
            'label'        => 'Fabric Type',
            'label_plural' => 'Fabric Types',
            'model'        => FabricType::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'gsms' => [
            'label'        => 'GSM',
            'label_plural' => 'GSM',
            'model'        => Gsm::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'value'     => ['type' => 'number', 'label' => 'GSM Value', 'required' => true, 'min' => 1],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'value', 'is_active'],
            'search'  => ['name', 'code'],
            'default_order' => ['value' => 'asc'],
        ],

        'sample-types' => [
            'label'        => 'Sample Type',
            'label_plural' => 'Sample Types',
            'model'        => SampleType::class,
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'order-types' => [
            'label' => 'Order Type', 'label_plural' => 'Order Types', 'model' => OrderType::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'shipment-modes' => [
            'label' => 'Shipment Mode', 'label_plural' => 'Shipment Modes', 'model' => ShipmentMode::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'shipout-conditions' => [
            'label' => 'Shipout Condition', 'label_plural' => 'Shipout Conditions', 'model' => ShipoutCondition::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'shipment-statuses' => [
            'label' => 'Shipment Status', 'label_plural' => 'Shipment Statuses', 'model' => ShipmentStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'fabric-categories' => [
            'label' => 'Fabric Category', 'label_plural' => 'Fabric Categories', 'model' => FabricCategory::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'sustainabilities' => [
            'label' => 'Sustainability', 'label_plural' => 'Sustainability', 'model' => Sustainability::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'price-statuses' => [
            'label' => 'Price Status', 'label_plural' => 'Price Statuses', 'model' => PriceStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'order-statuses' => [
            'label' => 'Order Status', 'label_plural' => 'Order Statuses', 'model' => OrderStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'short-order-statuses' => [
            'label' => 'Short Order Status', 'label_plural' => 'Short Order Statuses', 'model' => ShortOrderStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'yarn-statuses' => [
            'label' => 'Yarn Status', 'label_plural' => 'Yarn Statuses', 'model' => YarnStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'woven-statuses' => [
            'label' => 'Woven Status', 'label_plural' => 'Woven Statuses', 'model' => WovenStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'trims-statuses' => [
            'label' => 'Trims Status', 'label_plural' => 'Trims Statuses', 'model' => TrimsStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'accessories-statuses' => [
            'label' => 'Accessories Status', 'label_plural' => 'Accessories Statuses', 'model' => AccessoriesStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'sample-statuses' => [
            'label' => 'Sample Status', 'label_plural' => 'Sample Statuses', 'model' => SampleStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'garment-production-statuses' => [
            'label' => 'Garment Production Status', 'label_plural' => 'Garment Production Statuses', 'model' => GarmentProductionStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'payment-statuses' => [
            'label' => 'Payment Status', 'label_plural' => 'Payment Statuses', 'model' => PaymentStatus::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'supplier-types' => [
            'label' => 'Supplier Type', 'label_plural' => 'Supplier Types', 'model' => SupplierType::class,
            'fields' => ['name' => ['type' => 'text', 'label' => 'Name', 'required' => true], 'is_active' => ['type' => 'boolean', 'label' => 'Active', 'default' => true]],
            'columns' => ['code', 'name', 'is_active'], 'search' => ['name', 'code'],
        ],

        'suppliers' => [
            'label'        => 'Supplier',
            'label_plural' => 'Suppliers',
            'model'        => Supplier::class,
            'with'         => ['supplierType'],
            'fields' => [
                'name'             => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'supplier_type_id' => ['type' => 'relation', 'label' => 'Supplier Type', 'required' => true, 'relation' => SupplierType::class, 'display' => 'name'],
                'company'          => ['type' => 'text', 'label' => 'Company'],
                'email'            => ['type' => 'email', 'label' => 'Email'],
                'phone'            => ['type' => 'text', 'label' => 'Phone'],
                'country'          => ['type' => 'text', 'label' => 'Country'],
                'is_active'        => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'supplier_type_id', 'company', 'email', 'country', 'is_active'],
            'search'  => ['name', 'code', 'company'],
        ],

        'banks' => [
            'label'        => 'Bank',
            'label_plural' => 'Banks',
            'model'        => Bank::class,
            'fields' => [
                'name'           => ['type' => 'text', 'label' => 'Bank Name', 'required' => true],
                'branch'         => ['type' => 'text', 'label' => 'Branch'],
                'account_name'   => ['type' => 'text', 'label' => 'Account Name'],
                'account_number' => ['type' => 'text', 'label' => 'Account Number'],
                'routing_number' => ['type' => 'text', 'label' => 'Routing Number'],
                'swift_code'     => ['type' => 'text', 'label' => 'SWIFT Code'],
                'country'        => ['type' => 'text', 'label' => 'Country', 'default' => 'Bangladesh'],
                'is_active'      => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'branch', 'account_number', 'swift_code', 'country', 'is_active'],
            'search'  => ['name', 'code', 'branch', 'account_number'],
        ],

        'company-calendars' => [
            'label'        => 'Company Calendar',
            'label_plural' => 'Company Calendar',
            'model'        => CompanyCalendar::class,
            'with'         => ['factory'],
            'fields' => [
                'name'          => ['type' => 'text', 'label' => 'Title', 'required' => true],
                'calendar_type' => ['type' => 'select', 'label' => 'Calendar Type', 'required' => true, 'options' => ['National Holiday', 'Religious Holiday', 'Factory Off', 'Company Event', 'Weekend']],
                'start_date'    => ['type' => 'date', 'label' => 'Start Date', 'required' => true],
                'end_date'      => ['type' => 'date', 'label' => 'End Date'],
                'description'   => ['type' => 'textarea', 'label' => 'Description'],
                'factory_id'    => ['type' => 'relation', 'label' => 'Factory', 'relation' => Factory::class, 'display' => 'name', 'nullable' => true],
                'is_active'     => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'calendar_type', 'start_date', 'end_date', 'factory_id', 'is_active'],
            'search'  => ['name', 'code'],
            'default_order' => ['start_date' => 'desc'],
        ],

        'accessories-items' => [
            'label'        => 'Accessories Item',
            'label_plural' => 'Accessories Items',
            'model'        => AccessoriesItem::class,
            'fields' => [
                'name'        => ['type' => 'text', 'label' => 'Name', 'required' => true],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'unit'        => ['type' => 'text', 'label' => 'Unit', 'placeholder' => 'pcs, set, m'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'unit', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'item-body-parts' => [
            'label'        => 'Item Body Part',
            'label_plural' => 'Item Body Parts',
            'model'        => ItemBodyPart::class,
            'fields' => [
                'name'        => ['type' => 'text', 'label' => 'Body Parts', 'required' => true],
                'description' => ['type' => 'textarea', 'label' => 'Item Body Parts'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'description', 'is_active'],
            'search'  => ['name', 'code', 'description'],
        ],

    ],

    'relation_columns' => [
        'factory_id'         => ['relation' => 'factory', 'display' => 'name'],
        'department_id'      => ['relation' => 'department', 'display' => 'name'],
        'buyer_id'           => ['relation' => 'buyer', 'display' => 'name'],
        'material_type_id'   => ['relation' => 'materialType', 'display' => 'name'],
        'supplier_type_id'   => ['relation' => 'supplierType', 'display' => 'name'],
    ],

];
