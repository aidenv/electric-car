<?php

namespace Yilinker\Bundle\FrontendBundle\Hydrators;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class GroupHydrator extends AbstractHydrator
{

    /**
     * Hydrates all rows from the current statement instance at once.
     *
     * @return array
     */
    protected function hydrateAllData()
    {
        $cache  = array();
        $result = array();
        foreach($this->_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->hydrateRowData($row, $cache, $result);
        }

        return $result;
    }

    /**
     * Acts as semi PDO::FETCH GROUP
     *
     * @param array $row
     * @param array $cache
     * @param array $result
     * @return bool
     */
        protected function hydrateRowData(array $row, array &$cache, array &$result)
    {
        if(count($row) == 0) {
            return false;
        }

        $keys = array_keys($row);

        // Assume first column is id field
        $id = $row[$keys[0]];

        $value = false;

        if(count($row) == 2) {
            // If only one more field assume that this is the value field
            $value = $row[$keys[1]];
        } else {
            // Remove ID field and add remaining fields as value array
            array_shift($row);
            $value = $row;
        }

        $result[$id] = $value;
    }
}