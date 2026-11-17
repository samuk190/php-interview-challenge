<?php

namespace Database\Seeders;

use App\Models\CatFact;
use Illuminate\Database\Seeder;

class CatFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i <= 250; $i++) {
            $fact = "This is cat fact number $i";

            if (CatFact::where('fact', $fact)->first()) {
                continue; // this fact exists, skip it
            }

            $model = new CatFact;
            $model->fact = $fact;

            $model->save();
        }
    }
}
