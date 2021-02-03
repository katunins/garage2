<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Tasks;
use App\Models\Templates;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TemplateController extends Controller
{
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


        return view('board', [
            'templates' => $templates,
            'lineCount' => $lineCount,
            'positionCount' => $positionCount,
            'productId' => $productId,
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

    // парсит строку условий - делит ее на массив
    static function parseCondition($condition)
    {
        foreach (['!==', '!=', '==', '='] as $sign) {
            $conditionExplode = explode($sign, $condition);
            if (count($conditionExplode) > 1) {
                return [
                    'param' => $conditionExplode[0],
                    'sign' => $sign,
                    'values' => explode('/', $conditionExplode[1])
                ];
            }
        }
        return false;
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
                $conditionResult = 0; // 0 - не сработало / 1 - сработало
                $conditionCount = 0; // количество не пустых условий
                for ($i = 1; $i <= 3; $i++) {
                    if ($templateItem->{'condition' . $i}) {
                        $conditionCount++;
                        $conditionArr = self::parseCondition($templateItem->{'condition' . $i});

                        if (isset($productParams[$conditionArr['param']])) {
                            $productValue = $productParams[$conditionArr['param']];
                            foreach ($conditionArr['values'] as $value) {
                                switch ($conditionArr['sign']) {
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

                if ($conditionCount == 0 || $conditionResult > 0) {
                    // тут шаблон прошел условия, поэтому создадим задачу

                    // если есть минипараметры, которые нужно отобразить в сделке
                    $taskInfo = NULL;
                    if ($templateItem->params) {

                        foreach (explode('/', $templateItem->params) as $itemInfo) {
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
                        'deal' => $productParams['deal']
                    ];
                }
            }
        }
        return $taskArr;
    }

    // преобразует предварительно подобранные шаблоны задач в список задач со временем
    static function planGenerator($tasksArr)
    {

        //         "templateid" => 6
        //         "time" => 3
        //         "info" => null
        //         "deal" => "20 #8293"

        // Необходимо расчитать
        // + name
        // master
        // + status
        // start

        foreach ($tasksArr as $line) {
            foreach ($line as $task) {

                $template = Templates::find($task['templateid']);

                $task['name'] = $template->taskname;
                $task['status'] = 'wait';

                // найдем свободное время у мастера
                foreach (explode('/', $template->masters) as $masterId) {
                    self::getFreePlan($masterId, $task['time'], [
                        $template->period1,
                        $template->period2,
                    ]);
                }

                dd($task);
            }
        }
    }

    // возвращает первое возможное свободное время в графике мастера
    static function getFreePlan($masterId, $time, $periods){
        // $time - длительность задачи в минутах
        // $periods - массив с возможными периодами

        $timeBeforeStart = 12; //промежуток в часах между запуском и времени начала задач
        
        $startTime = new Carbon();
        dump ($startTime);
        $startTime->add($timeBeforeStart.' hours');
        dd ($startTime);

        $workDayPeriods = [
            '9:00-12:00',
            '13:00-18:00',
        ]; //период рабочего дня, с учетом обеда

        $activeTasks = Tasks::where('master', $masterId)->where('start', '=>', '$startTime') ->get();
        if ($activeTasks->count() > 0) {
            // в графике есть задачи
        } else {
            // график мастера пустой
        }
        dd ($activeTasks);
        
    }

    // проходит по каждому продукту в сделке
    static function tasksFromDeal($dealArr)
    {
        foreach ($dealArr['products'] as $key => $dealItem) {
            $tasks = self::taskGenegator(array_merge($dealItem, $dealArr['params']));
            $tasksPlan = self::planGenerator($tasks);
        }
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
        $request->validate([
            'taskname' => 'required',
            'masters' => 'required',
            'producttime' => 'required_without:paramtime',
            // 'paramtime'=>'required_without:producttime',
        ], [
            'taskname.required' => 'Заполните название задачи',
            'masters.required' => 'Укажите мастеров',
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
        $template->masters = $request->masters;
        $template->params = $request->params;
        $template->buffer = $request->buffer;
        $template->producttime = $request->producttime;
        $template->paramtime = $request->paramtime;

        $template->period1 = $request->period1;
        $template->period2 = $request->period2;

        $template->condition1 = $request->condition1;
        $template->condition2 = $request->condition2;
        $template->condition3 = $request->condition3;

        $template->save();
        return redirect('/board/' . $request['productid']);
    }
}
