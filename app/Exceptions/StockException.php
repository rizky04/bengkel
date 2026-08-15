<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat operasi stok tidak valid (mis. stok tidak cukup). */
class StockException extends RuntimeException
{
}
