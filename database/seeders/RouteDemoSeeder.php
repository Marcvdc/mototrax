<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RouteService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class RouteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(RouteService::class);
        $admin = User::query()->where('email', 'admin@mototrax.dev')->first()
            ?? User::factory()->create(['email' => 'admin@mototrax.dev', 'name' => 'Admin User']);

        $fixture = base_path('tests/Fixtures/gpx/sample-track.gpx');

        $service->createFromUpload(
            $admin,
            $this->upload($fixture),
            ['name' => 'Eindhoven Loop (publiek)', 'is_public' => true, 'tags' => ['scenic'], 'difficulty' => 'easy'],
        );

        $service->createFromUpload(
            $admin,
            $this->upload($fixture),
            ['name' => 'Geheim rondje (privé)', 'is_public' => false, 'tags' => ['curvy'], 'difficulty' => 'medium'],
        );

        foreach (User::query()->where('email', '!=', 'admin@mototrax.dev')->limit(2)->get() as $other) {
            $service->createFromUpload(
                $other,
                $this->upload($fixture),
                ['name' => "Tour van {$other->name}", 'is_public' => true, 'tags' => ['mountain']],
            );
        }
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile(
            path: $path,
            originalName: 'sample-track.gpx',
            mimeType: 'application/gpx+xml',
            error: null,
            test: true,
        );
    }
}
