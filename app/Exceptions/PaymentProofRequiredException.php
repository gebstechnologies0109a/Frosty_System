<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentProofRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cannot approve order. Proof of payment is missing.');
    }
}
