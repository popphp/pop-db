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
namespace Pop\Db\Parser;

/**
 * Column parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
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
        $i      = 1;

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
                $where[] = ($operator['op'] == 'NOT') ?
                    $operator['column'] . ' IS NOT NULL' . $combine :
                    $operator['column'] . ' IS NULL' . $combine;
            // IN or NOT IN
            } else if (is_array($value)) {
                $where[] = ($operator['op'] == 'NOT') ?
                    $operator['column'] . ' NOT IN (' . implode(', ', $value) . ')' . $combine :
                    $operator['column'] . ' IN (' . implode(', ', $value) . ')' . $combine;
            // BETWEEN or NOT BETWEEN
            } else if (!is_array($value) && (substr($value, 0, 1) == '(') && (substr($value, -1) == ')') &&
                (strpos($value, ',') !== false)) {
                $where[] = ($operator['op'] == 'NOT') ?
                    $where[] = $operator['column'] . ' NOT BETWEEN ' . $value . $combine :
                    $where[] = $operator['column'] . ' BETWEEN ' . $value . $combine;
            // LIKE or NOT LIKE
            } else if (strpos($operator['op'], 'LIKE') !== false) {
                $realValue = $value;
                if ((substr($column, 0, 1) == '%') || (substr($column, 0, 2) == '-%')) {
                    $realValue  = '%' . $realValue;
                }
                if ((substr($column, -1) == '%') || (substr($column, -2) == '%-')) {
                    $realValue .= '%';
                }

                $where[] = $operator['column'] . ' ' . $operator['op'] . ' ' .  $pHolder . $combine;

                if (isset($params[$operator['column']])) {
                    if (is_array($params[$operator['column']])) {
                        if ($placeholder == ':') {
                            $where[count($where) - 1] .= $i;
                        }
                        $params[$operator['column']][] = $realValue;
                    } else {
                        if ($placeholder == ':') {
                            $where[0] .= ($i - 1);
                            $where[1] .= $i;
                        }
                        $params[$operator['column']] = [$params[$operator['column']], $realValue];
                    }
                } else {
                    $params[$operator['column']] = $realValue;
                }
            // Standard operators
            } else {
                if (!is_array($value)) {
                    $where[] = $operator['column'] . ' ' . $operator['op'] . ' ' .  $pHolder . $combine;
                    if (isset($params[$operator['column']])) {
                        if (is_array($params[$operator['column']])) {
                            if ($placeholder == ':') {
                                $where[count($where) - 1] .= $i;
                            }
                            $params[$operator['column']][] = $value;
                        } else {
                            if ($placeholder == ':') {
                                $where[0] .= ($i - 1);
                                $where[1] .= $i;
                            }
                            $params[$operator['column']] = [$params[$operator['column']], $value];
                        }
                    } else {
                        $params[$operator['column']] = $value;
                    }
                } else {
                    foreach ($value as $i => $val) {
                        $where[] = $operator['column'] . ' ' . $operator['op'] . ' ' .  $pHolder . $combine;
                        if (isset($params[$operator['column']])) {
                            if (is_array($params[$operator['column']])) {
                                if ($placeholder == ':') {
                                    $where[count($where) - 1] .= $i;
                                }
                                $params[$operator['column']][] = $val;
                            } else {
                                if ($placeholder == ':') {
                                    $where[0] .= ($i - 1);
                                    $where[1] .= $i;
                                }
                                $params[$operator['column']] = [$params[$operator['column']], $val];
                            }
                        } else {
                            $params[$operator['column'] . ($i + 1)] = $val;
                        }
                    }
                }
            }

            $i++;
        }

        return ['where' => $where, 'params' => $params];
    }

}