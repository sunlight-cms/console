<?php declare(strict_types=1);

namespace SunlightConsole;

class JsonObject extends \ArrayObject
{
    private const DEFAULT_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /** @var array|null */
    private $origData;
    /** @var string|null */
    private $path;

    private function __construct(array $data, ?string $path = null)
    {
        parent::__construct($data);

        $this->origData = ($path !== null ? $data : null);
        $this->path = $path;
    }

    static function fromFile(string $path): self
    {
        $data = @file_get_contents($path);

        if ($data === false) {
            throw new \Exception(sprintf('Could not load "%s"', $path));
        }

        $data = json_decode($data, true);

        if ($data === null) {
            throw new \Exception(sprintf('Could not parse JSON from "%s"', $path));
        }

        if (!is_array($data)) {
            throw new \Exception(sprintf('JSON data in "%s" must be an array or an object', $path));
        }

        return new self($data, $path);
    }

    static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if ($data === null) {
            throw new \Exception('Could not parse JSON');
        }

        if (!is_array($data)) {
            throw new \Exception('JSON data must be an array or an object');
        }

        return new self($data);
    }

    static function fromData(array $data): self
    {
        return new self($data);
    }

    function encode(int $flags = self::DEFAULT_FLAGS): string
    {
        $json = json_encode($this->getArrayCopy(), $flags);

        if ($json === false) {
            throw new \Exception(sprintf('Could not encode JSON: %s', json_last_error_msg()));
        }

        return $json;
    }

    function save(int $flags = self::DEFAULT_FLAGS): void
    {
        if ($this->path === null) {
            throw new \LogicException('Cannot save changes to JSON file with no path');
        }

        if ($this->getArrayCopy() === $this->origData) {
            return; // no changes made
        }

        $json = $this->encode($flags);

        if (file_put_contents($this->path, $json, LOCK_EX) !== strlen($json)) {
            throw new \Exception(sprintf('Could not fully write "%s"', $this->path));
        }

        $this->origData = $this->getArrayCopy();
    }
}
