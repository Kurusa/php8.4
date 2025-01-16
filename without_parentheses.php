<?php
/**
 * Доступ до методів і властивостей нових об'єктів без дужок
 *
 * Було додано можливість викликати методи або отримувати доступ до властивостей новоствореного об'єкта
 * без необхідності обгортати виклик у дужки. Це зменшує кількість шаблонного коду та робить синтаксис
 * більш лаконічним і зрозумілим.
 *
 * Раніше в PHP:
 */
class User
{
    public function getName(): string
    {
        return 'John Doe';
    }

    public function getProfile(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com'
        ];
    }
}

// Використання до PHP 8.4
$userName = (new User())->getName();
$profile = (new User())->getProfile();

echo "User name: $userName" . PHP_EOL;
print_r($profile);

/**
 * У PHP 8.4:
 */
$userName = new User()->getName();
$profile = new User()->getProfile();

echo "User name: $userName" . PHP_EOL;
print_r($profile);

/**
 * Переваги:
 * - Зменшується кількість символів, роблячи код більш читабельним.
 * - Синтаксис виглядає природніше і схожий на інші сучасні мови програмування.
 *
 * Приклад використання з додатковими методами:
 */
class Product
{
    public function getName(): string
    {
        return 'Laptop';
    }

    public function getPrice(): float
    {
        return 1500.00;
    }

    public function applyDiscount(float $discount): self
    {
        echo "Applying discount of {$discount}%\n";
        return $this;
    }
}

$productName = new Product()->applyDiscount(10)->getName();
$productPrice = new Product()->getPrice();

echo "Product: $productName" . PHP_EOL;
echo "Price: $productPrice" . PHP_EOL;
