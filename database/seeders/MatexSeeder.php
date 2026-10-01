<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MatexSeeder extends Seeder
{
    public function run(): void
    {
        $sdi = Company::create([
            'code' => 'SDI',
            'name' => 'PT. SDI',
            'type' => CompanyType::Sdi,
            'address' => 'Jakarta, Indonesia',
        ]);

        // Supplier RM/OHP tidak di-seed: companies di-provision otomatis dari
        // qad_suppliers (ResolvesQadSupplier) saat pertama dipakai di PO.
        $users = [
            ['name' => 'Admin Matex', 'email' => 'admin@matex.test', 'role' => UserRole::Admin, 'company_id' => $sdi->id],
            ['name' => 'Dita Purchasing', 'email' => 'dita@matex.test', 'role' => UserRole::Purchasing, 'company_id' => $sdi->id],
            ['name' => 'PPIC SDI', 'email' => 'ppic@matex.test', 'role' => UserRole::Ppic, 'company_id' => $sdi->id],
        ];

        foreach ($users as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
