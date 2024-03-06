<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\Ssl\Deploy;
use App\Jobs\Ssl\Remove;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SslTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_ssls_list()
    {
        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->get(route('servers.sites.ssl', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertOk()
            ->assertSee($ssl->type);
    }

    public function test_see_ssls_list_with_no_ssls()
    {
        $this->actingAs($this->user);

        $this->get(route('servers.sites.ssl', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertOk()
            ->assertSeeText(__("You don't have any SSL certificates yet!"));
    }

    public function test_create_ssl()
    {
        Bus::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.sites.ssl.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'type' => SslType::LETSENCRYPT,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATING,
        ]);

        Bus::assertDispatched(Deploy::class);
    }

    public function test_delete_ssl()
    {
        Bus::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('servers.sites.ssl.destroy', [
            'server' => $this->server,
            'site' => $this->site,
            'ssl' => $ssl,
        ]))->assertRedirect();

        $this->assertDatabaseHas('ssls', [
            'id' => $ssl->id,
            'status' => SslStatus::DELETING,
        ]);

        Bus::assertDispatched(Remove::class);
    }
}