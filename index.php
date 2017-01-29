<?php

include 'src/Console.php';

// A program using the new machinery. Note that we could
// still create loops using the ->chain method to repeat
// parts of the computation.

$program =
    (new WriteLine('Hello! What\'s your name?'))
        ->chain(function ($_) {
            return (new ReadLine)
                ->chain(function ($name) {
                    return (new WriteLine("Hello, $name!"))
                        ->chain(function ($_) use ($name) {
                            return new Pure($name);
                        });
                });
        });

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
            return;

        case ReadLine::class:
            return trim(fgets(STDIN));

        case Pure::class:
            return $program->value;

        case Chain::class:
            $f = $program->f;

            return interpret(
                $f(interpret(
                    $program->that
                ))
            );

        case Map::class:
            $f = $program->f;

            return $f(interpret(
                $program->that
            ));
    }
}

// Finally, we can run the program by putting these two
// sides together. We can even return a value from it!

printf(
    "---\nNAME SAVED AS '%s'!\n",
    interpret(
        $program->map(
            'strtoupper'
        )
    )
);
