<?php

namespace App\Modules\Assistant\Gateway;

use RuntimeException;

/**
 * The model provider could not give an answer in time or at all.
 */
class GatewayException extends RuntimeException {}
