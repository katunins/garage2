<?php

namespace App\Http\Controllers;

const LUNCH_BREACK = '12:00-13:00'; //перевыр на обед
const STANDART_BUFFER = 10; //стандартный буфер в минутах между людыми задачами в календаре
const STANDART_PRODUCT_BUFFER = 180; //стандартный буфер в минутах между задачами в конкретном продукте. Он указывается в задаче
// const TIME_AFTER_SCRIPT = 12; //время задержки после запуска скрипта в часах
const TIME_AFTER_SCRIPT = 0; //время задержки после запуска скрипта в часах

// const isset($_GET['log']) =  true; //true - идет вывод echo;

use App\Models\Tasks;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TemplateController extends Controller
{

    static function getStandartVars()
    {
        return [
            'STANDART_BUFFER' => STANDART_BUFFER,
        ];
    }

    static function repair()
    {
        dd('функция для разработчика');
        $templates = Templates::whereNotNull('conditions')->get();
        if (!$templates) return;
        foreach ($templates as $item) {
            $conditions = $item->conditions;
            foreach ($conditions as $key => $el) {
                if ($el['equal'] === '==') {
                    $conditions[$key]['equal'] = '=';
                    dump($conditions[$key]['equal']);
                }
                if ($el['equal'] === '!==') {
                    $conditions[$key]['equal'] = '!=';
                    dump($conditions[$key]['equal']);
                }
            }
            $item->conditions = $conditions;
            $item->save();
        }
        dd($templates->first(), 'ok');
    }

    static  $isHoliday; //праздники
    // 0	Рабочий день	200
    // 1	Нерабочий день	200
    // 2	Сокращённый рабочий день	200
    // 4	Рабочий день	200
    // 100	Ошибка в дате	400
    // 101	Данные не найдены	404
    // 199	Ошибка сервиса	400


    // Загрузим выходные
    static function isHolidayInit()
    {
        $ch = curl_init('https://isdayoff.ru/api/getdata?year=' . date('Y') . '&delimeter=/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        if (!$result) self::$scriptErrors[] = 'Не загрузились данные о выходных и праздниках';
        self::$isHoliday = explode('/', $result);
        curl_close($ch);
    }

    static $startTime;
    static $needExtraSort; //true если необходимо досортировать задачи, к примеру если были задачи, зависящие от других
    static $scriptErrors; //ошибки

    // возвращает шаблоны для продукта
    static function getBoard($productId)
    {
        $templates = Templates::where('productid', $productId)->get();

        $lineCount = 1;
        $positionCount = 1;

        if ($templates->count() > 0) {
            $lineCount = $templates->max('line');
            $positionCount = $templates->max('position');
        }

        foreach ($templates as $item) {
            $item->realPosition = $item->position;
        }

        $safe = 0;

        do {
            foreach ($templates->groupBy('line') as $lineItem) {
                foreach ($lineItem->sortByDesc('position') as $currentTemplate) {
                    $shift = 0;
                    $line = $currentTemplate->line;
                    $position = $currentTemplate->position;
                    $safeCount = 0;
                    do {

                        $safeCount++;
                        $beforeTemplate = $templates->where('line', $line)->where('position', $position)->first();

                        if ($beforeTemplate->taskidbefore) {
                            $befBefTemp = $templates->find($beforeTemplate->taskidbefore);
                            $line = $befBefTemp->line;
                            $different = $befBefTemp->realPosition - $beforeTemplate->realPosition;
                            // $different = $befBefTemp->position-$beforeTemplate->position;
                            if ($different >= 0) {
                                $currentTemplate->realPosition += $different + 1;
                                $position = $befBefTemp->position;
                            }
                            // dd ($beforeTemplate->position - $befBefTemp->position);
                        } else {
                            // $shift++;
                            $position--;
                        }
                    } while ($position > 1 && $safeCount > 100);
                }
            }
            $safe++;
            foreach ($templates as $currentTemplate) {
                // проверим, вдруг у соседа слева - realPosition одинаковый
                $befBefTemp = $templates->where('line', $currentTemplate->line)->where('position', $currentTemplate->position - 1)->first();
                if ($befBefTemp && $befBefTemp->realPosition == $currentTemplate->realPosition) {
                    // dump ($currentTemplate->id);
                    $currentTemplate->realPosition++;
                }
            }
        } while ($safe < 10);
        return view('board', [
            'templates' => $templates,
            'lineCount' => $lineCount,
            'positionCount' => $positionCount,
            'productId' => $productId,
            'Users' => User::where('type', 'master')->get(),
            'productTitle' => self::getAllProducts()[$productId],
            'standartTemplates' => self::getStandartTemplates(),
            // 'productTitle' => Products::where('korobookid', $productId)->first()->title,
        ]);
    }

    // возвращает шаблон
    static function getTemplate($templateId)
    {
        return Templates::where('id', $templateId)->first();
    }

    static function deleteTemplate($templateId)
    {
        $currentTemplate = Templates::find($templateId);
        // проверим нужно ли сместить задачи?
        $templatesInLine = Templates::where(['productid' => $currentTemplate['productid'], 'line' => $currentTemplate['line']])->get()->sortBy('position');
        $currentPosition = $currentTemplate['position'];
        $lastPosition = $templatesInLine->max('position');

        // проверим, если есть элементы в других линиях, которые привязын к этому
        Templates::where(['productid' => $currentTemplate['productid'], 'taskidbefore' => $currentTemplate->id])->update(['taskidbefore' => NULL]);

        if ($lastPosition > $currentPosition) {
            $x = $currentPosition;
            do {
                $x++;
                $elem = $templatesInLine->where('position', $x)->first();
                // если позиция не зависит от другой линии, то сдвинем на -1
                if (!$elem->taskidbefore) {
                    $elem['position'] = $x - 1;
                    $elem->save();
                };
                dump($x);
            } while ($x < $lastPosition);
        }


        return $currentTemplate->delete();
    }

    // перестраивает позиции в линии по порядку от 1 до 9
    static function rebuildPosition($productId, $line)
    {
        $templatesToSort = Templates::where([
            'productid' => $productId,
            'line' => $line,
        ])->get()->sortBy('position');
        $truePosition = 1;
        foreach ($templatesToSort as $item) {
            if ($item->position != $truePosition) {
                $item->position = $truePosition;
                $item->save();
            }
            $truePosition++;
        }
    }

    // перестраивает позиции шаблонов по порядку во всем продукте
    static function rebuildTemplate($productId)
    {
        if (Templates::where('productid', $productId)->count() > 0) {
            $maxLine = Templates::where('productid', $productId)->max('line');
            for ($line = 1; $line <= $maxLine; $line++) {
                if (Templates::where('productid', $productId)->where('line', $line)->count() > 0) {
                    self::rebuildPosition($productId, $line);
                }
            }
        }
    }

    // проходит по каждому продукту в сделке
    static function tasksFromDeal($dealArr)
    {
        if (isset($_GET['log']) == true) echo 'tasksFromDeal()' . 'Start' . '<br>';
        self::$scriptErrors = [];

        self::isHolidayInit();

        sleep(1);

        foreach ($dealArr['products'] as $key => $dealItem) {

            self::$scriptErrors = [];

            $dealName = count($dealArr['products']) > 1 ? $dealArr['params']['deal'] . '/' . $key : $dealArr['params']['deal'];
            if (isset($_GET['log']) == true) echo '- ' . 'Продукт ' . $dealName . '<br>';

            $productDataArr = array_merge($dealItem, $dealArr['params']);
            $productDataArr['dealname'] = $dealName;

            $tasks = self::taskGenegator($productDataArr, $key === count($dealArr['products'])); //сформированные по фильтрам задачи из шаблонов
            $tasks = self::linkAllTasks($tasks); //тут уже связанные друг с другом задачи
            self::planGeneratorNew($tasks, $dealItem, $productDataArr);

            if (isset($_GET['log'])) echo '<br><hr><br>';
            else Tasks::where('deal', $dealName)->where('status', 'temp')->update(['status' => 'wait']);
        }
        if (isset($_GET['log'])) {
            if (Tasks::where('deal', $dealName)->where('status', 'temp')->count() > 0) {
                echo '<div><a target="_blank" href="';
                echo '/calendar?filterdealname=' . explode('#', $dealArr['params']['deal'])[1];
                echo '&calendardays=7';
                echo '&calendarstyle=1';
                echo '&date=' . explode(' ', Tasks::where('deal', $dealName)->where('status', 'temp')->orderBy('start')->first()->start)[0];
                echo '">Перейти в календарь</a></div>';
            } else {
                dump('Задачи не сфорированы');
            }
            dd(self::$scriptErrors);
        }
        if (self::$scriptErrors == []) return true;
    }

    // получает массив выбранных по условию задач

    static function linkAllTasks($tasks)
    {
        // Cтавит всем задачам taskIdBefore в двух вариантах 
        // 'otherline' => null,
        // 'parentline' => null
        // 0 => array:3 [▼
        //     "temporaryid" => 0
        //     "templateid" => 1
        if (isset($_GET['log']) == true) echo 'linkAllTasks()' . '<br>';

        foreach ($tasks as $key => $itemTask) {

            $tasks[$key]['taskbefore'] = [
                'otherline' => null,
                'parentline' => null
            ];

            $currentTemplate = Templates::find($itemTask['templateid']);

            // Если шаблон первый в линии и у него taskidbefore пустой, то taskidbefore = null
            if ($currentTemplate->position == 1 && $currentTemplate->taskidbefore === null) continue;


            // Запустим обратный поиск по карте шаблонов
            // Ннайдем предыдущий шаблон, по которому создана задача в $tasks
            // в двух вариантах: По родительской линии и соседним линиям

            foreach (['parentline' => false, 'otherline' => true] as $taskBeforeType => $shiftNextLine) {
                $safeCount = 0;
                $taskBefore[$taskBeforeType] = null;
                $loopTemplate = clone $currentTemplate;

                do {
                    if ($safeCount > 100) {
                        if (isset($_GET['log']) == true) echo 'linkAllTasks()' . 'Превышен safeCount в цикле - ' . $taskBeforeType . '<br>';
                        return false;
                    }
                    $beforeTemplate = self::getBeboreTemplate($loopTemplate, $shiftNextLine);
                    if ($beforeTemplate) {
                        // найдем задачу с этим шаблоном
                        $resultArr = array_filter($tasks, function ($item) use ($beforeTemplate) {
                            return $item['templateid'] === $beforeTemplate->id;
                        });
                        if (count($resultArr) > 0) {
                            $taskBefore[$taskBeforeType] = array_shift($resultArr)['temporaryid'];
                            break;
                        } else {
                            $loopTemplate = clone $beforeTemplate;
                        }
                    } else {
                        // предыдущего шаблона не нашлось. Это значит - currentTemplate первый
                        break;
                    }
                } while ($taskBefore[$taskBeforeType] === null);
            }
            $tasks[$key]['taskbefore'] = $taskBefore;
        }
        return $tasks;
    }

    /* 
    // дополнительно передвинем зависящие задачи от предыдущих 
    static function extraSort($dealName)
    {
        $tasks = Tasks::where('deal', $dealName)->where('status', 'temp')->get();

        // обработка ошибок
        if ($tasks->count() == 0) {
            self::$scriptErrors[] = 'extraSort() При дополнительной сортировке зависящих задач возникла ошибка';
            return false;
        }
        if (isset($_GET['log'])) echo 'extraSort()<br>';
        // возьмем все задачи с taskidbefore = NULL
        foreach ($tasks->whereNull('taskidbefore') as $item) {

            $beforeTask = null;
            $safeCount = 0;
            $currentTemplate = Templates::find($item->templateid);
            $itemTemplate = $currentTemplate;

            if (isset($_GET['log'])) echo 'Задача <b>' . $item->name . '</b> - ' . $item->id . ' не привязана к предыдущей. Найдем задачу по карте шаблонов<br>';
            while (self::getBeboreTemplate($itemTemplate, true) && $safeCount < 100) {
                if ($beforeTemplate = self::getBeboreTemplate($itemTemplate, true)) {
                    $safeCount++;
                    if (isset($_GET['log'])) echo '+';
                    if ($beforeTask = $tasks->where('templateid', $beforeTemplate->id)->first()) {
                        if (isset($_GET['log'])) echo ' Нашли задачу ' . $beforeTask->id . '(' . $beforeTask->name . ')' . ' по шаблону ' . $beforeTemplate->id . '<br>';
                        $item->taskidbefore = $beforeTask->id;
                        $item->save();
                        break;
                    }
                    $itemTemplate = $beforeTemplate;
                }
            }

            // if (isset($_GET['log'])) echo '<br>';

            if ($item->taskidbefore == null) {
                if ($currentTemplate->taskidbefore && $currentTemplate->position > 1) {
                    if (isset($_GET['log'])) echo 'Задача была привязана к шаблону из другой линии. Ни одна задача из этой ветки шаблонов не поставлена. Найдем предущую задачу по шаблону из родительской линии<br>';
                    $itemTemplate = $currentTemplate;
                    while (self::getBeboreTemplate($itemTemplate) && $safeCount < 100) {
                        if ($beforeTemplate = self::getBeboreTemplate($itemTemplate)) {
                            $safeCount++;
                            if (isset($_GET['log'])) echo '+';
                            if ($beforeTask = $tasks->where('templateid', $beforeTemplate->id)->first()) {
                                if (isset($_GET['log'])) echo ' Нашли задачу ' . $beforeTask->id . ' по шаблону ' . $beforeTemplate->id . '<br>';
                                $item->taskidbefore = $beforeTask->id;
                                $item->save();
                                break;
                            }
                            $itemTemplate = $beforeTemplate;
                        }
                    }

                    if (isset($_GET['log'])) echo '<br>';
                } else {
                    if (isset($_GET['log'])) echo ' Привязанной задачи нет!<br>';
                }
            }

            // на всякйи случай проверим время старта найденной задачи и время предыдущей задачи в родительской линии
            if ($item->taskidbefore) {
                // && $parentBeforeTemplate = self::getBeboreTemplate(Templates::find($item->templateid))

                $beforeTask = null;
                $safeCount = 0;
                $itemTemplate = Templates::find($item->templateid);
                $beforeParentTask = NULL; //предыдущая задача в родительской линии

                while (self::getBeboreTemplate($itemTemplate) && $safeCount < 100) {
                    if ($beforeTemplate = self::getBeboreTemplate($itemTemplate)) {
                        if ($beforeTask = $tasks->where('templateid', $beforeTemplate->id)->first()) {
                            $beforeParentTask = $beforeTask;
                            break;
                        }
                        $itemTemplate = $beforeTemplate;
                    }
                }

                if ($beforeParentTask) {
                    // dump (Tasks::find($item->taskidbefore)->end,Tasks::find($item->taskidbefore)->end );
                    // dump (Tasks::find($item->taskidbefore)->end, Tasks::find($beforeParentTask->taskidbefore)->end);
                    $timeBeforeTask = Carbon::parse($tasks->find($item->taskidbefore)->end);
                    $timeBeforeParentTask = Carbon::parse($beforeParentTask->end);

                    if ($timeBeforeParentTask->greaterThan($timeBeforeTask)) {
                        if (isset($_GET['log'])) echo 'Предыдущая задача, найденная в ветках из других линий оказась в календаре раньше, чем предыдущая задача в родительской линии <b>' . $beforeParentTask->name . '</b>. Сделаем связку с родителькой задачей<br>';
                        $item->taskidbefore = $beforeParentTask->id;
                        $item->save();
                    }
                }
            }
        }
    }
    */

    // Возвращяет предыдущий шаблон. TRUE с переходом на другие линии, FALSE - только в данной линии
    static function getBeboreTemplate($currentTemplate, $shiftNextLine = false)
    {

        // найдем координаты предыдущего шаблона
        if ($currentTemplate->taskidbefore && $shiftNextLine == true) {
            $beforeTemplate = Templates::find($currentTemplate->taskidbefore);
            return Templates::where('productid', $currentTemplate->productid)->where('line', $beforeTemplate->line)->where('position', $beforeTemplate->position)->first();
        } elseif ($currentTemplate->position > 1) {
            // получим предыдущую задачу в линии
            return Templates::where('productid', $currentTemplate->productid)->where('line', $currentTemplate->line)->where('position', $currentTemplate->position - 1)->first();
        }
        return null;
    }


    // Подбирет шаблоны под параметры заказа и расчитывает длительность задачи
    static function taskGenegator($productParams, $lastProduct)
    {
        // "productname" => "Фотокниги"
        // "Формат" => "20х20 см"
        // "Материал обложки" => "Toronto Toronto Белый"
        // "Персонализация" => "Без персонализации"
        // "Форзацы" => "Белые Без печати"
        // "Первая страница книги" => "Без кальки"
        // "Печать" => "Шелк"
        // "Короб в комплекте" => "Без короба"
        // "Количество разворотов" => 12
        // "Ссылка на макет" => "yadi.sk/d/M_crzlp8U5vVfA?w=1"
        // "Количество" => 1
        // "Комментарий" => ""
        // "Рабочих дней на заказ" => 7
        // "Срок готовности" => "2021.02.09|"
        // "ПВЗ" => "ул. Профсоюзная, 126, ТЦ Крокус, 1 этаж"
        // "Доставка" => "До склада"
        // "Город" => "44"
        // "Получатель" => "Костина Татьяна"
        // "Телефон" => "+7(903) 110-52-07"

        // получим id продукта
        if (isset($_GET['log']) == true) echo 'taskGenerator()' . '<br>';

        $productId = array_search($productParams['productname'], self::getAllProducts());
        // $productData = Products::where('title', $productParams['productname'])->get();
        if (!$productId) {
            dump('нет такого продукта в сайте: ' . $productParams['productname']);
        }
        $taskArr = [];
        $temporaryid = 0;

        $templates = Templates::where('productid', $productId)->get();
        for ($line = 1; $line <= $templates->max('line'); $line++) {
            foreach ($templates->where('line', $line)->sortBy('position') as $templateItem) {

                if ($templateItem->grouptask && !$lastProduct) {
                    // if (isset($_GET['log']) == true) echo 'Общая задача на весь заказ' . '<br>';
                    continue;
                };

                // Проверим условия шаблона. Работают по принципу OR

                $conditionResult = 0; // 0 - не сработало / 1 - сработало
                $conditionCount = 0; // количество не пустых условий

                if ($templateItem->conditions) {
                    foreach ($templateItem->conditions as $conditionItem) {

                        $conditionCount++;
                        // if (isset($productParams[$conditionItem['condition']])) {


                        //     $productValue = $productParams[$conditionItem['condition']];
                        //     foreach (explode('/', $conditionItem['value']) as $value) {
                        //         switch ($conditionItem['equal']) {
                        //             case '?':
                        //                 $conditionResult++;
                        //                 break (2);
                        //             case '!?':
                        //                 break (2);
                        //             case '=':
                        //                 $conditionResult += strpos($productValue, $value) !== false ? 1 : 0;
                        //                 break (1);
                        //             case '!=':
                        //                 $conditionResult += strpos($productValue, $value) === false ? 1 : 0;
                        //                 break (1);
                        //         }
                        //     }
                        // }



                        $productValue = $productParams[$conditionItem['condition']] ?? null;

                        if ($conditionItem['equal'] === '?' && $conditionItem['condition'] === $productValue) {
                            $conditionResult++;
                            continue;
                        }

                        if ($conditionItem['equal'] === '!?' && $productValue === null) {
                            $conditionResult++;
                            continue;
                        }

                        foreach (explode('/', $conditionItem['value']) as $value) {

                            if ($conditionItem['equal'] === '=' && strpos($productValue, $value) !== false) {
                                $conditionResult++;
                                continue;
                            }

                            if ($conditionItem['equal'] === '!=' && strpos($productValue, $value) === false) {
                                $conditionResult++;
                                continue;
                            }
                        }
                    }
                    // echo $conditionCount.' '.$conditionResult.' '.$templateItem->taskname.'<br>';
                }

                // заменим логику на AND
                // if ($conditionCount == 0 || ($conditionCount > 0 && $conditionResult > 0)) {
                if ($conditionCount == 0 || ($conditionCount > 0 && $conditionResult === $conditionCount)) {
                    // тут шаблон прошел условия, поэтому создадим задачу
                    // если есть минипараметры, которые нужно отобразить в сделке

                    $taskInfo = NULL;
                    if ($templateItem->miniparams) {

                        foreach ($templateItem->miniparams as $itemInfo) {
                            if (isset($productParams[$itemInfo])) {
                                $taskInfo[] = $itemInfo . ' : ' . $productParams[$itemInfo];
                            }
                        }
                    }

                    // расчитаем время
                    $taskTime = 0;
                    if ($templateItem->producttime) $taskTime += $templateItem->producttime; // * $productParams['Количество'];
                    if ($templateItem->paramtime) {
                        foreach (['Формат', 'Размер паспарту (размер фотографии)'] as $sizeName) {
                            if (array_key_exists($sizeName, $productParams)) {
                                // посчитаем площадь разовротов в квадратных дециметрах (разделим на 100)
                                $size = explode(' ', $productParams[$sizeName]);
                                $widthHeight = explode('x', $size[0]); //английская литера x
                                if (!isset($widthHeight[1])) $widthHeight = explode('х', $size[0]); //русская литера x;

                                $sheetCount = 1;
                                foreach (['Количество паспарту', 'Количество разворотов', 'Количество фотокарточек'] as $countParam) {
                                    if (array_key_exists($countParam, $productParams)) $sheetCount = (int)$productParams[$countParam];
                                }
                                $area = (int) $widthHeight[0] * (int) $widthHeight[1] * $sheetCount / 100;
                                $taskTime += $area * ($templateItem->paramtime / 60);

                                if (isset($_GET['log']) == true) {
                                    echo $templateItem->taskname . ' расчетное время (' . $templateItem->paramtime . ' сек.). ' . 'Площадь ' . $area . ' дм2.' . '<br>';
                                }
                            }
                        }
                    }
                    $taskArr[] = [
                        'temporaryid' => $temporaryid,
                        'realtaskid' => null,
                        'templateid' => $templateItem->id,
                        'time' => ceil($templateItem->nocounttime ? $taskTime : $taskTime * (int)$productParams['Количество']),
                        'info' => $taskInfo,
                        'dealname' => $productParams['dealname'],
                        'grouptask' => $templateItem->grouptask,
                    ];

                    $temporaryid++;
                }
            }
        }
        // dd('ok');
        return $taskArr;
    }

    static function planGeneratorNew($tasksArr, $dealItem, $productDataArr)
    {

        if (isset($_GET['log']) == true) echo 'planGeneratorNew()<br>';

        // "temporaryid" => 0
        // "templateid" => 1 
        // "realtaskid" => null //тут запишем реальный id задачи в базе
        // "time" => 13.0
        // "info" => array:2 [▶]
        // "dealname" => "20 #8424/1"
        // "taskbefore" => array:2 [▼
        // "otherline" => null
        // "parentline" => null
        // ]

        $firstTasksArr = array_filter($tasksArr, function ($item) {
            return is_null($item['taskbefore']['parentline']) && is_null($item['taskbefore']['otherline']);
        });

        $startFrom = new Carbon; //время с которого можно ставить задачи
        $startFrom->addHours(TIME_AFTER_SCRIPT);

        // Поставим статровые задачи
        if (isset($_GET['log']) == true) echo 'Поставим первые задачи в линиях' . '<br>';
        foreach ($firstTasksArr as $key => $task) {
            $template = Templates::find($task["templateid"]);
            $taskIdBefore = null;

            $newTask = self::setNewTask(
                $template,
                $task,
                $startFrom,
                $dealItem,
                $productDataArr,
                $taskIdBefore
            );
            $newTask->save();
            $tasksArr[$key]['realtaskid'] = $newTask->id;
        }
        if (isset($_GET['log']) == true) {
            dump(array_filter($tasksArr, function ($item) {
                return is_null($item['realtaskid']);
            }));
        }


        $generalSafeCount = 0;
        do {
            # code...
            $generalSafeCount++;

            if ($generalSafeCount > 40) {
                if (isset($_GET['log']) == true) echo 'Счетчик цикла safeGeneralCount превысил допустимое значение' . '<br>';
                break;
            }

            if (isset($_GET['log']) == true) echo 'Поставим задачи, у которых taskbefore один актуальный и не null' . '<br>';
            $safeCount = 0;
            do {
                $safeCount++;
                if ($safeCount > 50) {
                    if (isset($_GET['log']) == true) echo 'Цикл остановлен safeCount<br>';
                    break;
                }

                $beforeTasksArr = array_filter($tasksArr, function ($item) {
                    return !is_null($item['realtaskid']);
                });

                $newTaskCount = 0;
                foreach ($beforeTasksArr as $beforeTask) {
                    $filterArr = array_filter($tasksArr, function ($arr) use ($beforeTask) {
                        if (!is_null($arr['realtaskid'])) return false;
                        return ($arr['taskbefore']['parentline'] === $beforeTask['temporaryid'] && is_null($arr['taskbefore']['otherline']))
                            || ($arr['taskbefore']['otherline'] === $beforeTask['temporaryid'] && is_null($arr['taskbefore']['parentline']))
                            || ($arr['taskbefore']['otherline'] === $beforeTask['temporaryid'] && $arr['taskbefore']['parentline'] === $beforeTask['temporaryid']);
                    });
                    if (count($filterArr) > 0) {
                        $newTaskCount++;
                        foreach ($filterArr as $key => $task) {

                            $realBeforeTask = Tasks::find($beforeTask['realtaskid']);
                            $startFrom = Carbon::parse($realBeforeTask->end); //время с которого можно ставить задачи
                            $startFrom->addMinutes($realBeforeTask->buffer > TIME_AFTER_SCRIPT ? $realBeforeTask->buffer : TIME_AFTER_SCRIPT);

                            $template = Templates::find($task["templateid"]);
                            $taskIdBefore = $beforeTask['realtaskid'];

                            $newTask = self::setNewTask(
                                $template,
                                $task,
                                $startFrom,
                                $dealItem,
                                $productDataArr,
                                $taskIdBefore
                            );
                            $newTask->save();
                            $tasksArr[$key]['realtaskid'] = $newTask->id;
                        }
                    }
                }
            } while ($newTaskCount > 0);
            if (isset($_GET['log']) == true) {
                dump(array_filter($tasksArr, function ($item) {
                    return is_null($item['realtaskid']);
                }));
            }



            if (isset($_GET['log']) == true) echo 'Поставим задачи, у которых taskbefore 2 шт' . '<br>';

            $doubleBeforeTasksArr = array_filter($tasksArr, function ($item) use ($tasksArr) {
                $beforeID1 = $item['taskbefore']['parentline'];
                $beforeID2 = $item['taskbefore']['otherline'];
                if (is_null($beforeID1) || is_null($beforeID2)) return false;
                return !is_null($tasksArr[$beforeID1]['realtaskid'])
                    && !is_null($tasksArr[$beforeID2]['realtaskid'])
                    && is_null($item['realtaskid']);
            });

            foreach ($doubleBeforeTasksArr as $key => $task) {

                $realBeforeTask_1 = Tasks::find($tasksArr[$task['taskbefore']['parentline']]['realtaskid']);
                $realBeforeTask_1_end = Carbon::parse($realBeforeTask_1->end);
                $realBeforeTask_1_end->addMinutes($realBeforeTask_1->buffer > TIME_AFTER_SCRIPT ? $realBeforeTask_1->buffer : TIME_AFTER_SCRIPT);


                $realBeforeTask_2 = Tasks::find($tasksArr[$task['taskbefore']['otherline']]['realtaskid']);
                $realBeforeTask_2_end = Carbon::parse($realBeforeTask_2->end);
                $realBeforeTask_2_end->addMinutes($realBeforeTask_2->buffer > TIME_AFTER_SCRIPT ? $realBeforeTask_2->buffer : TIME_AFTER_SCRIPT);

                $template = Templates::find($task["templateid"]);
                if ($realBeforeTask_1_end > $realBeforeTask_2_end) {
                    $startFrom = clone $realBeforeTask_1_end;
                    $taskIdBefore = $realBeforeTask_1->id;
                } else {
                    $startFrom = clone $realBeforeTask_2_end;
                    $taskIdBefore = $realBeforeTask_2->id;
                }

                $newTask = self::setNewTask(
                    $template,
                    $task,
                    $startFrom,
                    $dealItem,
                    $productDataArr,
                    $taskIdBefore
                );
                $newTask->save();
                $tasksArr[$key]['realtaskid'] = $newTask->id;
            }
            if (isset($_GET['log']) == true) {
                dump(array_filter($tasksArr, function ($item) {
                    return is_null($item['realtaskid']);
                }));
            }
        } while (count(array_filter($tasksArr, function ($item) {
            return is_null($item['realtaskid']);
        })) > 0);
    }

    // найдем свободное время у мастера
    // Создадим задачу в базе
    // вернем ID задачи в базе
    static function setNewTask($template, $task, $startFrom, $dealItem, $productDataArr, $taskIdBefore)
    {

        if (isset($_GET['log']) == true) echo 'setNewTask() -' . $template->taskname . '<br>';
        // сделаем специально познее вермя старта, что бы потом выбрать самого свободного мастера
        $start = new Carbon; //
        $start->addYear();

        // выберем ближайшего свободного мастера
        $resultMasterId = 0;

        foreach ($template->masters as $masterId) {
            $resultTime = self::getFreePlan($masterId, $task['time'], $startFrom, $template->periods);

            if ($resultTime < $start) {
                $start = clone $resultTime;
                $end = clone $start;
                $end->addMinutes((int)$task['time']);
                $resultMasterId = $masterId;
            }
        }
        $newTask = new Tasks;
        $newTask->name = $template->taskname;
        $newTask->templateid = $template->id;
        $newTask->master = $resultMasterId;
        $newTask->time = $task['time'];
        $newTask->line = $template->line;
        $newTask->position = $template->position;
        $newTask->status = 'temp';
        if ($taskIdBefore) $newTask->taskidbefore = $taskIdBefore; //предварительная задача
        $newTask->start = $start->format('Y-m-d H:i:s');
        $newTask->end = $end->format('Y-m-d H:i:s');
        $newTask->buffer = $template->buffer ? $template->buffer : $template->buffer + STANDART_PRODUCT_BUFFER; //стандартный буфер задержки после задачи


        $newTask->generalinfo = $dealItem['productname'];
        if (isset($dealItem['Формат'])) $newTask->generalinfo .= ' ' . $dealItem['Формат'];
        foreach (['Количество паспарту', 'Количество разворотов', 'Количество фотокарточек'] as $item) {
            if (isset($dealItem[$item])) $newTask->generalinfo .= ', ' . $dealItem[$item] . ' ' . explode(' ', $item)[1];
        }

        // if ($template->miniparams) {
        //     $info = '';
        //     foreach ($template->miniparams as $param) {
        //         if (isset($dealItem[$param]) !== false) $info .= $param . ': ' . $dealItem[$param] . '; ';
        //         // else self::$scriptErrors[] = 'Не найден параметр, проверьте "' . $param . '"';
        //     }
        //     $newTask->info = $info;
        // }
        // dd ();
        if ($task['info']) $newTask->info = implode(', ', $task['info']);
        $newTask->deal = $task['dealname'];

        // generalInfo
        $newTask->dealid = $productDataArr['dealid'];
        $newTask->manager = $productDataArr['manager'];
        if ($productDataArr['managernote'] != "") $newTask->managernote = true;
        // generalInfo
        // $newTask->save();
        return $newTask;
    }

    // возвращает первое свободное время в графике мастера
    static function getFreePlan($masterId, $time, $startFrom, $periods)
    {
        // $time - длительность задачи в минутах
        // $periods - массив с запрещенными периодами

        self::$startTime = clone $startFrom;
        // self::$startTime->add('12 hours'); //промежуток в часах между запуском и времени начала задач

        // Проверим можно ли поставить задачу в данное время
        // Если нет, то возвращает время окончания препятствия
        // self::$startTime = Carbon::create(2021, 02, 04, 13, 0);

        $safeCount = 0; //защита от зависания
        $result = false;
        do {
            $safeCount++;

            if ($safeCount > 100) {
                // self::$scriptErrors[] = 'getFreePlan() цикл поиска свободного времени превысил допустимое значение. Мастер - ' . $masterId . ', startFrom - ' . $startFrom->toDateTimeString() . ', Длительность ' . $time . ' мин.';
                if (isset($_GET['log']) == true) echo 'getFreePlan() цикл поиска свободного времени превысил допустимое значение. Мастер - ' . $masterId . '<br>';
                break;
            }
            if (isset($_GET['log']) == true) echo 'Мастер ' . $masterId . ' / Поиск  ' . self::$startTime->format('Y-m-d H:i:s') . ' / ' . $time . ' мин.';
            $result = self::tryToPlan($time, $periods, $masterId);
        } while ($result == false);

        if (isset($_GET['log']) == true) echo ' OK<br><br>';
        return self::$startTime;
    }

    // Проверяет можно ли запланировать задачу мастеру в данное время
    static function tryToPlan($time, $periods, $masterId)
    {

        // $startTime - время начала
        // $periods - запретные периоды

        $endTime = clone self::$startTime;
        $endTime->addMinutes(round($time)); //время окончания задачи
        // dump (self::$startTime->toDateTimeString(), $endTime->toDateTimeString());

        $periods[] = LUNCH_BREACK; //Перерыв на обед

        // рабочее время
        $workDayStart = clone self::$startTime;
        $workDayEnd = clone self::$startTime;
        $workDayStart->setTime(9, 0, 0);
        $workDayEnd->setTime(18, 0, 0);

        // Проерим startTime на: выходные (масивв номер дня года - результат)
        if (self::$isHoliday[self::$startTime->format('z')] != 0 && self::$isHoliday[self::$startTime->format('z')] != 4) {
            if (isset($_GET['log']) == true) echo ' - Выходной<br>';
            self::$startTime->setTime(9, 0, 0);
            self::$startTime->addDay();
            return false;
        }

        // Проверим startTime если раньше начала рабочего дня или endTime позже времени окончания, то вернем 9:00 следующего дня
        if (self::$startTime < $workDayStart || $endTime < $workDayStart) {
            if (isset($_GET['log']) == true) echo ' - Слишком рано<br>';
            self::$startTime->setTime(9, 0, 0);
            return false;
        }

        // если время позже рабочего времени, то + 1 день
        if (self::$startTime > $workDayEnd || $endTime > $workDayEnd) {
            if (isset($_GET['log']) == true) echo ' - Слишком поздно<br>';
            self::$startTime->setTime(9, 0, 0);
            self::$startTime->addDay();
            return false;
        }

        // Проверим данное время на попадание в запрещенные периоды:
        foreach ($periods as $item) {
            if ($item) {
                $periodArr = explode('-', $item);
                $periodStartArr = explode(':', $periodArr[0]);
                $periodEndArr = explode(':', $periodArr[1]);

                $periodStart = clone self::$startTime;
                $periodEnd = clone self::$startTime;


                $periodStart->setTime($periodStartArr[0], $periodStartArr[1]);
                $periodEnd->setTime($periodEndArr[0], $periodEndArr[1]);

                // если данное время попадает в период запрещенных вернем 
                if (self::$startTime->between($periodStart, $periodEnd, false) || $endTime->between($periodStart, $periodEnd, false)) {
                    self::$startTime = clone $periodEnd;
                    if (isset($_GET['log']) == true) echo ' - Запретный период<br>';
                    return false;
                }
            }
        }

        // Проверим данное время на совпадение с запланированными задачами:
        // временно добавим 1 секунду, что бы не было времени 9:01
        self::$startTime->addSecond();
        $endTime->subSecond();

        // времена для проверки со стандартными буфером
        $trueEndTime = clone $endTime;
        $trueEndTime->addMinutes(STANDART_BUFFER);

        $trueStartTime = clone self::$startTime;
        $trueStartTime->subMinutes(STANDART_BUFFER);

        $taskHere = Tasks::whereBetween('start', [self::$startTime, $trueEndTime])
            ->orWhereBetween('end', [$trueStartTime, $endTime])
            ->orWhere([['start', '<=', self::$startTime], ['end', '>=', $endTime]])->get()->sortBy('end')
            ->where('master', $masterId);

        // $taskHere = Tasks::whereBetween('start', [self::$startTime, $endTime])
        //     ->orWhereBetween('end', [self::$startTime, $endTime])
        //     ->orWhere([['start', '<=', self::$startTime], ['end', '>=', $endTime]])->get()->sortBy('end')
        //     ->where('master', $masterId);


        if ($taskHere->count() > 0) {

            // уберем временную 1 секунду
            self::$startTime->subSecond(); //addSecond();
            $endTime->addSecond();

            // if ($taskHere->count() > 2) dump ($taskHere);
            self::$startTime = Carbon::createFromFormat('Y-m-d H:i:s', $taskHere->last()->end);
            self::$startTime->addMinutes(STANDART_BUFFER);
            if (isset($_GET['log']) == true) {

                echo ' - в это вреия есть задачи: ' . $taskHere->count() . '<br>';
                // dump($taskHere);
            }
            return false;
        };

        // уберем временную 1 секунду
        self::$startTime->subSecond(); //addSecond();
        $endTime->addSecond();

        return true;
    }


    // перемещает шаблон на другую позицию
    static function moveTemplate($data)

    {
        // "line" => "1"
        // "position" => "1"
        // "lineshift" => "1"
        // "positionshift" => "0"
        // "productid" => "1"
        // "templateid" => "11"
        $currentTemplate = Templates::find($data['templateid']);

        // Меняем позицией с соседом в линии

        if ($data['positionshift'] != 0) {

            $newPosition = $data['position'] + $data['positionshift'];

            // вдруг нет шаблонов перед новым
            $minPosition = Templates::where([
                'productid' => $data['productid'],
                'line' => $data['line'],
            ])->min('position');

            $changeTemplate = Templates::where([
                'productid' => $data['productid'],
                'line' => $data['line'],
                'position' => $newPosition
            ])->first();


            if ($newPosition <=  $minPosition) {
                $newPosition = 1;
            }
            $currentTemplate->position = $newPosition;
            $changeTemplate->position = $data['position'];
            $currentTemplate->save();
            $changeTemplate->save();


            // На всякий случай переименуем последовательность позиций 2,3,5 - 1, 2, 3
            self::rebuildPosition($data['productid'], $data['line']);
        }

        // Cмещаяем на другую линию

        // Проверим не привязан ли к этому элементу какой то шаблон
        if (Templates::where('taskidbefore', $data['templateid'])->get()->count() > 0) return;

        if ($data['lineshift'] != 0) {

            $newLine = $data['line'] + $data['lineshift'];
            $newLineTemplates = Templates::where([
                'productid' => $data['productid'],
                'line' => $newLine,
            ])->get();
            // $newPosition = $newLineTemplates->count() > 0 ? $newLineTemplates->max('position') + 1 : 1;

            $currentTemplate->line = $newLine;
            // $currentTemplate->position = $newPosition;
            $currentTemplate->save();


            // переименуем последовательность позиций 2,3,5 - 1, 2, 3
            self::rebuildPosition($data['productid'], $newLine);
            self::rebuildPosition($data['productid'], $data['line']);
        }
    }

    // копирует шаблон и вставляет в соседнюю позицию
    static function copyTemplate($data)

    {
        // "line" => "1"
        // "position" => "1"
        // "lineshift" => "1"
        // "positionshift" => "0"
        // "productid" => "1"
        // "templateid" => "11"

        // сместим позиции следующих шаблонов в линии на +1
        $nextTemplates = Templates::where([
            'productid' => $data['productid'],
            'line' => $data['line']
        ])->where('position', '>', $data['position'])->get();
        foreach ($nextTemplates as $item) {
            $item->position++;
            $item->save();
        }

        $currentTemplate = Templates::find($data['templateid']);
        $newTemplate = $currentTemplate->replicate();
        $newTemplate->position++;
        $newTemplate->save();
    }

    // клонирует шаблон из дугого ID
    static function cloneTemplate($data)
    {
        // "cloneid" => "44"
        //   "line" => "1"
        //   "position" => "6"
        //   "productid" => "128"
        $cloneTemplate = Templates::find($data['cloneid']);
        if ($cloneTemplate) {
            $newTemplate = $cloneTemplate->replicate();
            $newTemplate->line = $_GET['line'];
            $newTemplate->position = $_GET['position'] + 1;
            $newTemplate->productid = $_GET['productid'];
            $newTemplate->taskidbefore = null;
            $newTemplate->standarttemplate = null;
            $newTemplate->save();
            // dd ($newTemplate);
        }
        return redirect('/board/' . $_GET['productid']);
    }


    // перемещает линию вверх или вниз
    static function moveLine($data)
    {
        // "line" => "1"
        // "lineshift" => "1"

        $newLine = (int)$data['line'] + (int)$data['lineshift'];
        $currentLine = (int)$data['line'];

        $templates = Templates::where(['productid' => $data['productid']])->get();
        foreach ($templates as $item) {
            $elem = Templates::find($item->id);
            if ($item->line == $currentLine) {
                $elem->line = $newLine;
                $elem->save();
            }
            if ($item->line == $newLine) {
                $elem->line = $currentLine;
                $elem->save();
            }
        };
    }



    public function saveTemplate(Request $request)
    {
        // "_token" => "2zQgKq9AI1IZ9gnMUxW4S9ftzJlSIPfBLhLY25yT"
        // "productid" => "1"
        // "line" => "2"
        // "position" => "2"
        // "taskname" => "Обрубка картона"
        // "params" => null
        // "masters" => "2/3"
        // "templateid" => "4"
        // "taskidbefore" => "21"
        // "condition1" => null
        // "condition2" => null
        // "condition3" => null
        // "producttime" => "5"
        // "paramtime" => null
        // "buffer" => null
        // "period1" => null
        // "period2" => null

        $request->validate([
            'taskname' => 'required',
            'masters.0' => 'required',
            'producttime' => 'required_without:paramtime',
        ], [
            'taskname.required' => 'Заполните название задачи',
            'masters.0.required' => 'Хотя бы один мастер должен быть указан',
            'producttime.required_without' => 'Должен быть заполнен хотя бы один параметр времени',
            // 'paramtime.required_without'=>'Должен быть заполнен хотя бы один параметр времени'
        ]);

        if ($request->templateid) {
            // редактирование записи
            $template = Templates::find($request->templateid);
        } else {
            // новая запись
            $template = new Templates;
        }




        $template['productid'] = $request['productid'];
        $template['line'] = $request['line'];
        $template['position'] = $request['position'];
        $template->taskidbefore = $request->taskidbefore;

        $template->taskname = $request->taskname;

        // уберем пустые ячейки в массиве
        foreach ($request->masters as $item) {
            if ($item != null) $trueMasters[] = $item;
        }
        $template->masters = $trueMasters;

        // уберем пустые ячейки в массиве
        foreach ($request->miniparams as $item) {
            if ($item) $trueMiniparams[] = $item;
        }
        // $request->miniparams = array_filter($request->miniparams, function($item){return $item !=null;});
        $template->miniparams = $trueMiniparams ?? null;

        $template->buffer = $request->buffer;
        $template->producttime = $request->producttime;
        $template->paramtime = $request->paramtime;

        // уберем пустые ячейки в массиве
        foreach ($request->periods as $item) {
            if ($item) $truePeriods[] = $item;
        }
        $template->periods = $truePeriods ?? null;

        // уберем пустые ячейки в массиве
        foreach ($request->conditions as $item) {
            // if ($item['condition'] && $item['equal'] && $item['value']) {
            if (($item['condition'] && $item['equal'] && $item['value']) || ($item['condition'] && $item['equal'] === '?') || ($item['condition'] && $item['equal'] === '!?')) {
                $trueConditions[] = $item;
            }
        }
        $template->conditions = $trueConditions ?? null;
        $template->standarttemplate = $request->standarttemplate ? 1 : null;
        $template->grouptask = $request->grouptask ? 1 : null;

        $template->save();
        return redirect('/board/' . $request['productid']);
    }

    // получим все возможные варианты параметров продукта в виде массива
    static function getAllProductParams($productId)
    {
        $response = Http::asForm()->post('https://korobook.ru/ajax/ajaxtaskgarage.php', [
            'productid' => $productId
        ]);
        $generalParams = [
            'Доставка' => ['До склада', 'До двери '],
            'Самовывоз' => [],
            'Объединен с' => []
        ];
        return (array_merge($response->json(), $generalParams));
    }

    // получим все продукты и их ID на сайте
    static function getAllProducts()
    {
        $response = Http::asForm()->post('https://korobook.ru/ajax/ajaxgarage_getproducts.php');
        return ($response->json());
    }

    // вернет коллекцию "повторяющийхся" шаблонов для их клонирования
    static function getStandartTemplates()
    {
        return Templates::where('standarttemplate', 1)->get();
    }
}
