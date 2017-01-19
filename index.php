<?php

include './src/Console.php';

// A simple sample program. Uses all the classes in src/,
// and terminates the program. Notice that we could make
// while() loops based on ReadLine by creating potential
// cycles in the WriteLine's $then.

$program = new WriteLine(
    'Hello! What\'s your name?',
    new ReadLine(function ($name) {
        return new WriteLine(
            "Hello, $name!",
            new End
        );
    })
);

// An interpreter for the program. Note that this is 100%
// separated from the $program above; the whole program
// is generated without any knowledge of how to read or
// write from the console. This means that, to test the
// app, we can just supply a different interpreter, and
// we're away!

function interpret($program)
{
    switch (get_class($program)) {
        case WriteLine::class:
            echo $program->line, PHP_EOL;
            return interpret($program->then);

        case ReadLine::class:
            $process = $program->process;

            return interpret($process(
                trim(fgets(STDIN))
            ));

        case End::class:
        default:
            return;
    }
}

// Finally, we can run the program by putting these two
// sides together!

interpret($program);
