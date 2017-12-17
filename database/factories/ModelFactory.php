<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'last_name'  => $faker->firstName,
        'first_name' => $faker->lastName,
        'api_token'  => str_random(60),
        'email'      => $faker->email,
    ];
});

$factory->define(App\Bankings\BankAccount::class, function (Faker\Generator $faker) {
    return [
        'user_id'         => factory(App\User::class)->create()->id,
        'uuid'            => str_random(60),
        'is_active'       => true,
        'balance'         => rand(0, 50000),
    ];
});

$factory->define(App\Bankings\Transaction::class, function (Faker\Generator $faker) {
    return [
        'amount'                 => 1000,
        'bank_account_id'        => factory(App\Bankings\BankAccount::class)->create()->id,
        'service_fee'            => 0,
        'description'            => 'Lorem ipsum',
        'type'                   => 'deposit',
    ];
});
