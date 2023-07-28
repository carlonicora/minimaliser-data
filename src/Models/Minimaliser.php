<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Factories\MinimalismFactories;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data;
use CarloNicora\Minimalism\MinimaliserData\Factories\FileFactory;
use CarloNicora\Minimalism\MinimaliserData\Factories\TestsFactory;
use CarloNicora\Minimalism\MinimaliserData\Objects\DatabaseObject;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Discovery\Data\EndpointData;
use CarloNicora\Minimalism\Services\Discovery\Discovery;
use CarloNicora\Minimalism\Services\Discovery\Factories\MicroserviceDataFactory;
use CarloNicora\Minimalism\Services\Discovery\Models\Discovery\RunRegister;
use Exception;

class Minimaliser extends AbstractModel
{
    /** @var Data  */
    private Data $minimaliser;

    /** @var SqlInterface  */
    private SqlInterface $data;

    /** @var string|null  */
    private ?string $databaseIdentifier=null;

    /** @var string  */
    private string $projectName;

    /**
     * @param MinimalismFactories $minimalismFactories
     * @param string|null $function
     */
    public function __construct(
        private readonly MinimalismFactories $minimalismFactories,
        ?string $function = null,
    )
    {
        parent::__construct(
            minimalismFactories: $minimalismFactories,
            function: $function,
        );

        $this->minimaliser = $minimalismFactories->getServiceFactory()->create(Data::class);
        $this->data = $minimalismFactories->getServiceFactory()->create(SqlInterface::class);
    }

    /**
     * @param string $prompt
     * @param bool $isYesNoAnswer
     * @param string|bool|null $defaultAnswer
     * @return string|bool
     */
    private function readInput(
        string $prompt,
        bool $isYesNoAnswer=false,
        string|bool|null $defaultAnswer=null,
    ): string|bool
    {
        if ($defaultAnswer !== null){
            if ($isYesNoAnswer && $defaultAnswer === true) {
                $prompt .= '(Y/n)';
            } elseif ($isYesNoAnswer && $defaultAnswer === false){
                $prompt .= '(y/N)';
            } else {
                $prompt .= '(' . $defaultAnswer . ')';
            }
        }
        $prompt .= ': ';
        $textResponse = readline($prompt);

        if ($isYesNoAnswer){
            if ($textResponse === ''){
                $response = $defaultAnswer;
            } else {
                switch (strtolower($textResponse)) {
                    case 'y':
                        $response = true;
                        break;
                    case 'n':
                        $response = false;
                        break;
                    default:
                        echo 'The answer can be either Yes (y/Y) or No (N/n). Please reply with a valid answer.' . PHP_EOL;
                        $response = $this->readInput($prompt, $isYesNoAnswer, $defaultAnswer);
                        break;
                }
            }
        } elseif ($defaultAnswer !== null && $textResponse === ''){
            $response = $defaultAnswer;
        } else {
            $response = $textResponse;
        }

        return $response;
    }

    /**
     * @return HttpCode
     * @throws Exception
     */
    public function cli(
    ): HttpCode
    {
        system('clear');
        $namespace = $this->minimaliser->getNamespace();
        if (str_ends_with($namespace, '\\')){
            $namespace = substr($namespace, 0, -1);
        }
        $namespace = explode('\\',$namespace);
        $this->projectName = $namespace[0] . $namespace[count($namespace)-1];

        if (array_key_exists('MINIMALISM_SERVICE_MYSQL', $_ENV) && $_ENV['MINIMALISM_SERVICE_MYSQL'] !== ''){
            $dbs = explode(',', $_ENV['MINIMALISM_SERVICE_MYSQL']);
            if (count($dbs) === 1){
                $this->databaseIdentifier = $dbs[0];
            } else {
                if (count($dbs) === 2){
                    if ($dbs[0] === 'OAuth'){
                        $this->databaseIdentifier = $dbs[1];
                    } elseif ($dbs[1] === 'OAuth'){
                        $this->databaseIdentifier = $dbs[0];
                    }
                }

                if ($this->databaseIdentifier === null) {
                    echo 'Available database:' . PHP_EOL;
                    foreach ($dbs as $dbKey => $dbName) {
                        echo '  ' . $dbKey . '. ' . $dbName . PHP_EOL;
                    }

                    while ($this->databaseIdentifier === null) {
                        $input = $this->readInput(prompt: 'Select database to import');
                        if (is_numeric($input) && $input <= count($dbs) - 1) {
                            $this->databaseIdentifier = $dbs[$input];
                        } else {
                            echo 'Invalid selection';
                        }
                    }
                }
            }

            $loadedServices = [];

            foreach ($this->minimalismFactories->getServiceFactory()->getServices() as $serviceKey => $service) {
                $loadedServices[$serviceKey] = $serviceKey;
            }

            $database = new DatabaseObject(
                data: $this->data,
                projectName: $this->projectName,
                namespace: $this->minimaliser->getNamespace(),
                identifier: $this->databaseIdentifier,
                loadedServices: $loadedServices,
            );

            $this->writeObjects(
                tables: $database->getTables(),
                loadedServices: $loadedServices,
            );

            $this->writeTests(
                tables: $database->getTables(),
            );

            return $this->redirect(
                modelClass: RunRegister::class,
                function: 'cli'
            );
        }

        echo 'No databases specified in the .env file.';
        return HttpCode::Ok;
    }

    /**
     * @param array $tables
     * @param string[] $loadedServices
     * @return void
     * @throws Exception
     */
    private function writeObjects(
        array $tables,
        array $loadedServices,
    ): void
    {
        FileFactory::createFiles(
            projectName: $this->projectName,
            loadedServices: $loadedServices,
            tables: $tables,
        );

        foreach ($tables as $table) {
            FileFactory::generateObjectFiles(
                tables: $tables,
                table: $table,
            );
        }

        $discovery = $this->minimalismFactories->getServiceFactory()->create(Discovery::class);

        $serviceData = FileFactory::getServiceData();

        $additionalEndpoints = $this->minimaliser->getAdditionalEndpoints();

        if ($additionalEndpoints !== null){
            $endpoints = explode(';', $additionalEndpoints);
            foreach ($endpoints as $endpoint){
                if ($endpoint !== ''){
                    [$name, $configurations] = explode('?', $endpoint);
                    [$url, $methodList] = explode('-', $configurations);
                    $methods = explode(',', $methodList);

                    $alreadyPresent = $serviceData->getElements()[0]->findChild($name);

                    if ($alreadyPresent !== null){
                        continue;
                    }

                    $endpointResource = new ResourceObject(type: 'endpoint', id: $serviceData->getElements()[0]->getId() . "-" . $name);
                    $endpointResource->attributes->add(name: 'name', value: $url);
                    foreach ($methods as $method){
                        if ($method !== ''){
                            $endpointResource->relationship('methods')->resourceLinkage->addResource(
                                new ResourceObject(type: 'method', id: $method)
                            );
                        }
                    }

                    $endpointData = new EndpointData();
                    $endpointData->import($endpointResource);

                    $serviceData->getElements()[0]->add($endpointData);
                }
            }
        }

        $discovery->setMicroserviceRegistry([$serviceData]);

        $dataFactory = new MicroserviceDataFactory(
            path: $this->minimalismFactories->getServiceFactory()->getPath(),
            discovery: $discovery,
        );

        $dataFactory->write([$serviceData]);
    }

    /**
     * @param TableObject[] $tables
     * @return void
     * @throws Exception
     */
    private function writeTests(
        array $tables,
    ): void
    {
        TestsFactory::createTestFiles(
            namespace: $this->minimaliser->getNamespace(),
            projectName: $this->projectName,
            databaseName: $this->databaseIdentifier,
            tables: $tables,
        );

        foreach ($tables as $table) {
            TestsFactory::generateDataFiles(
                namespace: $this->minimaliser->getNamespace(),
                projectName: $this->projectName,
                databaseName: $this->databaseIdentifier,
                table: $table,
            );

            if (!$table->isManyToMany() && $table->isComplete()) {
                TestsFactory::generateFunctionalTestFiles(
                    namespace: $this->minimaliser->getNamespace(),
                    projectName: $this->projectName,
                    databaseName: $this->databaseIdentifier,
                    table: $table,
                );
            }
        }
    }
}