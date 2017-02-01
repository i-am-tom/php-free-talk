<?php
/**
 * The Free monad machinery. The point of this is that it is totally independent
 * of any instruction set. Because we know that the monad properties can model
 * any program that is sequential in nature, we can re-use this code for any
 * sequential program!
 *
 * NB: if you want parallelism, take a look at Free applicative architecture...
 * your prayers are answered!
 */

/**
 * In friendly Haskell, we could define 80% of this file with
 * "data Free f a = Pure a | Roll (f (Free f a))", leaving only the monad and
 * functor instances for us to derive. However, this isn't friendly Haskell, so
 * we have a fair amount of boilerplate to build...
 */
abstract class Free
{
    /**
     * Sequence two instructions. $this' instruction is executed, its result is
     * passed to $f, and ITS result is interpreted as another instruction. Thus,
     * we can model computations whose behaviour changes depending on its input.
     * In other words, any sequential program that we could imagine!
     *
     * @param callable $f Free f a ~> (a -> Free f b) -> Free f b
     * @return Free A new Free-built program.
     */
    abstract public function chain(callable $f) : Free;

    /**
     * Transform the program value. This is like a chain, but we're guaranteeing
     * that this transformation won't change future behaviour. All it can do is
     * in some way transform the value in a way describable by some "pure"
     * program. In fact, we actually derive this method straight from chain -
     * it is literally just a special case of monad chaining!
     *
     * @param callable $f Free f a ~> (a -> b) -> Free f b
     * @return Free A new Free-built program.
     */
    public function map(callable $f) : Free
    {
        return static::chain(
            function ($x) use ($f) {
                return Free::pure($f($x));
            }
        );
    }

    /**
     * Create a new "pure" program that does nothing and returns the provided
     * value. This can be automatically interpreted, and allows us to compose
     * our programs together into larger programs.
     *
     * @param mixed $x a -> Free f a
     * @return Free A new Free-built program.
     */
    public static function pure($x) : Free { return new Pure($x); }

    /**
     * Interpret a Free value given some functor interpreter. All the machinery
     * of the Free can be automated, but we will need a specific interpreter to
     * be supplied for the given DSL.
     *
     * @param callable $f Free f a -> g a
     * @return mixed g a
     */
    abstract public function interpret(callable $f);
}

/**
 * A value "lifted" into a Pure computation becomes a program that returns that
 * value and has no side-effects. When we want to "return" a value from our
 * computation, we use Pure. When we want to transform a value (without defining
 * another step in our program), we use Pure. Indeed, Pure is the _only_ way to
 * import a value into our "programming language" for use in computation. It is
 * pretty neat, and surprisingly simple.
 */
class Pure extends Free
{
    /**
     * Create a pure computation with a given value lifted in.
     * @param mixed $value a -> Free f a
     */
    public function __construct($value) { $this->value = $value; }

    /**
     * Create a new program that in some way uses the Pure's value. In other
     * words, define a computation with one step, whose value and behaviour is
     * determined by the callback given to chain.
     *
     * @param callable $f Free f a ~> (a -> Free f b) -> Free f b
     * @return Free A new Free-built program.
     */
    public function chain(callable $f) : Free { return $f($this->value); }

    /**
     * Interpreting will remove the value from the Pure context. It would be a
     * useful further exercise to look into comonads - specifically, cofree
     * comonads - and their relationship with the free monad.
     *
     * @param callable $interpreter Free f a -> g a
     * @return mixed g a
     */
    public function interpret(callable $interpreter) { return $this->value; }
}

/**
 * In the literature, Roll is usually called Free, exactly the same as the type.
 * However, PHP doesn't let us separate a type from its constructors (unless it
 * is a primitive, when aaaaall the rules change. Go figure). The Roll class
 * defines a step in the computation. We're effectively "rolling up" new
 * instructions within our Free structure to be interpreted later on. It's not
 * the strongest analogy, I know, but it's good enough as an intuition.
 */
class Roll extends Free
{
    /**
     * Define one of our functor constructors as "effectful" (i.e. impure), and
     * declare it as one of the steps in our language. The example given in the
     * index.php file is going to be the really helpful bit here.
     *
     * @param mixed $functor Functor f => f a
     */
    public function __construct($functor) { $this->functor = $functor; }

    /**
     * Roll up another computation! Again, weak analogy, but think of this as
     * adding another computation to the instruction set. This is how we define
     * sequential programs: do this, then this, then this. Replace the commas in
     * that phrase with ->chain and you're half way to Haskell!
     *
     * @param callable $f Free f a ~> (a -> Free f b) -> Free f b
     * @return Free A new Free-built program.
     */
    public function chain(callable $f) : Free
    {
        return new Roll($this->functor->map(
            function ($x) use ($f) {
                return $x->chain($f);
            }
        ));
    }

    /**
     * Interpret an instruction given the supplied DSL interpreter. This means
     * that the only code required by the user at interpretation level is the
     * translation from our DSL to our effectful computation. Usually, this is
     * a natural transformation from Free f a to IO a, but PHP isn't so friendly
     * in this regard.
     *
     * @param callable $interpreter Free f a -> g a
     * @return mixed g a
     */
    public function interpret(callable $interpreter)
    {
        return $interpreter($this->functor);
    }
}
