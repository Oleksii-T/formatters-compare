<?php

namespace App\Models;

use Yajra\DataTables\DataTables;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasAttachments;

    const ATTACHMENTS = [
        'icon',
    ];

    private $icon = null;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function icon()
    {
        if (!$this->icon) {
            $this->icon = $this->morphToMany(Attachment::class, 'attachmentable')->where('group', 'icon')->first();
        }

        return $this->icon;
    }

    public function games()
    {
        return $this->belongsToMany(Game::class);
    }

    public static function dataTable($query)
    {
        $query->withCount('games');
        return DataTables::of($query)
            ->addColumn('icon', function ($model) {
                return '<img style="max-width:40px" src="'.$model->icon()->url.'"/>';
            })
            ->addColumn('games', function ($model) {
                return $model->games_count;
            })
            ->editColumn('created_at', function ($model) {
                return $model->created_at->adminFormat();
            })
            ->addColumn('action', function ($model) {
                return view('admin.platforms.actions', compact('model'))->render();
            })
            ->rawColumns(['icon', 'action'])
            ->make(true);
    }
}
