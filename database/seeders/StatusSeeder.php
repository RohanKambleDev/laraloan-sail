<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statusArr = $this->getStatuses();
        foreach ($statusArr as $status) {
            Status::UpdateorCreate([
                'slug' => $status['slug'],
                'name' => $status['name']
            ]);
        }
    }

    /**
     * getStatuses
     *
     * @return array
     */
    private function getStatuses()
    {
        return [
            [
                'slug' => 'pending',
                'name' => 'Pending'
            ],
            [
                'slug' => 'approved',
                'name' => 'Approved'
            ],
            [
                'slug' => 'paid',
                'name' => 'Paid'
            ]
        ];
    }
}
