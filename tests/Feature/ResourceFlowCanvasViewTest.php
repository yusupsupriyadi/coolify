<?php

use App\Services\ResourceFlowBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

it('renders the resource flow canvas mount point and payload in the resources view', function () {
    View::share('errors', new ViewErrorBag);

    $project = new class
    {
        public string $name = 'Acme';

        public string $uuid = 'project-uuid';
    };

    $environment = new class
    {
        public string $name = 'production';

        public string $uuid = 'environment-uuid';

        public Collection $applications;

        public Collection $postgresqls;

        public Collection $redis;

        public Collection $mongodbs;

        public Collection $mysqls;

        public Collection $mariadbs;

        public Collection $keydbs;

        public Collection $dragonflies;

        public Collection $clickhouses;

        public Collection $services;

        public function __construct()
        {
            $this->applications = new Collection;
            $this->postgresqls = new Collection;
            $this->redis = new Collection;
            $this->mongodbs = new Collection;
            $this->mysqls = new Collection;
            $this->mariadbs = new Collection;
            $this->keydbs = new Collection;
            $this->dragonflies = new Collection;
            $this->clickhouses = new Collection;
            $this->services = new Collection;
        }

        public function isEmpty(): bool
        {
            return false;
        }
    };

    $resourceFlow = ResourceFlowBuilder::build(
        projectName: 'Acme',
        environmentName: 'production',
        resources: collect([
            [
                'type' => 'application',
                'uuid' => 'app-uuid',
                'name' => 'Web App',
                'description' => null,
                'fqdn' => null,
                'status' => 'running:healthy',
                'hrefLink' => '/apps/app-uuid',
                'destination' => ['server' => ['name' => 'alpha']],
                'tags' => [],
            ],
        ]),
    );

    $html = view('livewire.project.resource.index', [
        'project' => $project,
        'environment' => $environment,
        'allProjects' => new Collection([$project]),
        'allEnvironments' => new Collection([$environment]),
        'parameters' => [
            'project_uuid' => $project->uuid,
            'environment_uuid' => $environment->uuid,
        ],
        'applications' => new Collection,
        'postgresqls' => new Collection,
        'redis' => new Collection,
        'mongodbs' => new Collection,
        'mysqls' => new Collection,
        'mariadbs' => new Collection,
        'keydbs' => new Collection,
        'dragonflies' => new Collection,
        'clickhouses' => new Collection,
        'services' => new Collection,
        'applicationsJs' => [],
        'postgresqlsJs' => [],
        'redisJs' => [],
        'mongodbsJs' => [],
        'mysqlsJs' => [],
        'mariadbsJs' => [],
        'keydbsJs' => [],
        'dragonfliesJs' => [],
        'clickhousesJs' => [],
        'servicesJs' => [],
        'resourceFlow' => $resourceFlow,
    ])->render();

    expect($html)
        ->toContain('data-resource-flow-canvas')
        ->toContain('data-flow-source="resource-flow-data-environment-uuid"')
        ->toContain('data-environment="production"')
        ->toContain('resource-application-app-uuid')
        ->toContain('Web App')
        ->toContain("setView('canvas')")
        ->toContain("setView('list')");
});
