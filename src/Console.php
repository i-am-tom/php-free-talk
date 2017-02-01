<?php
/**
 * The DSL (domain-specific language) for console interactions! How pretty is
 * this? We can define a type, ConsoleF, and instances for all the possible
 * instructions. They do nothing more complex than that. Then, we can combine
 * them with our Free machinery to create a composable programming language for
 * describing anything we can do with our language! Finally, a completely de-
 * coupled interpreter provides context to the instructions defined below.
 */

/**
 * In Friendly Haskell, again, this would be really pretty. Something as simple
 * as "data ConsoleF next = WriteLine String next | ReadLine (String -> next)",
 * and we could even automatically derive the functor instance. Still, this is
 * OOP, so nothing comes easily. C'est la vie.
 */
abstract class ConsoleF
{
    /**
     * The ONLY requirement for a Free monad is that the given type obeys the
     * functor laws (for now, read this as "has a map method") so that it can
     * link the instructions together.
     *
     * @param callable $f ConsoleF a ~> (a -> b) -> ConsoleF b
     * @return ConsoleF A transformed version of the ConsoleF supplied.
     */
    abstract public function map(callable $f) : ConsoleF;
}

/**
 * Here is our custom instruction for writing a line of data to the console. It
 * really just defines what the line will be, and where our next instruction
 * will live.
 */
class WriteLine extends ConsoleF
{
    /**
     * Save the line to print and the following instruction.
     * @param string $line The string to print to the console.
     * @param mixed $next Most likely a Free, but not necessarily.
     */
    public function __construct($line, $next)
    {
        $this->line = $line;
        $this->next = $next;
    }

    /**
     * Transform the $next value. This is an important thing to realise: the
     * map method does NOT alter the text to print, as that isn't the part of
     * this constructor that makes up the functor. In other words, mapping over
     * new WriteLine('hello') with strtoupper won't give you HELLO later on in
     * the terminal.
     *
     * @param callable $f ConsoleF a ~> (a -> b) -> ConsoleF b
     * @return ConsoleF The transformed ConsoleF.
     */
    public function map(callable $f) : ConsoleF
    {
        return new self(
            $this->line,
            $f($this->next)
        );
    }
}

/**
 * A custom instruction for denoting console reads. Again, notice that this does
 * NOT read from the console: it's just a placeholder to indicate that we want
 * to do a console read at some point. In order to read, all we need to supply
 * is a function that takes a line of input and returns some subsequent value
 * for the "next". In other words, when we're using Frees with our ConsoleF,
 * ReadLine requires an action-generating function to tell us what to do next.
 */
class ReadLine extends ConsoleF
{
    /**
     * Create a new ReadLine value with a given input processor.
     * @param callable $process String -> next
     */
    public function __construct(callable $process) { $this->process = $process; }

    /**
     * Extend the input-processing function. Specifically, transform the output
     * of the processing function. It's composition - nothing fancier. As with
     * the WriteLine examples, index.php is going to be the most helpful thing
     * here.
     *
     * @param callable $f ConsoleF a ~> (a -> b) -> ConsoleF b
     * @return ConsoleF The transformed ConsoleF.
     */
    public function map(callable $f) : ConsoleF
    {
        return new ReadLine(function ($x) use ($f) {
            return $f(($this->process)($x));
        });
    }
}
