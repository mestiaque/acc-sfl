<?php

namespace ME\AccSfl\Database\Seeders;

use Illuminate\Database\Seeder;
use ME\AccSfl\Models\AcBranch;

class AcBranchSeeder extends Seeder
{
    public function run(): void
    {
        AcBranch::withTrashed()->firstOrCreate(
            ['code' => 'HO'],
            [
                'name' => 'Head Office',
                'location' => config('acc-sfl.company.address'),
                'branch_head' => null,
                'is_active' => true,
            ],
        );
    }
}
