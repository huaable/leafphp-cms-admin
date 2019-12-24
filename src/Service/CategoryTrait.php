<?php

namespace Service;


use Entity\Lang;

/**
 * Trait CategoryTrait
 * @package Service
 */
trait CategoryTrait
{

    /**
     * 显示父级分类名
     * @return string
     */
    public function showParent()
    {
        if (!empty($this->parent_id)) {
            $parent = self::findByPk($this->parent_id);
            return $parent ? $parent['name'] : '';
        }
        return '';
    }


    /**
     * 所有子集合集
     * @param     $id
     * @param int $level
     * @return array
     */
    public static function getAllChild($id = 0, $level = 0)
    {
        $map = [];
        $child = self::orderBy('weight desc')->findAll(['lang' => Lang::getLang(), 'parent_id' => $id]);
        $level += 1;
        foreach ($child as $item) {
            $item->level = $level;
            $map[] = $item;
            $map = array_merge($map, self::getAllChild($item->id, $level));
        }
        return $map;
    }

    /**
     * 多维数组
     * @param     $lang
     * @param int $id
     * @param int $level
     * @return array
     */

    public static function getTree($lang, $id = 0)
    {
        $map = [];
        $child = self::orderBy('weight desc')->findAll(['lang' => Lang::getLang(), 'parent_id' => $id]);
        foreach ($child as $item) {
            $map[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'child' => self::getTree($lang, $item->id)
            ];
        }
        return $map;
    }
}