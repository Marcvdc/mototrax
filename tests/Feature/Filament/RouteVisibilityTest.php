<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RouteResource\Pages\ListRoutes;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RouteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $a;

    private User $b;

    private Route $privateA;

    private Route $publicB;

    private Route $privateB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = User::factory()->create();
        $this->b = User::factory()->create();
        $this->privateA = Route::factory()->for($this->a)->create(['is_public' => false]);
        $this->publicB = Route::factory()->for($this->b)->public()->create();
        $this->privateB = Route::factory()->for($this->b)->create(['is_public' => false]);
    }

    public function test_user_sees_own_and_public_routes_only(): void
    {
        $this->actingAs($this->a);

        Livewire::test(ListRoutes::class)
            ->assertCanSeeTableRecords([$this->privateA, $this->publicB])
            ->assertCanNotSeeTableRecords([$this->privateB]);
    }

    public function test_admin_sees_all_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListRoutes::class)
            ->assertCanSeeTableRecords([$this->privateA, $this->publicB, $this->privateB]);
    }

    public function test_user_cannot_open_view_or_edit_of_foreign_private_route(): void
    {
        $this->actingAs($this->a);

        $this->get("/admin/routes/{$this->privateB->id}")->assertNotFound();
        $this->get("/admin/routes/{$this->privateB->id}/edit")->assertNotFound();
    }
}
