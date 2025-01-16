<?php
/**
 * PHP 8.4 додає новий спосіб оголошення застарілих методів, функцій та констант через атрибут #[\Deprecated].
 * Це дозволяє чітко вказати повідомлення про застарілість, а також дату чи версію, з якої елемент вважається застарілим.
 *
 * Цей підхід спрощує підтримку коду, надаючи розробникам інструменти для роботи з застарілим функціоналом
 * і чітко вказуючи, які методи або функції слід замінити.
 *
 * Раніше ви вже могли використовувати docblock, наприклад @deprecated, але сам PHP нічого з ним не робив.
 * У той час, як статичні аналізатори та IDE могли інтерпретувати ці doc-блоки, вам знадобилися б зовнішні інструменти,
 * щоб переконатися, що всі застарілі засоби користувача були виявлені.
 */
class PhpVersion
{
    // Метод позначено як застарілий із повідомленням та вказівкою на версію, починаючи з якої він застарів
    #[\Deprecated(
        message: "Використовуйте PhpVersion::getVersion() замість цього",
        since: "8.4",
    )]
    public function getPhpVersion(): string
    {
        return $this->getVersion();
    }

    // Актуальний метод
    public function getVersion(): string
    {
        return '8.4';
    }
}

$phpVersion = new PhpVersion();

// Виклик застарілого методу видасть попередження
// Deprecated: Method PhpVersion::getPhpVersion() is deprecated since 8.4, use PhpVersion::getVersion() instead
echo $phpVersion->getPhpVersion() . PHP_EOL;

// Виклик актуального методу
echo $phpVersion->getVersion() . PHP_EOL;

/**
 * Інший приклад: застаріла константа
 */
class Config
{
    #[\Deprecated(
        message: "Використовуйте Config::NEW_SETTING замість цього",
        since: "8.4"
    )]
    public const OLD_SETTING = 'deprecated_value';

    public const NEW_SETTING = 'new_value';
}

// Виклик застарілої константи
// Deprecated: Constant Config::OLD_SETTING is deprecated since 8.4, use Config::NEW_SETTING instead
echo Config::OLD_SETTING . PHP_EOL;

echo Config::NEW_SETTING . PHP_EOL;
