<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       DB::table('products')->insert(
            [
                [
                    'name'=>'dress',
                    'quantity'=>20,
                    'order_counter' =>10,
                    'price'=>500,
                ],
                [
                    'name'=>'pants',
                    'quantity'=>40,
                    'order_counter' =>1,
                    'price'=>1000,
                ],
                [
                    'name'=>'skirt',
                    'quantity'=>70,
                    'order_counter' =>20,
                    'price'=>1500,
                ],
                [
                    'name'=>'T-shert',
                    'quantity'=>10,
                    'order_counter' =>30,
                    'price'=>350,

                ],


                [
                    'name'=>'Hamburger',
                    'quantity'=>67,
                    'order_counter' =>6,
                    'price'=>1050,

                ],
                [
                    'name'=>'Pizza',
                    'quantity'=>20,
                    'order_counter' =>3,
                    'price'=>550,

                ],

            ]);
    }
}
