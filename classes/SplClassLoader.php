<?php

if (!class_exists('SplClassLoader')) {

/**
 * SplClassLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 * http://groups.google.com/group/php-standards/web/psr-0-final-proposal
 *
 *     // Example which loads classes for the Doctrine Common package in the
 *     // Doctrine\Common namespace.
 *     $classLoader = new SplClassLoader('Doctrine\Common', '/path/to/doctrine');
 *     $classLoader->register();
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * @author Moritz Schmidt <fusselwurm@gmail.com>
 *
 */
class SplClassLoader {
	private $_fileExtension = '.php';
	private $_namespace;
	private $_includePath;
	private $_namespaceSeparator = '\\';

	/**
	 * @var bool
	 */
	private $_checkFileExists = false;

	/**
	 * Creates a new <tt>SplClassLoader</tt> that loads classes of the
	 * specified namespace.
	 *
	 * @param string $ns The namespace to use.
	 * @param string $includePath (optional) base path for namespaced classes
	 */
	public function __construct($ns = null, $includePath = null) {
		$this->_namespace = $ns;
		$this->_includePath = $includePath;
	}

	/**
	 * Get namespace this autoloader is responsible for
	 *
	 * @return null|string
	 */
	public function getNamespace() {
		return $this->_namespace;
	}

	/**
	 * Sets the namespace separator used by classes in the namespace of this class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function setNamespaceSeparator($sep) {
		$this->_namespaceSeparator = $sep;
	}

	/**
	 * Gets the namespace seperator used by classes in the namespace of this class loader.
	 *
	 * @return string
	 */
	public function getNamespaceSeparator() {
		return $this->_namespaceSeparator;
	}

	/**
	 * Sets the base include path for all class files in the namespace of this class loader.
	 *
	 * @param string $includePath
	 */
	public function setIncludePath($includePath) {
		$this->_includePath = $includePath;
	}

	/**
	 * Gets the base include path for all class files in the namespace of this class loader.
	 *
	 * @return string $includePath
	 */
	public function getIncludePath() {
		return $this->_includePath;
	}

	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 *
	 * @param string $fileExtension
	 */
	public function setFileExtension($fileExtension) {
		$this->_fileExtension = $fileExtension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $fileExtension
	 */
	public function getFileExtension() {
		return $this->_fileExtension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 *
	 * @param boolean $prepend
	 */
	public function register($prepend = false) {
		spl_autoload_register(
			array($this, 'loadClass'), true, $prepend);
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $className The name of the class to load.
	 * @param bool $check [optional] If the function should return false
	 *                    on failure to find the class file, instead of
	 *                    requiring the non-existant file
	 * @return boolean
	 */
	public function loadClass($className, $check = false) {
		if (null === $this->_namespace || $this->_namespace . $this->_namespaceSeparator === substr($className, 0, strlen($this->_namespace . $this->_namespaceSeparator))) {
			$fileName = '';

			if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
				$namespace = substr($className, 0, $lastNsPos);
				$className = substr($className, $lastNsPos + 1);
				$fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
			}
			$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;
			$fileName = ($this->_includePath !== null
					? $this->_includePath . DIRECTORY_SEPARATOR
					: '') . $fileName;

			if (($check || $this->_checkFileExists) && !file_exists($fileName)) {
				return false;
			}
			require $fileName;
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function getCheckFileExists() {
		return $this->_checkFileExists;
	}

	/**
	 * @param bool $checkFileExists
	 */
	public function setCheckFileExists($checkFileExists) {
		$this->_checkFileExists = $checkFileExists;
	}
}

}