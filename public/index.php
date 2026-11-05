<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Service/CatalogService.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$serviceError = null;
try {
    $catalog = new CatalogService(
        __DIR__ . '/../dataset/products.json',
        __DIR__ . '/../dataset/categories.json',
        __DIR__ . '/../dataset/class_requirements.csv'
    );
} catch (Throwable $exception) {
    $serviceError = $exception->getMessage();
    $catalog = null;
}

$selectedCategory = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;
if ($selectedCategory === false || $selectedCategory === 0) {
    $selectedCategory = null;
}

if ($catalog !== null && $selectedCategory !== null) {
    $validCategoryIds = array_map(
        static fn (Category $category): int => $category->getId(),
        $catalog->getCategories()
    );
    if (!in_array($selectedCategory, $validCategoryIds, true)) {
        $selectedCategory = null;
    }
}

$products = $catalog !== null ? $catalog->getProducts($selectedCategory) : [];
$categories = $catalog !== null ? $catalog->getCategories() : [];
$requirements = $catalog !== null ? $catalog->getClassRequirements() : [];
$discountExample = $catalog !== null ? $catalog->getDiscountExample() : null;

$constructorCheck = 'Не выполнена';
try {
    new Product(0, '', -10, 0);
} catch (InvalidArgumentException $exception) {
    $constructorCheck = 'Пройдено: ' . $exception->getMessage();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Практическая работа № 5 — PHP ООП</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php"><span class="brand-mark">05</span> Каталог объектов</a>
    <span class="topbar-note">ТРПО · Практическая работа № 5</span>
</header>
<main class="container">
    <section class="hero">
        <div class="eyebrow">PHP ООП · КЛАССЫ · КОНСТРУКТОРЫ</div>
        <h1>Каталог товаров</h1>
        <p>Каталог построен на объектах Product и Category. CatalogService загружает JSON, связывает товары с категориями и выполняет операции над коллекцией.</p>
        <div class="hero-tags"><span>Product</span><span>Category</span><span>CatalogService</span></div>
    </section>

    <?php if ($serviceError !== null): ?><div class="alert alert-error"><?= e($serviceError) ?></div><?php endif; ?>

    <section class="stats">
        <article class="stat-card"><span>Всего товаров</span><strong><?= $catalog?->getProductCount() ?? 0 ?></strong><small>Из products.json</small></article>
        <article class="stat-card"><span>Активных товаров</span><strong><?= $catalog?->getActiveProductCount() ?? 0 ?></strong><small>С учётом поля active</small></article>
        <article class="stat-card"><span>Средняя цена</span><strong><?= $catalog !== null ? e(number_format($catalog->getAveragePrice(), 2, ',', ' ')) . ' ₽' : '—' ?></strong><small>Рассчитана сервисом</small></article>
    </section>

    <section class="panel">
        <div class="section-heading"><div><div class="eyebrow">КОЛЛЕКЦИЯ ОБЪЕКТОВ</div><h2>Товары</h2></div><span class="counter"><?= count($products) ?> позиций</span></div>
        <form class="filter-form" method="get" action="index.php">
            <label for="category">Категория</label>
            <select id="category" name="category">
                <option value="">Все категории</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category->getId() ?>" <?= $selectedCategory === $category->getId() ? 'selected' : '' ?>><?= e($category->getName()) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button-secondary" type="submit">Фильтровать</button>
            <?php if ($selectedCategory !== null): ?><a class="text-link" href="index.php">Сбросить</a><?php endif; ?>
        </form>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <div class="product-top"><span class="product-id">#<?= $product->getId() ?></span><span class="status <?= $product->isActive() ? 'status-ok' : 'status-muted' ?>"><?= e($product->availabilityLabel()) ?></span></div>
                    <div class="product-icon"><?= $product->getId() % 3 === 0 ? '◈' : ($product->getId() % 3 === 1 ? '▦' : '⌘') ?></div>
                    <h3><?= e($product->getName()) ?></h3>
                    <p class="category-name"><?= e($catalog->getCategoryName($product->getCategoryId())) ?></p>
                    <strong class="price"><?= e($product->formattedPrice()) ?></strong>
                    <small class="product-method">Product::belongsToCategory() · Product::isActive()</small>
                </article>
            <?php endforeach; ?>
            <?php if ($products === []): ?><p class="empty">В этой категории пока нет товаров.</p><?php endif; ?>
        </div>
        <p class="hint">Названия и цены сохранены в том виде, в котором они предоставлены в <code>products.json</code>.</p>
    </section>

    <section class="two-columns">
        <article class="panel">
            <div class="eyebrow">МОДЕЛИ ПРЕДМЕТНОЙ ОБЛАСТИ</div><h2>Требования к классам</h2>
            <div class="table-wrap">
                <table><thead><tr><th>Класс</th><th>Свойство</th><th>Тип</th><th>Обязательно</th></tr></thead>
                    <tbody>
                    <?php foreach ($requirements as $requirement): ?>
                        <tr><td><strong><?= e($requirement['class'] ?? '') ?></strong></td><td><?= e($requirement['property'] ?? '') ?></td><td><code><?= e($requirement['type'] ?? '') ?></code></td><td><?= e($requirement['required'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="hint">Таблица прочитана из <code>class_requirements.csv</code> и использована при проектировании свойств моделей.</p>
        </article>

        <article class="panel">
            <div class="eyebrow">ПРОВЕРКА ПОВЕДЕНИЯ</div><h2>Проверка конструктора</h2>
            <div class="check-box"><span class="check-icon">✓</span><div><strong>Некорректный Product отклонён</strong><p><?= e($constructorCheck) ?></p></div></div>
            <?php if ($discountExample !== null): ?>
                <div class="calculation">
                    <span>Пример предметного метода: скидка 10%</span><strong><?= e($discountExample['product']->getName()) ?></strong>
                    <div class="calculation-values"><span><?= e($discountExample['product']->formattedPrice()) ?></span><b><?= e(number_format($discountExample['discounted_price'], 2, ',', ' ')) ?> ₽</b></div>
                    <small>Расчёт выполнен методом priceAfterDiscount(), HTML не вычисляет цену самостоятельно.</small>
                </div>
            <?php endif; ?>
        </article>
    </section>
    <footer class="footer">Практическая работа № 5 · Модель хранит данные и поведение, сервис управляет коллекцией, HTML отвечает за отображение.</footer>
</main>
</body>
</html>
