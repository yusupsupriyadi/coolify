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
        flowResource(['uuid' => 'app-1', 'name' => 'web', 'subtype' => 'git', 'server' => 'alpha']),
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
