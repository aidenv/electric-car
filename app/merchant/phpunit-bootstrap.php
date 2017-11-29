<?php

ini_set('memory_limit', '200M');
require_once __DIR__.'/bootstrap.php.cache';

exec(__DIR__."/console fos:elastica:populate --env=test");