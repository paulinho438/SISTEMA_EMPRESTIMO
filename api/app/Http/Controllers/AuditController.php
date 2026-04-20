<?php

namespace App\Http\Controllers;

use App\Models\Emprestimo;
use App\Models\Parcela;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 200;

    public function emprestimoAudits(Request $request, int $id)
    {
        $companyId = $request->header('company-id');
        if (!$companyId) {
            return response()->json(['message' => 'Header company-id é obrigatório.'], 400);
        }

        $emprestimo = Emprestimo::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        [$page, $perPage] = $this->paginationParams($request);

        $parcelaIdsQuery = Parcela::query()
            ->select('id')
            ->where('emprestimo_id', $emprestimo->id);

        $query = Audit::query()
            ->where(function ($q) use ($id, $parcelaIdsQuery) {
                $q->where(function ($q2) use ($id) {
                    $q2->where('auditable_type', Emprestimo::class)
                        ->where('auditable_id', $id);
                });

                $q->orWhere(function ($q2) use ($parcelaIdsQuery) {
                    $q2->where('auditable_type', Parcela::class)
                        ->whereIn('auditable_id', $parcelaIdsQuery);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($this->formatPaginator($paginator));
    }

    public function parcelaAudits(Request $request, int $id)
    {
        $companyId = $request->header('company-id');
        if (!$companyId) {
            return response()->json(['message' => 'Header company-id é obrigatório.'], 400);
        }

        $parcela = Parcela::query()
            ->whereHas('emprestimo', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->findOrFail($id);

        [$page, $perPage] = $this->paginationParams($request);

        $paginator = $parcela->audits()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($this->formatPaginator($paginator));
    }

    private function paginationParams(Request $request): array
    {
        $page = (int) $request->query('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }
        if ($perPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        }

        return [$page, $perPage];
    }

    private function formatPaginator(LengthAwarePaginator $paginator): array
    {
        $data = $paginator->getCollection()
            ->values()
            ->map(fn ($audit) => $this->mapAudit($audit))
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    private function mapAudit($audit): array
    {
        return [
            'id' => $audit->id,
            'event' => $audit->event,
            'auditable_type' => $audit->auditable_type,
            'auditable_id' => $audit->auditable_id,
            'user_id' => $audit->user_id,
            'user_type' => $audit->user_type,
            'ip_address' => $audit->ip_address,
            'user_agent' => $audit->user_agent,
            'url' => $audit->url,
            'old_values' => $audit->old_values,
            'new_values' => $audit->new_values,
            'created_at' => $audit->created_at,
        ];
    }
}

