<?php

namespace App\Services;

use App\Models\Checklist;

class ChecklistService
{
    public function all()
    {
        return Checklist::all();
    }

    public function create(array $data)
    {
        return Checklist::create($data);
    }

    public function update(Checklist $checklist, array $data)
    {
        $checklist->update($data);
        return $checklist;
    }

    public function delete(Checklist $checklist): void
    {
        $checklist->delete();
    }
}
