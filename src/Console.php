<?php

// The root class for console IO. This now has a way to
// describe basic sequential computation.

abstract class ConsoleIO
{
    public function chain(callable $f)
    {
        return new Chain($this, $f);
    }

    public function map(callable $f)
    {
        return new Map($this, $f);
    }
}

// Write a line to the console.

class WriteLine extends ConsoleIO
{
    public function __construct($line)
    {
        $this->line = $line;
    }
}

// Read a line from the console.

class ReadLine extends ConsoleIO {}

// Lift a value into the computation.

class Pure extends ConsoleIO
{
    public function __construct($value)
    {
        $this->value = $value;
    }
}

// Create a sequence of computations by transforming the
// input computation to produce an output computation.

class Chain extends ConsoleIO
{
    public function __construct(ConsoleIO $that, callable $f)
    {
        $this->that = $that;
        $this->f = $f;
    }
}


// Transform the value of a program in some way. Add some
// block of processing logic to the computation.

class Map extends ConsoleIO
{
    public function __construct(ConsoleIO $that, callable $f)
    {
        $this->that = $that;
        $this->f = $f;
    }
}
