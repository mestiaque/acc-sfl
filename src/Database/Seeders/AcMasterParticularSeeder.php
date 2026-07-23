<?php

namespace ME\AccSfl\Database\Seeders;

use Illuminate\Database\Seeder;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;

/**
 * Seeds the Master Particular / Particular taxonomy matching the company's existing
 * Cash Flow (Operation) spreadsheet line items, so reports render with the correct
 * structure out of the box. Names/order can be edited later through the UI.
 */
class AcMasterParticularSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGroup('CASH RECEIPTS', AcMasterParticular::TYPE_DEBIT, [
            'Cash',
            'Cash (Paid by MD Sir Directly)',
            'Cash (Sales)',
            'Customer Account Collections',
            'Loan / Cash Injection',
            'Interest Income',
            'Cash (Inventory Scrap Selling)',
            'Cash (Others)',
            'Others',
            'Others',
        ]);

        $this->seedGroup('COST OF GOODS & ETC', AcMasterParticular::TYPE_CREDIT, [
            'Direct Product / Service Costs',
            'Payroll Taxes / Benefits - Direct',
            'Salaries - Direct',
            'Bonus - Yearly',
            'Licenses / Permits',
            'Legal & Professional',
            'Supplies - Fabrics',
            'Supplies - Yarn/Thread',
            'Supplies - Accessories',
            'Supplies - Equipment',
        ]);

        $this->seedGroup('OPERATING EXPENSES', AcMasterParticular::TYPE_CREDIT, [
            'Salaries - Indirect',
            'Salary - Advance',
            'Utility',
            'Communication',
            'Transports',
            'Conveyance',
            'Electric Bill',
            'Meals / Food for Guest & Staff',
            'Office Supplies',
            'Repair & Maintenance',
            'Printing & Stationery',
            'Entertainment',
            'Miscellaneous',
        ]);
    }

    private function seedGroup(string $masterName, string $type, array $particulars): void
    {
        $master = AcMasterParticular::withTrashed()->firstOrCreate(
            ['name' => $masterName],
            ['type' => $type, 'is_active' => true],
        );

        // Counted rather than firstOrCreate()'d by name, since a couple of groups
        // (e.g. the two "Others" rows under Cash Receipts) legitimately repeat a name.
        $seenCounts = [];
        foreach ($particulars as $name) {
            $seenCounts[$name] = ($seenCounts[$name] ?? 0) + 1;
            $occurrence = $seenCounts[$name];

            $existingCount = AcParticular::withTrashed()
                ->where('master_particular_id', $master->id)
                ->where('name', $name)
                ->count();

            if ($existingCount < $occurrence) {
                AcParticular::create([
                    'master_particular_id' => $master->id,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }
        }
    }
}
