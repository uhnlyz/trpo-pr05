<?php
declare(strict_types=1);

final class Category
{
    private int $id;
    private string $name;

    public function __construct(int $id, string $name)
    {
        $name = trim($name);

        if ($id <= 0) {
            throw new InvalidArgumentException('Идентификатор категории должен быть положительным.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Название категории не может быть пустым.');
        }

        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }

    public function matches(int $categoryId): bool
    {
        return $this->id === $categoryId;
    }
}
