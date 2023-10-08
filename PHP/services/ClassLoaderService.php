<?php

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * Description: Singleton Factory class that creates singleton classes that can be reused during code execution
 * without re-initializing a new class of its kind, and without clashing with circular dependency injection. 
 */
class ClassLoaderService {
    private $loadedClasses = [];
    private $authorizationService;

    function __construct(AuthorizationService $authorizationService = null) {
        $this->authorizationService = $authorizationService;
    }

    /**
     * Function that checks if an instance of a class already exists, if so return it
     * if not create an instance of the class. Returns an instance of said class.
     * @template T of object
     * @param class-string<T> $classInstanceName
     * @param string $identifier
     * @return T|null 
     */
    function getClassInstance(mixed $classInstanceName, string $identifier = null): ?object {
        foreach ($this->loadedClasses as $loadedClass) {
            if ($identifier && $loadedClass['id'] === $identifier) return $loadedClass['class'];
            else if (!$identifier && $loadedClass['class']::class === $classInstanceName) return $loadedClass['class'];
        }
        return $this->loadClass($classInstanceName, $identifier)['class'];
    }

    /**
     * Function that creates an instance of the class and adds it to the loadedClasses arr.
     * Returns an array containing instance of said class and id.
     * @template T of object
     * @param class-string<T> $classInstance
     * @param string $identifier
     * @return T 
     */
    private function loadClass(mixed $classInstance, string $identifier = null): array {
        $classEntry = [
            'class' => new $classInstance($this->authorizationService),
            'id' => $identifier
        ];
        $this->loadedClasses[] = $classEntry;
        return $classEntry;
    }
}
