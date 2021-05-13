<?php
namespace n2n\composer\module;

use Composer\Installer\LibraryInstaller;
use Composer\Package\Package;

class ModuleInstaller extends LibraryInstaller {
	/**
	 * {@inheritDoc}
	 * @see \Composer\Installer\InstallerInterface::supports()
	 */
	public function supports($packageType) {
		return $packageType == self::N2N_MODULE_TYPE || $packageType == self::N2N_TMPL_MODULE_TYPE; 
	}

	
	public function isInstalled(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		if (!$this->needsUpdate($package)) {
			return true;
		}

		return parent::isInstalled($repo, $package);
	}

	
	/**
	 * {@inheritDoc}
	 * @see \Composer\Installer\InstallerInterface::install()
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		parent::install($repo, $package);
		
		$this->removeResources($package);
		$this->installResources($package);
		
	}
	/**
	 * {@inheritDoc}
	 * @see \Composer\Installer\InstallerInterface::update()
	 */
	public function update(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $initial, 
			\Composer\Package\PackageInterface $target) {
		if (!$this->needsUpdate($initial)) {
			//add the new version of the module to the composer.lock
			$repo->removePackage($initial);
			if (!$repo->hasPackage($target)) {
				$repo->addPackage(clone $target);
			}
			return;
		}
				
    	$this->moveBackResources($initial);
				
		parent::update($repo, $initial, $target);
		
		$this->removeResources($target);
		$this->installResources($target);
	}

	/**
	 * {@inheritDoc}
	 * @see \Composer\Installer\InstallerInterface::uninstall()
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
    	$this->moveBackResources($package);
    	
    	if (!$this->isTmplPackage($package)) {
    		$pattern = '/' . $this->getModuleName($package);
	    	$this->removeFromGitIgnore($this->getVarDestDirPath() . DIRECTORY_SEPARATOR . self::ETC_DIR,
	    			$pattern);
	    	$this->removeFromGitIgnore($this->getPublicDestDirPath() . DIRECTORY_SEPARATOR . self::ASSETS_DIR,
	    			$pattern);
			parent::uninstall($repo, $package);
    	} else {
    		$repo->removePackage($package);
    	}
		
	}
	
	const N2N_MODULE_TYPE = 'n2n-module';
	const N2N_TMPL_MODULE_TYPE = 'n2n-tmpl-module';
	const APP_ORIG_DIR = 'src' . DIRECTORY_SEPARATOR . 'app';
	const APP_DEST_DIR = '..' . DIRECTORY_SEPARATOR . 'app';
	const VAR_ORIG_DIR = 'src' . DIRECTORY_SEPARATOR . 'var';
	const VAR_DEST_DIR = '..' . DIRECTORY_SEPARATOR . 'var';
	const ETC_DIR = 'etc';
	const PUBLIC_ORIG_DIR = 'src' . DIRECTORY_SEPARATOR . 'public';
	const PUBLIC_DEST_DIR = '..' . DIRECTORY_SEPARATOR . 'public';
	const ASSETS_DIR = 'assets';
	
	private function getModuleName(Package $package) {
		return pathinfo($this->getInstallPath($package), PATHINFO_BASENAME);
	}
	
	private function getAppOrigDirPath(Package $package) {
		return $this->filesystem->normalizePath($this->getInstallPath($package) . DIRECTORY_SEPARATOR 
				. self::APP_ORIG_DIR);
	}
	
	private function getAppDestDirPath() {
		return $this->filesystem->normalizePath($this->vendorDir . DIRECTORY_SEPARATOR . self::APP_DEST_DIR);
	}
	
	private function getRelAppDirPath(Package $package) {
	    return DIRECTORY_SEPARATOR . str_replace('-', DIRECTORY_SEPARATOR, $this->getModuleName($package));
	}
	
	private function getVarOrigDirPath(Package $package) {
		return $this->filesystem->normalizePath($this->getInstallPath($package) . DIRECTORY_SEPARATOR 
				. self::VAR_ORIG_DIR);
	}
	
	private function getVarDestDirPath() {
		return $this->filesystem->normalizePath($this->vendorDir . DIRECTORY_SEPARATOR . self::VAR_DEST_DIR);
	}
	
	private function getRelEtcDirPath(Package $package) {
		return DIRECTORY_SEPARATOR . self::ETC_DIR . DIRECTORY_SEPARATOR . $this->getModuleName($package);
	}
	
	private function getPublicOrigDirPath(Package $package) {
		return $this->filesystem->normalizePath($this->getInstallPath($package) . DIRECTORY_SEPARATOR 
				. self::PUBLIC_ORIG_DIR);
	}
	
	private function getPublicDestDirPath() {
		return $this->filesystem->normalizePath($this->vendorDir . DIRECTORY_SEPARATOR . self::PUBLIC_DEST_DIR);
	}

	private function getRelAssetsDirPath(Package $package) {
		return DIRECTORY_SEPARATOR . self::ASSETS_DIR . DIRECTORY_SEPARATOR . $this->getModuleName($package);
	}
	
	public function moveBackResources(Package $package) {
		if (!$this->needsUpdate($package)) return;
		
		$relEtcDirPath = $this->getRelEtcDirPath($package);
		$mdlEtcOrigDirPath = $this->getVarOrigDirPath($package) . $relEtcDirPath;
		$mdlEtcDestDirPath = $this->getVarDestDirPath() . $relEtcDirPath;
		
		if (is_dir($mdlEtcDestDirPath)) {
			$this->filesystem->copyThenRemove($mdlEtcDestDirPath, $mdlEtcOrigDirPath);
		}

		$relAssetsDirPath = $this->getRelAssetsDirPath($package);
		$mdlAssetsOrigDirPath = $this->getPublicOrigDirPath($package) . $relAssetsDirPath;
		$mdlAssetsDestDirPath = $this->getPublicDestDirPath() . $relAssetsDirPath;
		if (is_dir($mdlAssetsDestDirPath)) {
			$this->filesystem->copyThenRemove($mdlAssetsDestDirPath, $mdlAssetsOrigDirPath);
		}
	}
	
	private function removeResources(Package $package) {
		if (!$this->needsUpdate($package)) return;
		$moduleName = $this->getModuleName($package);
		
		$mdlEtcDestDirPath = $this->getVarDestDirPath() . $this->getRelEtcDirPath($package);
		if (is_dir($mdlEtcDestDirPath)) {
			try {
				$this->filesystem->removeDirectory($mdlEtcDestDirPath);
				$this->removeFromGitIgnore($this->getVarDestDirPath() . DIRECTORY_SEPARATOR . self::ETC_DIR, 
						$moduleName);
			} catch (\RuntimeException $e) {}
		}
		
		$mdlAssetsDestDirPath = $this->getPublicDestDirPath() . $this->getRelAssetsDirPath($package);
		if (is_dir($mdlAssetsDestDirPath)) {
			try {
				$this->filesystem->removeDirectory($mdlAssetsDestDirPath);
				$this->removeFromGitIgnore($this->getPublicDestDirPath() . DIRECTORY_SEPARATOR . self::ASSETS_DIR, 
						$moduleName);
			} catch (\RuntimeException $e) {}	
		}
	}
	
	private function installResources(Package $package) {
		$this->moveApp($package);
		$this->moveAssets($package);
		$this->moveEtc($package);
		
		if ($this->isTmplPackage($package)) {
			try {
				$this->filesystem->removeDirectory($this->getInstallPath($package));
			} catch (\RuntimeException $e) {}
		}
	}
	
	private function moveApp(Package $package) {
		if (!$this->isTmplPackage($package) || $this->hasDestEtcDirPath($package)) return;
		
 	    $appOrigDirPath = $this->getAppOrigDirPath($package);
 	    $appDestDirPath = $this->getAppDestDirPath();
	    
 	    $this->valOrigDirPath($appOrigDirPath, $package);
 	    
 	    $relAppDirPath = $this->getRelAppDirPath($package);
 	    $mdlAppOrigDirPath = $appOrigDirPath . $relAppDirPath;
 	    $mdlAppDestDirPath = $appDestDirPath . $relAppDirPath;
 	    
 	    if (!is_dir($mdlAppOrigDirPath)) {
 	        return;
 	    }
 	    if (!is_dir($mdlAppDestDirPath) && $this->valDestDirPath($appDestDirPath, $package)) {
 	    	$this->filesystem->copyThenRemove($mdlAppOrigDirPath, $mdlAppDestDirPath);
	    }
	}
	
	private function moveEtc(Package $package) {
		if (!$this->needsUpdate($package)) return;
		
		$varOrigDirPath = $this->getVarOrigDirPath($package);
		$varDestDirPath = $this->getVarDestDirPath();
	
		$this->valOrigDirPath($varOrigDirPath, $package);
	
		$relEtcDirPath = $this->getRelEtcDirPath($package);
		$mdlEtcOrigDirPath = $varOrigDirPath . $relEtcDirPath;
		$mdlEtcDestDirPath = $varDestDirPath . $relEtcDirPath;
	
		//don't move if etc folder exists and 
		if (!is_dir($mdlEtcOrigDirPath)) {
			return;
		}
	
		if ($this->valDestDirPath($varDestDirPath, $package)) {
			$this->filesystem->copyThenRemove($mdlEtcOrigDirPath, $mdlEtcDestDirPath);
		}
		
		if (!$this->isTmplPackage($package)) {
			$this->addToGitIgnore($varDestDirPath . DIRECTORY_SEPARATOR . self::ETC_DIR, 
					'/' . $this->getModuleName($package));
		}
	}
	
	private function moveAssets(Package $package) {
		if (!$this->needsUpdate($package)) return;
		
		$publicOrigDirPath = $this->getPublicOrigDirPath($package);
		$publicDestDirPath = $this->getPublicDestDirPath();
	
// 		$this->valOrigDirPath($publicOrigDirPath, $package);
	
		$relAssetsDirPath = $this->getRelAssetsDirPath($package);
		$mdlAssetsOrigDirPath = $publicOrigDirPath . $relAssetsDirPath;
		$mdlAssetsDestDirPath = $publicDestDirPath . $relAssetsDirPath;
	
		if (!is_dir($mdlAssetsOrigDirPath)) {
			return;
		}
	
		if (!is_dir($mdlAssetsDestDirPath) && $this->valDestDirPath($publicDestDirPath, $package)) {
			$this->filesystem->copyThenRemove($mdlAssetsOrigDirPath, $mdlAssetsDestDirPath);
		}
		
		if (!$this->isTmplPackage($package)) {
			$this->addToGitIgnore($publicDestDirPath . DIRECTORY_SEPARATOR . self::ASSETS_DIR, 
					'/' . $this->getModuleName($package));
		}
	}
	
	private function valOrigDirPath($origDirPath, Package $package) {
		if (is_dir($origDirPath)) return;
	
		$dirName = pathinfo($origDirPath, PATHINFO_BASENAME);
		throw new CorruptedN2nModuleException($package->getPrettyName() . ' has type \'' . $package->getType()
				. '\' but contains no ' . $dirName . ' directory: ' . $origDirPath);
	}
	
	private function valDestDirPath($destDirPath, Package $package) {
		if (is_dir($destDirPath)) return true;
	
		$dirName = pathinfo($destDirPath, PATHINFO_BASENAME);
	
		$question = $package->getPrettyName() . ' is an ' . $package->getType()
				. ' and requires a ' . $dirName . ' directory (' . $destDirPath
				. '). Do you want to skip the installation of the ' . $dirName . ' files? [y,n] (default: y): ';
		if ($this->io->askConfirmation($question)) return false;
	
		throw new N2nModuleInstallationException('Failed to install ' . $package->getType() . ' '
				. $package->getPrettyName() . '. Reason: ' . $dirName . ' directory missing: ' . $destDirPath);
	}
	
	private function needsUpdate(Package $package) {
		if (!$this->isTmplPackage($package)) return true;
		
		return !$this->hasDestEtcDirPath($package);
	}
	
	private function hasDestEtcDirPath(Package $package) {
		return is_dir($this->getVarDestDirPath() . $this->getRelEtcDirPath($package));
	}
	
	private function isTmplPackage(Package $package) {
		return $package->getType() === self::N2N_TMPL_MODULE_TYPE;
	}
	
	private function addToGitIgnore($dirPath, $pattern) {
		if (!is_dir($dirPath)) return;
		
		$filename = $dirPath . DIRECTORY_SEPARATOR . '.gitignore';
		
		$contents = [];
		if (is_file($filename)) {
			$contents = file($filename);
		}
		
		foreach ($contents as $content) {
			if (trim($content) == $pattern) return;
		}
		
		$contents[] = (!empty($contents) ? PHP_EOL : '') . $pattern;
		
		
		file_put_contents($filename, $contents);
	}
	
	private function removeFromGitIgnore($dirPath, $pattern) {
		if (!is_dir($dirPath)) return;
		
		$filename = $dirPath . DIRECTORY_SEPARATOR . '.gitignore';
		
		if (is_file($filename)) return;
		$contents = file($filename);
		
		$newContents = [];
		foreach ($contents as $content) {
			if (trim($content) == $pattern) continue;
			
			$newContents[] = $content;
		}
		
		file_put_contents($filename, $newContents);
	}
	
// 	private function copy($source, $target) {
//         if (!is_dir($source)) {
//             copy($source, $target);
//             return;
//         }

//         $it = new \RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
//         $ri = new \RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
//         $this->ensureDirectoryExists($target);

//         foreach ($ri as $file) {
//             $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
//             if ($file->isDir()) {
//                 $this->filesystem->ensureDirectoryExists($targetPath);
//             } else {
//                 copy($file->getPathname(), $targetPath);
//             }
//         }
// 	}
}
