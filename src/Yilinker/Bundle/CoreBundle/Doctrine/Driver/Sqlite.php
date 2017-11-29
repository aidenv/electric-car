<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Driver;

use Doctrine\DBAL\Driver\PDOSqlite\Driver;

class Sqlite extends Driver
{
    static public function concatString()
    {
        return join(' || ' , func_get_args());
    }

    protected $_userDefinedFunctions = array(
        'sqrt' => array('callback' => array('Doctrine\DBAL\Platforms\SqlitePlatform', 'udfSqrt'), 'numArgs' => 1),
        'mod'  => array('callback' => array('Doctrine\DBAL\Platforms\SqlitePlatform', 'udfMod'), 'numArgs' => 2),
        'locate'  => array('callback' => array('Doctrine\DBAL\Platforms\SqlitePlatform', 'udfLocate'), 'numArgs' => -1),
        'concat'  => array('callback' => array('Yilinker\Bundle\CoreBundle\Doctrine\Driver\Sqlite', 'concatString'), 'numArgs' => -1),
    );

}