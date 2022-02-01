<?php

declare(strict_types=1);

namespace Apiato\Core\Foundation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Apiato.
 *
 * @method static array getShipFoldersNames()                                                                                                                       Get the port folders names
 * @method static array getShipPath()                                                                                                                               Get Ship layer directories paths
 * @method static array getSectionContainerNames(string $sectionName)
 * @method static mixed getClassObjectFromFile(string $filePathName)                                                                                                Build and return an object of a class from its file path
 * @method static string getClassFullNameFromFile(string $filePathName) Get the full name (name \ namespace) of a class from its file path result example: (string) "I\Am\The\Namespace\Of\This\Class"
 * @method static array getSectionPaths()
 * @method static string getClassType($className)                                                                                                                   Get the last part of a camel case string. Example input = helloDearWorld | returns = World
 * @method static array getAllContainerNames()
 * @method static array getAllContainerPaths()
 * @method static array getSectionNames()
 * @method static array getSectionContainerPaths(string $sectionName)
 * @method static string getApiPrefix()                                                                                                                             Return current api prefix, by default '/'
 *
 * @see \Apiato\Core\Foundation\Apiato
 */
class Apiato extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Apiato';
    }
}
