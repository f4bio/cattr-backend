<?php

namespace Database\Seeders;

use App\Helpers\ServiceAccountHelper;
use Illuminate\Database\Seeder;

class ServiceAccountSeeder extends Seeder
{
    private ServiceAccountHelper $serviceAccountHelper;

    public function __construct(ServiceAccountHelper $serviceAccountHelper)
    {
        $this->serviceAccountHelper = $serviceAccountHelper;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->serviceAccountHelper->findOrCreate();
    }
}
