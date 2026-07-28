<?php

namespace ME\AccSfl\Database\Seeders;

use Illuminate\Database\Seeder;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;

/**
 * Seeds the Master Particular / Particular taxonomy matching the company's actual
 * chart of accounts (with the codes they use internally), so reports render with
 * the correct structure out of the box. Names/order can be edited later through the UI.
 */
class AcMasterParticularSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGroup('CASH RECEIPTS', AcMasterParticular::TYPE_DEBIT, [
            ['Begining Balance', '1000'],
            ['Cash', '1001'],
            ['Cash (Paid by MD Sir Directly)', '1002'],
            ['Cash (Sales)', '1003'],
            ['Customer Account Collections', '1004'],
            ['Loan / Cash Injection', '1005'],
            ['Interest Income', '1006'],
            ['Cash (Inventory Scrap Selling)', '1007'],
            ['Cash (Others)', '1008'],
            ['Others', '1009'],
            ['Others', '1010'],
        ]);

        $this->seedGroup('COST OF GOODS', AcMasterParticular::TYPE_CREDIT, [
            ['Direct Product / Service Costs', '2001'],
            ['Payroll Taxes / Benefits - Direct', '2002'],
            ['Salaries - Direct', '2003'],
            ['Bonus - Yearly', '2004'],
            ['Licenses / Permits', '2005'],
            ['Legal & Professional', '2006'],
            ['Supplies - Fabrics', '2007'],
            ['Supplies - Yarn/Thread', '2008'],
            ['Supplies - Accessories', '2009'],
            ['Supplies - Equipment', '2010'],
        ]);

        $this->seedGroup('OPERATION COST', AcMasterParticular::TYPE_CREDIT, [
            ['Salaries - Indirect', '3001'],
            ['Salary - Advance', '3002'],
            ['Utility', '3003'],
            ['Communication', '3004'],
            ['Transports', '3005'],
            ['Conveyance', '3006'],
            ['Electric Bill', '3007'],
            ['Meals / Food for Guest & Staff', '3008'],
            ['Tiffin/Food Allowance', '3009'],
            ['Maintenance', '3010'],
            ['Garment Machinery', '3011'],
            ['Office Supply', '3012'],
            ['Garments Accessories', '3013'],
            ['Jacquard Parts/Accessories', '3014'],
            ['Packeging Accessories', '3015'],
            ['Thread, Fabrics, Fusing', '3016'],
            ['Fire Safety Equipment', '3017'],
            ['Maintenance Accessories', '3018'],
            ['Machine/Equipment/Parts', '3019'],
            ['Electric Accessories', '3020'],
            ['Tech Accessories', '3021'],
            ['Generator Fuel', '3022'],
            ['Embroidary Aceessories', '3023'],
            ['Chemical', '3024'],
            ['Rent- Floor/ Machine/ Equipment', '3025'],
            ['Advertising', '3026'],
            ['Bank Fees', '3027'],
            ['Internet', '3028'],
            ['Printing & Press', '3029'],
            ['Subcontractors', '3030'],
            ['Fees', '3031'],
            ['Web Development', '3032'],
            ['Water Purifying & Aceessories', '3033'],
            ['Entertainment/Annual Picnic/Tour', '3034'],
            ['Land', '3035'],
        ]);

        // Note: double space in "Construction  Material" matches the company's own
        // chart-of-accounts naming.
        $this->seedGroup('Construction  Material', AcMasterParticular::TYPE_CREDIT, [
            ['Bricks', '4001'],
            ['Cement', '4002'],
            ['Sand', '4003'],
            ['Steel Rod', '4004'],
            ['Electric', '4005'],
            ['Hardwere', '4006'],
            ['Painting', '4007'],
            ['Wood', '4008'],
            ['Thai Aluminium & Glass', '4009'],
            ['Steel Materials', '4010', 'MSI Sheets, SS Bar, SS Pipe & etc'],
            ['Sanitary & Fittings', '4011'],
            ['Tiles', '4012'],
            ['Construction Material- Others', '4013'],
        ]);

        $this->seedGroup('Construction Wages', AcMasterParticular::TYPE_CREDIT, [
            ['Masson Labour', '4014'],
            ['Welding Labour', '4015'],
            ['Electrical Labour', '4016'],
            ['Sanitary Labour', '4017'],
            ['Tiles Labour', '4018'],
            ['Thai Aluminium & Glass Work Labour', '4019'],
            ['Painting Labour', '4020'],
            ['Construction Wages- Others', '4021'],
            ['Carrying Cost- Construction Material', '4022'],
        ]);

        $this->seedGroup('Additional Expenses', AcMasterParticular::TYPE_CREDIT, [
            ['Cash Return to MD Sir', '5001'],
            ['Charitable Contributions', '5002'],
            ['Interest Expense', '5003'],
            ['Income Tax Expense', '5004'],
            ['Other', '5005'],
            ['Other', '5006'],
            ['Other', '5007'],
        ]);
    }

    private function seedGroup(string $masterName, string $type, array $particulars): void
    {
        $master = AcMasterParticular::withTrashed()->firstOrCreate(
            ['name' => $masterName],
            ['type' => $type, 'is_active' => true],
        );

        // Counted rather than firstOrCreate()'d by name, since a couple of groups
        // (e.g. the two "Others" rows under Cash Receipts, three "Other" rows under
        // Additional Expenses) legitimately repeat a name.
        $seenCounts = [];
        foreach ($particulars as $row) {
            [$name, $code] = $row;
            $description = $row[2] ?? null;
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
                    'code' => $code,
                    'description' => $description,
                    'is_active' => true,
                ]);
            }
        }
    }
}
