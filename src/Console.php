<?php

// The root class for console IO. This doesn't yet do anything
// as there's nothing particularly interesting about the type.

abstract class ConsoleIO {}

// Write a line to the console. All this stores is the line to
// write to the console, along with the next IO instruction to
// execute.

class WriteLine extends ConsoleIO
{
    public function __construct($line, $then)
    {
        $this->line = $line;
        $this->then = $then;
    }
}

// Read a line from the console. This constructor takes a
// function that, given the line of console input, will
// produce the next IO instruction to execute.

class ReadLine extends ConsoleIO
{
    public function __construct(callable $process)
    {
        $this->process = $process;
    }
}

// End a console program, and output a value. This means we
// can produce programs that result in values, which means
// we can reuse sub-programs!

class EndWith extends ConsoleIO
{
    public function __construct($value)
    {
        $this->value = $value;
    }
}
