<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

/**
 * Predicate set class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class PredicateSet
{

    /**
     * SQL object
     * @var AbstractSql
     */
    protected $sql = null;

    /**
     * Predicates
     * @var array
     */
    protected $predicates = [];

    /**
     * Nested predicate sets
     * @var array
     */
    protected $predicateSets = [];

    /**
     * Conjunction
     * @var string
     */
    protected $conjunction = null;

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  AbstractSql $sql
     * @param  mixed       $predicates
     * @param  string      $conjunction
     */
    public function __construct(AbstractSql $sql, $predicates = null, $conjunction = null)
    {
        $this->sql = $sql;

        if (null !== $predicates) {
            if (is_array($predicates)) {
                $this->addPredicates($predicates);
            } else {
                $this->addPredicate($predicates);
            }
        }

        if (null !== $conjunction) {
            $this->setConjunction($conjunction);
        }
    }

    /**
     * Add a predicate from a string expression
     *
     * @param  string $expression
     * @return PredicateSet
     */
    public function add($expression)
    {
        ;

        return $this;
    }

    /**
     * Add AND predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    //public function and(Predicate\AbstractPredicate $predicate)
    //{
    //    $predicate->setConjunction('AND');
    //    return $this->addPredicate($predicate);
    //}

    /**
     * Add OR predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    //public function or(Predicate\AbstractPredicate $predicate)
    //{
    //    $predicate->setConjunction('OR');
    //    return $this->addPredicate($predicate);
    //}

    /**
     * Add predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    public function addPredicate(Predicate\AbstractPredicate $predicate)
    {
        $this->predicates[] = $predicate;
        return $this;
    }

    /**
     * Add predicates
     *
     * @param  array $predicates
     * @return PredicateSet
     */
    public function addPredicates(array $predicates)
    {
        foreach ($predicates as $predicate) {
            $this->addPredicate($predicate);
        }

        return $this;
    }

    /**
     * Add predicate set
     *
     * @param  PredicateSet $predicateSet
     * @return PredicateSet
     */
    public function addPredicateSet(PredicateSet $predicateSet)
    {
        $this->predicateSets[] = $predicateSet;
        return $this;
    }

    /**
     * Add predicate sets
     *
     * @param  array $predicateSets
     * @return PredicateSet
     */
    public function addPredicateSets(array $predicateSets)
    {
        foreach ($predicateSets as $predicateSet) {
            $this->addPredicateSet($predicateSet);
        }

        return $this;
    }

    /**
     * Get the conjunction
     *
     * @param  string $conjunction
     * @return PredicateSet
     */
    public function setConjunction($conjunction)
    {
        if ((strtoupper($conjunction) != 'OR') && (strtoupper($conjunction) != 'AND')) {
            throw new Exception("Error: The conjunction must be 'AND' or 'OR'. '" . $conjunction . "' is not allowed.");
        }

        $this->conjunction = $conjunction;

        return $this;
    }

    /**
     * Get the conjunction
     *
     * @return string
     */
    public function getConjunction()
    {
        return $this->conjunction;
    }

    /**
     * Predicate set render method
     *
     * @return string
     */
    public function render()
    {
        $predicateString = null;

        foreach ($this->predicates as $i => $predicate) {
            $predicateString .= ($i == 0) ?
                $predicate->render($this->sql) : ' ' . $predicate->getConjunction() . ' ' . $predicate->render($this->sql);
        }

        foreach ($this->predicateSets as $i => $predicateSet) {
            if (empty($predicateSet->getConjunction())) {
                throw new Exception('Error: The combination conjunction was not set for this predicate set.');
            }
            $predicateString .= ' ' . $predicateSet->getConjunction() . ' ' . $predicateSet->render();
        }

        return '(' . $predicateString . ')';
    }

    /**
     * Return predicate set string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}