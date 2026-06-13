<?php

use App\Services\ResourceFlowBuilder;
use Illuminate\Support\Collection;

it('builds a deterministic Railway-style flow from environment resources grouped by server', function () {
    $flow = ResourceFlowBuilder::build(
        projectName: 'Acme',
        environmentName: 'production',
        resources: collect([
            [
                'type' => 'application',
                'uuid' => 'app-uuid',
                'name' => 'Web',
                'description' => 'Customer frontend',
                'fqdn' => 'https://web.example.com',
                'status' => 'running:healthy',
                'hrefLink' => '/apps/app-uuid',
                'destination' => ['server' => ['name' => 'alpha']],
            ],
            [
                'type' => 'database',
                'uuid' => 'db-uuid',
                'name' => 'Postgres',
                'description' => null,
                'fqdn' => null,
                'status' => 'exited',
                'hrefLink' => '/databases/db-uuid',
                'destination' => ['server' => ['name' => 'alpha']],
            ],
            [
                'type' => 'service',
                'uuid' => 'svc-uuid',
                'name' => 'Redis Stack',
                'description' => 'Cache service',
                'fqdn' => null,
                'status' => 'degraded',
                'hrefLink' => '/services/svc-uuid',
                'destination' => ['server' => ['name' => 'beta']],
            ],
        ]),
    );

    expect($flow['nodes'])->toHaveCount(6)
        ->and($flow['edges'])->toHaveCount(5)
        ->and($flow['summary'])->toMatchArray([
            'total' => 3,
            'applications' => 1,
            'databases' => 1,
            'services' => 1,
            'servers' => 2,
        ]);

    expect(collect($flow['nodes'])->pluck('id')->all())->toBe([
        'environment-production',
        'server-alpha',
        'resource-application-app-uuid',
        'resource-database-db-uuid',
        'server-beta',
        'resource-service-svc-uuid',
    ]);

    expect($flow['nodes'][2])->toMatchArray([
        'id' => 'resource-application-app-uuid',
        'type' => 'resource',
    ]);

    expect($flow['nodes'][2]['data'])->toMatchArray([
        'kind' => 'application',
        'label' => 'Web',
        'status' => 'running:healthy',
        'href' => '/apps/app-uuid',
        'server' => 'alpha',
    ]);

    expect(collect($flow['edges'])->pluck('id')->all())->toBe([
        'environment-production-to-server-alpha',
        'server-alpha-to-resource-application-app-uuid',
        'server-alpha-to-resource-database-db-uuid',
        'environment-production-to-server-beta',
        'server-beta-to-resource-service-svc-uuid',
    ]);
});

it('returns an empty canvas with only the environment node when there are no resources', function () {
    $flow = ResourceFlowBuilder::build(
        projectName: 'Acme',
        environmentName: 'staging',
        resources: new Collection,
    );

    expect($flow['nodes'])->toHaveCount(1)
        ->and($flow['edges'])->toBeEmpty()
        ->and($flow['summary'])->toMatchArray([
            'total' => 0,
            'applications' => 0,
            'databases' => 0,
            'services' => 0,
            'servers' => 0,
        ]);

    expect($flow['nodes'][0])->toMatchArray([
        'id' => 'environment-staging',
        'type' => 'environment',
        'data' => [
            'label' => 'staging',
            'project' => 'Acme',
        ],
    ]);
});
