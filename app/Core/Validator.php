<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validador simple. Reglas: required, string, numeric, integer, min, max, in, email, date, nullable.
 * Uso: $v = Validator::make($data, ['ancho_mm' => 'required|numeric|min:200|max:8000']);
 */
final class Validator
{
    /** @var array<string,array<int,string>> */
    private array $errors = [];

    /** @param array<string,mixed> $data */
    private function __construct(private array $data)
    {
    }

    /** @param array<string,mixed> $data @param array<string,string> $rules */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        foreach ($rules as $field => $ruleString) {
            $validator->apply($field, explode('|', $ruleString));
        }
        return $validator;
    }

    /** @param array<int,string> $rules */
    private function apply(string $field, array $rules): void
    {
        $value = $this->data[$field] ?? null;
        $isNullable = in_array('nullable', $rules, true);
        if ($isNullable && ($value === null || $value === '')) {
            return;
        }
        foreach ($rules as $rule) {
            [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
            switch ($name) {
                case 'required':
                    if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === [])) {
                        $this->addError($field, 'Este campo es obligatorio.');
                    }
                    break;
                case 'string':
                    if ($value !== null && !is_string($value)) {
                        $this->addError($field, 'Debe ser texto.');
                    }
                    break;
                case 'numeric':
                    if ($value !== null && $value !== '' && !is_numeric($value)) {
                        $this->addError($field, 'Debe ser un número.');
                    }
                    break;
                case 'integer':
                    if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $this->addError($field, 'Debe ser un número entero.');
                    }
                    break;
                case 'min':
                    if ($value !== null && $value !== '' && is_numeric($value) && (float) $value < (float) $param) {
                        $this->addError($field, 'El valor mínimo es ' . $param . '.');
                    }
                    break;
                case 'max':
                    if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > (float) $param) {
                        $this->addError($field, 'El valor máximo es ' . $param . '.');
                    }
                    break;
                case 'in':
                    $allowed = explode(',', (string) $param);
                    if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                        $this->addError($field, 'Valor no permitido.');
                    }
                    break;
                case 'email':
                    if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                        $this->addError($field, 'Correo electrónico inválido.');
                    }
                    break;
                case 'date':
                    if ($value !== null && $value !== '' && !strtotime((string) $value)) {
                        $this->addError($field, 'Fecha inválida.');
                    }
                    break;
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,array<int,string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $messages) {
            if (isset($messages[0])) {
                return $messages[0];
            }
        }
        return 'Datos inválidos.';
    }
}
