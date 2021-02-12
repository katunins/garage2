<?php

namespace App\Http\Controllers;

const LUNCH_BREACK = '12:00-13:00'; //перевыр на обед
const STANDART_BUFFER = 10; //стандартный буфер в минутах
const TIME_AFTER_SCRIPT = 12; //время задержки после запуска скрипта в часах
const LOG = false; //true - идет вывод echo;

use App\Models\Products;
use App\Models\Tasks;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isNull;

class TemplateController extends Controller
{

    static  $isHoliday; //праздники
    // 0	Рабочий день	200
    // 1	Нерабочий день	200
    // 2	Сокращённый рабочий день	200
    // 4	Рабочий день	200
    // 100	Ошибка в дате	400
    // 101	Данные не найдены	404
    // 199	Ошибка сервиса	400

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


        // dd($templates);

        return view('board', [
            'templates' => $templates,
            'lineCount' => $lineCount,
            'positionCount' => $positionCount,
            'productId' => $productId,
            'Users' => User::where('type', 'master')->get(),
            'productTitle' => Products::where('korobookid', $productId)->first()->title,
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

    // проходит по каждому продукту в сделке
    static function tasksFromDeal($dealArr)
    {
        self::$scriptErrors = [];

        // Загрузим выходные
        $ch = curl_init('https://isdayoff.ru/api/getdata?year=' . date('Y') . '&delimeter=/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        if (!$result) self::$scriptErrors[] = 'Не загрузились данные о выходных и праздниках';
        self::$isHoliday = explode('/', $result);
        curl_close($ch);

        foreach ($dealArr['products'] as $key => $dealItem) {

            // self::extraSort('20 #8293');

            self::$scriptErrors = [];
            self::$needExtraSort = [];

            $dealName = count($dealArr['products']) > 1 ? $dealArr['params']['deal'] . '/' . $key : $dealArr['params']['deal'];

            $productDataArr = array_merge($dealItem, $dealArr['params']);
            $productDataArr['dealname'] = $dealName;

            $tasks = self::taskGenegator($productDataArr);
            self::planGenerator($tasks, $dealItem);

            // если есть задачи, зависящие от других и еще не передвинутые на нужное стартовое время - передвинем их
            if (count(self::$needExtraSort) > 0) self::extraSort($dealName);

            // запустим дополнительную - проверку последовательностей
            self::rebuildTrueTime($dealName);

            if (LOG) {
                dump('errors', self::$scriptErrors);
                die();
            }

            // переведем все задачи в статус Wait
            Tasks::where('deal', $dealName)->where('status', 'temp')->update(['status' => 'wait']);
            // dd('finish tasksFromDeal()');
        }
        if (self::$scriptErrors == []) return true;
        else {
            dd(self::$scriptErrors);
        }
    }

    // дополнительно передвинем зависящие задачи от предыдущих 
    static function extraSort($dealName)
    {
        $tasks = Tasks::where('deal', $dealName)->where('status', 'temp')->get();

        // обработка ошибок
        if ($tasks->count() == 0) {
            self::$scriptErrors[] = 'extraSort() При дополнительной сортировке зависящих задач возникла ошибка';
            return false;
        }
        if (LOG) echo 'extraSort()<br>';
        // возьмем все задачи с taskidbefore = NULL
        foreach ($tasks->whereNull('taskidbefore') as $item) {



            // $item - задача без привязки к предыдущей
            $itemTemplate = Templates::find($item->templateid);

            // найдем координаты предыдущего шаблон
            if ($itemTemplate->taskidbefore) {
                if (LOG) echo 'Шаблон задачи ' . $item->id . ' шаблон привязан к другому шаблону <br>';
                // если была связка с шаблоном из другой линии
                $beforeTempLineCursor = Templates::find($itemTemplate->taskidbefore)->line; //стартовая линия шаблона для поиска
                $beforeTempPositionCursor = Templates::find($itemTemplate->taskidbefore)->position; //стартовая позиция шаблона для поиска
            } else {
                if ($itemTemplate->position > 1) {
                    if (LOG) echo 'Шаблон задачи ' . $item->id . ' не имеет привязки, возьмем предыдущий шаблон в линии <br>';
                    // получим предыдущую задачу в линии
                    $beforeTempLineCursor = $itemTemplate->line; //стартовая линия шаблона для поиска
                    $beforeTempPositionCursor = $itemTemplate->position - 1; //стартовая позиция шаблона для поиска
                } else {
                    // нет предыдущего шаблона - задача 1я в линии
                    if (LOG) echo 'Шаблон задачи' . $item->id . ' первый в линии и он не привязан к другой линии<br>';

                    continue;
                }
            }




            // if ($itemTemplate->position > 1) {
            //     if (LOG) echo 'Шаблон задачи ' . $item->id . ' возьмем предыдущий шаблон в линии <br>';
            //     // получим предыдущую задачу в линии
            //     $beforeTempLineCursor = $itemTemplate->line; //стартовая линия шаблона для поиска
            //     $beforeTempPositionCursor = $itemTemplate->position - 1; //стартовая позиция шаблона для поиска

            // } 

            // запустим цикл, который обратно будет получать все шаблоны и искать по ним поставленные задачи
            $safeCount = 0;
            $beforeTask = null;
            do {
                $safeCount++; //защита от зацикливания
                $beforeTemplate = Templates::where('productid', $itemTemplate->productid)
                    ->where('line', $beforeTempLineCursor)
                    ->where('position', $beforeTempPositionCursor)
                    ->first();
                if (LOG) echo 'Ищем с координатами ' . $beforeTempLineCursor . ' x ' . $beforeTempPositionCursor . ' - ';
                if ($beforeTemplate) {
                    // проверим - есть ли задача, поставленная по этому шаблону
                    if ($tasks->where('templateid', $beforeTemplate->id)->count() > 0) {
                        $beforeTask = $tasks->where('templateid', $beforeTemplate->id)->first();
                        if (LOG) echo 'задача нашлась !<br>';
                        break;
                    } else {
                        if (LOG) echo 'нет шаблона, ';
                        // задача по этому шаблону не найдена - передвигаем курсор на предыдущую
                        if ($beforeTemplate->taskidbefore) {
                            if (LOG) echo 'но есть предыдущий связанный шаблон ' . $beforeTemplate->taskidbefore . ' !<br>';
                            $beforeTempLineCursor = Templates::find($beforeTemplate->taskidbefore)->line; //стартовая линия шаблона для поиска
                            $beforeTempPositionCursor = Templates::find($beforeTemplate->taskidbefore)->position; //стартовая позиция шаблона для поиска
                        } else {
                            if (LOG) echo 'уменьшаем Position<br>';
                            $beforeTempPositionCursor--;
                        }
                    }
                } else {
                    self::$scriptErrors[] = 'extraSort() искал предыдущий шаблон и не нашел его!. Позиция шаблона ' . $beforeTempLineCursor . ' x ' . $beforeTempPositionCursor;
                    return false;
                }
            } while ($safeCount < 100 && $beforeTempPositionCursor > 0);

            // Пометим taskidbefore в задаче $item
            if ($beforeTask) {
                $item->taskidbefore = $beforeTask->id;
                $item->save();
            }


            // if ($itemTemplate->taskidbefore) {
            //     // шаблон связан с предыдущим шаблоном. Найдем задачу, созданную предыдущим шаблоном
            //     $beforeTask = $tasks->where('templateid', $itemTemplate->taskidbefore);

            //     // обработка ошибок
            //     if ($beforeTask->count() > 1) {
            //         self::$scriptErrors[] = 'extraSort() В созданных задачах по следке ' . $item->deal . ' есть задачи, созданные по одному и тому же шаблону ' . $itemTemplate->taskidbefore;
            //         return false;
            //     }

            //     // предыдущая привязанная задача по шалону могла не создаться, если в ней было условие, поэтому найдем первую из созданных по предыщущим шаблонам в линии
            //     if ($beforeTask->count() == 0) {
            //         $templatesInLine = Templates::where('productid', $itemTemplate->productid)
            //             ->where('line', $itemTemplate->line)
            //             ->where('position', '<', $itemTemplate->position)
            //             ->get()->sortByDesc('positions'); // все шаблоны в линии

            //         $beforeTask = null;
            //         foreach ($templatesInLine as $tmp) {
            //             $beforeTask = $tasks->where('templateid', $tmp->id);
            //             if ($beforeTask->count() > 0) break;
            //         }
            //         // обработка ошибок
            //         if ($beforeTask == null) {
            //             self::$scriptErrors[] = 'extraSort() В созданных задачах по следке ' . $item->deal . ' есть задачи, предыдущей задачи по шаблону ' . $itemTemplate->taskidbefore . ', но таких задач не существует';
            //             return false;
            //         }
            //     }

            //     // $beforeTask->first() - найденная предыдущая задача для задачи $item

            // Пометим taskidbefore в задаче $item
            // $item->taskidbefore = $beforeTask->first()->id;
            // $item->save();
            // }
        }
    }

    // проверяет и исправляет правильную последовательность в задачах во времени
    static function rebuildTrueTime($dealName)
    {
        $tasks = Tasks::where('deal', $dealName)->where('status', 'temp')->get();
        if (LOG) echo 'rebuildTrueTime()<br>';
        // обработка ошибок
        if ($tasks->count() == 0) {
            self::$scriptErrors[] = 'rebuildTrueTime() Нет задач по такой сделке';
            return false;
        }

        // проверим последовательность. 
        $errorCount = 0; //защитный счетчик
        do {
            $falseTasks = 0; //количество задач, у которых не правильно выставлено время
            $errorCount++;

            foreach ($tasks->groupBy('line') as $lineItem) {

                foreach ($lineItem->sortBy('position') as $item) {

                    $needRebuild = false; //
                    $itemStart = Carbon::createFromFormat('Y-m-d H:i:s', $item->start); //время начала данной задачи
                    $trueStart = clone $itemStart;

                    // Проверка 1 - возьмем задачи, у которых есть привязка к предыдущей
                    if ($item->taskidbefore) {

                        $beforeTask = $tasks->find($item->taskidbefore);
                        $beforeLinkEnd = Carbon::createFromFormat('Y-m-d H:i:s', $beforeTask->end); //время окончания предыдущей задачи
                        if ($beforeTask->buffer) $beforeLinkEnd->addMinutes($beforeTask->buffer);
                        $beforeLinkEnd->addMinutes(STANDART_BUFFER);

                        // Если время старта данной задачи < времени окончания предыдущей
                        // if (!$itemStart->greaterThan($beforeLinkEnd)) {
                        if (!$itemStart->greaterThanOrEqualTo($beforeLinkEnd)) {
                            if ($beforeLinkEnd > $trueStart) $trueStart = clone $beforeLinkEnd;
                            $needRebuild = true;
                            if (LOG) echo ' времени окончания предыдущей задачи > время старта данной задачи  ' . $beforeTask->id . '<br>';
                            if (LOG) echo $beforeLinkEnd->toDateTimeString() . ' ? ' . $itemStart->toDateTimeString() . '<br>';
                        }
                    }

                    // Проверка 2 - если привязки нет, то проверим нет ли задачи до этого в интервале стандартного буфера

                    $SafeTimeBefore = clone $itemStart; //защитное время до со стандартным периодом
                    $SafeTimeBefore->subMinutes(STANDART_BUFFER);
                    $tasksInSafePeriod = Tasks::where('master', $item->master)
                        ->whereBetween('end', [$SafeTimeBefore, $itemStart])
                        ->get();

                    // if ($item->id == 14) {
                    //     dump('$SafeTimeBefore'.$SafeTimeBefore->toDateTimeString());
                    //     dump($tasksInSafePeriod);
                    // }


                    if ($tasksInSafePeriod->count() > 0) {
                        $beforeTask = $tasksInSafePeriod->last();
                        $beforeEnd = Carbon::createFromFormat('Y-m-d H:i:s', $beforeTask->end); //время окончания предыдущей задачи
                        $beforeEnd->addMinutes(STANDART_BUFFER);
                        if ($beforeEnd > $trueStart) $trueStart = $beforeEnd;
                        $needRebuild = true;
                        if (LOG) echo 'Есть задачи до этого в интервале стандартного буфера' . $beforeTask->id . '<br>';
                    }


                    // нужно совместить два верхних условия в одно общее

                    // 
                    if ($needRebuild == true) {

                        if (LOG) echo 'делаем Rebuild задачи ' . $item->id . '<br>';

                        // сделаем специально познее вермя старта, что бы потом выбрать самого свободного мастера
                        $start = new Carbon; //
                        $start->addYear();

                        // выберем ближайшего свободного мастера
                        $resultMasterId = 0;

                        // найдем ближайшее свободное время у возможных мастеров
                        $template = Templates::find($item->templateid);
                        foreach ($template->masters as $masterId) {
                            $resultTime = self::getFreePlan($masterId, $item->time, $trueStart, [
                                $template->period1,
                                $template->period2,
                            ]);

                            if ($resultTime < $start) {
                                $start = clone $resultTime;
                                $end = clone $start;
                                $end->addMinutes($item->time);
                                $resultMasterId = $masterId;
                            }
                        }

                        $item->master = $resultMasterId;
                        $item->start = $start->toDateTimeString();
                        $item->end = $end->toDateTimeString();
                        $item->save();

                        $falseTasks++;
                    }
                }
            }
            if ($errorCount > 30) {
                self::$scriptErrors[] = 'rebuildTrueTime() В дополнительной сортировке количество циклов превысело максимальное значение. Цикл остановлен. Проверьте порядок у задач';
                break;
            }
        } while ($falseTasks > 0);
        return true;
    }



    // Подбирет шаблоны под параметры заказа и расчитывает длительность задачи
    static function taskGenegator($productParams)
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
        $productData = Products::where('title', $productParams['productname'])->get();
        if ($productData->count() > 0) {
            $productId = $productData->first()->korobookid;
        };
        $taskArr = [];

        $templates = Templates::where('productid', $productId)->get();
        for ($line = 1; $line <= $templates->max('line'); $line++) {
            foreach ($templates->where('line', $line)->sortBy('position') as $templateItem) {

                // Проверим условия шаблона. Работают по принципу OR
                // $conditionResult = 0; // 0 - не сработало / 1 - сработало
                // $conditionCount = 0; // количество не пустых условий
                // for ($i = 1; $i <= 3; $i++) {
                //     if ($templateItem->{'condition' . $i}) {
                //         $conditionCount++;
                //         $conditionArr = self::parseCondition($templateItem->{'condition' . $i});

                //         if (isset($productParams[$conditionArr['param']])) {
                //             $productValue = $productParams[$conditionArr['param']];
                //             foreach ($conditionArr['values'] as $value) {
                //                 switch ($conditionArr['sign']) {
                //                     case '=':
                //                         $conditionResult += strpos($productValue, $value) !== false ? 1 : 0;
                //                         break;
                //                     case '!=':
                //                         $conditionResult += strpos($productValue, $value) === false ? 1 : 0;
                //                         break;
                //                     case '==':
                //                         $conditionResult += strcasecmp($productValue, $value) == 0 ? 1 : 0;
                //                         break;
                //                     case '!==':
                //                         $conditionResult += strcasecmp($productValue, $value) != 0 ? 1 : 0;
                //                         break;
                //                 }
                //             }
                //         }
                //     }
                // }

                // Проверим условия шаблона. Работают по принципу OR
                $conditionResult = 0; // 0 - не сработало / 1 - сработало
                $conditionCount = 0; // количество не пустых условий

                if ($templateItem->conditions) {
                    $conditionCount++;
                    foreach ($templateItem->conditions as $conditionItem) {

                        if (isset($productParams[$conditionItem['condition']])) {
                            $productValue = $productParams[$conditionItem['condition']];
                            foreach (explode('/', $conditionItem['value']) as $value) {

                                switch ($conditionItem['equal']) {
                                    case '=':
                                        $conditionResult += strpos($productValue, $value) !== false ? 1 : 0;
                                        break;
                                    case '!=':
                                        $conditionResult += strpos($productValue, $value) === false ? 1 : 0;
                                        break;
                                    case '==':
                                        $conditionResult += strcasecmp($productValue, $value) == 0 ? 1 : 0;
                                        break;
                                    case '!==':
                                        $conditionResult += strcasecmp($productValue, $value) != 0 ? 1 : 0;
                                        break;
                                }
                            }
                        }
                    }
                }

                if ($conditionCount == 0 || ($conditionCount > 0 && $conditionResult > 0)) {
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
                    if ($templateItem->producttime) $taskTime += $templateItem->producttime * $productParams['Количество'];
                    if ($templateItem->paramtime) {

                        // посчитаем площадь разовротов в квадратных дециметрах (разделим на 100)
                        $size = explode(' ', $productParams['Формат']);
                        $widthHeight = explode('x', $size[0]); //английская литера x
                        if (!isset($widthHeight[1])) $widthHeight = explode('х', $size[0]); //русская литера x;

                        $area = (int) $widthHeight[0] * (int) $widthHeight[1] * (int)$productParams['Количество разворотов'] / 100;
                        $taskTime += $area * $templateItem->paramtime;
                    }

                    $taskArr[$line][] = [
                        'templateid' => $templateItem->id,
                        'time' => $taskTime,
                        'info' => $taskInfo,
                        'dealname' => $productParams['dealname']
                    ];
                }
            }
        }
        return $taskArr;
    }

    // парсит строку условий - делит ее на массив
    // static function parseCondition($condition)
    // {
    //     foreach (['!==', '!=', '==', '='] as $sign) {
    //         $conditionExplode = explode($sign, $condition);
    //         if (count($conditionExplode) > 1) {
    //             return [
    //                 'param' => $conditionExplode[0],
    //                 'sign' => $sign,
    //                 'values' => explode('/', $conditionExplode[1])
    //             ];
    //         }
    //     }
    //     return false;
    // }

    // преобразует предварительно подобранные шаблоны задач в список задач со временем
    static function planGenerator($tasksArr, $dealItem)
    {

        //         "templateid" => 6
        //         "time" => 3
        //         "info" => null
        //         "deal" => "20 #8293

        foreach ($tasksArr as $line) {
            $taskBefore = NULL;

            foreach ($line as $task) {

                $template = Templates::find($task['templateid']);
                $lastTaskId = NULL;
                $extraSort = false;

                if ($template->taskidbefore) {

                    // предварительная задача из другой линии
                    $beforeTask = Tasks::where('deal', $task['dealname'])
                        ->where('status', 'temp')
                        ->where('templateid', $template->taskidbefore)
                        ->get();
                    if ($beforeTask->count() > 0) {
                        // такая уже задача создана
                        $startFrom = Carbon::parse($beforeTask->last()->end);
                        $startFrom->addMinutes($beforeTask->last()->buffer + STANDART_BUFFER);
                        $lastTaskId = $beforeTask->last()->id;
                        if ($beforeTask->count() > 1) {
                            // найдено предварительных задач больше 1.
                            $scriptErrors[] = 'Template ID ' . $template->id . 'устанавливается после ID' . $template->taskidbefore . '. В графике уже заплпанировано ' . $beforeTask->count() . 'таких временных задач!';
                        }
                    } else {
                        // такая задача пока не создана, поэтому поставим пометку на передвижение задач после выполнения всех скриптов
                        $startFrom = new Carbon; //время с которого можно ставить задачи
                        // $startFrom->addDays(30);
                        $extraSort = true; //в конце цикла добавим эту задачу в массив не отсортированных по времени старта задач
                    }
                } elseif ($taskBefore) {
                    // есть предыдущая задача в линии
                    $startFrom = Carbon::parse($taskBefore->end);
                    $startFrom->addMinutes($taskBefore->buffer + STANDART_BUFFER);
                    $lastTaskId = $taskBefore->id;
                } else {
                    // это первая задача в линии
                    $startFrom = new Carbon; //время с которого можно ставить задачи
                    // $startFrom = Carbon::parse('2021-02-04 17:16:12'); //для теста
                    $startFrom->addHours(TIME_AFTER_SCRIPT);
                }

                // найдем свободное время у мастера
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

                $taskBefore = new Tasks;
                $taskBefore->name = $template->taskname;
                $taskBefore->templateid = $template->id;
                $taskBefore->master = $resultMasterId;
                $taskBefore->time = $task['time'];
                $taskBefore->line = $template->line;
                $taskBefore->position = $template->position;
                $taskBefore->status = 'temp';
                if ($lastTaskId) $taskBefore->taskidbefore = $lastTaskId; //предварительная задача
                $taskBefore->start = $start->format('Y-m-d H:i:s');
                $taskBefore->end = $end->format('Y-m-d H:i:s');
                $taskBefore->buffer = $template->buffer; // + STANDART_BUFFER; //стандартный буфер задержки после задачи
                $taskBefore->generalinfo = $dealItem['productname'] . ' ' . $dealItem['Формат'] ?? '';
                if ($template->miniparams) {
                    $info = '';
                    foreach ($template->miniparams as $param) {
                        if (isset($dealItem[$param]) !== false) $info .= $param . ': ' . $dealItem[$param] . '; ';
                        // else self::$scriptErrors[] = 'Не найден параметр, проверьте "' . $param . '"';
                    }
                    $taskBefore->info = $info;
                }
                $taskBefore->deal = $task['dealname'];
                $taskBefore->save();

                if ($extraSort == true) self::$needExtraSort[] = $taskBefore->id; //добавим ID задачи в 

            }
        }
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

        $test = 0; //защита от зависания
        $result = false;
        do {
            $test++;

            if ($test > 10000) {
                self::$scriptErrors[] = 'getFreePlan() цикл поиска свободного времени превысил допустимое значение. Мастер - ' . $masterId . ', startFrom - ' . $startFrom->toDateTimeString() . ', Длительность ' . $time . ' мин.';
                // echo '<h1>test-stop!!!</h1>';
                break;
            }
            if (LOG == true) echo 'Мастер ' . $masterId . ' / Поиск  ' . self::$startTime->format('Y-m-d H:i:s') . ' / ' . $time . ' мин.';
            $result = self::tryToPlan($time, $periods, $masterId);
            // echo 'После '.self::$startTime->format('Y-m-d H:i:s').'<br>';
        } while ($result == false);
        if (LOG == true) echo ' OK<br><br>';
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
            if (LOG == true) echo ' - Выходной<br>';
            self::$startTime->setTime(9, 0, 0);
            self::$startTime->addDay();
            return false;
        }

        // Проверим startTime если раньше начала рабочего дня или endTime позже времени окончания, то вернем 9:00 следующего дня
        if (self::$startTime < $workDayStart || $endTime < $workDayStart) {
            if (LOG == true) echo ' - Слишком рано<br>';
            self::$startTime->setTime(9, 0, 0);
            return false;
        }

        // если время позже рабочего времени, то + 1 день
        if (self::$startTime > $workDayEnd || $endTime > $workDayEnd) {
            if (LOG == true) echo ' - Слишком поздно<br>';
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
                    if (LOG == true) echo ' - Запретный период<br>';
                    return false;
                }
            }
        }


        // Проверим данное время на совпадение с запланированными задачами:

        // временно добавим 1 секунду, что бы не было времени 9:01
        self::$startTime->addSecond();
        $endTime->subSecond();

        $taskHere = Tasks::whereBetween('start', [self::$startTime, $endTime])
            ->orWhereBetween('end', [self::$startTime, $endTime])
            ->orWhere([['start', '<=', self::$startTime], ['end', '>=', $endTime]])->get()->sortBy('end')
            ->where('master', $masterId);


        if ($taskHere->count() > 0) {

            // уберем временную 1 секунду
            self::$startTime->subSecond(); //addSecond();
            $endTime->addSecond();

            // if ($taskHere->count() > 2) dump ($taskHere);
            self::$startTime = Carbon::createFromFormat('Y-m-d H:i:s', $taskHere->last()->end);
            if (LOG == true) {

                echo ' - в это вреия есть задачи: ' . $taskHere->count() . '<br>';
                dump($taskHere);
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

    public function saveTemplate(Request $request)
    {
        // dd ($request->all());

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
            if ($item['condition'] && $item['equal'] && $item['value']) {
                $trueConditions[] = $item;
            }
        }
        $template->conditions = $trueConditions ?? null;

        $template->save();
        return redirect('/board/' . $request['productid']);
    }

    // получим все возможные варианты параметров продукта в виде массива
    static function getAllProductParams($productId)
    {
        $response = Http::asForm()->post('https://korobook.ru/ajax/ajaxtaskgarage.php', [
            'productid' => $productId
        ]);
        return ($response->json());
    }
}
