<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter\Profiler;

use Pop\Debug\Debugger;

/**
 * MySQL database adapter profiler class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
class Profiler extends AbstractProfiler
{

    /**
     * Profiler current index
     * @var int
     */
    protected int $current = 0;

    /**
     * Profiler steps
     * @var array
     */
    protected array $steps = [];

    /**
     * Debugger
     * @var ?Debugger
     */
    protected ?Debugger $debugger = null;

    /**
     * Constructor
     *
     * Instantiate the profiler object
     * @param ?Debugger $debugger
     */
    public function __construct(?Debugger $debugger = null)
    {
        parent::__construct();
        if ($debugger !== null) {
            $this->setDebugger($debugger);
        }
    }
    /**

     * Set debugger
     *
     * @param  Debugger $debugger
     * @return Profiler
     */
    public function setDebugger(Debugger $debugger): Profiler
    {
        $this->debugger = $debugger;
        return $this;
    }

    /**
     * Get debugger
     *
     * @return ?Debugger
     */
    public function getDebugger(): ?Debugger
    {
        return $this->debugger;
    }

    /**
     * Get debugger (alias)
     *
     * @return ?Debugger
     */
    public function debugger(): ?Debugger
    {
        return $this->debugger;
    }

    /**
     * Has debugger
     *
     * @return bool
     */
    public function hasDebugger(): bool
    {
        return ($this->debugger !== null);
    }

    /**
     * Add step
     *
     * @param  ?Step $step
     * @return Profiler
     */
    public function addStep(?Step $step = null): Profiler
    {
        if ($step === null) {
            $step = new Step();
        }
        $this->steps[] = $step;
        $this->current = count($this->steps) - 1;

        return $this;
    }

    /**
     * Get steps
     *
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get current step
     *
     * @return ?Step
     */
    public function getCurrentStep(): ?Step
    {
        return $this->steps[$this->current] ?? null;
    }

    /**
     * Magic method to support shorthand calls to certain values in the profiler
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'start':
                return $this->start;
                break;
            case 'finish':
                return $this->finish;
                break;
            case 'current':
                return $this->getCurrentStep();
                break;
            case 'elapsed':
                return $this->getElapsed();
                break;
            default:
                return null;
        }
    }

}
