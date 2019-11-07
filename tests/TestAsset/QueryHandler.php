<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Adapter\Profiler\Profiler;

class QueryHandler
{
    /**
     * Query profiler
     * @var Profiler
     */
    protected $profiler = null;

    /**
     * @param  Profiler $profiler
     */
    public function __construct(Profiler $profiler = null)
    {
        if (null !== $profiler) {
            $this->setProfiler($profiler);
        }
    }

    /**
     * @param  Profiler $profiler
     * @return self
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * @return Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

}