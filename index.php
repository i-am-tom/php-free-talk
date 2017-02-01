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
 * Here's a program to get your name. Nothing fancy. There's a strtoupper in
 * there just to prove that the free monads can be mapped as functors.
 *
 * @var Free More specifically, Free ConsoleF String
 */
$name = write('Hello! What\'s your name?')->chain(function ($_) {
    return read()->map('strtoupper');
});

/**
 * Here's a program to get your age. Note that intval means this program has a
 * different type to the one above! We can compose programs of any type, and
 * reuse them across applications.
 *
 * @var Free More specifically, Free ConsoleF Int
 */
$age = write('Hello! What\'s your age?')->chain(function ($_) {
    return read()->map('intval');
});

/**
 * This program composes the above two programs to form a new program! This is
 * the true power of free monadic structures: beyond testability, we have these
 * composable blocks that add no complexity to the interpretation stage at all:
 * the interpreter for a given set of instructions never changes!
 *
 * @var Free More specifically, Free ConsoleF Unit
 */
$program = $name->chain(function ($name) use ($age) {
    return $age->chain(function ($age) use ($name) {
        $result = sprintf('Wow! %s is half way to %d!', $name, $age * 2);
        return write($result); // Wow! Tom is half way to 30!
    });
});

/**
 * By moving all our Free interpretation machinery to the Free classes, our
 * final interpreter is very straightforward: all it does is explain how the
 * instructions are executed, and how they feed into the next one. That's it!
 *
 * @var callable
 */
$production = function (ConsoleF $functor) use (&$production) {
    switch (get_class($functor)) {
        case WriteLine::class:
            echo $functor->line, PHP_EOL;

            return $functor->next
                ->interpret($production);

        case ReadLine::class:
            $input = trim(fgets(STDIN));

            return ($functor->process)($input)
                ->interpret($production);
    }
};

/**
 * Or, as an alternative, we could define a totally different interpreter to do,
 * say, testing. It requires no change to the actual code, and the actual code's
 * behaviour can be 100% unit-tested because we're not changing any of it with
 * mocks. We have completely decoupled execution from program description!
 *
 * @var callable
 */
$development = function (ConsoleF $functor) use (&$development) {
    switch (get_class($functor)) {
        case WriteLine::class:
            echo 'STDOUT: ', $functor->line, PHP_EOL;
            return $functor->next->interpret($development);

        case ReadLine::class:
            echo 'READING FROM STDIN. USING "Ronald" AS INPUT.', PHP_EOL;
            return ($functor->process)('Ronald')->interpret($development);
    }
};

// Finally, do the actual work!

$program
    // ->interpret($development)
    ->interpret($production);
