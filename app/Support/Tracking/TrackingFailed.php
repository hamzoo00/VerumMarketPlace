<?php

namespace App\Support\Tracking;

use RuntimeException;

/** The tracking API refused or could not be reached. Safe to retry. */
class TrackingFailed extends RuntimeException {}
