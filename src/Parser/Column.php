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
namespace Pop\Db\Parser;

/**
 * Db column parser class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.2
 */
class Column
{

    /**
     * Method to parse the columns to create $where and $param arrays
     *
     * @param  array  $columns
     * @param  string $placeholder
     * @return array
     */
    public static function parse($columns, $placeholder)
    {
        $params = [];
        $where  = [];

        $i = 1;
        foreach ($columns as $column => $value) {
            if (!is_array($value) && (substr($value, -3) == ' OR')) {
                $value   = substr($value, 0, -3);
                $combine = ' OR';
            } else {
                $combine = null;
            }

            $operator = Operator::parse($column);
            if ($placeholder == ':') {
                $pHolder = $placeholder . $operator['column'];
            } else if ($placeholder == '$') {
                $pHolder = $placeholder . $i;
            } else {
                $pHolder = $placeholder;
            }

            // IS NULL or IS NOT NULL
            if (null === $value) {
                if (substr($column, -1) == '-') {
                    $column  = substr($column, 0, -1);
                    $where[] = $column . ' IS NOT NULL' . $combine;
                } else {
                    $where[] = $column . ' IS NULL' . $combine;
                }
            // IN or NOT IN
            } else if (is_array($value)) {
                if (substr($column, -1) == '-') {
                    $column  = substr($column, 0, -1);
                    $where[] = $column . ' NOT IN (' . implode(', ', $value) . ')' . $combine;
                } else {
                    $where[] = $column . ' IN (' . implode(', ', $value) . ')' . $combine;
                }
            // BETWEEN or NOT BETWEEN
            } else if ((substr($value, 0, 1) == '(') && (substr($value, -1) == ')') &&
                (strpos($value, ',') !== false)) {
                if (substr($column, -1) == '-') {
                    $column  = substr($column, 0, -1);
                    $where[] = $column . ' NOT BETWEEN ' . $value . $combine;
                } else {
                    $where[] = $column . ' BETWEEN ' . $value . $combine;
                }
            // LIKE or NOT LIKE
            } else if ((substr($value, 0, 2) == '-%') || (substr($value, -2) == '%-') ||
                (substr($value, 0, 1) == '%') || (substr($value, -1) == '%')) {
                $op = ((substr($value, 0, 2) == '-%') || (substr($value, -2) == '%-')) ? 'NOT LIKE' : 'LIKE';

                $where[]  = $column . ' ' . $op . ' ' .  $pHolder . $combine;
                if (substr($value, 0, 2) == '-%') {
                    $value = substr($value, 1);
                }
                if (substr($value, -2) == '%-') {
                    $value = substr($value, 0, -1);
                }
                if (isset($params[$column])) {
                    if (is_array($params[$column])) {
                        if ($placeholder == ':') {
                            $where[count($where) - 1] .= $i;
                        }
                        $params[$column][] = $value;
                    } else {
                        if ($placeholder == ':') {
                            $where[0] .= ($i - 1);
                            $where[1] .= $i;
                        }
                        $params[$column] = [$params[$column], $value];
                    }
                } else {
                    $params[$column] = $value;
                }
            // Standard operators
            } else {
                $column  = $operator['column'];
                $where[] = $column . ' ' . $operator['op'] . ' ' .  $pHolder . $combine;
                if (isset($params[$column])) {
                    if (is_array($params[$column])) {
                        if ($placeholder == ':') {
                            $where[count($where) - 1] .= $i;
                        }
                        $params[$column][] = $value;
                    } else {
                        if ($placeholder == ':') {
                            $where[0] .= ($i - 1);
                            $where[1] .= $i;
                        }
                        $params[$column] = [$params[$column], $value];
                    }
                } else {
                    $params[$column] = $value;
                }
            }

            $i++;
        }

        return ['where' => $where, 'params' => $params];
    }

}