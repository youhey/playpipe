<?php

namespace Tests\Support;

use JsonException;
use PHPUnit\Framework\Assert;

/**
 * OpenAPI component schema に対して JSON payload を検証する軽量 helper。
 */
class OpenApiSchemaValidator
{
    /** @var array<string, mixed> */
    private array $document;

    /**
     * @param array<string, mixed> $document
     */
    private function __construct(array $document)
    {
        $this->document = $document;
    }

    /**
     * JSON-compatible YAML として OpenAPI document を読み込む。
     *
     * @throws JsonException
     */
    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);
        Assert::assertIsString($contents, "OpenAPI file [{$path}] could not be read.");

        $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($document, "OpenAPI file [{$path}] did not decode to an object.");

        /** @var array<string, mixed> $document */
        return new self($document);
    }

    /**
     * @return array<string, mixed>
     */
    public function document(): array
    {
        return $this->document;
    }

    public function validateComponent(string $component, mixed $data): void
    {
        $this->validateSchema($this->componentSchema($component), $data, "#/components/schemas/{$component}");
    }

    /**
     * @return array<string, mixed>
     */
    private function componentSchema(string $component): array
    {
        $schema = data_get($this->document, "components.schemas.{$component}");
        Assert::assertIsArray($schema, "OpenAPI component schema [{$component}] was not found.");

        /** @var array<string, mixed> $schema */
        return $schema;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function validateSchema(array $schema, mixed $data, string $path): void
    {
        $schema = $this->resolveReference($schema);

        if (array_key_exists('enum', $schema)) {
            $enum = $schema['enum'];
            Assert::assertIsArray($enum, "{$path} enum must be an array.");
            Assert::assertContains($data, $enum, "{$path} must be one of the documented enum values.");
        }

        if (array_key_exists('type', $schema)) {
            $this->assertType($schema['type'], $data, $path);
        }

        if ($data === null) {
            return;
        }

        if (($schema['type'] ?? null) === 'object' || array_key_exists('properties', $schema)) {
            $this->validateObject($schema, $data, $path);
        }

        if (($schema['type'] ?? null) === 'array' || array_key_exists('items', $schema)) {
            $this->validateArray($schema, $data, $path);
        }

        if (array_key_exists('minimum', $schema) && (is_int($data) || is_float($data))) {
            Assert::assertGreaterThanOrEqual($schema['minimum'], $data, "{$path} must be greater than or equal to minimum.");
        }

        if (array_key_exists('maxLength', $schema) && is_string($data)) {
            Assert::assertLessThanOrEqual($schema['maxLength'], mb_strlen($data), "{$path} must not exceed maxLength.");
        }

        if (array_key_exists('minLength', $schema) && is_string($data)) {
            Assert::assertGreaterThanOrEqual($schema['minLength'], mb_strlen($data), "{$path} must be at least minLength.");
        }

        if (($schema['format'] ?? null) === 'date-time' && is_string($data)) {
            Assert::assertNotFalse(strtotime($data), "{$path} must be a date-time string.");
        }

        if (($schema['format'] ?? null) === 'uri' && is_string($data)) {
            Assert::assertNotFalse(filter_var($data, FILTER_VALIDATE_URL), "{$path} must be a URI string.");
        }
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function resolveReference(array $schema): array
    {
        $reference = $schema['$ref'] ?? null;

        if (! is_string($reference)) {
            return $schema;
        }

        $prefix = '#/components/schemas/';
        Assert::assertStringStartsWith($prefix, $reference, "Unsupported schema reference [{$reference}].");

        return $this->componentSchema(substr($reference, strlen($prefix)));
    }

    private function assertType(mixed $typeSpec, mixed $data, string $path): void
    {
        $types = is_array($typeSpec) ? $typeSpec : [$typeSpec];

        foreach ($types as $type) {
            if ($this->matchesType($type, $data)) {
                return;
            }
        }

        Assert::fail("{$path} type did not match schema.");
    }

    private function matchesType(mixed $type, mixed $data): bool
    {
        return match ($type) {
            'null' => $data === null,
            'object' => is_array($data) && ! array_is_list($data),
            'array' => is_array($data) && array_is_list($data),
            'string' => is_string($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function validateObject(array $schema, mixed $data, string $path): void
    {
        Assert::assertIsArray($data, "{$path} must be an object.");
        Assert::assertFalse(array_is_list($data), "{$path} must be an object, not a list.");

        $properties = $schema['properties'] ?? [];
        Assert::assertIsArray($properties, "{$path} properties must be an object.");

        $required = $schema['required'] ?? [];
        Assert::assertIsArray($required, "{$path} required must be an array.");

        foreach ($required as $property) {
            Assert::assertIsString($property, "{$path} required property name must be a string.");
            Assert::assertArrayHasKey($property, $data, "{$path}.{$property} is required.");
        }

        if (($schema['additionalProperties'] ?? null) === false) {
            foreach (array_keys($data) as $property) {
                Assert::assertArrayHasKey($property, $properties, "{$path}.{$property} is not defined by the schema.");
            }
        }

        foreach ($properties as $property => $propertySchema) {
            Assert::assertIsString($property, "{$path} property name must be a string.");

            if (! array_key_exists($property, $data)) {
                continue;
            }

            Assert::assertIsArray($propertySchema, "{$path}.{$property} schema must be an object.");
            /** @var array<string, mixed> $propertySchema */
            $this->validateSchema($propertySchema, $data[$property], "{$path}.{$property}");
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function validateArray(array $schema, mixed $data, string $path): void
    {
        Assert::assertIsArray($data, "{$path} must be an array.");
        Assert::assertTrue(array_is_list($data), "{$path} must be a list.");

        if (array_key_exists('minItems', $schema)) {
            Assert::assertGreaterThanOrEqual($schema['minItems'], count($data), "{$path} must contain at least minItems.");
        }

        $itemSchema = $schema['items'] ?? null;

        if ($itemSchema === null) {
            return;
        }

        Assert::assertIsArray($itemSchema, "{$path} items schema must be an object.");

        foreach ($data as $index => $item) {
            /** @var array<string, mixed> $itemSchema */
            $this->validateSchema($itemSchema, $item, "{$path}[{$index}]");
        }
    }
}
