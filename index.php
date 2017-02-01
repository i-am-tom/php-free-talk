<?php

include 'src/Console.php';
include 'src/Free.php';

/**
 * Define an effectful read. In other words, lift our ConsoleF constructor into
 * our shiny new programming language.
 *
 * @return Free More specifically, Free ConsoleF String
 */
function read() { return new Roll(new ReadLine('Free::pure')); }

/**
 * Define an effectful write. This declares an instruction for console writes,
 * that, of course, can be interpreted in any way we like later on.
 *
 * @param String $x The string to write to the console.
 * @return Free More specifically, Free ConsoleF Unit
 */
function write($x) { return new Roll(new WriteLine($x, Free::pure(null))); }

/**
 * Here's a sample program. Obviously, not as pretty as it could be, but it's
 * VERY strictly lawful. For wide-scale PHP use, you'd probably want to make use
 * of __call() and friends to make it look a little less clunky.
 *
 * LOOK - THIS DOESN'T DO ANTYHING. HOW MAGICAL.
 *
 * @var Free More specifically, Free ConsoleF String
 */
$program = write('Hello! What\'s your name?')->chain(function ($_) {
    return read()->chain(function ($name) {
        return write("Hello, $name!")->chain(function ($_) use ($name) {
            return new Pure($name);
        });
    });
});

$program = write('Hello! What\'s your name?')->chain(function ($_) {
    return read()->chain(function ($name) {
        return write("Hello, $name!")->chain(function ($_) use ($name) {
            return new Pure($name);
        });
    });
});

/**
 * Fully-interpreted program. We can actually automate most of this machinery,
 * as we'll see in the next commit. The point is that this function defines ALL
 * "impure" behaviour in our entire application, and we can swap it out as and
 * how we like.
 *
 * @param Free $program The program to interpret - Free ConsoleF a
 * @return mixed The result of interpreting - a
 */
function interpret(Free $program) {
    switch (get_class($program)) {
        case Pure::class:
            return $program->value;

        case Roll::class:
            switch (get_class($program->functor)) {
                case WriteLine::class:
                    echo $program->functor->line, PHP_EOL;
                    return interpret($program->functor->next);

                case ReadLine::class:
                    return interpret(
                        ($program->functor->process)
                            (trim(fgets(STDIN))));
            }
    }
}

// As with before, we can return values from our programs and compose them
// together to make bigger programs!

printf(
    "---\nNAME SAVED AS '%s'!\n",
    interpret($program->map('strtoupper'))
);
