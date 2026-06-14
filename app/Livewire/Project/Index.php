<?php

namespace App\Livewire\Project;

use App\Models\PrivateKey;
use App\Models\Project;
use App\Models\Server;
use Livewire\Component;

class Index extends Component
{
    public $projects;

    public $servers;

    public $private_keys;

    public function mount()
    {
        $this->private_keys = PrivateKey::ownedByCurrentTeamCached();
        $this->projects = Project::ownedByCurrentTeam()
            ->with('environments:id,uuid,project_id,name')
            ->get();
        $this->servers = Server::ownedByCurrentTeamCached();
    }

    public function render()
    {
        return view('livewire.project.index');
    }
}
