<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistEnfermeriaItemStoreRequest;
use App\Http\Requests\ChecklistEnfermeriaItemUpdateRequest;
use App\Models\ChecklistEnfermeriaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChecklistEnfermeriaItemController extends Controller
{
    public function index(Request $request)
    {
        $checklistEnfermeriaItems = ChecklistEnfermeriaItem::all();

        return $checklist_enfermeria_items;
    }

    public function store(ChecklistEnfermeriaItemStoreRequest $request): Response
    {
        $checklistEnfermeriaItem = ChecklistEnfermeriaItem::create($request->validated());

        return response()->noContent(201);
    }

    public function update(ChecklistEnfermeriaItemUpdateRequest $request, ChecklistEnfermeriaItem $checklistEnfermeriaItem)
    {
        $checklistEnfermeriaItem = ChecklistEnfermeriaItem::find($checklist_enfermeria_item);

        $checklistEnfermeriaItem->update($request->validated());

        return $checklist_enfermeria_item;
    }

    public function destroy(Request $request, ChecklistEnfermeriaItem $checklistEnfermeriaItem): Response
    {
        $checklistEnfermeriaItem = ChecklistEnfermeriaItem::find($checklist_enfermeria_item);

        $checklistEnfermeriaItem->delete();

        return response()->noContent();
    }
}
