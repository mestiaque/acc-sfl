<?php

namespace ME\AccSfl\Database\Seeders;

use Illuminate\Database\Seeder;

class AccSflDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AcBranchSeeder::class,
            AcPaymentMethodSeeder::class,
            AcMasterParticularSeeder::class,
        ]);
    }
}
