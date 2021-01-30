<?php

namespace App\Http\Controllers;

use App\Models\Templates;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
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
        ]);
    }

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



    // "line" => "1"
    // "position" => "1"
    // "lineshift" => "1"
    // "positionshift" => "0"
    // "productid" => "1"
    // "templateid" => "11"
    static function moveTemplate($data)
    {
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
        if (Templates::where('taskidbefore', $data['templateid'])->get()->count()>0) return;

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
