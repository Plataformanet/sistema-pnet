<?php

namespace App\Services;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantRegistrationService
{
    public function __construct(private CreateNewUser $createNewUser){}

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
                $this->createNewUser->create([
                    'name'     => $data['userName'],
                    'email'    => $data['email'],
                    'password' => $data['password'],
                ]);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });

      } catch (\Throwable $e) {
        $tenant->delete();
        throw $e;
      }

      return $tenant;
    }
}
