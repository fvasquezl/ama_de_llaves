<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistItemStoreRequest;
use App\Http\Requests\ChecklistItemUpdateRequest;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChecklistItemController extends Controller
{
    public function index(Request $request)
    {
        $checklistItems = ChecklistItem::all();

        return $checklist_items;
    }

    public function store(ChecklistItemStoreRequest $request): Response
    {
        $checklistItem = ChecklistItem::create($request->validated());

        return response()->noContent(201);
    }

    public function update(ChecklistItemUpdateRequest $request, ChecklistItem $checklistItem)
    {
        $checklistItem = ChecklistItem::find($checklist_item);

        $checklistItem->update($request->validated());

        return $checklist_item;
    }

    public function destroy(Request $request, ChecklistItem $checklistItem): Response
    {
        $checklistItem = ChecklistItem::find($checklist_item);

        $checklistItem->delete();

        return response()->noContent();
    }
}
