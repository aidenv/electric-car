<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

trait PaginationHandler
{
    public function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }
}
