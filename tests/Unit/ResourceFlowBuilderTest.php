<?php

use App\Services\ResourceFlowBuilder;

function flowResource(array $overrides = []): array
{
    return array_merge([
        'type' => 'application',
        'subtype' => 'git',
        'uuid' => 'app-1',
        'name' => 'web',
        'fqdn' => 'web.example.com',
        'description' => null,
        'status' => 'running:healthy',
        'hrefLink' => '/apps/app-1',
        'server' => 'alpha',
        'volumes' => [],
    ], $overrides);
}

it('builds resource-centric nodes grouped into Railway-style server containers', function () {
    $resources = collect([
        flowResource(['uuid' => 'app-1', 'name' => 'web', 'subtype' => 'git', 'server' => 'alpha', 'links' => ['settings' => '/apps/app-1', 'logs' => '/apps/app-1/logs']]),
        flowResource([
            'type' => 'database',
            'subtype' => 'standalone-postgresql',
            'uuid' => 'db-1',
            'name' => 'postgres',
            'fqdn' => null,
            'status' => 'exited',
            'hrefLink' => '/db/db-1',
            'server' => 'alpha',
            'volumes' => ['postgres-volume'],
        ]),
        flowResource([
            'type' => 'service',
            'subtype' => 'service',
            'uuid' => 'svc-1',
            'name' => 'plausible',
            'fqdn' => null,
            'status' => 'running:healthy',
            'hrefLink' => '/svc/svc-1',
            'server' => 'beta',
            'volumes' => [],
        ]),
    ]);

    $flow = ResourceFlowBuilder::build('Acme', 'production', $resources);

    expect($flow['summary'])->toMatchArray([
        'total' => 3,
        'applications' => 1,
        'databases' => 1,
        'services' => 1,
        'servers' => 2,
    ]);

    $groups = collect($flow['nodes'])->where('type', 'group')->values();
    expect($groups)->toHaveCount(2);

    $alpha = $groups->firstWhere('data.label', 'alpha');
    expect($alpha)->not->toBeNull();
    expect($alpha['data']['kind'])->toBe('server');
    expect($alpha['data']['resourceCount'])->toBe(2);
    expect($alpha['style']['width'])->toBeGreaterThan(0);
    expect($alpha['style']['height'])->toBeGreaterThan(0);

    $resourceNodes = collect($flow['nodes'])->where('type', 'resource')->values();
    expect($resourceNodes)->toHaveCount(3);

    $web = $resourceNodes->firstWhere('data.label', 'web');
    expect($web['parentId'])->toBe($alpha['id']);
    expect($web['extent'])->toBe('parent');
    expect($web['data']['kind'])->toBe('application');
    expect($web['data']['icon'])->toBe('/svgs/github.svg');
    expect($web['data']['statusLabel'])->toBe('Online');
    expect($web['data']['statusColor'])->toBe('#22c55e');
    expect($web['data']['href'])->toBe('/apps/app-1');
    expect($web['data']['links'])->toBe(['settings' => '/apps/app-1', 'logs' => '/apps/app-1/logs']);

    $postgres = $resourceNodes->firstWhere('data.label', 'postgres');
    expect($postgres['data']['kind'])->toBe('database');
    expect($postgres['data']['icon'])->toBe('/svgs/postgresql.svg');
    expect($postgres['data']['statusLabel'])->toBe('Offline');
    expect($postgres['data']['volumes'])->toBe(['postgres-volume']);

    // react-flow requires each parent group to appear before its children in the nodes array
    $ids = collect($flow['nodes'])->pluck('id')->values();
    expect($ids->search($alpha['id']))->toBeLessThan($ids->search($web['id']));
});

it('draws edges between resources that reference each other', function () {
    $resources = collect([
        flowResource(['type' => 'application', 'subtype' => 'git', 'uuid' => 'app-1', 'name' => 'web', 'server' => 'alpha']),
        flowResource(['type' => 'database', 'subtype' => 'standalone-postgresql', 'uuid' => 'db-1', 'name' => 'pg', 'server' => 'alpha']),
    ]);

    $connections = [
        ['from' => 'app-1', 'fromType' => 'application', 'to' => 'db-1', 'toType' => 'database'],
    ];

    $flow = ResourceFlowBuilder::build('Acme', 'production', $resources, $connections);

    expect($flow['edges'])->toHaveCount(1);

    $edge = $flow['edges'][0];
    expect($edge['source'])->toBe('resource-application-app-1');
    expect($edge['target'])->toBe('resource-database-db-1');

    // connections referencing a resource that is not on the canvas are skipped
    $flow = ResourceFlowBuilder::build('Acme', 'production', $resources, [
        ['from' => 'app-1', 'fromType' => 'application', 'to' => 'ghost', 'toType' => 'database'],
    ]);
    expect($flow['edges'])->toBe([]);
});

it('maps docker apps and every database engine to the right icon', function () {
    $cases = [
        ['application', 'docker', '/svgs/docker.svg'],
        ['database', 'standalone-redis', '/svgs/redis.svg'],
        ['database', 'standalone-keydb', '/svgs/redis.svg'],
        ['database', 'standalone-dragonfly', '/svgs/redis.svg'],
        ['database', 'standalone-mysql', '/svgs/mysql.svg'],
        ['database', 'standalone-mariadb', '/svgs/mariadb.svg'],
        ['database', 'standalone-mongodb', '/svgs/mongodb.svg'],
        ['database', 'standalone-clickhouse', '/svgs/clickhouse.svg'],
        ['service', 'service', '/svgs/docker.svg'],
    ];

    foreach ($cases as [$type, $subtype, $expectedIcon]) {
        $flow = ResourceFlowBuilder::build('Acme', 'production', collect([
            flowResource(['type' => $type, 'subtype' => $subtype, 'uuid' => 'r-1', 'name' => 'r']),
        ]));

        $node = collect($flow['nodes'])->firstWhere('type', 'resource');
        expect($node['data']['icon'])->toBe($expectedIcon);
    }
});

it('exposes status metadata and node ids matching build() for live updates', function () {
    expect(ResourceFlowBuilder::statusMeta('running:healthy'))->toBe(['Online', '#22c55e']);
    expect(ResourceFlowBuilder::statusMeta('running:unhealthy'))->toBe(['Degraded', '#fcd452']);
    expect(ResourceFlowBuilder::statusMeta('starting'))->toBe(['Deploying', '#fcd452']);
    expect(ResourceFlowBuilder::statusMeta('exited'))->toBe(['Offline', '#ef4444']);
    expect(ResourceFlowBuilder::statusMeta(''))->toBe(['Unknown', '#737373']);

    // The id helper must produce the same id build() assigns, so live status patches hit the right node.
    $flow = ResourceFlowBuilder::build('Acme', 'production', collect([
        flowResource(['type' => 'database', 'subtype' => 'standalone-redis', 'uuid' => 'Db-XYZ', 'name' => 'cache']),
    ]));
    $node = collect($flow['nodes'])->firstWhere('type', 'resource');
    expect(ResourceFlowBuilder::resourceNodeId('database', 'Db-XYZ'))->toBe($node['id']);
});

it('spreads dense single-server groups across multiple columns', function () {
    $resources = collect(range(1, 9))->map(fn (int $i): array => flowResource([
        'uuid' => "app-{$i}", 'name' => "app-{$i}", 'server' => 'alpha',
    ]));

    $flow = ResourceFlowBuilder::build('Acme', 'production', $resources);
    $resourceNodes = collect($flow['nodes'])->where('type', 'resource');

    // 9 resources => ceil(sqrt(9)) = 3 columns, so the top row holds 3 cards.
    $topRowY = $resourceNodes->min(fn (array $n) => $n['position']['y']);
    $topRow = $resourceNodes->filter(fn (array $n): bool => $n['position']['y'] === $topRowY);
    expect($topRow->count())->toBe(3);
});

it('applies saved node positions when provided', function () {
    $resources = collect([
        flowResource(['type' => 'application', 'subtype' => 'git', 'uuid' => 'app-1', 'name' => 'web', 'server' => 'alpha']),
    ]);

    $nodeId = ResourceFlowBuilder::resourceNodeId('application', 'app-1');

    $flow = ResourceFlowBuilder::build('Acme', 'production', $resources, [], [
        $nodeId => ['x' => 999, 'y' => 777],
    ]);

    $node = collect($flow['nodes'])->firstWhere('id', $nodeId);
    expect($node['position'])->toBe(['x' => 999.0, 'y' => 777.0]);
});

it('returns an empty canvas when there are no resources', function () {
    $flow = ResourceFlowBuilder::build('Acme', 'production', collect());

    expect($flow['nodes'])->toBe([]);
    expect($flow['edges'])->toBe([]);
    expect($flow['summary'])->toMatchArray([
        'total' => 0,
        'applications' => 0,
        'databases' => 0,
        'services' => 0,
        'servers' => 0,
    ]);
});
