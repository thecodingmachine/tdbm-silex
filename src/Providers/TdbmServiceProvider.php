<?php

namespace TheCodingMachine\TDBM\Silex\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use TheCodingMachine\TDBM\Configuration;
use TheCodingMachine\TDBM\Silex\Services\DaoDumper;
use TheCodingMachine\TDBM\TDBMException;
use TheCodingMachine\TDBM\TDBMService;
use TheCodingMachine\TDBM\Utils\DefaultNamingStrategy;

class TdbmServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $app A container instance
     */
    public function register(Container $app)
    {
        $app['tdbm.namingStrategy'] = function() {
            return new DefaultNamingStrategy();
        };

        $app['tdbm.daoDumper'] = function() {
            return new DaoDumper();
        };

        $app['tdbm.configuration'] = function ($app) {
            if (!isset($app['tdbm.daoNamespace'])) {
                throw new TDBMException('Missing "tdbm.daoNamespace" option when registering the TdbmServiceProvider.');
            }
            if (!isset($app['tdbm.beanNamespace'])) {
                throw new TDBMException('Missing "tdbm.beanNamespace" option when registering the TdbmServiceProvider.');
            }
            if (isset($app['monolog'])) {
                $logger = $app['monolog'];
            } else {
                $logger = null;
            }

            return new Configuration($app['tdbm.beanNamespace'], $app['tdbm.daoNamespace'], $app['db'], $app['tdbm.namingStrategy'], $app['cache'], null, $logger, [ $app['tdbm.daoDumper'] ]);
        };

        $app['tdbmService'] = function ($app) {
            return new TDBMService($app['tdbm.configuration']);
        };

    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        if (!isset($app['tdbm.daoNamespace'])) {
            throw new TDBMException('Missing "tdbm.daoNamespace" option when registering the TdbmServiceProvider.');
        }

        $daoRegistryClass = $app['tdbm.daoNamespace'].'\\Generated\\DaoRegistry';
        if (class_exists($daoRegistryClass)) {
            $daos = $daoRegistryClass::getDaoList();
            foreach ($daos as $instanceName => $className) {
                $app[$instanceName] = function($app) use ($className) {
                    return new $className($app['tdbmService']);
                };
            }
        }
    }
}
