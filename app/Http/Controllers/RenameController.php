<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class RenameController extends Controller
{

    public function index()
    {
        return view('rename');
    }

    public function rename(Request $request)
    {
        set_time_limit(0);
        $basePath = $request->input('path');
        $path = $basePath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'modules';
        if (empty($path) || !is_dir($path)) {
            return response()->json(['error' => 'Invalid path'], 400);
        }
        $allDirectories = new Collection($this->fixDirectoryName($path));
        $this->replaceDirName($path, $allDirectories);
        // rename in modules
        $controllerLists = $this->renameController($path);
        $moduleLists = $this->renameModel($path);
        $allLists = $this->getAllAndFixName($path)->sortByDesc('originalName');
        $this->replaceNameModel($path, $moduleLists->sortByDesc('originalName'));
        $this->replaceNameController($path, $controllerLists->sortByDesc('originalName'));
        // rename in route
        $path = $basePath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'route';
        $this->replaceRouteName($path, $controllerLists->sortByDesc('originalName'));
        $this->replaceRouteName($path, $allLists->sortByDesc('originalName')->filter(function ($value, $key) {
            return $value['fileType'] == 'controller';
        })->values());
        $path = $basePath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'helpers';
        $this->replaceHelper($path, $moduleLists->sortByDesc('originalName'));
        $this->replaceHelper($path, $allLists->sortByDesc('originalName')->filter(function ($value, $key) {
            return $value['fileType'] == 'model';
        })->values());
        return response()->json(['success' =>
            [
                'controller' => $controllerLists,
                'model' => $moduleLists,
                'directory' => $allDirectories,
            ]
        ]);
    }
    public function renameController(string $path) : Collection
    {
        try {
            $finder = new Finder();
            $finder->files()->in($path)->name('/^c[A-Z]\w+\.php$/')->notName('/(_controller|_model)\.php$/');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
        }
        return $this->loopRename($finder);

    }

    public function renameModel(string $path) : Collection
    {

        try {
            $finder = new Finder();
            $finder->files()->in($path)->name('/^m[A-Z]\w+\.php$/')->notName('/(_controller|_model)\.php$/');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
        }

        return $this->loopRename($finder);

    }

    private function loopRename(Finder $finder) : Collection
    {
        $fileList = new Collection();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $originalName = $file->getBasename('.php');
            $prefix = substr($originalName, 0, 1);
            $newName = ucfirst(strtolower(substr($originalName, 1)));

            switch ($prefix) {
                case 'c':
                    $newName .= '_controller';
                    break;
                case 'm':
                    $newName .= '_model';
                    break;
            }

            // rename the file
            rename($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php');

            // change class name in the file
            $content = file_get_contents($file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php');
            $content = str_replace("class {$originalName}", "class {$newName}", $content);
            file_put_contents($file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php', $content);

            $fileList->push(
                [
                    'originalName' => $originalName,
                    'newName' => $newName,
                ]
            );
        }
        return $fileList;
    }

    public function replaceNameModel(string $path ,Collection $array) : void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php')->notName('/^[wj].*/');

        foreach ($finder as $file) {
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());

            // Replace all occurrences
            $array->each(function ($item, $key) use (&$fileContents) {
                $fileContents = str_replace($item['originalName'], $item['newName'], $fileContents);
            });

            file_put_contents($file->getRealPath(), $fileContents);
        }
    }

    public function replaceNameController(string $path ,Collection $array) : void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php')->notName('/^[wj].*/');

        foreach ($finder as $file) {
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());

            // Replace all occurrences
            $array->each(function ($item, $key) use (&$fileContents) {
                $fileContents = str_replace($item['originalName'] . "/", $item['newName'] . "/", $fileContents);
            });

            file_put_contents($file->getRealPath(), $fileContents);
        }
    }

    public function replaceDirName(string $path ,Collection $array) : void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());

            // Replace
            $array->each(function ($item, $key) use (&$fileContents) {
                $fileContents = str_replace($item['originalName']."/", $item['newName']."/", $fileContents);
            });

            file_put_contents($file->getRealPath(), $fileContents);
        }
    }

    public function replaceRouteName(string $path ,Collection $array) : void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());

            // Replace all occurrences
            $array->each(function ($item, $key) use (&$fileContents) {
                $fileContents = str_replace($item['originalName'], $item['newName'], $fileContents);
            });

            file_put_contents($file->getRealPath(), $fileContents);
        }
    }

    public function replaceHelper(string $path,Collection $array) : void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php')->notName('/^[wj].*/');

        foreach ($finder as $file) {
            $originalName = $file->getBasename('.php');
            $newName = strtolower($originalName);
            // rename the file
            rename($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php');
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());

            // Replace all occurrences
            $array->each(function ($item, $key) use (&$fileContents) {
                $fileContents = str_replace($item['originalName'], $item['newName'], $fileContents);
            });

            file_put_contents($file->getRealPath(), $fileContents);
        }
    }

    public function getAllAndFixName(string $path) : Collection
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('/(_controller|_model)\.php$/');
        $fileList = new Collection();
        foreach ($finder as $file) {
            $originalName = $file->getBasename('.php');
            $newName = ucfirst(strtolower($originalName));
            // rename the file
            rename($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php');

            // change class name in the file
            $content = file_get_contents($file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php');
            $content = str_replace("class {$originalName}", "class {$newName}", $content);
            file_put_contents($file->getPath() . DIRECTORY_SEPARATOR . $newName . '.php', $content);
            $fileType =strpos($newName, '_controller') ? 'controller' : 'model';
            $fileList->push(
                [
                    'originalName' => $originalName,
                    'newName' => $newName,
                    'fileType' => $fileType,
                ]
            );
        }
        return $fileList;
    }

    public function fixDirectoryName(string $directory , $subdirectories = [] , &$nameChange = []) : array
    {
        $directories = File::directories($directory);

        foreach ($directories as $directory) {
            $subdirectories[] = $directory;
            if($this->hasUppercase(basename($directory))){
                $newName = strtolower(basename($directory));
                $nameChange[] = [
                    'originalName' => basename($directory),
                    'newName' => $newName,
                ];
                rename($directory, dirname($directory) . DIRECTORY_SEPARATOR . $newName);
            }
            $this->fixDirectoryName($directory, $subdirectories, $nameChange);
        }
        return $nameChange;
    }

    function hasUppercase(string $string) : bool
    {
        return preg_match('/[A-Z]/', $string) > 0;
    }
}
