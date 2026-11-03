<?php

namespace Orbit\Contracts;

/**
 * Stores all of a model's records in one file instead of one file per record.
 *
 * The driver's `parse()` returns the records in the file and `compile()` receives
 * them, as an array of attributes keyed by each record's primary key.
 */
interface SingleFile {}
