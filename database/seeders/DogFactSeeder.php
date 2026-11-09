<?php

namespace Database\Seeders;

use App\Models\DogFact;
use Illuminate\Database\Seeder;

class DogFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i <= 250; $i++) {
            $fact = "This is dog fact number $i";

            if (DogFact::where('fact', $fact)->first()) {
                continue; // this fact exists, skip it
            }

            $model = new DogFact();
            $model->fact = $fact;

            $model->save();
        }
    }
}
