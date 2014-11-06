<?php

$ds = DIRECTORY_SEPARATOR;
$vendorDir = __DIR__ . "/../../vendor";
$configDir = realpath("{$vendorDir}/../app/config");
$logFile = "{$vendorDir}/../app/logs/post_update.log";

//I'm going to need some stefk libs...
//require __DIR__ . "{$ds}..{$ds}vendor{$ds}autoload.php";
require_once __DIR__. "/../../app/bootstrap.php.cache";
include __DIR__ . '/libs.php';

use Claroline\BundleRecorder\Operation;
use Claroline\BundleRecorder\Handler\OperationHandler;
use Claroline\BundleRecorder\Detector\Detector;
use Claroline\BundleRecorder\Handler\BundleHandler;
use Claroline\BundleRecorder\Recorder;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpFoundation\Request;
use Claroline\CoreBundle\Library\Installation\Refresher;

require_once __DIR__. "/../../app/AppKernel.php";

$logLine = "Emptying the cache...\n";
file_put_contents($logFile, $logLine . "\n", FILE_APPEND);
Refresher::removeContentFrom($vendorDir . '/../app/cache');

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//I need to do that in order to access some librairies required for the installation...
$kernel->boot();

//we can also get the PDO connection from the sf2 container.
//database parameters from the parameters.yml file
$value = Yaml::parse($configDir . $ds . 'parameters.yml');
$host = $value['parameters']['database_host'];
$dbName = $value['parameters']['database_name'];
//dsn driver... hardcoded. Change this if you really need it.
$driver = 'mysql';
$dsn = "{$driver}:host={$host};dbname={$dbName}";
$username = $value['parameters']['database_user'];
$password = $value['parameters']['database_password'];;

//create connection
$conn = new \PDO($dsn, $username, $password, array());

//Let's use stefk stuff !
$operationFilePath = __DIR__ . "/../../app/config/operations.xml";
//remove the old operation file if it exists (maybe it would be better to do a backup).
unlink($operationFilePath);
$operationHandler = new OperationHandler($operationFilePath);
$detector = new Detector($vendorDir);
$bundleHandler = new BundleHandler($configDir . $ds . 'bundles.ini');

//parsing installed.json...
$bundles = array();
$ds = DIRECTORY_SEPARATOR;
$jsonFile = "{$vendorDir}/composer/installed.json";
$data = json_decode(file_get_contents($jsonFile));

//retrieve the current bundles
foreach ($data as $row) {
    if ($row->type === 'claroline-plugin' || $row->type === 'claroline-core') {
        $bundles[] = array(
            'type'         => $row->type,
            'name'         => $row->name,
            'new_version'  => $row->version,
            'is_installed' => false,
            'fqcn'         => $detector->detectBundle($row->name)
        );
    }
}

//retrieve the already installed bundles
$sql = "SELECT * from `claro_bundle`";
$res = $conn->query($sql);

$operations = [];

foreach ($res->fetchAll() as $installedBundle) {
    foreach ($bundles as &$bundle) {
        if ($bundle['name'] === $installedBundle['name']) {
            $bundle['is_installed'] = true;
            $bundle['old_version'] = $installedBundle['version'];
        }
    }
}

//generating the operations.xml file
foreach ($bundles as $bundle) {
    $operation = new Operation(
        $bundle['is_installed'] ? Operation::UPDATE: Operation::INSTALL,
        $bundle['fqcn'],
        $bundle['type'] === 'claroline-plugin' ? Operation::BUNDLE_PLUGIN: Operation::BUNDLE_CORE
    );

    if (isset($bundle['old_version'])) {
        $operation->setFromVersion($bundle['old_version']);
    }

    $operation->setToVersion($bundle['new_version']);
    $operationHandler->addOperation($operation);
}

$fqcns = [];

foreach ($bundles as $bundle) {
    $fqcns[] = $bundle['fqcn'];
}

//Build the bundle file
$recorder = new Recorder(
    new Detector($vendorDir),
    new BundleHandler($configDir . '/bundles.ini'),
    new OperationHandler($configDir . '/operations.xml'),
    $vendorDir
);
$recorder->buildBundleFile();

//reboot the kernel for the new bundle file
$kernel->shutdown();
$kernel->boot();

//install from the operation file
$container = $kernel->getContainer();
$installer = $container->get('claroline.installation.platform_installer');
$installer->setLogger(
    function ($message) use ($logFile) {
        file_put_contents($logFile, $message . "\n", FILE_APPEND);
    }
);

//assets & assetic dump
$installer->installFromOperationFile();
$refresher = $container->get('claroline.installation.refresher');
$output = new StreamOutput(fopen($logFile, 'a', false));
$refresher->setOutput($output);
$refresher->installAssets();
$refresher->dumpAssets($container->getParameter('kernel.environment'));
$refresher->compileGeneratedThemes();

$logLine = "Done\n";
file_put_contents($logFile, $logLine, FILE_APPEND);