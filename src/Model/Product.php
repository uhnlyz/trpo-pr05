<?php
declare(strict_types=1);

final class Product
{
    private int $id;
    private string $name;
    private float $price;
    private int $categoryId;
    private bool $active;

    public function __construct(int $id, string $name, float $price, int $categoryId, bool $active = true)
    {
        $name = trim($name);

        if ($id <= 0) {
            throw new InvalidArgumentException('Идентификатор товара должен быть положительным.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Название товара не может быть пустым.');
        }
        if ($price < 0) {
            throw new InvalidArgumentException('Цена товара не может быть отрицательной.');
        }
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('У товара должна быть корректная категория.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->categoryId = $categoryId;
        $this->active = $active;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function getCategoryId(): int { return $this->categoryId; }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function belongsToCategory(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    public function priceAfterDiscount(float $percent): float
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Скидка должна быть от 0 до 100 процентов.');
        }

        return $this->price * (1 - $percent / 100);
    }

    public function availabilityLabel(): string
    {
        return $this->active ? 'Активен' : 'Неактивен';
    }

    public function formattedPrice(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ₽';
    }
}
