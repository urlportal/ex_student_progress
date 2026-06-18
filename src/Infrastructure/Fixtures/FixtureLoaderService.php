<?php

namespace App\Infrastructure\Fixtures;

use App\Domain\Entity\Course;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Module;
use App\Domain\Entity\Skill;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @phpstan-type LessonData array{title: string, tasks: list<string>}
 * @phpstan-type ModuleData array{title: string, lessons: list<LessonData>}
 * @phpstan-type CourseData array{title: string, description: string, modules: list<ModuleData>, lessons: list<LessonData>}
 */
class FixtureLoaderService
{
    private const SKILLS = [
        'Аудирование',
        'Говорение',
        'Чтение',
        'Письмо',
        'Грамматика',
        'Лексика',
        'Произношение',
        'Перевод',
    ];

    /**
     * @var list<CourseData>
     */
    private const COURSES = [
        // Course 1: Английский язык: уровень A1 — 3 модуля, 4 занятия/модуль, 4 задания/занятие
        [
            'title' => 'Английский язык: уровень A1',
            'description' => 'Курс для начинающих. Базовые конструкции, словарный запас и произношение.',
            'modules' => [
                [
                    'title' => 'Введение в язык',
                    'lessons' => [
                        ['title' => 'Алфавит и произношение', 'tasks' => ['Прослушать алфавит', 'Повторить буквы', 'Читать слоги', 'Написать буквы']],
                        ['title' => 'Числа и счёт', 'tasks' => ['Прослушать числа 1–10', 'Назвать числа вслух', 'Прочитать числа 11–20', 'Написать числа']],
                        ['title' => 'Цвета и формы', 'tasks' => ['Прослушать цвета', 'Назвать цвета по картинке', 'Читать названия форм', 'Описать картинку']],
                        ['title' => 'Дни недели и месяцы', 'tasks' => ['Прослушать дни недели', 'Произнести дни недели', 'Прочитать месяцы', 'Написать текущую дату']],
                    ],
                ],
                [
                    'title' => 'Базовая грамматика',
                    'lessons' => [
                        ['title' => 'Артикли и существительные', 'tasks' => ['Прослушать примеры', 'Произнести фразы с артиклем', 'Прочитать текст', 'Вставить артикли']],
                        ['title' => 'Глагол to be', 'tasks' => ['Прослушать диалог', 'Составить предложения с to be', 'Прочитать упражнение', 'Написать предложения']],
                        ['title' => 'Местоимения', 'tasks' => ['Прослушать примеры', 'Произнести местоимения', 'Прочитать текст', 'Заменить существительные местоимениями']],
                        ['title' => 'Present Simple', 'tasks' => ['Прослушать диалог', 'Ответить на вопросы устно', 'Прочитать правило', 'Написать предложения']],
                    ],
                ],
                [
                    'title' => 'Повседневное общение',
                    'lessons' => [
                        ['title' => 'Приветствие и знакомство', 'tasks' => ['Прослушать диалог', 'Разыграть диалог знакомства', 'Прочитать фразы вежливости', 'Написать короткое письмо']],
                        ['title' => 'В магазине', 'tasks' => ['Прослушать диалог в магазине', 'Спросить о цене товара', 'Прочитать список товаров', 'Составить список покупок']],
                        ['title' => 'В кафе и ресторане', 'tasks' => ['Прослушать заказ в кафе', 'Сделать заказ самостоятельно', 'Прочитать меню', 'Написать заказ письменно']],
                        ['title' => 'Транспорт и направления', 'tasks' => ['Прослушать инструкции пути', 'Спросить дорогу', 'Прочитать карту маршрута', 'Написать маршрут']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 2: Английский язык: уровень A2 — 3 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Английский язык: уровень A2',
            'description' => 'Элементарный уровень. Расширение словарного запаса и грамматических структур.',
            'modules' => [
                [
                    'title' => 'Грамматика A2',
                    'lessons' => [
                        ['title' => 'Past Simple', 'tasks' => ['Прослушать рассказ о прошлом', 'Рассказать о своих выходных', 'Написать историю в прошедшем времени']],
                        ['title' => 'Future Simple', 'tasks' => ['Прослушать планы на будущее', 'Составить свои планы вслух', 'Написать о своих планах']],
                        ['title' => 'Вопросительные предложения', 'tasks' => ['Прослушать вопросы из диалога', 'Задать вопросы партнёру', 'Прочитать и дополнить диалог']],
                    ],
                ],
                [
                    'title' => 'Лексика по темам',
                    'lessons' => [
                        ['title' => 'Семья и отношения', 'tasks' => ['Прослушать рассказ о семье', 'Рассказать о своей семье', 'Прочитать статью о семье']],
                        ['title' => 'Работа и профессии', 'tasks' => ['Прослушать монолог о работе', 'Описать свою профессию', 'Написать краткое резюме']],
                        ['title' => 'Хобби и увлечения', 'tasks' => ['Прослушать рассказ об увлечениях', 'Рассказать о своём хобби', 'Прочитать текст об увлечениях']],
                    ],
                ],
                [
                    'title' => 'Разговорная практика A2',
                    'lessons' => [
                        ['title' => 'В аэропорту', 'tasks' => ['Прослушать объявления в аэропорту', 'Пройти регистрацию в ролевой игре', 'Прочитать инструкцию на посадочном талоне']],
                        ['title' => 'В гостинице', 'tasks' => ['Прослушать разговор на ресепшн', 'Забронировать номер в ролевой игре', 'Написать запрос на бронирование']],
                        ['title' => 'На почте и в банке', 'tasks' => ['Прослушать диалог в банке', 'Провести банковскую операцию в ролевой игре', 'Заполнить форму перевода']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 3: Английский язык: уровень B1 — 3 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Английский язык: уровень B1',
            'description' => 'Средний уровень. Сложные грамматические конструкции и развитие всех навыков.',
            'modules' => [
                [
                    'title' => 'Грамматика B1',
                    'lessons' => [
                        ['title' => 'Present Perfect', 'tasks' => ['Прослушать текст с Present Perfect', 'Составить предложения с опытом', 'Написать эссе об опыте']],
                        ['title' => 'Условные предложения', 'tasks' => ['Прослушать примеры условных конструкций', 'Составить условные предложения вслух', 'Написать сочинение с условиями']],
                        ['title' => 'Пассивный залог', 'tasks' => ['Прослушать текст в пассивном залоге', 'Преобразовать предложения из активного в пассивный', 'Прочитать статью в пассивном залоге']],
                    ],
                ],
                [
                    'title' => 'Профессиональное общение',
                    'lessons' => [
                        ['title' => 'Деловая переписка', 'tasks' => ['Прослушать деловой диалог', 'Провести переговоры в ролевой игре', 'Написать деловое письмо']],
                        ['title' => 'Презентации на английском', 'tasks' => ['Прослушать образцовую презентацию', 'Провести мини-презентацию', 'Написать план презентации']],
                        ['title' => 'Телефонные переговоры', 'tasks' => ['Прослушать деловой звонок', 'Провести переговоры по телефону', 'Написать заметки по итогам звонка']],
                    ],
                ],
                [
                    'title' => 'Тексты и медиа',
                    'lessons' => [
                        ['title' => 'Газетные статьи', 'tasks' => ['Прослушать новостной выпуск', 'Обсудить статью из газеты', 'Написать рецензию на статью']],
                        ['title' => 'Художественная литература', 'tasks' => ['Прослушать отрывок из книги', 'Пересказать прочитанный текст', 'Написать отзыв о книге']],
                        ['title' => 'Документальные фильмы', 'tasks' => ['Прослушать фрагмент документального фильма', 'Обсудить тему документального фильма', 'Написать аннотацию к фильму']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 4: Английский язык: уровень B2 — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Английский язык: уровень B2',
            'description' => 'Выше среднего. Академический язык, сложные тексты и профессиональное общение.',
            'modules' => [
                [
                    'title' => 'Академический английский',
                    'lessons' => [
                        ['title' => 'Академическое письмо', 'tasks' => ['Прослушать лекцию по академическому письму', 'Написать академическое эссе', 'Рецензировать чужую работу']],
                        ['title' => 'Критическое мышление', 'tasks' => ['Прослушать дискуссию', 'Привести аргументы за и против', 'Написать аналитическую статью']],
                        ['title' => 'Сложные аутентичные тексты', 'tasks' => ['Прослушать научный доклад', 'Пересказать содержание доклада', 'Написать конспект доклада']],
                    ],
                ],
                [
                    'title' => 'Профессиональная коммуникация B2',
                    'lessons' => [
                        ['title' => 'Собеседование на английском', 'tasks' => ['Прослушать образцовое интервью', 'Пройти симуляцию собеседования', 'Написать сопроводительное письмо']],
                        ['title' => 'Конференции и форумы', 'tasks' => ['Прослушать доклад на конференции', 'Выступить с коротким докладом', 'Написать тезисы доклада']],
                        ['title' => 'Технические тексты', 'tasks' => ['Прослушать техническое объяснение', 'Объяснить технический термин своими словами', 'Перевести технический текст']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 5: Деловой английский — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Деловой английский',
            'description' => 'Английский для бизнеса: переговоры, деловая переписка, презентации.',
            'modules' => [
                [
                    'title' => 'Деловое общение',
                    'lessons' => [
                        ['title' => 'Бизнес-встречи', 'tasks' => ['Прослушать запись бизнес-встречи', 'Провести встречу в ролевой игре', 'Написать протокол встречи']],
                        ['title' => 'Переговоры', 'tasks' => ['Прослушать деловые переговоры', 'Вести переговоры с партнёром', 'Написать меморандум о договорённостях']],
                        ['title' => 'Маркетинг и реклама', 'tasks' => ['Прослушать рекламный ролик', 'Представить продукт потенциальному клиенту', 'Написать маркетинговое описание продукта']],
                    ],
                ],
                [
                    'title' => 'Деловая документация',
                    'lessons' => [
                        ['title' => 'Контракты и соглашения', 'tasks' => ['Прослушать объяснение юридических терминов', 'Обсудить условия контракта', 'Перевести ключевые пункты договора']],
                        ['title' => 'Финансовые отчёты', 'tasks' => ['Прослушать финансовый отчёт', 'Представить финансовые данные', 'Написать аналитику по отчёту']],
                        ['title' => 'Корпоративная переписка', 'tasks' => ['Прослушать примеры деловых писем', 'Ответить на деловое письмо', 'Написать корпоративный запрос']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 6: Разговорный английский — 0 модулей, 4 прямых занятия, 3 задания/занятие
        [
            'title' => 'Разговорный английский',
            'description' => 'Практика устной речи: светские беседы, дискуссии, истории и идиомы.',
            'modules' => [],
            'lessons' => [
                ['title' => 'Светские беседы', 'tasks' => ['Прослушать образец светской беседы', 'Поддержать беседу с собеседником', 'Описать ситуацию из жизни']],
                ['title' => 'Дискуссии и дебаты', 'tasks' => ['Прослушать запись дебатов', 'Отстоять свою позицию в споре', 'Написать аргументы для дебатов']],
                ['title' => 'Рассказывание историй', 'tasks' => ['Прослушать рассказ на английском', 'Рассказать историю из своей жизни', 'Написать короткий рассказ']],
                ['title' => 'Юмор и идиомы', 'tasks' => ['Прослушать анекдоты и идиоматические выражения', 'Использовать идиомы в разговоре', 'Написать диалог с идиомами']],
            ],
        ],
        // Course 7: Немецкий язык: уровень A1 — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Немецкий язык: уровень A1',
            'description' => 'Начальный немецкий. Алфавит, базовые фразы и простые грамматические конструкции.',
            'modules' => [
                [
                    'title' => 'Основы немецкого',
                    'lessons' => [
                        ['title' => 'Алфавит и умлауты', 'tasks' => ['Прослушать немецкий алфавит', 'Произнести умлауты ä, ö, ü', 'Написать слова с умлаутами']],
                        ['title' => 'Приветствие и прощание', 'tasks' => ['Прослушать приветственный диалог', 'Поздороваться и представиться', 'Написать фразы приветствия']],
                        ['title' => 'Числа и время', 'tasks' => ['Прослушать числа по-немецки', 'Назвать текущее время', 'Написать числа от 1 до 20']],
                    ],
                ],
                [
                    'title' => 'Начальная грамматика немецкого',
                    'lessons' => [
                        ['title' => 'Артикли der, die, das', 'tasks' => ['Прослушать примеры с артиклями', 'Назвать артикли существительных', 'Написать существительные с артиклями']],
                        ['title' => 'Глагол sein (быть)', 'tasks' => ['Прослушать предложения с sein', 'Составить предложения с sein', 'Написать текст о себе']],
                        ['title' => 'Семья и описание людей', 'tasks' => ['Прослушать рассказ о семье', 'Описать членов своей семьи', 'Написать о своей семье']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 8: Немецкий язык: уровень A2 — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Немецкий язык: уровень A2',
            'description' => 'Элементарный немецкий. Разговорные темы, падежи и повседневные ситуации.',
            'modules' => [
                [
                    'title' => 'Грамматика A2 (немецкий)',
                    'lessons' => [
                        ['title' => 'Падежи: Nominativ и Akkusativ', 'tasks' => ['Прослушать примеры падежей', 'Определить падеж существительного', 'Написать предложения с падежами']],
                        ['title' => 'Модальные глаголы', 'tasks' => ['Прослушать диалог с модальными глаголами', 'Выразить возможность и желание', 'Написать текст с модальными глаголами']],
                        ['title' => 'Претерит haben и sein', 'tasks' => ['Прослушать рассказ в прошедшем времени', 'Рассказать о прошлых событиях', 'Написать историю в претерите']],
                    ],
                ],
                [
                    'title' => 'Разговорные темы A2',
                    'lessons' => [
                        ['title' => 'Покупки и магазины', 'tasks' => ['Прослушать диалог в магазине', 'Сделать покупку в ролевой игре', 'Написать список покупок']],
                        ['title' => 'Путешествия и транспорт', 'tasks' => ['Прослушать объявление на вокзале', 'Купить билет в ролевой игре', 'Написать маршрут поездки']],
                        ['title' => 'Здоровье и самочувствие', 'tasks' => ['Прослушать разговор с врачом', 'Описать симптомы заболевания', 'Написать рецепт от врача']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 9: Немецкий язык: уровень B1 — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Немецкий язык: уровень B1',
            'description' => 'Средний немецкий. Сложные тексты, деловое общение и культурный контекст.',
            'modules' => [
                [
                    'title' => 'Грамматика B1 (немецкий)',
                    'lessons' => [
                        ['title' => 'Konjunktiv II', 'tasks' => ['Прослушать диалог с Konjunktiv II', 'Выразить желание и предположение', 'Написать просьбу в вежливой форме']],
                        ['title' => 'Пассивный залог', 'tasks' => ['Прослушать текст с пассивным залогом', 'Преобразовать фразы в пассив', 'Написать инструкцию в пассивном залоге']],
                        ['title' => 'Придаточные предложения', 'tasks' => ['Прослушать примеры придаточных', 'Составить сложные предложения', 'Написать описание с придаточными']],
                    ],
                ],
                [
                    'title' => 'Культура и общество Германии',
                    'lessons' => [
                        ['title' => 'Немецкие традиции и праздники', 'tasks' => ['Прослушать рассказ о традициях', 'Обсудить немецкие праздники', 'Написать сочинение о традиции']],
                        ['title' => 'Экология и природа', 'tasks' => ['Прослушать лекцию об экологии', 'Обсудить экологические проблемы', 'Написать эссе об охране природы']],
                        ['title' => 'СМИ и интернет в Германии', 'tasks' => ['Прослушать немецкий подкаст', 'Обсудить роль СМИ', 'Написать комментарий к статье']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
        // Course 10: Французский язык: уровень A1 — 2 модуля, 3 занятия/модуль, 3 задания/занятие
        [
            'title' => 'Французский язык: уровень A1',
            'description' => 'Начальный французский. Произношение, базовая лексика и простые фразы.',
            'modules' => [
                [
                    'title' => 'Введение во французский',
                    'lessons' => [
                        ['title' => 'Произношение и алфавит', 'tasks' => ['Прослушать французские звуки', 'Произнести буквы алфавита', 'Написать слова с носовыми гласными']],
                        ['title' => 'Приветствия и вежливость', 'tasks' => ['Прослушать приветственный диалог', 'Поздороваться по-французски', 'Написать фразы приветствия']],
                        ['title' => 'Числа и цвета', 'tasks' => ['Прослушать числа по-французски', 'Назвать цвета предметов', 'Написать числа от 1 до 20']],
                    ],
                ],
                [
                    'title' => 'Базовая грамматика французского',
                    'lessons' => [
                        ['title' => 'Артикли un, une, le, la', 'tasks' => ['Прослушать примеры с артиклями', 'Выбрать правильный артикль', 'Написать фразы с артиклями']],
                        ['title' => 'Глагол être (быть)', 'tasks' => ['Прослушать предложения с être', 'Составить фразы самопредставления', 'Написать текст о себе']],
                        ['title' => 'Présent indicatif', 'tasks' => ['Прослушать диалог в настоящем времени', 'Проспрягать глаголы первой группы', 'Написать предложения в настоящем времени']],
                    ],
                ],
            ],
            'lessons' => [],
        ],
    ];

    /** @var list<string> */
    private const FIRST_NAMES = [
        // Мужские (индексы 0–9)
        'Александр', 'Дмитрий', 'Иван', 'Сергей', 'Андрей',
        'Михаил', 'Николай', 'Павел', 'Артём', 'Владимир',
        // Женские (индексы 10–19)
        'Мария', 'Анна', 'Елена', 'Ольга', 'Наталья',
        'Татьяна', 'Ирина', 'Светлана', 'Юлия', 'Екатерина',
    ];

    /** @var list<string> */
    private const LAST_NAMES = [
        // Мужские (индексы 0–19)
        'Иванов', 'Петров', 'Сидоров', 'Смирнов', 'Козлов',
        'Новиков', 'Морозов', 'Попов', 'Лебедев', 'Волков',
        'Соколов', 'Зайцев', 'Павлов', 'Семёнов', 'Голубев',
        'Виноградов', 'Богданов', 'Воробьёв', 'Фёдоров', 'Михайлов',
        // Женские (индексы 20–39)
        'Иванова', 'Петрова', 'Сидорова', 'Смирнова', 'Козлова',
        'Новикова', 'Морозова', 'Попова', 'Лебедева', 'Волкова',
        'Соколова', 'Зайцева', 'Павлова', 'Семёнова', 'Голубева',
        'Виноградова', 'Богданова', 'Воробьёва', 'Фёдорова', 'Михайлова',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Target('listsCache')] private readonly CacheItemPoolInterface $listsCache,
        #[Target('aggregationCache')] private readonly CacheItemPoolInterface $aggregationCache,
    ) {
    }

    public function load(SymfonyStyle $io): void
    {
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM course');
        if ((int) $count > 0) {
            throw new \RuntimeException('База содержит данные. Используйте флаг --reset для перезаписи');
        }

        $io->writeln('Загрузка фикстур...');

        // 1. Навыки
        $skills = $this->loadSkills();
        $io->writeln('  ✓ Навыки: '.\count($skills));

        // 2. Курсы, модули, занятия, задания
        [$moduleCount, $lessonCount, $taskCount, $allTasks] = $this->loadCourses($skills);
        $io->writeln('  ✓ Курсы: '.\count(self::COURSES));
        $io->writeln('  ✓ Модули: '.$moduleCount);
        $io->writeln('  ✓ Занятия: '.$lessonCount);
        $io->writeln('  ✓ Задания: '.$taskCount);

        // 3. Связи заданий с навыками
        $taskSkillCount = $this->loadTaskSkills($allTasks, $skills);
        $io->writeln('  ✓ Связи заданий с навыками: '.$taskSkillCount);

        // 4. Пользователи
        [$allUsers] = $this->loadUsers();
        $io->writeln('  ✓ Пользователи: '.\count($allUsers));
    }

    public function reset(): void
    {
        $this->listsCache->clear();
        $this->aggregationCache->clear();

        $this->connection->executeStatement('DELETE FROM agg_student_lesson_skill');
        $this->connection->executeStatement('DELETE FROM agg_student_module');
        $this->connection->executeStatement('DELETE FROM agg_student_course');
        $this->connection->executeStatement('DELETE FROM task_execution');
        $this->connection->executeStatement('DELETE FROM task_skill');
        $this->connection->executeStatement('DELETE FROM task');
        $this->connection->executeStatement('DELETE FROM lesson');
        $this->connection->executeStatement('DELETE FROM module');
        $this->connection->executeStatement('DELETE FROM course');
        $this->connection->executeStatement('DELETE FROM skill');
        $this->connection->executeStatement('DELETE FROM "user"');
    }

    /**
     * @return list<Skill>
     */
    private function loadSkills(): array
    {
        $skills = [];
        foreach (self::SKILLS as $title) {
            $skill = (new Skill())->setTitle($title);
            $this->entityManager->persist($skill);
            $skills[] = $skill;
        }
        $this->entityManager->flush();

        return $skills;
    }

    /**
     * @param list<Skill> $skills
     *
     * @return array{int, int, int, list<Task>}
     */
    private function loadCourses(array $skills): array
    {
        $moduleCount = 0;
        $lessonCount = 0;
        $taskCount = 0;
        /** @var list<Task> $allTasks */
        $allTasks = [];

        foreach (self::COURSES as $courseData) {
            $course = (new Course())
                ->setTitle($courseData['title'])
                ->setDescription($courseData['description']);
            $this->entityManager->persist($course);

            if ([] !== $courseData['modules']) {
                foreach ($courseData['modules'] as $moduleData) {
                    $module = (new Module())
                        ->setCourse($course)
                        ->setTitle($moduleData['title']);
                    $this->entityManager->persist($module);
                    ++$moduleCount;

                    $lessonSort = 1;
                    foreach ($moduleData['lessons'] as $lessonData) {
                        [$lesson, $tasks] = $this->createLesson($course, $module, $lessonData, $lessonSort++);
                        ++$lessonCount;
                        $taskCount += \count($tasks);
                        $allTasks = array_merge($allTasks, $tasks);
                    }
                }
            } else {
                $lessonSort = 1;
                foreach ($courseData['lessons'] as $lessonData) {
                    [$lesson, $tasks] = $this->createLesson($course, null, $lessonData, $lessonSort++);
                    ++$lessonCount;
                    $taskCount += \count($tasks);
                    $allTasks = array_merge($allTasks, $tasks);
                }
            }
        }
        $this->entityManager->flush();

        return [$moduleCount, $lessonCount, $taskCount, $allTasks];
    }

    /**
     * @param LessonData $lessonData
     *
     * @return array{Lesson, list<Task>}
     */
    private function createLesson(Course $course, ?Module $module, array $lessonData, int $sort): array
    {
        $lesson = (new Lesson())
            ->setCourse($course)
            ->setModule($module)
            ->setTitle($lessonData['title'])
            ->setSort($sort * 100);
        $this->entityManager->persist($lesson);

        /** @var list<Task> $tasks */
        $tasks = [];
        $taskSort = 1;
        foreach ($lessonData['tasks'] as $taskTitle) {
            $task = (new Task())
                ->setLesson($lesson)
                ->setTitle($taskTitle)
                ->setSort($taskSort++ * 100);
            $this->entityManager->persist($task);
            $tasks[] = $task;
        }

        return [$lesson, $tasks];
    }

    /**
     * @param list<Task>  $allTasks
     * @param list<Skill> $skills
     */
    private function loadTaskSkills(array $allTasks, array $skills): int
    {
        $count = 0;
        foreach ($allTasks as $idx => $task) {
            $mod = $idx % 10;
            if ($mod <= 1) {
                $task->addSkill($skills[$idx % 8], 100.0);
                ++$count;
            } elseif ($mod <= 8) {
                $task->addSkill($skills[$idx % 8], 60.0);
                $task->addSkill($skills[($idx + 1) % 8], 40.0);
                $count += 2;
            } else {
                $task->addSkill($skills[$idx % 8], 50.0);
                $task->addSkill($skills[($idx + 1) % 8], 30.0);
                $task->addSkill($skills[($idx + 2) % 8], 20.0);
                $count += 3;
            }
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * @return array{list<User>}
     */
    private function loadUsers(): array
    {
        /** @var list<User> $allUsers */
        $allUsers = [];

        $admin = $this->createUser('admin@example.com', 'Admin@12345', [UserRole::ADMIN->value], 1);
        $this->entityManager->persist($admin);
        $allUsers[] = $admin;

        for ($t = 1; $t <= 2; ++$t) {
            $teacher = $this->createUser('teacher'.$t.'@example.com', 'Teacher@12345', [UserRole::TEACHER->value], $t + 1);
            $this->entityManager->persist($teacher);
            $allUsers[] = $teacher;
        }

        for ($s = 1; $s <= 17; ++$s) {
            $student = $this->createUser('student'.$s.'@example.com', 'Student@12345', [UserRole::STUDENT->value], $s + 3);
            $this->entityManager->persist($student);
            $allUsers[] = $student;
        }
        $this->entityManager->flush();

        return [$allUsers];
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, string $plainPassword, array $roles, int $userNum): User
    {
        $nameIdx = ($userNum - 1) % 20;
        $isFemale = $nameIdx >= 10;
        $groupOffset = (int) (($userNum - 1) / 20);
        $lastNameIdx = ($isFemale ? 20 : 0) + ($nameIdx % 10 + $groupOffset) % 20;

        $user = (new User())
            ->setEmail($email)
            ->setFirstName(self::FIRST_NAMES[$nameIdx])
            ->setLastName(self::LAST_NAMES[$lastNameIdx])
            ->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        return $user;
    }
}
