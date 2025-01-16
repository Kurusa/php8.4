<?php
/**
 * array_find(), array_find_key(), array_any() і array_all()
 *
 * Впроваджені нові функції пошуку масиву, призначені для спрощення процесу пошуку в масивах
 *
 * Кожна функція приймає 2 аргументи - масив, з яким потрібно працювати, і колбек,
 * який потрібно викликати для кожного елемента в масиві
 * Якщо колбек повертає true, він припинить пошук і негайно повернеться,
 * за винятком array_all(), який повертає true, лише якщо всі значення в масиві перевірені колбеком.
 */

// array_find - Знаходить перший елемент у масиві, який задовольняє задану умову
$users = [
    ['name' => 'Alice', 'active' => false],
    ['name' => 'Bob', 'active' => true],
    ['name' => 'Charlie', 'active' => false],
];
$activeUser = array_find($users, fn($user) => $user['active']);
echo "Перший активний користувач: {$activeUser['name']}" . PHP_EOL;

// array_find_key - Знаходить ключ першого елемента, який задовольняє задану умову
$products = [
    'apple' => ['quantity' => 10],
    'banana' => ['quantity' => 0],
    'cherry' => ['quantity' => 15],
];
$outOfStockKey = array_find_key($products, fn($product) => $product['quantity'] === 0);
echo "Продукт, який вичерпано: {$outOfStockKey}" . PHP_EOL;

// array_any - Перевіряє, чи будь-який елемент у масиві задовольняє задану умову
$group = [
    ['name' => 'Eve', 'vip' => false],
    ['name' => 'Frank', 'vip' => true],
    ['name' => 'Grace', 'vip' => false],
];
$hasVip = array_any($group, fn($member) => $member['vip']);
echo "Чи є VIP у групі? " . ($hasVip ? 'Так' : 'Ні') . PHP_EOL;

// array_all - Перевіряє, чи всі елементи в масиві задовольняють задану умову
$notifications = [
    ['message' => 'Welcome!', 'read' => false],
    ['message' => 'New update available', 'read' => true],
    ['message' => 'Your subscription is expiring', 'read' => true],
];
$allRead = array_all($notifications, fn($notification) => $notification['read']);
echo "Чи всі повідомлення прочитані? " . ($allRead ? 'Так' : 'Ні') . PHP_EOL;
