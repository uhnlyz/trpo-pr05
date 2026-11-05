<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Product.php';
require_once __DIR__ . '/../Model/Category.php';

final class CatalogService
{
    /** @var Product[] */
    private array $products = [];

    /** @var Category[] */
    private array $categories = [];

    private array $classRequirements = [];

    public function __construct(string $productsPath, string $categoriesPath, string $requirementsPath)
    {
        foreach ($this->readJson($categoriesPath) as $row) {
            $this->categories[] = new Category((int) $row['id'], (string) $row['name']);
        }

        foreach ($this->readJson($productsPath) as $row) {
            $this->products[] = new Product(
                (int) $row['id'],
                (string) $row['name'],
                (float) $row['price'],
                (int) $row['category_id'],
                (bool) ($row['active'] ?? true)
            );
        }

        $this->classRequirements = $this->readRequirements($requirementsPath);
    }

    private function readJson(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Не найден файл: ' . basename($path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Не удалось прочитать файл: ' . basename($path));
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Файл должен содержать JSON-массив: ' . basename($path));
        }

        return $data;
    }

    private function readRequirements(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Не найден class_requirements.csv.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось открыть class_requirements.csv.');
        }

        $headers = fgetcsv($handle, 0, ';');
        if ($headers !== false && isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? $headers[0];
        }

        $requirements = [];

        while (($values = fgetcsv($handle, 0, ';')) !== false) {
            if ($headers !== false && count($headers) === count($values)) {
                $requirements[] = array_combine($headers, $values);
            }
        }

        fclose($handle);
        return $requirements;
    }

    /** @return Product[] */
    public function getProducts(?int $categoryId = null): array
    {
        if ($categoryId === null) {
            return $this->products;
        }

        return array_values(array_filter(
            $this->products,
            static fn (Product $product): bool => $product->belongsToCategory($categoryId)
        ));
    }

    /** @return Category[] */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getCategoryName(int $categoryId): string
    {
        foreach ($this->categories as $category) {
            if ($category->matches($categoryId)) {
                return $category->getName();
            }
        }

        return 'Категория не найдена';
    }

    public function getProductCount(): int
    {
        return count($this->products);
    }

    public function getActiveProductCount(): int
    {
        return count(array_filter(
            $this->products,
            static fn (Product $product): bool => $product->isActive()
        ));
    }

    public function getAveragePrice(): float
    {
        if ($this->products === []) {
            return 0.0;
        }

        $sum = array_reduce(
            $this->products,
            static fn (float $total, Product $product): float => $total + $product->getPrice(),
            0.0
        );

        return $sum / count($this->products);
    }

    public function getClassRequirements(): array
    {
        return $this->classRequirements;
    }

    public function getDiscountExample(): ?array
    {
        $product = $this->products[0] ?? null;
        if ($product === null) {
            return null;
        }

        return [
            'product' => $product,
            'discount_percent' => 10,
            'discounted_price' => $product->priceAfterDiscount(10),
        ];
    }
}
