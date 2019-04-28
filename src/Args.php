<?php

namespace Datashaman\PHPCheck;

class Args
{
    public $bootstrap;
    public $coverageHtml = false;
    public $coverageText = false;
    public $filter;
    public $logJunit = false;
    public $logText = false;
    public $maxSuccess = Runner::MAX_SUCCESS;
    public $noDefects;
    public $path;
    public $output;
    public $subject;
}
