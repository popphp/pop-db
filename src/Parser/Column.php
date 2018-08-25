<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Parser;

/**
 * Column parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.3.0
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
            if (!is_array($column) && (substr($column, -3) == ' OR')) {
                $column  = substr($column, 0, -3);
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
            } else if (is_array($value) && ($operator['op'] == '=')) {
                if (substr($column, -1) == '-') {
                    $column  = substr($column, 0, -1);
                    $where[] = $column . ' NOT IN (' . implode(', ', $value) . ')' . $combine;
                } else {
                    $where[] = $column . ' IN (' . implode(', ', $value) . ')' . $combine;
                }
            // BETWEEN or NOT BETWEEN
            } else if (!is_array($value) && (substr($value, 0, 1) == '(') && (substr($value, -1) == ')') &&
                (strpos($value, ',') !== false)) {
                if (substr($column, -1) == '-') {
                    $column  = substr($column, 0, -1);
                    $where[] = $column . ' NOT BETWEEN ' . $value . $combine;
                } else {
                    $where[] = $column . ' BETWEEN ' . $value . $combine;
                }
            // LIKE or NOT LIKE
            } else if ((substr($column, 0, 2) == '-%') || (substr($column, -2) == '%-') ||
                (substr($column, 0, 1) == '%') || (substr($column, -1) == '%')) {
                $op = ((substr($column, 0, 2) == '-%') || (substr($column, -2) == '%-')) ? 'NOT LIKE' : 'LIKE';

                $realColumn = $column;
                $realValue  = $value;
                if (substr($realColumn, 0, 2) == '-%') {
                    $realColumn = substr($realColumn, 2);
                    $realValue  = '%' . $realValue;
                } else if (substr($realColumn, 0, 1) == '%') {
                    $realColumn = substr($realColumn, 1);
                    $realValue  = '%' . $realValue;
                }
                if (substr($realColumn, -2) == '%-') {
                    $realColumn = substr($realColumn, 0, -2);
                    $realValue .= '%';
                } else if (substr($realColumn, -1) == '%') {
                    $realColumn = substr($realColumn, 0, -1);
                    $realValue .= '%';
                }

                $where[]  = $realColumn . ' ' . $op . ' ' .  $pHolder . $combine;

                if (isset($params[$realColumn])) {
                    if (is_array($params[$realColumn])) {
                        if ($placeholder == ':') {
                            $where[count($where) - 1] .= $i;
                        }
                        $params[$realColumn][] = $realValue;
                    } else {
                        if ($placeholder == ':') {
                            $where[0] .= ($i - 1);
                            $where[1] .= $i;
                        }
                        $params[$realColumn] = [$params[$realColumn], $realValue];
                    }
                } else {
                    $params[$realColumn] = $realValue;
                }
            // Standard operators
            } else {
                $column = $operator['column'];

                if (!is_array($value)) {
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
                } else {
                    foreach ($value as $i => $val) {
                        $where[] = $column . ' ' . $operator['op'] . ' ' .  $pHolder . $combine;
                        if (isset($params[$column])) {
                            if (is_array($params[$column])) {
                                if ($placeholder == ':') {
                                    $where[count($where) - 1] .= $i;
                                }
                                $params[$column][] = $val;
                            } else {
                                if ($placeholder == ':') {
                                    $where[0] .= ($i - 1);
                                    $where[1] .= $i;
                                }
                                $params[$column] = [$params[$column], $val];
                            }
                        } else {
                            $params[$column . ($i + 1)] = $val;
                        }
                    }
                }
            }

            $i++;
        }

        return ['where' => $where, 'params' => $params];
    }

}