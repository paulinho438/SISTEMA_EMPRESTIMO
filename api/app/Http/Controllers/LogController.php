<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Movimentacaofinanceira;
use App\Models\CustomLog;
use App\Models\User;

use DateTime;
use App\Http\Resources\MovimentacaofinanceiraResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class LogController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    public function all(Request $request){
        // Parâmetros de paginação
        $perPage = $request->input('per_page', 15); // Padrão: 15 itens por página
        $page = $request->input('page', 1);

        // Parâmetros de filtro por data
        $dtInicio = $request->input('dt_inicio');
        $dtFinal = $request->input('dt_final');

        // Construir query base
        $query = CustomLog::query();

        // Aplicar filtros de data se fornecidos
        if ($dtInicio) {
            $query->whereDate('created_at', '>=', $dtInicio);
        }
        if ($dtFinal) {
            $query->whereDate('created_at', '<=', $dtFinal);
        }

        // Aplicar filtro por company_id se necessário (via header)
        $companyId = $request->header('company-id');
        if ($companyId) {
            // Se CustomLog tiver company_id, descomente a linha abaixo
            // $query->where('company_id', $companyId);
        }

        // Eager loading de relacionamentos se existirem
        // Se CustomLog tiver relacionamento com User, descomente:
        // $query->with(['user']);

        // Ordenar e paginar
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($logs);
    }
}
