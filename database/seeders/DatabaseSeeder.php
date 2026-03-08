<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 5 fixed users dengan password yang diketahui — untuk verifikasi koneksi DB
        $users = [
            ['username' => 'admin',   'email' => 'admin@ewall.app',   'phone' => '081100000001'],
            ['username' => 'alice',   'email' => 'alice@ewall.app',   'phone' => '081100000002'],
            ['username' => 'bob',     'email' => 'bob@ewall.app',     'phone' => '081100000003'],
            ['username' => 'charlie', 'email' => 'charlie@ewall.app', 'phone' => '081100000004'],
            ['username' => 'diana',   'email' => 'diana@ewall.app',   'phone' => '081100000005'],
        ];

        foreach ($users as $data) {
            // Skip jika user sudah ada (aman di-run ulang)
            if (User::where('email', $data['email'])->exists()) {
                continue;
            }

            $user = User::create([
                'username' => $data['username'],
                'email'    => $data['email'],
                'phone'    => $data['phone'],
                'password' => Hash::make('password123'),
            ]);

            // Buat wallet untuk setiap user
            Wallet::create([
                'user_id'        => $user->id,
                'balance'        => rand(10, 200) * 10000, // saldo random 100k–2jt
                'wallet_address' => collect(range(1, 4))
                    ->map(fn() => str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT))
                    ->implode(''),
            ]);
        }

        $this->command->info('✅ Seeder selesai: 5 users + wallets berhasil dibuat.');
        $this->command->info('   Password semua user: password123');
    }
}
