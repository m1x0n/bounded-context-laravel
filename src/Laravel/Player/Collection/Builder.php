<?php namespace BoundedContext\Laravel\Player\Collection;

use BoundedContext\Collection\Collection;
use BoundedContext\Player\Collection\Player;
use BoundedContext\Player\Repository;
use BoundedContext\Player\Snapshot\ClassName;

class Builder
{
    protected $repository;
    protected $players;
    protected $config;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->players = new Collection();
        $this->config = config('players');
    }

    public function all()
    {
        $this->players = new Collection();

        $this->prepare_type($this->config->application);
        $this->prepare_type($this->config->domain);

        return $this;
    }

    private function prepare_type($players)
    {
        foreach($players as $player_type) {
            foreach($player_type as $player_namespace) {
                $this->players->append( new ClassName($player_namespace) );
            }
        }
    }

    public function application()
    {
        $this->players = new Collection();
        $this->prepare_type($this->config['application']);
        return $this;
    }

    public function domain()
    {
        $this->players = new Collection();
        $this->prepare_type($this->config['domain']);
        return $this;
    }

    public function projectors()
    {
        $this->players = new Collection();
        foreach ($this->config as $layer_players) {
            foreach ($layer_players as $type=>$players) {
                if ($type == 'projectors') {
                    foreach ($players as $player_namespace) {
                        $this->players->append(new ClassName($player_namespace));
                    }
                }
            }
        }
        return $this;
    }

    public function get()
    {
        return new Player(
            $this->repository,
            $this->players
        );
    }
}
