<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Traits\GetAllSlugs;
use App\Traits\HasAttachments;
use Yajra\DataTables\DataTables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Game extends Model
{
    use HasAttachments, GetAllSlugs;

    const ATTACHMENTS = [
        'thumbnail',
        'esbr_image',
        'screenshots',
    ];

    private $thumbnail = null;
    private $esbr_image = null;

    protected $fillable = [
        'name', 
        'slug',
        'rating',
        'status',
        'meta_title',
        'meta_description',
        'metacritic',
        'users_score',
        'release_date',
        'developer',
        'publisher',
        'platforms_new',
        'ganres',
        'esbr',
        'description',
        'summary',
        'hours',
        'official_site',
        'steam',
        'playstation_store',
        'xbox_store',
        'nintendo_store',
        'updated_at' // fix for mass assignment
    ];

    protected $casts = [
        'status' => GameStatus::class,
        'release_date' => 'date',
        'hours' => 'array',
        'platforms_new' => 'array',
    ];

    // overload laravel`s method for route key generation
    public function getRouteKey()
    {
        return $this->slug;
    }

    public function scopePublished($query)
    {
        return $query->where('status', GameStatus::PUBLISHED);
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class);
    }

    public function thumbnail()
    {
        if (!$this->thumbnail) {
            $this->thumbnail = $this->morphToMany(Attachment::class, 'attachmentable')->where('group', 'thumbnail')->first();
        }

        return $this->thumbnail;
    }

    public function esbr_image()
    {
        if (!$this->esbr_image) {
            $this->esbr_image = $this->morphToMany(Attachment::class, 'attachmentable')->where('group', 'esbr_image')->first();
        }

        return $this->esbr_image;
    }

    public function screenshots()
    {
        return $this->morphToMany(Attachment::class, 'attachmentable')->where('group', 'screenshots');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public static function dataTable($query)
    {
        return DataTables::of($query)
            ->editColumn('created_at', function ($model) {
                return $model->created_at->format(env('ADMIN_DATETIME_FORMAT'));
            })
            ->editColumn('status', function ($model) {
                return $model->status->readable();
            })
            ->addColumn('action', function ($model) {
                return view('components.admin.actions', [
                    'model' => $model,
                    'name' => 'games'
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public static function prepareNewGame($gameName)
    {
        $game = self::create([
            'name' => $gameName,
            'status' => GameStatus::DRAFT,
            'meta_title' => $gameName,
            'meta_description' => $gameName,
            'developer' => '',
            'ganres' => '',
            'slug' => makeSlug($gameName, self::pluck('slug')->toArray()),
            'hours' => [],
            'description' => '',
        ]);
        self::getAllSlugs(true);

        return $game;
    }
}
