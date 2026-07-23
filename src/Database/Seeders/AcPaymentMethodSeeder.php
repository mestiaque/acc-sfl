<?php

namespace ME\AccSfl\Database\Seeders;

use Illuminate\Database\Seeder;
use ME\AccSfl\Models\AcPaymentMethod;

class AcPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cash', 'Bank', 'bKash', 'Rocket', 'Nagad', 'Upay'] as $name) {
            AcPaymentMethod::withTrashed()->firstOrCreate(['name' => $name]);
        }
    }
}
