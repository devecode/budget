<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = ['Credit Card', 'Debit Card', 'Transfer', 'Cash'];

        foreach ($methods as $method) {
            PaymentMethod::create(['name' => $method]);
        }
    }
}

