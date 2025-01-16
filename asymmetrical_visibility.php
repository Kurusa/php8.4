<?php
/**
 * Асиметрична видимість
 *
 * Асиметрична видимість дозволяє окремо контролювати доступ до властивостей для читання і запису.
 * Це спрощує код, зменшує потребу в геттерах і сеттерах та підтримує принципи інкапсуляції.
 *
 * Як це працює і навіщо це потрібно:
 *
 * Раніше доступ до властивостей контролювався лише трьома рівнями видимості: public, protected і private.
 * Якщо потрібно було дозволити читання властивості ззовні, але обмежити її запис лише до класу або його нащадків,
 * доводилося використовувати геттери та сеттери. Це призводило до збільшення кількості шаблонного коду.
 *
 * Наприклад, ось як виглядала реалізація без асиметричної видимості:
 */
enum LogLevel: string
{
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}

abstract class AbstractMessage
{
    private const TEMPLATE = '[%s] (%s) %s';

    abstract public function __toString(): string;

    protected function formatMessage(string $message, LogLevel $level): string
    {
        return sprintf(
            self::TEMPLATE,
            (new DateTimeImmutable())->format('c'),
            $level->value,
            $message
        );
    }
}

class DefaultMessage extends AbstractMessage
{
    public function __construct(
        private string $message,
        private LogLevel $level,
    )
    {
    }

    public function __toString(): string
    {
        return $this->formatMessage($this->message, $this->level);
    }
}

class JsonMessage extends DefaultMessage
{
    protected function formatMessage(string $message, LogLevel $level): string
    {
        return json_encode([
            'message' => $message,
            'severity' => $level->value,
            'ts' => (new DateTimeImmutable())->format('c'),
        ]);
    }
}

$message = new DefaultMessage('A basic message', LogLevel::INFO);
$jsonMessage = new JsonMessage('A JSON message', LogLevel::NOTICE);
echo $message . PHP_EOL;
echo $jsonMessage . PHP_EOL;

/**
 * Тут константа TEMPLATE є приватною. До неї можна отримати доступ лише з методів, визначених безпосередньо в AbstractMessage.
 *
 * Стандартний метод formatMessage() захищений, що означає:
 * - Його можна викликати лише в поточному екземплярі або екземплярі розширення класу.
 * - Його можна перевизначити в межах розширення.
 *
 * Оскільки formatMessage() визначено в AbstractMessage, він має доступ до константи TEMPLATE. Це означає, що будь-який дочірній клас,
 * який викликає цей метод, використовуватиме його в контексті AbstractMessage, дозволяючи зберегти доступ до TEMPLATE.
 * Таким чином, метод DefaultMessage::__toString() формує рядок, що відповідає формату шаблону з цієї константи.
 *
 * Тож, що означає термін асиметрична видимість і яку проблему вона вирішує?
 *
 * У контексті ООП повідомлення повинно лише зберігати дані або стан, тоді як форматування повідомлення має бути покладене на інші
 * об’єкти чи механізми. Такий підхід допомагає уникнути змішування відповідальностей і підтримує кращу організацію коду
 */
class Message
{
    public string $message;
    public LogLevel $severity;
    public DateTimeInterface $timestamp;
}

interface Formatter
{
    public function format(Message $message): string;
}

/**
 * Цей підхід виглядає окей на перший погляд, але він не гарантує, що повідомлення завжди буде у валідному стані. Наприклад,
 * немає механізму для перевірки, що повідомлення не є порожнім. Крім того, якщо повідомлення має відображати певний стан,
 * можливість змінювати його властивості після створення об'єкта може призвести до непередбачуваних наслідків
 *
 * Тож ми проводимо рефакторинг. У PHP 8.1 представлено концепцію властивості readonly, яка дає спосіб переконатися,
 * що значення присутнє та не може бути перезаписано:
 */
class MessageReadonly
{
    public function __construct(
        public readonly string $message,
        public readonly LogLevel $severity,
        public readonly DateTimeInterface $timestamp,
    )
    {
    }
}

/**
 * Це розв'язує проблему незмінності, а також гарантує, що у нас є значення для кожної властивості. Але як переконатися,
 * що $message не порожнє?
 *
 * Ми могли б підтвердити це в конструкторі:
 */
class MessageReadonlyValidate
{
    public readonly string $message;

    public function __construct(
        string $message,
        public readonly LogLevel $severity,
        public readonly DateTimeInterface $timestamp,
    )
    {
        if (preg_match('/^\s*$/', $message)) {
            throw new InvalidArgumentException('message must be non-empty');
        }
        $this->message = $message;
    }
}

/**
 * Однак це стає проблемою, коли ми розширюємо клас і перевизначаємо конструктор. Усі властивості все одно будуть існувати,
 * але наша перевірка для $message буде втрачена.
 *
 * Отже, тут ми можемо звернутись до хуків властивостей у PHP 8.4
 */
class MessageHooks
{
    public string $message {
        set (string $value) {
            if (preg_match('/^\s*$/', $value)) {
                throw new InvalidArgumentException('message must be non-empty');
            }
            $this->message = $value;
        }
    }

    public function __construct(
        string $message,
        public readonly LogLevel $severity,
        public readonly DateTimeInterface $timestamp,
    )
    {
        $this->message = $message;
    }
}

/**
 * Тепер значення $message перевірено, але ми втратили незмінність.
 *
 * І це проблема, яку вирішує асиметрична видимість.
 *
 * RFC про асиметричну видимість описує механізм, за допомогою якого властивості можуть мати окрему ("асиметричну") видимість,
 * дозволяючи різну видимість для операцій запису та читання.
 *
 * Синтаксис:
 *
 * {видимість на читання} {видимість на запис}(set) {тип змінної} $name;
 *
 * Майте на увазі:
 * - Якщо видимість на читання не надано, передбачається, що публічний.
 * - Видимість на запис ПОВИННА бути такою ж або меншою, ніж видимість читання.
 * - У поєднанні з хуками, властивості видимості застосовується до відповідних хуків get і set.
 * - Асиметрична видимість вимагає, щоб властивість мала оголошення типу.
 *
 * Ось кілька прикладів:
 *
 * // Загальнодоступний, можливий лише внутрішній запис
 * public private(set) string $message;
 * // Еквівалент
 * private(set) string $message;
 *
 * // Загальнодоступний, доступний для запису з екземплярів і класів розширення
 * public protected(set) string $message;
 * // Еквівалент
 * protected(set) string $message;
 *
 * // Доступний в межах екземпляра та дочірнії, доступний для запису лише в межах визначення екземпляра
 * protected private(set) string $message;
 *
 * Давайте знову перепишемо наш попередній приклад класу Message.
 */
final class MessageAsymmetrical
{
    private(set) string $message {
        set (string $value) {
            if (preg_match('/^\s*$/', $value)) {
                throw new InvalidArgumentException('message must be non-empty');
            }
            $this->message = $value;
        }
    }

    public function __construct(
        string $message,
        public readonly LogLevel $severity,
        public readonly DateTimeInterface $timestamp,
    )
    {
        $this->message = $message;
    }
}

/**
 * Додаючи лише одну річ — private(set) — до властивості $message, ми гарантуємо, що її неможливо перезаписати,
 * окрім як у самому екземплярі. Зробивши сам клас final і не визначаючи жодних додаткових методів, ми зробили його повністю незмінним
 *
 * Масиви:
 *
 * Так само як і з хуками, масиви є проблематичним резервним значенням для асиметричної видимості, і причина полягає в тому,
 * що запис у масив неявно передбачає спочатку отримання посилання. Таким чином, асиметрична видимість забороняє додавати
 * або записувати властивість масиву через загальнодоступний контекст, якщо він не встановлений публічно.
 * Іншими словами, ви не можете зробити так:
 *
 * class Collection {
 *   public private(set) array $items = [];
 * }
 *
 * $collection = new Collection();
 * $collection->items[] = new Item('value');
 *
 * Якби ви змінили декларацію на public set, тоді приклад спрацював би:
 * public public(set) array $items = [];
 *
 * Однак це буде те саме, що просто оголосити масив публічним.
 *
 * Значення "асиметрична видимість", яка надається для значень масиву, полягає в тому, щоб приховати їх і запобігти змінам
 * масиву за межами області дії екземпляра. Як приклад, спираючись на попередній:
 */
class Collection
{
    public private(set) array $items = [];

    public function insert(Item $item): void
    {
        $this->items[] = $item;
    }
}

$collection = new Collection();
$collection->insert(new Item('value'));
$items = $collection->items;

/**
 * Це нібито краще, ніж використання звичайного масиву, оскільки воно забезпечує, що
 * (а) значення, що надходять до екземпляра, є певним типом, і
 * (б) що доступ до властивості завжди повертатиме копію, запобігаючи змінам самого екземпляра.
 */

/**
 * Загалом, у поєднанні з хуками, асиметрична видимість надає класні інструменти для створення об'єктів із контрольованим станом.
 * До PHP 8.1 доводилося докладати зусиль, щоб обмежити доступ до властивостей класу, зазвичай використовуючи
 * методи екземпляра для встановлення та отримання значень.
 * Хоча readonly, представлений у PHP 8.1, став потужним інструментом для створення незмінних структур, він не дуже добре
 * працював з успадкуванням і не вирішував випадки, коли зміна внутрішнього стану була допустимою.
 *
 * Хуки забезпечують можливості для перевірки коректності значення властивості, а також дозволяють визначати та отримувати доступ
 * до "віртуальних" значень; асиметрична видимість дозволяє обмежувати, коли і як значення змінюється.
 * Це поєднання надає величезну функціональність безпосередньо в мові, яка раніше потребувала складних і не завжди вдалих обхідних рішень.
 *
 * Цікаво буде побачити, як розробники будуть використовувати ці дві функції, а також як буде розвиватися мова в майбутньом
 */
