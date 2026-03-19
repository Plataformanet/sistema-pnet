<?php

namespace App\Services;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TenantRegistrationService
{

    public function store(array $data): Tenant
    {

     $tenant = Tenant::create([
        'name'      => $data['name'],
        'is_active' => true,
     ]);

     try {
        $tenant->domains()->create([
            'domain' => $data['domain'],
        ]);

        $tenant->run(function () use ($data) {
            DB::beginTransaction();

            try {
                User::create([
                    'name'     => $data['userName'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error creating user for tenant: ' . $e->getMessage());
            }
        });

      } catch (\Throwable $e) {
        $tenant->delete();
        Log::error('Error creating user for tenant: ' . $e->getMessage());
      }

      return $tenant;
    }
}
