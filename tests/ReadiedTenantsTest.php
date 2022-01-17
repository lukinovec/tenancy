<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Tests;

use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Commands\ClearReadiedTenants;
use Stancl\Tenancy\Commands\CreateReadiedTenants;
use Stancl\Tenancy\Tests\Etc\Tenant;

class ReadiedTenantsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function a_tenant_is_correctly_identified_as_readied()
    {
        Tenant::createReadied();

        $this->assertCount(1, Tenant::onlyReadied()->get());

        Tenant::onlyReadied()->first()->update([
            'readied' => null
        ]);

        $this->assertCount(0, Tenant::onlyReadied()->get());
    }

    /** @test */
    public function readied_tenants_are_created_and_deleted_from_the_commands()
    {
        config(['tenancy.readied.count' => 4]);

        Artisan::call(CreateReadiedTenants::class);

        $this->assertCount(4, Tenant::onlyReadied()->get());

        Artisan::call(ClearReadiedTenants::class);

        $this->assertCount(0, Tenant::onlyReadied()->get());
    }

    /** @test */
    public function clear_readied_tenants_command_only_delete_readied_tenants_older_than()
    {
        config(['tenancy.readied.count' => 2]);

        Artisan::call(CreateReadiedTenants::class);

        config(['tenancy.readied.older_than_days' => 4]);

        tenancy()->model()->query()->onlyReadied()->first()->update([
            'readied' => now()->subDays()
        ]);

        Artisan::call(ClearReadiedTenants::class);

        $this->assertCount(1, Tenant::onlyReadied()->get());
    }

    /** @test */
    public function clear_readied_tenants_command_all_option_overrides_config()
    {
        Tenant::createReadied();
        Tenant::createReadied();

        tenancy()->model()->query()->onlyReadied()->first()->update([
            'readied' => now()->subDays(10)
        ]);

        config(['tenancy.readied.older_than_days' => 4]);

        Artisan::call(ClearReadiedTenants::class, [
            '--all' => true
        ]);

        $this->assertCount(0, Tenant::onlyReadied()->get());
    }

    /** @test */
    public function tenancy_can_check_for_readied_tenants()
    {
        Tenant::query()->delete();

        $this->assertFalse(Tenant::onlyReadied()->exists());

        Tenant::createReadied();

        $this->assertTrue(Tenant::onlyReadied()->exists());
    }

    /** @test */
    public function tenancy_can_pull_a_readied_tenant()
    {
        $this->assertNull(Tenant::pullReadiedTenant());

        Tenant::createReadied();

        $this->assertInstanceOf(Tenant::class, Tenant::pullReadiedTenant(true));
    }

    /** @test */
    public function tenancy_can_create_if_none_are_readied()
    {
        $this->assertDatabaseCount(Tenant::class, 0);

        Tenant::pullReadiedTenant(true);

        $this->assertDatabaseCount(Tenant::class, 1);
    }

    /** @test */
    public function readied_tenants_global_scope_config_can_include_or_exclude()
    {
        Tenant::createReadied();

        config(['tenancy.readied.include_in_scope' => false]);

        $this->assertCount(0, Tenant::all());

        config(['tenancy.readied.include_in_scope' => true]);

        $this->assertCount(1, Tenant::all());
        Tenant::all();
    }
}
