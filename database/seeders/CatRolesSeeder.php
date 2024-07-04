<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatRolesSeeder extends Seeder
{
    public function run()
    {
        DB::table('cat_roles')->insert([
            ['name' => 'OWNER'],
            ['name' => 'ADMIN'],
        ]);
    }
}
