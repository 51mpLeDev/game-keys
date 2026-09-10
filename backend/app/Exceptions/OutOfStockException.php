<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfStockException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Product is out of stock.');
    }
}
