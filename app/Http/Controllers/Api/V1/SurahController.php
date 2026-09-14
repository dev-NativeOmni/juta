<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AyahResource;
use App\Http\Resources\Api\V1\SurahResource;
use App\Models\Ayah;
use App\Models\Surah;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SurahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'juz' => [
                'nullable',
                'integer',
                'min:1',
                'max:30',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:114',
            ],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 114), 1), 114);
        $search = trim((string) ($validated['search'] ?? ''));

        $surahs = Surah::query()
            ->withCount('ayahs')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name_latin', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('number', $search);
                });
            })
            ->when(isset($validated['juz']), function (Builder $query) use ($validated) {
                $juz = (int) $validated['juz'];

                $query->where(function (Builder $subQuery) use ($juz) {
                    $subQuery->where(function (Builder $rangeQuery) use ($juz) {
                        $rangeQuery->whereNotNull('juz_start')
                            ->whereNotNull('juz_end')
                            ->where('juz_start', '<=', $juz)
                            ->where('juz_end', '>=', $juz);
                    })->orWhereHas('ayahs', function (Builder $ayahQuery) use ($juz) {
                        $ayahQuery->where('juz', $juz);
                    });
                });
            })
            ->orderBy('number')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::success(
            data: [
                'surahs' => SurahResource::collection($surahs->getCollection())->resolve($request),
            ],
            message: 'Data surah berhasil diambil.',
            meta: [
                'pagination' => $this->paginationMeta($surahs),
                'filters' => [
                    'search' => $search,
                    'juz' => $validated['juz'] ?? null,
                ],
            ]
        );
    }

    public function show(Request $request, string $surah): JsonResponse
    {
        if (! ctype_digit($surah)) {
            return ApiResponse::error(
                message: 'Data surah tidak ditemukan.',
                status: 404
            );
        }

        $surahModel = Surah::query()
            ->withCount('ayahs')
            ->with([
                'ayahs' => function ($query) {
                    $query->orderBy('ayah_number');
                },
            ])
            ->where('number', (int) $surah)
            ->first();

        if (! $surahModel) {
            return ApiResponse::error(
                message: 'Data surah tidak ditemukan.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: [
                'surah' => (new SurahResource($surahModel))->resolve($request),
            ],
            message: 'Detail surah berhasil diambil.'
        );
    }

    public function ayahs(Request $request, string $surah): JsonResponse
    {
        if (! ctype_digit($surah)) {
            return ApiResponse::error(
                message: 'Data surah tidak ditemukan.',
                status: 404
            );
        }

        $surahModel = Surah::query()
            ->where('number', (int) $surah)
            ->first();

        if (! $surahModel) {
            return ApiResponse::error(
                message: 'Data surah tidak ditemukan.',
                status: 404
            );
        }

        $ayahs = Ayah::query()
            ->with('surah')
            ->where('surah_id', $surahModel->id)
            ->orderBy('ayah_number')
            ->get();

        return ApiResponse::success(
            data: [
                'surah' => [
                    'id' => $surahModel->id,
                    'number' => $surahModel->number,
                    'name_ar' => $surahModel->name_ar,
                    'name_latin' => $surahModel->name_latin,
                    'total_ayah' => $surahModel->total_ayah,
                    'juz_start' => $surahModel->juz_start,
                    'juz_end' => $surahModel->juz_end,
                ],
                'ayahs' => AyahResource::collection($ayahs)->resolve($request),
            ],
            message: 'Data ayat surah berhasil diambil.',
            meta: [
                'total' => $ayahs->count(),
            ]
        );
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }
}
