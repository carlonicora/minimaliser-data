<?php
namespace CarloNicora\Minimalism\MinimaliserData\Factories;

use CarloNicora\JsonApi\Document;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Twig\Twig;
use Exception;

class TestsFactory
{
    /** @var Twig  */
    private static Twig $twig;

    /** @var string  */
    private static string $testsDirectory;

    /**
     * @param Twig $twig
     * @param string $testsDirectory
     * @return void
     */
    public static function initialise(
        Twig $twig,
        string $testsDirectory,
    ): void
    {
        self::$twig = $twig;
        self::$testsDirectory = $testsDirectory;
    }

    /**
     * @param string $namespace
     * @param string $projectName
     * @param string $databaseName
     * @param TableObject[] $tables
     * @return void
     * @throws Exception
     */
    public static function createTestFiles(
        string $namespace,
        string $projectName,
        string $databaseName,
        array $tables,
    ): void
    {
        foreach (glob(self::$testsDirectory . '*', GLOB_NOSORT) as $folder){
            if ($folder !== '.' && $folder !== '..') {
                exec(sprintf("rm -rf %s", escapeshellarg($folder)));
            }
        }

        mkdir(self::$testsDirectory . 'Abstracts');
        mkdir(self::$testsDirectory . 'Data');
        mkdir(self::$testsDirectory . 'Data' . DIRECTORY_SEPARATOR . 'Oauth');
        mkdir(self::$testsDirectory . 'Data' . DIRECTORY_SEPARATOR . $databaseName);
        mkdir(self::$testsDirectory . 'Functional');
        mkdir(self::$testsDirectory . 'Validators');

        $document = new Document();
        $document->meta->add(name: 'namespace', value: $namespace);
        $document->meta->add(name: 'projectName', value: $projectName);
        $document->meta->add(name: 'database', value: $databaseName);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Abstracts/AbstractFunctionalTest',
        );

        file_put_contents(self::$testsDirectory . 'Abstracts' . DIRECTORY_SEPARATOR . 'AbstractFunctionalTest.php', $file, LOCK_EX);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Data/Oauth/AppsData',
        );

        file_put_contents(self::$testsDirectory . 'Data' . DIRECTORY_SEPARATOR . 'Oauth' . DIRECTORY_SEPARATOR . 'AppsData.php', $file, LOCK_EX);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Data/Oauth/TokensData',
        );

        file_put_contents(self::$testsDirectory . 'Data' . DIRECTORY_SEPARATOR . 'Oauth' . DIRECTORY_SEPARATOR . 'TokensData.php', $file, LOCK_EX);



        foreach ($tables as $table){
            if ($table->isComplete()) {
                $singleDocument = clone($document);
                $singleDocument->addResource($table->generateResourceObject());

                $file = self::$twig->transform(
                    document: $singleDocument,
                    viewFile: 'Tests/Validators/TableValidator',
                );

                file_put_contents(self::$testsDirectory . 'Validators' . DIRECTORY_SEPARATOR . $table->getObjectName() . 'Validator.php', $file, LOCK_EX);
            }
        }

        $document->forceResourceList();
        foreach ($tables as $table){
            if ($table->isComplete()) {
                $document->addResource($table->generateResourceObject());
            }
        }

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Abstracts/AbstractValidator',
        );

        file_put_contents(self::$testsDirectory . 'Abstracts' . DIRECTORY_SEPARATOR . 'AbstractValidator.php', $file, LOCK_EX);
    }

    /**
     * @param string $namespace
     * @param string $projectName
     * @param string $databaseName
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    public static function generateDataFiles(
        string $namespace,
        string $projectName,
        string $databaseName,
        TableObject $table,
    ): void
    {
        $document = new Document();
        $document->meta->add(name: 'namespace', value: $namespace);
        $document->meta->add(name: 'projectName', value: $projectName);
        $document->meta->add(name: 'database', value: $databaseName);

        $document->addResource(
            $table->generateResourceObject(),
        );

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Data/TableData',
        );

        file_put_contents(self::$testsDirectory . 'Data' . DIRECTORY_SEPARATOR . $databaseName . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() .  'Data.php', $file, LOCK_EX);
    }

    /**
     * @param string $namespace
     * @param string $projectName
     * @param string $databaseName
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    public static function generateFunctionalTestFiles(
        string $namespace,
        string $projectName,
        string $databaseName,
        TableObject $table,
    ): void
    {
        mkdir(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural());

        $document = new Document();
        $document->meta->add(name: 'namespace', value: $namespace);
        $document->meta->add(name: 'projectName', value: $projectName);
        $document->meta->add(name: 'database', value: $databaseName);

        $document->addResource(
            $table->generateResourceObject(),
        );

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Functional/Get',
        );

        file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Get' . $table->getObjectNamePlural() .  '.php', $file, LOCK_EX);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Functional/Post',
        );

        file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Post' . $table->getObjectNamePlural() .  '.php', $file, LOCK_EX);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Functional/Patch',
        );

        file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Patch' . $table->getObjectNamePlural() .  '.php', $file, LOCK_EX);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'Tests/Functional/Delete',
        );

        file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Delete' . $table->getObjectNamePlural() .  '.php', $file, LOCK_EX);

        foreach ($table->getChildren() ?? [] as $childTable) {
            mkdir(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . $childTable->getTable()->getObjectNamePlural());

            $childDocument = clone($document);
            $childDocument->resources[0]->meta->add(name: 'childTable', value: $childTable->getTable()->getName());

            $file = self::$twig->transform(
                document: $document,
                viewFile: 'Tests/Functional/GetChild',
            );

            file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR .
                $table->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                $childTable->getTable()->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                'Get' . $childTable->getTable()->getObjectNamePlural() .  '.php', $file, LOCK_EX);

            if (!$childTable->getTable()->isComplete()) {
                $file = self::$twig->transform(
                    document: $document,
                    viewFile: 'Tests/Functional/PostChild',
                );

                file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR .
                    $table->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                    $childTable->getTable()->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                    'Post' . $childTable->getTable()->getObjectNamePlural() .  '.php', $file, LOCK_EX);

                $file = self::$twig->transform(
                    document: $document,
                    viewFile: 'Tests/Functional/DeleteChild',
                );

                file_put_contents(self::$testsDirectory . 'Functional' . DIRECTORY_SEPARATOR .
                    $table->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                    $childTable->getTable()->getObjectNamePlural() . DIRECTORY_SEPARATOR .
                    'Delete' . $childTable->getTable()->getObjectNamePlural() .  '.php', $file, LOCK_EX);
            }

            $childDocument->resources[0]->meta->remove(name: 'childTable');
        }
    }
}