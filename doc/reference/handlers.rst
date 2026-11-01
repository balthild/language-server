Method Handlers
===============

Method handlers handle the RPC calls from the client.

They look like this:

.. code-block:: php

    <?php

    use Amp\Cancellation;
    use Phpactor\LanguageServer\Core\Handler\Handler;

    class MyHandler implements Handler
    {
        public function methods(): array
        {
            return [
                'method/name' => 'doSomething',
            ];
        }

        public function doSomething($args, ?Cancellation $cancellation = null): string
        {
            return 'hello!';
        }
    }

Once registered this command will respond to an RPC request to ``method/name``
with ``hello!``.

Argument Resolvers
------------------

The first arguments passed to the parameter will depend on the argument resolvers
used by the ``HandlerRunner``, the last argument is *always* a cancellation
token more on this later.

.. code-block:: php

    <?php

    $runner = new HandlerMethodRunner(
        new Handlers(new MyHandler()),
        new ChainArgumentResolver(
            new LanguageSeverProtocolParamsResolver(),
            new PassThroughArgumentResolver()
        ),
    );

Here we use the ``ChainArgumentResolver`` to try two different stragies.

LanguageServerProtocolParamsResolver
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This strategy will see if your method implements an LSP ``*Params`` class and
automatically instantaite it for you:

.. code-block:: php

    <?php

    class MyHandler implements Handler
    {
        public function methods(): array
        {
            return [
                'textDocument/completion' => 'complete',
            ];
        }

        public function doSomething(CompletionParams $completionParams, ?Cancellation $cancellation = null): CompletionList
        {
            $uriToTextDocument = $completionParams->textDocument->uri;
            // ...
        }
    }

You should be able to do this with *any method documented in the language
server specification*.

DTLArgumentResolver
~~~~~~~~~~~~~~~~~~~

This argument resolver will try and match the parameters from the request to
the parameters of your method.

PassThroughArgumentResolver
~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is a fallback resolver which will simply pass the raw array of arguments.

Blocking
--------

Your method is called inside a fiber, so it may block: the server will suspend
this request and continue serving others. Anything that waits on I/O (including
``Amp\delay()``) suspends only the current request.

.. code-block:: php

    <?php

    class MyHandler implements Handler
    {
        //...

        public function doSomething(CompletionParams $params, ?Cancellation $cancellation = null): array
        {
            // ...
            $completionItems = [];

            foreach($this->magicCompletionProvider->provideCompletions($params) as $completion) {
                $completionItems[] = $completion;
                \Amp\delay(0.001);
            }

            return $completionItems;
        }
    }

The above will process a single completion item but then suspend for 1
millisecond before continuing. This allows the server to do other things (like
for example **cancel this request**).

If you want to run several things concurrently you can start them with
``Amp\async()`` and wait for the results with ``Amp\Future\await()``:

.. code-block:: php

    <?php

    use Amp\Future;

    $futures = [];

    foreach ($providers as $provider) {
        $futures[] = \Amp\async(fn () => $provider->provide($params));
    }

    [$errors, $results] = Future\await($futures);

Cancellation
------------

The ``Cancellation`` passed to the method handler can throw an exception
if the request is cancelled as follows:

.. code-block:: php

    <?php

    use Amp\Cancellation;
    use Amp\CancelledException;

    class MyHandler implements Handler
    {
        //...

        public function doSomething(CompletionParams $params, ?Cancellation $cancellation = null): array
        {
            // ...
            $completionItems = [];

            foreach($this->magicCompletionProvider->provideCompletions($params) as $completion) {
                $completionItems[] = $completion;
                \Amp\delay(0.001);

                try {
                    $cancellation?->throwIfRequested();
                } catch (CancelledException $cancelled) {
                    break;
                }
            }

            return $completionItems;
        }
    }

In the above example, when the server cancels this request, the exception will
be thrown and we will return early.

The ``Cancellation`` is a live token rather than an immutable value object: it
is cancelled when its ``Amp\DeferredCancellation`` is destroyed. Keep the
deferred cancellation alive for as long as the token may be used; a token
obtained from a discarded deferred cancellation is already requested:

.. code-block:: php

    <?php

    use Amp\DeferredCancellation;

    $cancellation = (new DeferredCancellation())->getCancellation();
    $cancellation->isRequested(); // true

For request handlers the server owns the deferred cancellation and holds it
while the handler runs. The trap is code that creates its own
``DeferredCancellation`` — for example to compose or wrap tokens — and lets it
go out of scope.
