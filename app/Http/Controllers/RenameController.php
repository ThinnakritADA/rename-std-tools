<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            return response()->json(['error' => 'ที่อยู่ไม่ถูกต้อง'], 400);
        }
        // rename in modules
        $controllerLists = $this->renameController($path);
        $moduleLists = $this->renameModel($path);
        $this->replaceName($path, $moduleLists);
        $this->replaceName($path, $controllerLists);
        // rename in route
        $path = $basePath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'route';
        $this->replaceRouteName($path, $controllerLists);
        return response()->json(['success' =>
            [
            'controller' => $controllerLists,
            'model' => $moduleLists,
            ]
        ]);
    }
    public function renameController(string $path) : Collection
    {
        try {
            $finder = new Finder();
            $finder->files()->in($path)->name('/^c[A-Z]\w+\.php$/');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
        }
        return $this->loopRename($finder);

    }

    public function renameModel(string $path) : Collection
    {

        try {
            $finder = new Finder();
            $finder->files()->in($path)->name('/^m[A-Z]\w+\.php$/');
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

    public function replaceName(string $path ,Collection $array)
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

    public function replaceRouteName(string $path ,Collection $array)
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

}
