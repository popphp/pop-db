<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter\Profiler;

use Pop\Utils\DebuggerInterface;

/**
 * MySQL database adapter profiler class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 *
 * @property-read ?Step $current Current step
 */
class Profiler extends AbstractProfiler
{

    /**
     * Profiler current step index
     * @var int
     */
    protected int $currentIndex = 0;

    /**
     * Profiler steps
     * @var array
     */
    protected array $steps = [];

    /**
     * Debugger
     * @var ?DebuggerInterface
     */
    protected ?DebuggerInterface $debugger = null;

    /**
     * Constructor
     *
     * Instantiate the profiler object
     * @param ?DebuggerInterface $debugger
     */
    public function __construct(?DebuggerInterface $debugger = null)
    {
        parent::__construct();
        if ($debugger !== null) {
            $this->setDebugger($debugger);
        }
    }
    /**

     * Set debugger
     *
     * @param  DebuggerInterface $debugger
     * @return Profiler
     */
    public function setDebugger(DebuggerInterface $debugger): Profiler
    {
        $this->debugger = $debugger;
        return $this;
    }

    /**
     * Get debugger
     *
     * @return ?DebuggerInterface
     */
    public function getDebugger(): ?DebuggerInterface
    {
        return $this->debugger;
    }

    /**
     * Get debugger (alias)
     *
     * @return ?DebuggerInterface
     */
    public function debugger(): ?DebuggerInterface
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
        $this->currentIndex = count($this->steps) - 1;

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
        return $this->steps[$this->currentIndex] ?? null;
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
            case 'finish':
                return $this->finish;
            case 'current':
                return $this->getCurrentStep();
            case 'elapsed':
                return $this->getElapsed();
            default:
                return null;
        }
    }

}
