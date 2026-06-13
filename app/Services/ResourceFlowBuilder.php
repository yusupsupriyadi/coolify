<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResourceFlowBuilder
{
    /**
     * @param  Collection<int, array<string, mixed>>  $resources
     * @return array{
     *     nodes: array<int, array<string, mixed>>,
     *     edges: array<int, array<string, mixed>>,
     *     summary: array{total: int, applications: int, databases: int, services: int, servers: int}
     * }
     */
    public static function build(string $projectName, string $environmentName, Collection $resources): array
    {
        $resources = $resources
            ->sortBy([
                fn (array $left, array $right): int => self::serverName($left) <=> self::serverName($right),
                fn (array $left, array $right): int => ((string) $left['type']) <=> ((string) $right['type']),
                fn (array $left, array $right): int => ((string) $left['name']) <=> ((string) $right['name']),
            ])
            ->values();

        $environmentId = self::nodeId('environment', $environmentName);
        $nodes = [self::environmentNode($environmentId, $projectName, $environmentName)];
        $edges = [];

        $x = 360;
        $serverColumn = 0;

        $resourcesByServer = $resources->groupBy(fn (array $resource): string => self::serverName($resource));

        foreach ($resourcesByServer as $serverName => $serverResources) {
            $serverId = self::nodeId('server', $serverName);
            $serverY = $serverColumn * 260;
            $nodes[] = self::serverNode($serverId, $serverName, $serverResources->count(), $x, $serverY);
            $edges[] = self::edge($environmentId, $serverId);

            foreach ($serverResources->values() as $index => $resource) {
                $resourceId = self::nodeId('resource-'.$resource['type'], (string) $resource['uuid']);
                $nodes[] = self::resourceNode($resourceId, $resource, $x + 360, $serverY + ($index * 160));
                $edges[] = self::edge($serverId, $resourceId);
            }

            $serverColumn++;
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'summary' => [
                'total' => $resources->count(),
                'applications' => $resources->where('type', 'application')->count(),
                'databases' => $resources->where('type', 'database')->count(),
                'services' => $resources->where('type', 'service')->count(),
                'servers' => $resourcesByServer->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function environmentNode(string $id, string $projectName, string $environmentName): array
    {
        return [
            'id' => $id,
            'type' => 'environment',
            'position' => ['x' => 0, 'y' => 0],
            'data' => [
                'label' => $environmentName,
                'project' => $projectName,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serverNode(string $id, string $serverName, int $resourceCount, int $x, int $y): array
    {
        return [
            'id' => $id,
            'type' => 'server',
            'position' => ['x' => $x, 'y' => $y],
            'data' => [
                'label' => $serverName,
                'resourceCount' => $resourceCount,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private static function resourceNode(string $id, array $resource, int $x, int $y): array
    {
        return [
            'id' => $id,
            'type' => 'resource',
            'position' => ['x' => $x, 'y' => $y],
            'data' => [
                'kind' => (string) $resource['type'],
                'label' => (string) $resource['name'],
                'description' => $resource['description'] ?? null,
                'fqdn' => $resource['fqdn'] ?? null,
                'status' => (string) ($resource['status'] ?? ''),
                'href' => (string) ($resource['hrefLink'] ?? ''),
                'server' => self::serverName($resource),
                'tags' => $resource['tags'] ?? [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function edge(string $source, string $target): array
    {
        return [
            'id' => $source.'-to-'.$target,
            'source' => $source,
            'target' => $target,
            'animated' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private static function serverName(array $resource): string
    {
        $name = data_get($resource, 'destination.server.name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return 'Unknown';
    }

    private static function nodeId(string $type, string $value): string
    {
        return $type.'-'.Str::slug($value);
    }
}
