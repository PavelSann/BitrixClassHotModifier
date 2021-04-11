<?php

if (function_exists('spl_autoload_register')) {
	\spl_autoload_register([ClassHotModifier::class, 'splAutoloadClass'], true, true);
	StreamFilterHotModifier::registerFilter();
}

use Bitrix\Main\IO\Path;

/**
 * ClassHotModifier
 *  The class can overwrite typical classes of "bitrix" to  add a new functional without modification files of core.
 * @author https://github.com/PavelSann
 */
class ClassHotModifier {

	const REG_AN_CLASS_NAME = '/@CLASS_FILE\s([\.\_\d\/\w]*\.php)/m';
	const REG_AN_CLASS_MOD = '/@CLASS_MOD\s([\w]*)/m';
	const REG_AN_IGNORE_CLASS_COUNT = '/@IGNORE_CLASS_COUNT\s/m';

	private static $modClasses = [];

	private static function log($var, $varName = null) {
		
	}

	static public function splAutoloadClass($class) {
		$clm = self::$modClasses[$class];
		if ($clm) {
			$fName = $clm['file'];
			if (file_exists($fName)) {
				if ($clm['processing']) {
					if (is_array($clm['classes']) && !empty($clm['classes'])) {
						self::loadProcessingClasses($clm['classes'], $fName);
					} else {
						self::loadProcessingClasses([$class], $fName);
					}
				} else {
//					self::log($fName,"load $class");
					require_once $fName;
				}
			} else {
				self::log($fName, "File not found");
			}
		}
	}

	static private function getAn($text, $regEx) {
		$matches = [];
		if (preg_match($regEx, $text, $matches)) {
			return $matches[1];
		}
		return null;
	}

	static private function loadProcessingClasses(array $classNames, $modClassFile) {
		//оверхэд примерно 1мс
//		Bitrix\Main\Diag\Debug::startTimeLabel("loadProcessingClasses");
		$classModNames = [];
		$classOriginNames = [];
		foreach ($classNames as $key => $cn) {
			$ind = strrpos($cn, '\\');
			if ($ind !== false) {
				$cn = substr($cn, $ind + 1);
				$classNames[$key] = $cn;
			}
			$classModNames[$cn . '__'] = $cn;
			$classOriginNames[$cn] = $cn . "__ClassHotMod";
		}

		if (!file_exists($modClassFile)) {
			self::log($modClassFile, "File not found");
			return;
		}

		$modClass = file_get_contents($modClassFile);
		$originClassFile = self::getAn($modClass, self::REG_AN_CLASS_NAME);
		$ignoreClassReplaceCount = self::getAn($modClass, self::REG_AN_IGNORE_CLASS_COUNT) != null;
		if ($originClassFile) {
			$originClassFile = self::normPath($originClassFile, "bitrix/");
			if (!file_exists($originClassFile)) {
				self::log($originClassFile, "File not found");
				return;
			}

			try {
				$rc = 0;
				StreamFilterHotModifier::setPatterns(['/class (\w+)([\s{])/' =>
					function ($match)use ($classOriginNames, &$rc) {
						if ($classOriginNames[$match[1]]) {
							$rc++;
							return 'class ' . $classOriginNames[$match[1]] . $match[2];
						} else {
							return 'class ' . $match[1] . $match[2];
						}
					}]);
//				Bitrix\Main\Diag\Debug::startTimeLabel("loadProcessingClasses.require.origin");
				StreamFilterHotModifier::require($originClassFile, false, false);
//				Bitrix\Main\Diag\Debug::endTimeLabel("loadProcessingClasses.require.origin");
				if ($rc != count($classOriginNames)) {
					self::log($originClassFile, "Error original class replace. Count replace:$rc Count search:" . count($classOriginNames));
					//self::log(StreamFilterHotModifier::getLastModificationData(), "originClassFile");
					return;
				}
			} catch (Throwable $e) {
				self::log($e->getMessage(), $originClassFile);
//				self::log(StreamFilterHotModifier::getLastModificationData(), $originClassFile);
				throw $e;
			}

			try {
				$rcClass = 0;
				$rcExt = 0;
				StreamFilterHotModifier::setPatterns([
					'/class (\w+)([\s{])/' => function ($match)use ($classModNames, &$rcClass) {
						if ($classModNames[$match[1]]) {
							$rcClass++;
							return 'class ' . $classModNames[$match[1]] . $match[2];
						} else {
							return 'class ' . $match[1] . $match[2];
						}
					},
					'/extends (\w+)([\s{])/' => function ($match)use ($classOriginNames, &$rcExt) {
						if ($classOriginNames[$match[1]]) {
							$rcExt++;
							return 'extends ' . $classOriginNames[$match[1]] . $match[2];
						} else {
							return 'extends ' . $match[1] . $match[2];
						}
					}]);

//				Bitrix\Main\Diag\Debug::startTimeLabel("loadProcessingClasses.require.mod");
				StreamFilterHotModifier::require($modClassFile, false, false);
//				Bitrix\Main\Diag\Debug::endTimeLabel("loadProcessingClasses.require.mod");

				if (!$ignoreClassReplaceCount) {
					if ($rcClass != count($classModNames) || $rcExt != count($classOriginNames)) {
						self::log($modClassFile,
								"Error modified class replace. Count replace:$rcClass-$rcExt Count search:" . count($classModNames) . '-' . count($classOriginNames));
						//self::log(StreamFilterHotModifier::getLastModificationData(), "modClassFile");
						return;
					}
				}
			} catch (Throwable $e) {
				self::log($e->getMessage(), $modClassFile);
				self::log(StreamFilterHotModifier::getLastModificationData(), $modClassFile);
				throw $e;
			}

//			Bitrix\Main\Diag\Debug::endTimeLabel("loadProcessingClasses");
//			self::log(Bitrix\Main\Diag\Debug::getTimeLabels());
		} else {
			self::log("Not set @CLASS_FILE in $modClassFile");
			throw new \Bitrix\Main\SystemException("Not set @CLASS_FILE in $modClassFile");
		}
	}

	static private function normPath($path, $subdir = 'local/classmodifier/') {
		if (!Path::isAbsolute($path)) {
			$path = $_SERVER['DOCUMENT_ROOT'] . "/$subdir$path";
		}
		return Path::normalize($path);
	}

	static public function setClassesExt(array $loadClasses, array $replaceClasses, $filePath, $processing = true) {
		$filePath = self::normPath($filePath);
		foreach ($loadClasses as $class) {
			self::$modClasses[$class] = [
				'file' => $filePath,
				'classes' => $replaceClasses,
				'processing' => $processing
			];
		}
	}

	static public function setClass($class, $filePath) {
		self::setClasses([$class], $filePath);
	}

	static public function setClasses(array $classes, $filePath) {
		self::setClassesExt($classes, $classes, $filePath, true);
	}

//	static private function phpTokens($originClass) {
//		self::log(token_get_all($originClass), "tokens");
//	}
//
//	static private function refl() {
//		$rc = new ReflectionClass("\Bitrix\Main\Loader");
//		$rp = $rc->getProperty('arAutoLoadClasses');
//		$rp->setAccessible(true);
//	}
}

class StreamFilterHotModifier extends php_user_filter {

	private static $patternsAndCallbacks;
	private static $limit = -1;
	private static $lastModData = '';

	/** @var string $stream  contains pointer to the source */
	public static function registerFilter() {
		//class_hot_mod.*
		return stream_filter_register('class_hot_mod', StreamFilterHotModifier::class);
	}

	public static function setPatterns(array $patternsAndCallbacks, int $limit = -1) {
		self::$patternsAndCallbacks = $patternsAndCallbacks;
		self::$limit = $limit;
		self::$lastModData = "";
	}

	public static function getLastModificationData() {
		return self::$lastModData;
	}

	private static function normalizePath($path) {
		if (!Path::isAbsolute($path)) {
			$path = $_SERVER['DOCUMENT_ROOT'] . "/$path";
		}
		$path = Path::normalize($path);
		return $path;
	}

	public static function include($path, bool $normalizePath = true, $once = true) {
		if ($normalizePath) {
			$path = self::normalizePath($path);
		}
		if ($once) {
			include_once "php://filter/read=class_hot_mod/resource=$path";
		} else {
			include "php://filter/read=class_hot_mod/resource=$path";
		}
	}

	public static function require($path, bool $normalizePath = true, $once = true) {
		if ($normalizePath) {
			$path = self::normalizePath($path);
		}
		if ($once) {
			require_once "php://filter/read=class_hot_mod/resource=$path";
		} else {
			require "php://filter/read=class_hot_mod/resource=$path";
		}
	}

	private $data = '';

	private function modifierData() {
//		$this->resource;
		if (self::$patternsAndCallbacks && is_array(self::$patternsAndCallbacks)) {
			$this->data = preg_replace_callback_array(self::$patternsAndCallbacks, $this->data, self::$limit);
			self::$lastModData = $this->data;
		}
	}

	public function filter($in, $out, &$consumed, $closing) {
		while ($bucket = stream_bucket_make_writeable($in)) {
			$this->data .= $bucket->data;
		}

		// $this->stream contains pointer to the source
		if ($closing || feof($this->stream)) {
			$consumed = strlen($this->data);


//			dbg_log($this->data, 'StreamFilterHotModifier.data');
			$this->modifierData();

			$bucket = stream_bucket_new($this->stream, $this->data);
			stream_bucket_append($out, $bucket);
			return PSFS_PASS_ON;
		}
		return PSFS_FEED_ME;
	}

}
