<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;

/**
 * Result class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Mapper extends AbstractRecord
{

    /**
     * 1:1 relationships
     * @var array
     */
    protected $oneToOne = [];

    /**
     * 1:Many relationships
     * @var array
     */
    protected $oneToMany = [];

    /**
     * Find by ID method
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function findById($id)
    {
        $values = (is_array($id)) ? $id : [$id];
        $params = [];
        $sql    = $this->result->sql();

        $sql->select()->from($this->table);

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $sql->select()->where->equalTo($primaryKey, $placeholder);
            $params[$primaryKey] = $values[$i];
        }

        foreach ($this->oneToOne as $oneToOne) {
            $sql->select()->join($oneToOne['table'], $oneToOne['columns'], $oneToOne['join']);
        }

        $sql->select()->limit(1);

        $sql->db()->prepare((string)$sql)
            ->bindParams($params)
            ->execute();

        $row = $sql->db()->fetch();

        if (count ($this->oneToMany) > 0) {
            foreach ($this->oneToMany as $entity => $oneToMany) {
                $sql->reset();
                $sql->select()->from($oneToMany['table']);

                $params  = [];
                $columns = (is_array($oneToMany['columns'])) ? $oneToMany['columns'] : [$oneToMany['columns']];

                foreach ($columns as $i => $key) {
                    $placeholder = $sql->getPlaceholder();

                    if ($placeholder == ':') {
                        $placeholder .= $key;
                    } else if ($placeholder == '$') {
                        $placeholder .= ($i + 1);
                    }
                    $sql->select()->where->equalTo($key, $placeholder);
                    $params[$key] = $values[$i];
                }

                $sql->db()->prepare((string)$sql)
                    ->bindParams($params)
                    ->execute();

                $row[$entity] = $sql->db()->fetchAll();
            }
        }

        return $row;
    }

}