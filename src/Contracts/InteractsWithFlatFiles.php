<?php

namespace RyanChandler\FlatFile\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface InteractsWithFlatFiles
{
    /**
     * Define the structure of your Orbital model.
     */
    public function schema(Blueprint $table): void;

    /**
     * Declare which driver the Orbital should use.
     *
     * @return class-string<\RyanChandler\FlatFile\Contracts\Driver>
     */
    public function getFlatFileDriver(): string;

    /**
     * Get the name of the source folder (or file) where model content is stored.
     */
    public function getFlatFileSource(): string;
}
