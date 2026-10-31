Service Providers
=================

Service providers are background services which can should be started on
on the ``initialized`` notification from the client.

A good example of a service is a code indexing service which watches the file
system and indexes code when files change.

Example
-------

A full example of a service provider:

.. literalinclude:: ../../lib/Example/Service/PingProvider.php
   :language: php
   :linenos:

This is similar to :doc:`method handlers <handlers>` with the exception that:

- The ``services`` method provides only an array of method names. The name
  doubles as both the method and service name.
- The method is called when the Language Server is initialized (or when it is
  started via. the service manager).
- Services are passed only an optional ``Amp\Cancellation``. The method runs in
  its own fiber and should block for as long as the service is meant to run;
  the server stops it by cancelling the token.

Cancellation source lifetime
----------------------------

If you create a token with ``Amp\DeferredCancellation``, keep the source object
alive while the token is in use. Destroying the source requests cancellation, so
a token retrieved from a temporary source is already cancelled:

.. code-block:: php

    <?php

    use Amp\DeferredCancellation;

    $cancellation = (new DeferredCancellation())->getCancellation();
    $cancellation->isRequested(); // true

Usage
-----

.. code-block:: php

    <?php

    $serviceProviders = new ServiceProviders(
        new PingProvider($clientApi)
    );

    $serviceManager = new ServiceManager($serviceProviders, $logger);
    $eventDispatcher = new EventDispatcher(
        new ServiceListener($serviceManager)
    );

    $handlers = new Handlers(
        // ...
        new ServiceHandler($serviceManager, $clientApi),
        // ...
    );

    return new MiddlewareDispatcher(
        // ...
        new InitializeMiddleware($handlers, $eventDispatcher)
        // ...
    );

In the above code the ``ServiceManager`` is responsible for starting and
stopping services, the ``ServiceHandler`` handles RPC methods to start/stop
services, and we use the ``ServiceListener`` to start the services when the
server is initialized (based on the ``Initialized`` event issued by the
``InitializeMiddleware``.
