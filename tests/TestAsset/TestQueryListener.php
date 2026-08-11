<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Adapter\Profiler\Profiler;

class TestQueryListener
{

    protected ?Profiler $profiler = null;

    protected ?string $name = null;

    public function __construct(?Profiler $profiler = null, ?string $name = null)
    {
        $this->profiler = $profiler;
        $this->name     = $name;
    }

    public function getProfiler(): ?Profiler
    {
        return $this->profiler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

}
