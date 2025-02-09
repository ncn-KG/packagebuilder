<?php declare(strict_types=1);

/*
 * This file is part of the package bk2k/packagebuilder.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Package;
use App\Utility\FileUtility;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * SitepackageGenerator.
 */
class SitepackageGenerator
{
    /**
     * @var string
     */
    protected $zipPath;

    /**
     * @var string
     */
    protected $filename;

    public function create(Package $package)
    { 
        if ($package->getBasePackage() == 'ncn_custom_package' || $package->getBasePackage() == 'ncn_custom_multisite_package'){
            $extensionKey = $package->getPackageNameAlternative();
        } else {
            $extensionKey = $package->getExtensionKey();
        }
        
        $this->filename = $extensionKey . '.zip'; 

        $sourceDir = __DIR__ . '/../Resources/skeletons/BaseExtension/' . $package->getBasePackage() . '/';
        $customThemeDir = __DIR__ . '/../Resources/skeletons/BaseExtension/ncn_custom_theme/';
        $customThemeNewDir = 'packages/ncn_'. $package->getPackageNameAlternative() .'_theme';

        $customBasicDir = __DIR__ . '/../Resources/skeletons/BaseExtension/ncn_custom_basic/';
        $customBasicNewDir = 'packages/ncn_'. $package->getPackageNameAlternative() .'_basic';
        
        $this->zipPath = tempnam(sys_get_temp_dir(), $this->filename);
        $fileList = FileUtility::listDirectory($sourceDir);

        $fileListCustomTheme = FileUtility::listDirectory($customThemeDir);
        $fileListCustomBasic = FileUtility::listDirectory($customBasicDir);

        $zipFile = new \ZipArchive();
        $opened = $zipFile->open($this->zipPath, \ZipArchive::CREATE);
        if (true === $opened) {
            
            foreach ($fileList as $file) {
                if ($file !== $this->zipPath && file_exists($file)) {
                    $baseFileName = $this->createRelativeFilePath($file, $sourceDir);
                    if (is_dir($file)) {
                        $zipFile->addEmptyDir($baseFileName);
                    } elseif (!$this->isTwigFile($file)) {
                        $zipFile->addFile($file, $baseFileName);
                    } else {
                        $content = $this->getFileContent($file, $package);
                        $nameInZip = $this->removeTwigExtension($baseFileName);
                        $zipFile->addFromString($nameInZip, $content);
                    }
                }
            }
            
            if ($package->getBasePackage() == 'ncn_custom_package' || $package->getBasePackage() == 'ncn_custom_multisite_package'){
                $zipFile->addEmptyDir($customThemeNewDir);

                foreach ($fileListCustomTheme as $file) {
                    $baseFileName = $this->createRelativeFilePath($file, $customThemeDir);
                    $newBaseFileName = $customThemeNewDir.'/'.$baseFileName;

                    if (is_dir($file)) {
                        $zipFile->addEmptyDir($newBaseFileName);
                    } elseif (!$this->isTwigFile($file)) {
                        $zipFile->addFile($file, $newBaseFileName);
                    } else {
                        $content = $this->getFileContent($file, $package);
                        $nameInZip = $this->removeTwigExtension($newBaseFileName);
                        $zipFile->addFromString($nameInZip, $content);
                    }
                }
            }

            if ($package->getBasePackage() == 'ncn_custom_multisite_package'){
                $zipFile->addEmptyDir($customBasicNewDir);

                foreach ($fileListCustomBasic as $file) {
                    $baseFileName = $this->createRelativeFilePath($file, $customBasicDir);
                    $newBaseFileName = $customBasicNewDir.'/'.$baseFileName;

                    if (is_dir($file)) {
                        $zipFile->addEmptyDir($newBaseFileName);
                    } elseif (!$this->isTwigFile($file)) {
                        $zipFile->addFile($file, $newBaseFileName);
                    } else {
                        $content = $this->getFileContent($file, $package);
                        $nameInZip = $this->removeTwigExtension($newBaseFileName);
                        $zipFile->addFromString($nameInZip, $content);
                    }
                }
            }
            
            $zipFile->close();
        }
    }

    /**
     * @return string
     */
    public function getZipPath()
    {
        return $this->zipPath;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getFileContent($file, Package $package)
    {
        $content = file_get_contents($file);
        $fileUniqueId = uniqid('file');
        $twig = new Environment(new ArrayLoader([$fileUniqueId => $content]));
        $rendered = $twig->render(
            $fileUniqueId,
            [
                'package' => $package,
                'timestamp' => time(),
            ]
        );

        return $rendered;
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    private function isTwigFile($file)
    {
        $pathinfo = pathinfo($file);

        return 'twig' === $pathinfo['extension'];
    }

    /**
     * @param string $file
     * @param string $sourceDir
     *
     * @return mixed
     */
    protected function createRelativeFilePath($file, $sourceDir)
    {
        return substr($file, strlen($sourceDir));
    }

    /**
     * @param string $baseFileName
     *
     * @return mixed
     */
    protected function removeTwigExtension($baseFileName)
    {
        return substr($baseFileName, 0, -5);
    }
}
