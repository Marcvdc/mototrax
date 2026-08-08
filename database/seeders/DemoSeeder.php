<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use App\Models\User;
use App\Services\RouteService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Canonieke demo-dataset voor MotoTrax.
 *
 * Levert een deterministische, presenteerbare set: 5 gebruikers (incl. de admin),
 * bikes met onderhoudslogs, 10 routes met een écht GPX-bestand op de disk
 * (zodat kaartpreview en download werken) en een gevulde social feed.
 *
 * Bedoeld voor `php artisan migrate:fresh --seed`.
 */
class DemoSeeder extends Seeder
{
    private const GPX_FIXTURE = 'tests/Fixtures/gpx/sample-track.gpx';

    /**
     * Aantal bikes per demo-gebruiker (index = volgorde van de gebruikers).
     *
     * @var list<int>
     */
    private const BIKES_PER_USER = [2, 1, 3, 1, 2];

    private const MAINTENANCE_LOGS_PER_BIKE = 3;

    public function run(): void
    {
        $users = $this->createUsers();
        $this->createBikesWithLogs($users);
        $routes = $this->createRoutes($users);
        $this->createFeed($users, $routes);
    }

    /**
     * @return list<User>
     */
    private function createUsers(): array
    {
        $admin = User::query()->where('email', 'admin@mototrax.dev')->firstOrFail();

        $demoUsers = [
            ['name' => 'Jan de Vries', 'email' => 'jan@mototrax.dev'],
            ['name' => 'Sanne Bakker', 'email' => 'sanne@mototrax.dev'],
            ['name' => 'Youssef El Amrani', 'email' => 'youssef@mototrax.dev'],
            ['name' => 'Emma Visser', 'email' => 'emma@mototrax.dev'],
        ];

        $users = [$admin];

        foreach ($demoUsers as $attributes) {
            $users[] = User::factory()->create($attributes);
        }

        return $users;
    }

    /**
     * @param  list<User>  $users
     */
    private function createBikesWithLogs(array $users): void
    {
        foreach ($users as $index => $user) {
            $bikeCount = self::BIKES_PER_USER[$index] ?? 1;

            Bike::factory($bikeCount)
                ->for($user)
                ->create()
                ->each(function (Bike $bike) use ($user): void {
                    MaintenanceLog::factory(self::MAINTENANCE_LOGS_PER_BIKE)->create([
                        'user_id' => $user->id,
                        'bike_id' => $bike->id,
                    ]);
                });
        }
    }

    /**
     * Maakt 10 routes met een écht GPX-bestand via de RouteService.
     *
     * @param  list<User>  $users
     * @return list<Route>
     */
    private function createRoutes(array $users): array
    {
        $service = app(RouteService::class);

        [$admin, $jan, $sanne, $youssef, $emma] = $users;

        $definitions = [
            [$admin, 'Eindhoven Loop', true, ['scenic'], 'easy'],
            [$admin, 'Ardennen Klassieker', true, ['curvy', 'forest'], 'hard'],
            [$admin, 'Geheim testrondje', false, ['no_highway'], 'medium'],
            [$jan, 'Veluwe Bosrit', true, ['forest', 'scenic'], 'easy'],
            [$jan, 'Maasroute', true, ['coastal'], 'medium'],
            [$sanne, 'Limburg Heuvelland', true, ['curvy', 'mountain'], 'hard'],
            [$sanne, 'Zeeuwse Kustlijn', true, ['coastal', 'scenic'], 'easy'],
            [$youssef, 'Utrechtse Heuvelrug', true, ['forest'], 'medium'],
            [$youssef, 'Historisch Brabant', true, ['historic', 'food_stops'], 'easy'],
            [$emma, 'Drentse Fietspaden Tour', true, ['scenic'], 'easy'],
        ];

        $routes = [];

        foreach ($definitions as [$owner, $name, $isPublic, $tags, $difficulty]) {
            $routes[] = $service->createFromUpload(
                $owner,
                $this->gpxUpload(),
                [
                    'name' => $name,
                    'is_public' => $isPublic,
                    'tags' => $tags,
                    'difficulty' => $difficulty,
                ],
            );
        }

        return $routes;
    }

    /**
     * Vult de feed met route-shares, onderhoudsupdates en een paar tekstberichten.
     *
     * @param  list<User>  $users
     * @param  list<Route>  $routes
     */
    private function createFeed(array $users, array $routes): void
    {
        foreach ($routes as $route) {
            if (! $route->is_public) {
                continue;
            }

            Post::factory()->routeShare($route)->create([
                'user_id' => $route->user_id,
                'content' => "Nieuwe route gereden: {$route->name}. Aanrader!",
                'likes_count' => rand(0, 25),
                'comments_count' => rand(0, 8),
            ]);
        }

        foreach (MaintenanceLog::query()->inRandomOrder()->limit(6)->get() as $log) {
            Post::factory()->maintenance($log)->create([
                'user_id' => $log->user_id,
                'content' => 'Onderhoud afgerond, alles loopt weer soepel.',
                'likes_count' => rand(0, 15),
                'comments_count' => rand(0, 5),
            ]);
        }

        foreach ($users as $user) {
            Post::factory()->text()->create([
                'user_id' => $user->id,
                'content' => 'Mooie dag om te rijden! Wie gaat er mee dit weekend?',
                'likes_count' => rand(0, 30),
                'comments_count' => rand(0, 12),
            ]);
        }
    }

    private function gpxUpload(): UploadedFile
    {
        return new UploadedFile(
            path: base_path(self::GPX_FIXTURE),
            originalName: 'sample-track.gpx',
            mimeType: 'application/gpx+xml',
            error: null,
            test: true,
        );
    }
}
