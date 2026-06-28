<?php

namespace Database\Seeders;

use Database\Seeders\Masters\AccessoriesItemSeeder;
use Database\Seeders\Masters\AccessoriesStatusSeeder;
use Database\Seeders\Masters\BankSeeder;
use Database\Seeders\Masters\BrandSeeder;
use Database\Seeders\Masters\BuyerClassSeeder;
use Database\Seeders\Masters\BuyerSeeder;
use Database\Seeders\Masters\ColorSeeder;
use Database\Seeders\Masters\CompanyCalendarSeeder;
use Database\Seeders\Masters\CompositionSeeder;
use Database\Seeders\Masters\DepartmentSeeder;
use Database\Seeders\Masters\UnitDepartmentsDesignationsSeeder;
use Database\Seeders\Masters\FabricCategorySeeder;
use Database\Seeders\Masters\FabricationSeeder;
use Database\Seeders\Masters\FabricTypeSeeder;
use Database\Seeders\Hrm\DemoEmployeeSeeder;
use Database\Seeders\Hrm\HeadOfficeEmployeeSeeder;
use Database\Seeders\Hrm\DemoPerformanceSeeder;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Database\Seeders\Hrm\SalaryIncrementSeeder;
use Database\Seeders\Hrm\SalaryLegacySeeder;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Masters\GarmentProductionStatusSeeder;
use Database\Seeders\Masters\GsmSeeder;
use Database\Seeders\Masters\ItemBodyPartSeeder;
use Database\Seeders\Masters\ItemSeeder;
use Database\Seeders\Masters\MaterialSeeder;
use Database\Seeders\Masters\MaterialTypeSeeder;
use Database\Seeders\Masters\OrderStatusSeeder;
use Database\Seeders\Masters\OrderTypeSeeder;
use Database\Seeders\Masters\PaymentStatusSeeder;
use Database\Seeders\Masters\PriceStatusSeeder;
use Database\Seeders\Masters\SampleStatusSeeder;
use Database\Seeders\Masters\SampleTypeSeeder;
use Database\Seeders\Masters\SeasonSeeder;
use Database\Seeders\Masters\ShipmentModeSeeder;
use Database\Seeders\Masters\ShipmentStatusSeeder;
use Database\Seeders\Masters\ShipoutConditionSeeder;
use Database\Seeders\Masters\ShortOrderStatusSeeder;
use Database\Seeders\Masters\SizeSeeder;
use Database\Seeders\Masters\SupplierSeeder;
use Database\Seeders\Masters\SupplierTypeSeeder;
use Database\Seeders\Masters\SustainabilitySeeder;
use Database\Seeders\Masters\TrimsStatusSeeder;
use Database\Seeders\Masters\WovenStatusSeeder;
use Database\Seeders\Masters\YarnStatusSeeder;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FactorySeeder::class,
            HrmMasterDataSeeder::class,
            DepartmentSeeder::class,
            UnitDepartmentsDesignationsSeeder::class,
            CompanyCalendarSeeder::class,
            SalaryLegacySeeder::class,
            HeadOfficeEmployeeSeeder::class,
            DemoEmployeeSeeder::class,
            DemoPerformanceSeeder::class,
            SalaryIncrementSeeder::class,
            BuyerSeeder::class,
            BrandSeeder::class,
            SeasonSeeder::class,
            BuyerClassSeeder::class,
            ItemSeeder::class,
            AccessoriesItemSeeder::class,
            ItemBodyPartSeeder::class,
            ColorSeeder::class,
            SizeSeeder::class,
            MaterialTypeSeeder::class,
            MaterialSeeder::class,
            FabricCategorySeeder::class,
            FabricTypeSeeder::class,
            FabricationSeeder::class,
            CompositionSeeder::class,
            GsmSeeder::class,
            SustainabilitySeeder::class,
            OrderTypeSeeder::class,
            ShipmentModeSeeder::class,
            ShipoutConditionSeeder::class,
            ShipmentStatusSeeder::class,
            OrderStatusSeeder::class,
            ShortOrderStatusSeeder::class,
            PriceStatusSeeder::class,
            YarnStatusSeeder::class,
            WovenStatusSeeder::class,
            TrimsStatusSeeder::class,
            AccessoriesStatusSeeder::class,
            SampleStatusSeeder::class,
            GarmentProductionStatusSeeder::class,
            PaymentStatusSeeder::class,
            BankSeeder::class,
            SupplierTypeSeeder::class,
            SupplierSeeder::class,
            SampleTypeSeeder::class,
        ]);
    }
}
