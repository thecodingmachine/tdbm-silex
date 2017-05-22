<?php


namespace TheCodingMachine\TDBM\Silex\Services;

use Symfony\Component\Filesystem\Filesystem;
use TheCodingMachine\TDBM\Configuration;
use TheCodingMachine\TDBM\ConfigurationInterface;
use TheCodingMachine\TDBM\Utils\BeanDescriptorInterface;
use TheCodingMachine\TDBM\Utils\GeneratorListenerInterface;
use TheCodingMachine\TDBM\Utils\PathFinder\PathFinder;

/**
 * Dumps the list of DAOs in a file. Useful for registering DAOs on the fly in Pimple.
 */
class DaoDumper implements GeneratorListenerInterface
{

    /**
     * @param ConfigurationInterface $configuration
     * @param BeanDescriptorInterface[] $beanDescriptors
     */
    public function onGenerate(ConfigurationInterface $configuration, array $beanDescriptors): void
    {
        $daos = [];

        foreach ($beanDescriptors as $beanDescriptor) {
            $daoClassName = $beanDescriptor->getDaoClassName();

            $daos[lcfirst($daoClassName)] = $configuration->getDaoNamespace().'\\'.$daoClassName;
        }

        $this->dumpFile($configuration, $daos);
    }

    private function dumpFile(Configuration $configuration, array $daos) {
        $fileSystem = new Filesystem();
        $arrAsString = var_export($daos, true);
        $daoNamespace = $configuration->getDaoNamespace();
        $file = <<<EOF
<?php
namespace $daoNamespace\Generated;

class DaoRegistry {
    public static function getDaoList()
    {
        return $arrAsString;
    }
}

EOF;

        $path = $configuration->getPathFinder()->getPath($configuration->getDaoNamespace().'\\Xxx');
        $path = substr($path, 0,-7).'Generated/DaoRegistry.php';


        $fileSystem->dumpFile($path, $file);
    }
}